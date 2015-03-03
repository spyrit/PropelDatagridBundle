PropelDatagridBundle
==============

[![Join the chat at https://gitter.im/spyrit/PropelDatagridBundle](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/spyrit/PropelDatagridBundle?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

This bundle helps you to create and manage simple to complex datagrids quickly and easily. 

Unlike other similar bundle already available on github and/or packagist, there is no magic method that will render the datagrid in you view. This technical choice allow you to completely customize your datagrid aspect and render (filter fields, buttons, columns, data displayed in each column, pagination links and informations, etc.)

This make it easy to implement and use in both back-end and front-end applications.

Still skeptical ? Let's see how it works !

## Installation

### Get the code

Since composer is the simplest and fastest way to install dependencies, the only way to install this bundle automatically is to add the following line to your dependencies


	"require": {
    	...
    	"spyrit/propel-datagrid-bundle": "dev-master"
    	...
	},


Note : A branch and a tag will be created once the bundle will be stabilized. In the meantime, use the dev-master branch.

### Enable the bundle

You won't be surprised to be asked to add the following line in your Kernel :

```php
// app/AppKernel.php
<?php
    // ...
    public function registerBundles()
    {
        $bundles = array(
            // ...
            // don't forget the PropelBundle too
            new Spyrit\PropelDatagridBundle\SpyritPropelDatagridBundle(),
        );
    }
```

### Try the demo

A demo is included in the demo branch which is updated with the master updates. To try it, follow these few steps :

1. Build your model
```bash
app/console propel:build
```
1. Create the database structure
```bash
app/console propel:sql:insert
```
1. Publish assets in your web directory (in symlink mode?)
```bash
app/console assets:install --symlink
```
1. Add a route to the PropelDatagridBundle routing file :
```yml
spyrit_propel_datagrid:
    resource: "@SpyritPropelDatagridBundle/Resources/config/routing.yml"
```
If you used this previous code sample and didn't add a prefix to the route, you should access to the demo with this URL : <protocole>://<your_dommain>/datagrid/demo/book/list

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
    }

    public function getDefaultSortColumn()
    {
    }

    public function getName()
    {
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

Todo

### Display your datagrid - The view's Job and yours (or designer)

Todo

### Export datagrid data

Todo

## Credit

Our special thanks go to ...

- Charles SANQUER for its fork of the *LightCSV* library : *Colibri CSV* used in the export feature.
- Subosito for its standalone *Inflector* [class](http://subosito.com/inflector-in-symfony-2/)  transformed in a service for our needs.

