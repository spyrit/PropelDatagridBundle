<?php

namespace Spyrit\TestBundle\Propel;

use Spyrit\PropelDatagridBundle\Propel\om\BaseAuthor;

class Author extends BaseAuthor
{
    public function __toString()
    {
        return $this->getFullname();
    }
    
    public function getFullname()
    {
        return $this->getFirstName().' '.$this->getLastName();
    }
}
