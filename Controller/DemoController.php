<?php

namespace Spyrit\PropelDatagridBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Spyrit\PropelDatagridBundle\Datagrid\Demo\BookDatagrid;
use Spyrit\PropelDatagridBundle\Datagrid\Demo\PublisherDatagrid;

class DemoController extends Controller
{
    public function listAction(Request $request)
    {
        return $this->render('SpyritPropelDatagridBundle:Demo:list.html.twig', array(
            'booksDatagrid' => BookDatagrid::create($this->container)->execute(),
            'publishersDatagrid' => PublisherDatagrid::create($this->container)->execute(),
        ));
    }
    
    public function exportAction(Request $request)
    {
        $export = BookDatagrid::create($this->container)->export($request->get('name'));

        return $export->getResponse();
    }
}
