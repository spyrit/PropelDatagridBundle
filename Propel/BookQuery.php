<?php

namespace Spyrit\PropelDatagridBundle\Propel;

use Spyrit\PropelDatagridBundle\Propel\om\BaseBookQuery;

class BookQuery extends BaseBookQuery
{
    public function orderByAuthorFirstname($order = \Criteria::ASC)
    {
        return $this->useAuthorQuery()
                ->orderByFirstName($order)
            ->endUse();
    }
    
    public function orderByAuthorLastname($order = \Criteria::ASC)
    {
        return $this->useAuthorQuery()
                ->orderByLastName($order)
            ->endUse();
    }
    
    public function filterByAuthorFullname($value, $comparison = null)
    {
        return $this->useAuthorQuery()
                ->filterByFirstName($value, $comparison)
                ->_or()
                ->filterByLastname($value, $comparison)
            ->endUse();
    }
}
