<?php

namespace Spyrit\PropelDatagridBundle\Datagrid;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

class PropelDatagridFactory
{
    protected $request_stack;
    protected $session;
    protected $form_factory;
    protected $router;

    /**
     * Just a simple constructor.
     */
     public function __construct(
         RequestStack $requestStack,
         SessionInterface $session,
         FormFactoryInterface $formFactory,
         RouterInterface $router
    ) {
        $this->request_stack = $requestStack;
        $this->session = $session;
        $this->form_factory = $formFactory;
        $this->router = $router;
    }

    /**
     * Create an instance of DoctrineDatagrid.
     */
    public function create(string $name, array $params = []): PropelDatagrid
    {
        return new PropelDatagrid($this->request_stack, $this->session, $this->form_factory, $this->router, $name, $params);
    }
}
