<?php

namespace Spyrit\PropelDatagridBundle\Datagrid;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Collection\ObjectCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Datagrid management class that support and handle pagination, sort, filter
 * and now, export actions.
 * @author Maxime CORSON <maxime.corson@spyrit.net>
 */
abstract class PropelDatagrid implements PropelDatagridInterface
{
    const ACTION                = 'action';
    const ACTION_DATAGRID       = 'datagrid';
    const ACTION_PAGE           = 'page';
    const ACTION_SORT           = 'sort';
    const ACTION_REMOVE_SORT    = 'remove-sort';
    const ACTION_RESET          = 'reset';
    const ACTION_LIMIT          = 'limit';
    const ACTION_ADD_COLUMN     = 'add-column';
    const ACTION_REMOVE_COLUMN  = 'remove-column';

    const BATCH_INCLUDE = 'include';
    const BATCH_EXCLUDE = 'exclude';

    const PARAM1 = 'param1';
    const PARAM2 = 'param2';

    /**
     * The container witch is usefull to get Request parameters and differents
     * options and parameters.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * The query that filter the results
     */
    protected $query;

    /**
     * @var ?FilterObject
     */
    protected $filter;

    /**
     * @var bool
     */
    protected $isFiltered;

    /**
     * Results of the query (in fact this is a PropelPager object which contains
     * the result set and some methods to display pager and extra things)
     */
    protected $results;

    /**
     * Number of result(s) to display per page
     * @var integer
     */
    protected $maxPerPage;

    /**
     * Options that you can use in your Datagrid methods if you need
     */
    protected $options = [
        'multi_sort' => false,
    ];

    public function __construct($container, $options = [])
    {
        $this->container = $container;
        $this->options = array_merge($this->options, $options);
        $this->query = $this->configureQuery();
        $this->buildForm();
    }

    public static function create($container, $options = []): self
    {
        $class = get_called_class();

        return new $class($container, $options);
    }

    public function execute()
    {
        $this->preExecute();

        $this->controller();

        $this->postExecute();

        return $this;
    }

    public function preExecute()
    {
        return;
    }

    public function postExecute()
    {
        return;
    }

    public function reset()
    {
        return $this
            ->resetFilters()
            ->resetSort();
    }

    private function controller()
    {
        if ($this->isRequestedDatagrid()) {
            switch ($this->getRequestedAction()) {
                case self::ACTION_SORT: $this->updateSort(); break;
                case self::ACTION_LIMIT:  $this->limit(); break;
                case self::ACTION_REMOVE_SORT: $this->removeSort(); break;
                case self::ACTION_RESET: $this->reset(); break;
                case self::ACTION_ADD_COLUMN: $this->addColumn(); break;
                case self::ACTION_REMOVE_COLUMN: $this->removeColumn(); break;
            }
        }
        $this->sort();
        $this->filter();

        $this->results = $this->getQuery()->paginate(
            $this->getCurrentPage(),
            $this->getMaxPerPage()
        );
    }

    private function isRequestedDatagrid()
    {
        return ($this->getRequestedDatagrid() == $this->getName());
    }

    private function isRequestedAction($action)
    {
        return $this->getRequest()->get(self::ACTION) == $action;
    }

    protected function getSessionValue($name, $default = null)
    {
        return $this->getRequest()
            ->getSession()
            ->get($this->getSessionName().'.'.$name, $default);
    }

    protected function setSessionValue($name, $value)
    {
        return $this->getRequest()
            ->getSession()
            ->set($this->getSessionName().'.'.$name, $value);
    }

    private function removeSessionValue($name)
    {
        return $this->getRequest()
            ->getSession()
            ->remove($this->getSessionName().'.'.$name);
    }

    /*********************************/
    /** Filter features here *********/
    /*********************************/

    public function filter()
    {
        if (in_array(
                $this->getRequest()->getMethod(),
                array_map('strtoupper', $this->getAllowedFilterMethods())
            ) && $this->getRequest()->get($this->filter->getForm()->getName())
        ) {
            $data = $this->getRequest()->get($this->filter->getForm()->getName());
        } else {
            $data = $this->getSessionValue('filter', $this->getDefaultFilters());
        }

        $this->isFiltered = false;
        if ($this->filter) {
            $this->filter->submit($data);
            $form = $this->filter->getForm();
            $formData = $form->getData();

            if ($form->isValid()) {
                if (in_array(
                    $this->getRequest()->getMethod(),
                    array_map('strtoupper', $this->getAllowedFilterMethods())
                )) {
                    $this->setSessionValue('filter', $data);
                }
                $this->applyFilter($formData);
            }
        }

        return $this;
    }

    private function camelize(string $word): string
    {
        return str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $word)));
    }

    private function applyFilter($data)
    {
        foreach ($data as $key => $value) {
            $empty = true;

            if ($value instanceof ObjectCollection || is_array($value)) {
                $empty = count($value) == 0;
            } elseif (!empty($value) || $value === 0) {
                $empty = false;
            }

            if (!$empty) {
                $method = 'filterBy'.$this->camelize($key);
                $type = $this->filter->getType($key);

                switch ($type) {
                    case TextType::class:
                        $this->getQuery()->$method('%'.$value.'%', Criteria::LIKE);
                        $this->isFiltered = true;
                        break;
                    case CollectionType::class:
                        $data = $this->filter->getData();
                        if (isset($data[$key])) {
                            foreach ($data[$key] as $val) {
                                foreach ($val as $k => $v) {
                                    if ($v) {
                                        $v0 = is_array($v) ? current($v) : null;
                                        if (is_array($v0)) {
                                            foreach ($v0 as $k2 => $v2) {
                                                $this->getQuery()->$method($k, $k2, $v2);
                                            }
                                        } else {
                                            $this->getQuery()->$method($k, $v);
                                        }
                                        $this->isFiltered = true;
                                    }
                                }
                            }
                        }
                        break;
                    default:
                        $this->getQuery()->$method($value);
                        $this->isFiltered = true;
                        break;
                }
            }
        }
    }

    protected function buildForm()
    {
        $filters = $this->configureFilter();

        if (!empty($filters)) {
            $this->filter = new FilterObject($this->getFormFactory(), $this->getName());

            foreach ($filters as $name => $filter) {
                $this->filter->add(
                    $name,
                    $filter['type'],
                    isset($filter['options'])? $filter['options'] : [],
                    isset($filter['value'])? $filter['value'] : null
                );
            }
            $this->configureFilterBuilder($this->filter->getBuilder());
        }
    }

    public function setFilterValue($name, $value)
    {
        $filters = $this->getSessionValue('filter', []);
        $filters[$name] = $value;
        $this->setSessionValue('filter', $filters);
    }

    public function configureFilter()
    {
        return [];
    }

    protected function getDefaultFilters()
    {
        return [];
    }

    public function resetFilters()
    {
        $this->removeSessionValue('filter');
        return $this;
    }

    private function getSessionFilter($default = [])
    {
        return $this->getSessionValue('filter', $default);
    }

    private function setSessionFilter($value)
    {
        $this->setSessionValue('filter', $value);
        return $this;
    }

    /**
     * Shortcut
     */
    public function getFilterFormView()
    {
        return $this->filter->getForm()->createView();
    }

    public function configureFilterForm()
    {
        return [];
    }

    public function configureFilterBuilder($builder)
    {
        /**
         * Do what you want with the builder.
         * For example, add Event Listener PRE/POST_SET_DATA, etc.
         */
        return;
    }

    public function getAllowedFilterMethods()
    {
        return ['post'];
    }

    /*********************************/
    /** Sort features here ***********/
    /*********************************/

    private function sort()
    {
        $sort = $this->getSessionValue('sort', $this->getDefaultSort());

        foreach ($sort as $column => $order) {
            $method = 'orderBy'.ucfirst($column);
            @$this->getQuery()->{$method}($order); // fail silently if the method doesn't exist
        }
    }

    public function updateSort()
    {
        $sort = $this->getSessionValue('sort', $this->getDefaultSort());
        if (isset($this->options['multi_sort']) && ($this->options['multi_sort'] == false)) {
            unset($sort);
        }
        $sort[$this->getRequestedSortColumn()] = $this->getRequestedSortOrder();
        $this->setSessionValue('sort', $sort);
    }

    public function removeSort()
    {
        $sort = $this->getSessionValue('sort', $this->getDefaultSort());
        unset($sort[$this->getRequestedSortedColumnRemoval()]);
        $this->setSessionValue('sort', $sort);
    }

    public function getDefaultSort()
    {
        return [
            $this->getDefaultSortColumn() => $this->getDefaultSortOrder(),
        ];
    }

    public function isSortedColumn($column)
    {
        $sort = $this->getSessionValue('sort', $this->getDefaultSort());
        return isset($sort[$column]);
    }

    public function getSortedColumnOrder($column)
    {
        $sort = $this->getSessionValue('sort', $this->getDefaultSort());
        return $sort[$column];
    }

    public function getSortedColumnPriority($column)
    {
        $sort = $this->getSessionValue('sort', $this->getDefaultSort());
        return array_search($column, array_keys($sort));
    }

    public function getSortCount()
    {
        $sort = $this->getSessionValue('sort', $this->getDefaultSort());
        return count($sort);
    }

    public function resetSort()
    {
        $this->removeSessionValue('sort');
        return $this;
    }

    public function getDefaultSortOrder()
    {
        return strtolower(Criteria::ASC);
    }

    /*********************************/
    /** Export features here *********/
    /*********************************/

    /**
     * @param string $name
     * @param array  $params
     */
    public function export($name, $params = [])
    {
        $class = $this->getExport($name);
        $this->filter();
        $this->sort();

        $export = new $class($this->getQuery(), $params);
        return $export->execute();
    }

    protected function getExport($name)
    {
        $exports = $this->getExports();
        if (!isset($exports[$name])) {
            throw new \Exception('The "'.$name.'" export doesn\'t exist in this datagrid.');
        }
        return $exports[$name];
    }

    protected function getExports()
    {
        return [];
    }

    public function getSessionName()
    {
        return 'datagrid.'.$this->getName();
    }

    public function getCurrentPage($default = 1)
    {
        if ($this->isRequestedDatagrid() && $this->getRequestedAction() == self::ACTION_PAGE) {
            $page = $this->getRequestedPage();
        }
        if (!isset($page)) {
            $page = $this->getSessionValue('page', $default);
        }
        $this->setSessionValue('page', $page);

        return $page;
    }

    /*********************************/
    /** Dynamic columns feature here */
    /*********************************/

    private function removeColumn()
    {
        $columnToRemove = $this->getRequestedColumnRemoval();
        $columns = $this->getColumns();

        if (array_key_exists($columnToRemove, $columns)) {
            unset($columns[$columnToRemove]);
            $this->setSessionValue('columns', $columns);
            /**
             * @todo Remove sort on the removed column
             */
        }
    }

    private function addColumn()
    {
        $newColumn = $this->getRequestedNewColumn();
        $precedingColumn = $this->getRequestedPrecedingNewColumn();

        if (array_key_exists($newColumn, $this->getAvailableAppendableColumns())) {
            $columns = $this->getColumns();
            $newColumnsArray = [];

            foreach ($columns as $column => $label) {
                $newColumnsArray[$column] = $label;
                if ($column == $precedingColumn) {
                    $cols = array_merge(
                        $this->getAppendableColumns(),
                        $this->getDefaultColumns()
                    );
                    $newColumnsArray[$newColumn] = $cols[$newColumn];
                }
            }
            $this->setSessionValue('columns', $newColumnsArray);
        }
    }

    public function getDefaultColumns()
    {
        return [];
    }

    public function getNonRemovableColumns()
    {
        return [];
    }

    public function getAppendableColumns()
    {
        return [];
    }

    public function getAvailableAppendableColumns()
    {
        $columns = $this->getSessionValue('columns', $this->getDefaultColumns());

        return array_merge(
            array_diff_key($this->getAppendableColumns(), $columns),
            array_diff_key($this->getDefaultColumns(), $columns)
        );
    }

    public function getColumns()
    {
        return $this->getSessionValue('columns', $this->getDefaultColumns());
    }

    /*********************************/
    /** Max per page feature here ****/
    /*********************************/

    private function limit()
    {
        $limit = $this->getRequestedLimit();

        if (in_array($limit, $this->getAvailableMaxPerPage())) {
            $this->setSessionValue('limit', $limit);
        }
    }

    public function getAvailableMaxPerPage()
    {
        return [15, 30, 50];
    }

    public function getDefaultMaxPerPage()
    {
        return 30;
    }

    public function getMaxPerPage()
    {
        return $this->getSessionValue('limit', $this->getDefaultMaxPerPage());
    }

    public function setMaxPerPage($value)
    {
        $this->setSessionValue('limit', $value);
        return $this;
    }

    /*********************************/
    /** Routing helper methods here **/
    /*********************************/

    protected function getRequestedAction($default = null)
    {
        return $this->getRequest()->get(self::ACTION, $default);
    }

    protected function getRequestedDatagrid($default = null)
    {
        return $this->getRequest()->get(self::ACTION_DATAGRID, $default);
    }

    protected function getRequestedSortColumn($default = null)
    {
        return $this->getRequest()->get(self::PARAM1, $default);
    }

    protected function getRequestedSortOrder($default = Criteria::ASC)
    {
        $requested = strtolower($this->getRequest()->get(self::PARAM2, $default));

        if (!in_array($requested, ['asc', 'desc'])) {
            $requested = null;
        }

        return $requested ?? $default;
    }

    protected function getRequestedSortedColumnRemoval($default = null)
    {
        return $this->getRequest()->get(self::PARAM1, $default);
    }

    protected function getRequestedPage($default = null)
    {
        return $this->getRequest()->get(self::PARAM1, $default);
    }

    protected function getRequestedNewColumn($default = null)
    {
        return $this->getRequest()->get(self::PARAM1, $default);
    }

    protected function getRequestedPrecedingNewColumn($default = null)
    {
        return $this->getRequest()->get(self::PARAM2, $default);
    }

    protected function getRequestedColumnRemoval($default = null)
    {
        return $this->getRequest()->get(self::PARAM1, $default);
    }

    protected function getRequestedLimit($default = null)
    {
        return $this->getRequest()->get(self::PARAM1, $default);
    }

    /*********************************/
    /** Global service shortcuts *****/
    /*********************************/

    /**
     * Shortcut to return the request service.
     */
    protected function getRequest(): Request
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    /**
     * Shortcut to return the request service.
     */
    protected function getSession(): SessionInterface
    {
        return $this->container->get('session');
    }

    /**
     * return the Form Factory Service
     */
    protected function getFormFactory(): FormFactory
    {
        return $this->container->get('form.factory');
    }

    public function isFiltered()
    {
        return $this->isFiltered;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    public function getResults()
    {
        return $this->results;
    }

    public function getPager()
    {
        return $this->getResults();
    }

    /**
     * Generate pagination route
     */
    public function getPaginationPath($route, $page, $extraParams = []): string
    {
        $params = array_merge($extraParams, [
            self::ACTION => self::ACTION_PAGE,
            self::ACTION_DATAGRID => $this->getName(),
            self::PARAM1 => $page,
        ]);

        return $this->container->get('router')->generate($route, $params);
    }

    /**
     * Generate reset route for the button view
     */
    public function getResetPath($route, $extraParams = []): string
    {
        $params = array_merge($extraParams, [
            self::ACTION => self::ACTION_RESET,
            self::ACTION_DATAGRID => $this->getName(),
        ]);

        return $this->container->get('router')->generate($route, $params);
    }

    /**
     * Generate sorting route for a given column to be displayed in view
     * @todo Remove the order parameter and ask to the datagrid to guess it ?
     */
    public function getSortPath($route, $column, $order, $extraParams = []): string
    {
        $params = array_merge($extraParams, [
            self::ACTION => self::ACTION_SORT,
            self::ACTION_DATAGRID => $this->getName(),
            self::PARAM1 => $column,
            self::PARAM2 => $order,
        ]);

        return $this->container->get('router')->generate($route, $params);
    }

    /**
     * Generate remove sort route for a given column to be displayed in view
     */
    public function getRemoveSortPath($route, $column, $extraParams = []): string
    {
        $params = array_merge($extraParams, [
            self::ACTION => self::ACTION_REMOVE_SORT,
            self::ACTION_DATAGRID => $this->getName(),
            self::PARAM1 => $column,
        ]);

        return $this->container->get('router')->generate($route, $params);
    }

    /**
     * Generate new column route for a given column to be displayed in view
     */
    public function getNewColumnPath($route, $newColumn, $precedingColumn, $extraParams = [])
    {
        $params = array_merge($extraParams, [
            self::ACTION => self::ACTION_ADD_COLUMN,
            self::ACTION_DATAGRID => $this->getName(),
            self::PARAM1 => $newColumn,
            self::PARAM2 => $precedingColumn,
        ]);

        return $this->container->get('router')->generate($route, $params);
    }

    /**
     * Generate remove column route for a given column to be displayed in view
     */
    public function getRemoveColumnPath($route, $column, $extraParams = [])
    {
        $params = array_merge($extraParams, [
            self::ACTION => self::ACTION_REMOVE_COLUMN,
            self::ACTION_DATAGRID => $this->getName(),
            self::PARAM1 => $column,
        ]);

        return $this->container->get('router')->generate($route, $params);
    }

    /**
     * Generate max per page route to be displayed in view
     */
    public function getMaxPerPagePath($route, $limit, $extraParams = [])
    {
        $params = array_merge($extraParams, [
            self::ACTION => self::ACTION_LIMIT,
            self::ACTION_DATAGRID => $this->getName(),
            self::PARAM1 => $limit,
        ]);

        return $this->container->get('router')->generate($route, $params);
    }

    /***************************************/
    /** Batch feature for mass actions *****/
    /***************************************/

    public function getBatchData()
    {
        return (array) json_decode(
            $this->getRequest()->cookies->get($this->getName().'_batch')
        );
    }

    /**
     * Test if the record is checked
     */
    public function isBatchChecked($identifier): bool
    {
        $data = $this->getBatchData();
        if ($data) {
            if ($data['type'] == self::BATCH_INCLUDE
                && in_array($identifier, $data['checked'])) {
                return true;
            } elseif ($data['type'] == self::BATCH_EXCLUDE
                && !in_array($identifier, $data['checked'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Test if all records are checked
     */
    public function hasAllCheckedBatch(): bool
    {
        $data = $this->getBatchData();
        if ($data) {
            if ($data['type'] == self::BATCH_INCLUDE
                && count($data['checked']) == count($this->getResults())) {
                return true;
            } elseif ($data['type'] == self::BATCH_EXCLUDE
                && count($data['checked']) == 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Test if at least one record is checked.
     */
    public function hasCheckedBatch(): bool
    {
        $data = $this->getBatchData();
        if ($data) {
            if ($data['type'] == self::BATCH_INCLUDE
                && count($data['checked']) > 0) {
                return true;
            } elseif ($data['type'] == self::BATCH_EXCLUDE
                && count($data['checked']) < count($this->getResults())) {
                return true;
            }
        }
        return false;
    }
}
