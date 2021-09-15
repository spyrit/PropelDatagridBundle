<?php

namespace Spyrit\PropelDatagridBundle\Datagrid;

/**
 * @author Maxime CORSON <maxime.corson@spyrit.net>
 */
interface PropelDatagridInterface
{
    public function getDefaultSortColumn();

    public function getSessionName();

    public function getName();
}
