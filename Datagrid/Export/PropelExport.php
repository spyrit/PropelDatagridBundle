<?php

namespace Spyrit\PropelDatagridBundle\Datagrid\Export;

/**
 * @author Maxime CORSON <maxime.corson@spyrit.net>
 */
interface PropelExport
{
    public function execute();
    
    public function getResponse();
    
    public function getFilename();
}
