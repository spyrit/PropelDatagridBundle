<?php

namespace Spyrit\PropelDatagridBundle\Datagrid\Demo;

use Spyrit\PropelDatagridBundle\Datagrid\PropelDatagrid;
use Spyrit\PropelDatagridBundle\Propel\PublisherQuery;

class PublisherDatagrid extends PropelDatagrid
{
    public function configureQuery()
    {
        return PublisherQuery::create();
    }

    public function configureFilter()
    {
        return array(
            'id' => array(
                'type' => 'integer',
                'options' => array(
                    'required' => false,
                ),
            ),
            'name' => array(
                'type' => 'text',
                'options' => array(
                    'required' => false,
                ),
            ),
        );
    }

    /**
     * 
     * @return type
     */
    public function getDefaultFilters()
    {
        return array();
    }

    /**
     * 
     * @return string
     */
    public function getDefaultSortColumn()
    {
        return 'id';
    }

    /**
     * 
     * @return string
     */
    public function getName()
    {
        return 'publisher';
    }

    /**
     * 
     * @return int
     */
    public function getMaxPerPage()
    {
        return 6;
    }
}
