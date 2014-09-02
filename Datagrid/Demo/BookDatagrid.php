<?php

namespace Spyrit\PropelDatagridBundle\Datagrid\Demo;

use Spyrit\PropelDatagridBundle\Datagrid\PropelDatagrid;
use Spyrit\PropelDatagridBundle\Propel\BookQuery;

class BookDatagrid extends PropelDatagrid
{
    public function configureQuery()
    {
        return BookQuery::create()
            ->joinWith('Author', \Criteria::LEFT_JOIN)
            ->joinWith('Publisher', \Criteria::LEFT_JOIN)
        ;
    }

    public function configureFilter()
    {
        return array(
            'id' => array(
                // This is the classic form type
                'type' => 'text',
                // Following options are the classic FormType options
                'options' => array(
                    'required' => false,
                ),
            ),
            'title' => array(
                'type' => 'text',
                'options' => array(
                    'required' => false,
                ),
            ),
            'authorFullname' => array(
                'type' => 'text',
                'options' => array(
                    'required' => false,
                ),
            ),
            'publisher' => array(
                'type' => 'model',
                'options' => array(
                    'class' => '\Spyrit\PropelDatagridBundle\Propel\Publisher',
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
        return 'book';
    }

    /**
     * 
     * @return int
     */
    public function getMaxPerPage()
    {
        return 15;
    }

    /**
     * 
     * @return type
     */
    public function getExports()
    {
        return array(
            'csv' => 'Spyrit\PropelDatagridBundle\Datagrid\Export\Demo\BookCsvExport',
        );
    }
}
