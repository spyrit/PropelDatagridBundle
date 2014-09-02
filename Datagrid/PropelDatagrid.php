<?php

namespace Spyrit\PropelDatagridBundle\Datagrid;

use Spyrit\PropelDatagridBundle\Datagrid\PropelDatagridInterface;
use Spyrit\PropelDatagridBundle\Helper\Inflector;

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
    
    public function __construct($container)
    {
        $this->container = $container;
        $this->query = $this->configureQuery();
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
    
    protected function filter()
    {
        if($this->getRequest()->isMethod('post'))
        {
            $data = $this->getRequest()->get($this->filter->getForm()->getName());
        }
        else
        {
            $data = $this->getRequest()->getSession()->get($this->getSessionName().'.filter', $this->getDefaultFilters());
        }
        
        $this->filter->submit($data);
        $form = $this->filter->getForm();
        $data = $form->getData();
        
        if($form->isValid())
        {
            if($this->getRequest()->isMethod('post'))
            {
                $this->getRequest()->getSession()->set($this->getSessionName().'.filter', $data);
            }
            
            foreach($data as $key => $value)
            {
                if($value)
                {
                    $method = 'filterBy'.$this->container->get('spyrit.util.inflector')->camelize($key);

                    if($this->filter->getType($key) == FilterObject::TYPE_TEXT)
                    {
                        $this->getQuery()->$method('%'.$value.'%', \Criteria::LIKE);
                    }
                    elseif($this->filter->getType($key) == FilterObject::TYPE_MODEL)
                    {
                        $this->getQuery()->$method($value);
                    }
                    elseif($this->filter->getType($key) == FilterObject::TYPE_DATE)
                    {
                        $this->getQuery()->$method($value);
                    }
                }
            }
        }
        
        return $this;
    }
    
    protected function sort()
    {
        $namespace = $this->getSessionName().'.'.$this->getSortActionParameterName();
        
        $sort = $this->getSession()->get($namespace)? $this->getSession()->get($namespace) : $this->getDefaultSort();
        
        if($this->getRequest()->get($this->getActionParameterName()) == $this->getSortActionParameterName())
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
        return $this
            ->resetFilters()
            ->resetSort();
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
        $page = $this->getRequest()->get($this->getPageParameterName());
        if(!$page)
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
        return strtolower(\Criteria::ASC);
    }
    
    public function configureFilterForm()
    {
        return array();
    }
    
    public function configureFilterBuilder($builder)
    {
        // Do what you want with the builder
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
    public function getFormFactory()
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
}
