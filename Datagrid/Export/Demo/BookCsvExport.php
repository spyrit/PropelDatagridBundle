<?php
namespace Spyrit\PropelDatagridBundle\Datagrid\Export\Demo;

use Spyrit\PropelDatagridBundle\Datagrid\Export\CsvExport;

class BookCsvExport extends CsvExport
{
    public function getFilename()
    {
        return 'Books.csv';
    }
    
    public function getHeader()
    {
        return array(
            'Title', 
            'Author',
            'Publisher',
        );
    }
    
    public function getRow($object)
    {
        return array(
            $object->getTitle(),
            $object->getAuthor(),
            $object->getPublisher(),
        );
    }
}
