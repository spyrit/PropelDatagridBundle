PropelDatagridBundle
==============

[![Join the chat at https://gitter.im/spyrit/PropelDatagridBundle](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/spyrit/PropelDatagridBundle?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Build Status](https://travis-ci.org/spyrit/PropelDatagridBundle.svg?branch=master)](https://travis-ci.org/spyrit/PropelDatagridBundle)

This bundle helps you to create and manage simple to complex datagrids quickly and easily.

Unlike other similar bundle already available on github and/or packagist, there is no magic method that will render the datagrid in you view. This technical choice allow you to completely customize your datagrid aspect and render (filter fields, buttons, columns, data displayed in each column, pagination links and informations, etc.)

This make it easy to implement and use in both back-end and front-end applications.

Still skeptical ? Let's see how it works !

## Installation

### Get the code

Since composer is the simplest and fastest way to install dependencies, the only way to install this bundle automatically is to add the following line to your dependencies


    composer require spyrit/propel-datagrid-bundle


* Branches 1.x (unmaintained) are for Propel1 and Symfony2
    - Branch 1.0 is for backward-compatibility with old projects (PHP < 5.4).
    - Branch 1.1 requires PHP-5.4+ for `csanquer/colibri-csv 1.2`
    - Branch 1.2 integrates new functionnalities like dynamic max-per-page value
    - Branch 1.3 implements batch (mass) actions
* Branches 2.x (unmaintained) are for Propel2 and Symfony2
    - Branch 2.0 is for backward-compatibility with old projects (PHP < 5.4).
    - Branch 2.1 requires PHP-5.4+ for `csanquer/colibri-csv` 1.2
    - Branch 2.2 integrates new functionnalities like dynamic max-per-page value
    - Branch 2.3 implements batch (mass) actions
* Branch 3.0 (unmaintained) is for Propel2 and Symfony3
* Branch 4.0 (unmaintained) is for Propel2 and Symfony4
* Branch 5.0 (maintained) is for Propel2 and Symfony5
* Branch 6.0 (maintained) is for Propel2 and Symfony6
  -  requires PHP-7.4+
  -  requires `spyrit/colibri-csv` 1.3
  -  recommends the usage of PropelBundle fork in your composer.json https://github.com/SkyFoxvn/PropelBundle
  -  BC: DataGrid no longer depends of `Symfony\Component\DependencyInjection\ContainerInterface`.
     It depends of 
      - `Symfony\Component\HttpFoundation\RequestStack`
      - `Symfony\Component\Form\FormFactoryInterface`
      - `Symfony\Component\Routing\RouterInterface`
  -  BC: Use service autowiring, see https://github.com/spyrit/PropelDatagridBundle?tab=readme-ov-file#declare-your-datagrid---the-controllers-job 

### Enable the bundle

Enable the bundle in your application

```php
// config/bundles.php
<?php

return [
    ...
    Spyrit\PropelDatagridBundle\SpyritPropelDatagridBundle::class => ['all' => true],
```

## Usage

May be the most interesting part of this documentation which quickly describe how to create and use your first Datagrid.

### Create your datagrid - Your Job

To create a datagrid you have to create a single class that inherit from the PropelDatagrid object and implement all methods from the PropelDatagridInterface :

```php
<?php

namespace Spyrit\PropelDatagridBundle\Datagrid\Demo;

use Spyrit\PropelDatagridBundle\Datagrid\PropelDatagrid;

class BookDatagrid extends PropelDatagrid
{
    public function configureQuery()
    {
        return BookQuery::create();
    }

    public function getDefaultSortColumn()
    {
        return 'id';
    }

    public function getName()
    {
        return 'book';
    }
    
    public function configureFilter()
    {
        return [
            'id' => [
                'type' => IntegerType::class,
                'options' => [
                    'label' => '#',
                    'required' => false,
                ],
            ],
            'title' => [
                'type' => TextType::class,
                'options' => [
                    'label' => 'Title',
                ],
            ]
        ];
      }
}
```

The configureQuery method must return a predefined PropelQuery object (example: BookQuery object) as shown here :

```php
<?php
//...
public function configureQuery()
{
    return BookQuery::create()
        ->joinWith('Author', \Criteria::LEFT_JOIN)
        ->joinWith('Publisher', \Criteria::LEFT_JOIN)
    ;
}
```


### Declare your datagrid - The Controller's Job

```
<?php

namespace App\Controller;

use App\Propel\Annonce;
use App\Propel\AnnonceQuery;
use App\Datagrid\BookDatagrid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/books')]
class BookController extends AbstractController
{
    #[Route('/')]
    public function list(BookDatagrid $annonceDatagrid): Response
    {
        $datagrid = $annonceDatagrid->execute();

        return $this->render('book/list.html.twig', [
            'datagrid' => $datagrid,
        ]);
    }
}
```

### Display your datagrid - The view's Job and yours (or designer)

```twig
{% extends "base.html.twig" %}

{% import "@SpyritPropelDatagrid/Datagrid/macros.html.twig" as macros %}

{% block body %}

  {% set form = datagrid.filterFormView %}
  <form method="post">
      {{ form_widget(form.id) }}
      {{ form_widget(form.title) }}
      
      {{ form_errors(form) }}
      {{ form_rest(form) }}
  </form>
  
  <table>
      <thead>
        <tr>
            <th>{{ macros.sort('id', "#", route, datagrid) }}</th>
            <th>{{ macros.sort('title', "Title", route, datagrid) }}</th>
        </tr>
      </thead>
      <tbody>
        {% for book in datagrid.results %}
          <tr>
              <td>{{ book.id }}</td>
              <td>{{ book.title }}</td>
          </tr>
        {% endfor %}
      </tbody>
  </table>
{% endblock %}
```

### Export datagrid data

Todo

## Credit

Our special thanks go to ...

- Charles SANQUER for its fork of the *LightCSV* library : *Colibri CSV* used in the export feature.
- Subosito for its standalone *Inflector* [class](http://subosito.com/inflector-in-symfony-2/)  transformed in a service for our needs.

