<?php
namespace Spyrit\PropelDatagridBundle\Datagrid\Export;

use Spyrit\PropelDatagridBundle\Datagrid\Export\PropelExport;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use CSanquer\ColibriCsv\CsvWriter;

/**
 * @author Maxime CORSON <maxime.corson@spyrit.net>
 */
abstract class CsvExport implements PropelExport
{
    protected $content;
    /**
     * @var array 
     */
    protected $params;
    /**
     * @var PropelQuery 
     */
    protected $query;
    
    public function __construct($query, $params)
    {
        $this->query = $query;
        $this->params = $params;
    }
    
    public function execute()
    {
        $writer = new CsvWriter();
        $writer->createTempStream();
        
        if($this->getHeader())
        {
            $writer->writeRow($this->getHeader());
        }
        
        $results = $this->query->find();

        foreach($results as $result) 
        {
            $writer->writeRow($this->getRow($result));
        }

        $this->content = $writer->getFileContent();
        $writer->close();
        
        return $this;
    }
    
    public function getResponse()
    {
        $response = new Response($this->content); 
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $this->getFilename()
        );

        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/force-download');
        $response->setCharset('UTF-8');
        return $response;
    }
    
    public abstract function getHeader();
    
    public abstract function getRow($object);
    
    public function getDelimiter()
    {
        return ';';
    }
}
