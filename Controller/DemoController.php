<?php

namespace Spyrit\PropelDatagridBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Spyrit\PropelDatagridBundle\Datagrid\Demo\BookDatagrid;

class DemoController extends Controller
{
    public function listAction(Request $request)
    {
        $datagrid = BookDatagrid::create($this->container)->execute();

        return $this->render('SpyritPropelDatagridBundle:Demo:list.html.twig', array('datagrid' => $datagrid));
    }
    
    public function exportAction(Request $request)
    {
        $export = BookDatagrid::create($this->container)->export($request->get('name'));

        return $export->getResponse();
    }
}
