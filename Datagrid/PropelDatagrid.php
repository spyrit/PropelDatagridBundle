<?php

namespace Spyrit\PropelDatagridBundle\Datagrid;

use Spyrit\PropelDatagridBundle\Datagrid\PropelDatagridInterface;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Datagrid management class that support and handle pagination, sort, filter
 * and now, export actions.
 * @author Maxime CORSON <maxime.corson@spyrit.net>
 */
abstract class PropelDatagrid implements PropelDatagridInterface
{
    /**
     * The container witch is usefull to get Request parameters and differents 
     * options and parameters.
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;
    
    /**
     * The query that filter the results
     * @var \PropelQuery 
     */
    protected $query;
    
    /**
     * @var FilterObject
     */
    protected $filter;
    
    /**
     * Results of the query (in fact this is a PropelPager object which contains
     * the result set and some methods to display pager and extra things)
     * @var \PropelPager
     */
    protected $results;
    
    /**
     * Number of result(s) to display per page 
     * @var integer 
     */
    protected $maxPerPage;
    
    /**
     * Options that you can use in your Datagrid methods if you need
     * (Will be deprecated if not used)
     * @var integer 
     */
    protected $options;
    
    public function __construct($container, $options = array())
    {
        $this->container = $container;
        $this->query = $this->configureQuery();
        $this->options = $options;
        $this->buildForm();
    }
    
    /**
     * @param type $container
     * @return \self
     */
    public static function create($container)
    {
        $class = get_called_class();
        return new $class($container);
    }
    
    public function execute()
    {
        $this->preExecute();
        
        if($this->getRequest()->get($this->getActionParameterName()) == $this->getResetActionParameterName())
        {
            $this->reset();
        }
        $this->filter();
        $this->sort();
        $this->results = $this->getQuery()->paginate($this->getCurrentPage(), $this->getMaxPerPage());
        
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
    
    private function filter()
    {
        if($this->getRequest()->isMethod('post') && $this->getRequest()->get($this->filter->getForm()->getName()))
        {
            $data = $this->getRequest()->get($this->filter->getForm()->getName());
        }
        else
        {
            $data = $this->getRequest()->getSession()->get($this->getSessionName().'.filter', $this->getDefaultFilters());
        }
        
        $this->filter->submit($data);
        $form = $this->filter->getForm();
        $formData = $form->getData();
        
        if($form->isValid())
        {
            if($this->getRequest()->isMethod('post'))
            {
                $this->getRequest()->getSession()->set($this->getSessionName().'.filter', $data);
            }
            $this->applyFilter($formData);
        }
        
        return $this;
    }
    
    private function applyFilter($data)
    {
        foreach($data as $key => $value)
        {
            $empty = true;
            
            if(($value instanceof \PropelCollection || is_array($value)))
            {
                if(count($value) > 0)
                {
                    $empty = false;
                }
            }
            elseif(!empty($value) || $value === 0)
            {
                $empty = false;
            }
            
            if(!$empty)
            {
                $method = 'filterBy'.$this->container->get('spyrit.util.inflector')->camelize($key);

                if($this->filter->getType($key) === 'text')
                {
                    $this->getQuery()->$method('%'.$value.'%', Criteria::LIKE);
                }
                else
                {
                    $this->getQuery()->$method($value);
                }
            }
        }
    }
    
    protected function sort()
    {
        $namespace = $this->getSessionName().'.'.$this->getSortActionParameterName();
        
        $sort = $this->getSession()->get($namespace)? $this->getSession()->get($namespace) : $this->getDefaultSort();
        
        if(
            $this->getRequest()->get($this->getActionParameterName()) == $this->getSortActionParameterName() &&
            $this->getRequest()->get($this->getDatagridParameterName()) == $this->getName()
        )
        {
            $sort['column'] = $this->getRequest()->get($this->getSortColumnParameterName());
            $sort['order'] = $this->getRequest()->get($this->getSortOrderParameterName());
            
            $this->getSession()->set($namespace, $sort);
        }
        $method = 'orderBy'.ucfirst($sort['column']);
        try
        {
            $this->getQuery()->$method($sort['order']);
        }
        catch(\Exception $e)
        {
            throw new \Exception('There is no method "'.$method.'" to sort the datagrid on column "'.$sort['column'].'". Just create it in the "'.get_class($this->query).'" object.');
        }
    }
    
    protected function getDefaultFilters()
    {
        return array();
    }
    
    public function getDefaultSort()
    {
        return array(
            'column' => $this->getDefaultSortColumn(),
            'order' => $this->getDefaultSortOrder(),
        );
    }
    
    public function getSortColumn()
    {
        $sort = $this->getSession()->get($this->getSessionName().'.'.$this->getSortActionParameterName(), $this->getDefaultSort());
        return $sort['column'];
    }
    
    public function getSortOrder()
    {
        $sort = $this->getSession()->get($this->getSessionName().'.'.$this->getSortActionParameterName(), $this->getDefaultSort());
        return $sort['order'];
    }
    
    public function reset()
    {
        if($this->getRequest()->get($this->getDatagridParameterName()) == $this->getName())
        {
            return $this
                ->resetFilters()
                ->resetSort();
        }
        return $this;
    }
    
    public function resetFilters()
    {
        $this->getRequest()->getSession()->remove($this->getSessionName().'.filter');
        return $this;
    }
    
    public function resetSort()
    {
        $this->getRequest()->getSession()->remove($this->getSessionName().'.'.$this->getSortActionParameterName());
        return $this;
    }
    
    protected function buildForm()
    {
        $filters = $this->configureFilter();
        
        if(!empty($filters))
        {
            $this->filter = new FilterObject($this->getFormFactory(), $this->getName());
            
            foreach($filters as $name => $filter)
            {
                $this->filter->add($name, $filter['type'], isset($filter['options'])? $filter['options'] : array(), isset($filter['value'])? $filter['value'] : null);
            }
            
            $this->configureFilterBuilder($this->filter->getBuilder());
        }
    }
    
    public function configureFilter()
    {
        return array();
    }
    
    /**
     * @param type $name
     * @param type $params
     * @return self
     */
    public function export($name, $params = array())
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
        if(!isset($exports[$name]))
        {
            throw new \Exception('The "'.$name.'" export doesn\'t exist in this datagrid.');
        }
        return $exports[$name];
    }
    
    protected function getExports()
    {
        return array();
    }
    
    /**
     * Shortcut 
     */
    public function getFilterFormView()
    {
        return $this->filter->getForm()->createView();
    }
    
    public function getMaxPerPage()
    {
        if($this->maxPerPage)
        {
            return $this->maxPerPage;
        }
        return 30;
    }
    
    public function getSessionName()
    {
        return 'datagrid.'.$this->getName();
    }
    
    public function setMaxPerPage($v)
    {
        $this->maxPerPage = $v;
    }
    
    public function getCurrentPage($default = 1)
    {
        $name = $this->getSessionName().'.'.$this->getPageParameterName();
        if($this->getRequest()->get($this->getDatagridParameterName()) == $this->getName())
        {
            $page = $this->getRequest()->get($this->getPageParameterName());
        }
        if(!isset($page))
        {
            $page = $this->getRequest()->getSession()->get($name, $default);
        }
        $this->getRequest()->getSession()->set($name, $page);
        
        return $page;
    }
    
    public function getActionParameterName()
    {
        return 'action';
    }
    
    public function getSortActionParameterName()
    {
        return 'sort';
    }
    
    public function getPageActionParameterName()
    {
        return 'page';
    }
    
    public function getDatagridParameterName()
    {
        return 'datagrid';
    }
    
    public function getPageParameterName()
    {
        return 'param1';
    }
    
    public function getResetActionParameterName()
    {
        return 'reset';
    }
    
    public function getSortColumnParameterName()
    {
        return 'param1';
    }
    
    public function getSortOrderParameterName()
    {
        return 'param2';
    }
    
    public function getDefaultSortOrder()
    {
        return strtolower(Criteria::ASC);
    }
    
    public function configureFilterForm()
    {
        return array();
    }
    
    public function configureFilterBuilder($builder)
    {
        /**
         * Do what you want with the builder. For example, add Event Listener PRE/POST_SET_DATA, etc.
         */
        return;
    }
    
    /**
     * Shortcut to return the request service.
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        return $this->container->get('request');
    }
    
    /**
     * Shortcut to return the request service.
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function getSession()
    {
        return $this->container->get('session');
    }
    
    /**
     * return the Form Factory Service
     * @return \Symfony\Component\Form\FormFactory
     */
    protected function getFormFactory()
    {
        return $this->container->get('form.factory');
    }
    
    protected function getQuery()
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
     * @param type $route
     * @param type $extraParams
     * @return type
     */
    public function getPaginationPath($route, $page, $extraParams = array())
    {
        $params = array(
            $this->getActionParameterName() => $this->getPageActionParameterName(),
            $this->getDatagridParameterName() => $this->getName(),
            $this->getPageParameterName() => $page,
        );
        return $this->container->get('router')->generate($route, array_merge($params, $extraParams));
    }
    
    /**
     * Generate reset route for the button view
     * @param type $route
     * @param type $extraParams
     * @return type
     */
    public function getResetPath($route, $extraParams = array())
    {
        $params = array(
            $this->getActionParameterName() => $this->getResetActionParameterName(),
            $this->getDatagridParameterName() => $this->getName(),
        );
        return $this->container->get('router')->generate($route, array_merge($params, $extraParams));
    }
    
    /**
     * Generate sorting route for a given column to be displayed in view
     * @todo Remove the order parameter and ask to the datagrid to guess it ?
     * @param type $route
     * @param type $column
     * @param type $order
     * @param type $extraParams
     * @return type
     */
    public function getSortPath($route, $column, $order, $extraParams = array())
    {
        $params = array(
            $this->getActionParameterName() => $this->getSortActionParameterName(),
            $this->getDatagridParameterName() => $this->getName(),
            $this->getSortColumnParameterName() => $column,
            $this->getSortOrderParameterName() => $order,
        );
        return $this->container->get('router')->generate($route, array_merge($params, $extraParams));
    }
}
