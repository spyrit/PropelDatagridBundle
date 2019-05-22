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
        $writer = new CsvWriter($this->getCsvWriterOptions());
        $writer->createTempStream();
        
        if ($this->getHeader()) {
            $writer->writeRow($this->getHeader());
        }
        
        $results = $this->query->find();

        foreach ($results as $result) {
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

        $response->headers->set('Content-Description', 'File Transfer');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Content-Length', strlen($this->content));
        $response->setCharset('UTF-8');
        return $response;
    }
    
    abstract public function getHeader();
    
    abstract public function getRow($object);
    
    public function getDelimiter()
    {
        return ';';
    }
    
    protected function getCsvWriterOptions()
    {
        if (isset($this->params['csvWriter'])) {
            return $this->params['csvWriter'];
        }
        return [];
    }
}
