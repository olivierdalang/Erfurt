<?php
require_once 'Erfurt/Syntax/RdfSerializer/Adapter/Interface.php';

class Erfurt_Syntax_RdfSerializer_Adapter_RdfXml implements Erfurt_Syntax_RdfSerializer_Adapter_Interface
{
    protected $_currentSubject = null;
    protected $_currentSubjectType = null;
    protected $_pArray = array();
    
    protected $_store = null;
    protected $_graphUri = null;
    
    protected $_renderedTypes = array();
    
    protected $_rdfWriter = null;
    
    public function serializeGraphToString($graphUri, $pretty = false)
    {
        require_once 'Erfurt/Syntax/RdfSerializer/Adapter/RdfXml/StringWriterXml.php';
        require_once 'Erfurt/Syntax/RdfSerializer/Adapter/RdfXml/RdfWriter.php';
        
        $xmlStringWriter = new Erfurt_Syntax_RdfSerializer_Adapter_RdfXml_StringWriterXml();
        $this->_rdfWriter = new Erfurt_Syntax_RdfSerializer_Adapter_RdfXml_RdfWriter($xmlStringWriter);
        
        $this->_store = Erfurt_App::getInstance()->getStore();
        $this->_graphUri = $graphUri;
        $graph = $this->_store->getModel($graphUri);
        
		$this->_rdfWriter->setGraphUri($graphUri);
		
		$base  = $graph->getBaseUri();
		$this->_rdfWriter->setBase($base);
		
		foreach ($this->_store->listNamespacePrefixes($graphUri) as $ns => $prefix) {
            $this->_rdfWriter->addNamespacePrefix($prefix, $ns);
        }
		
		$config = Erfurt_App::getInstance()->getConfig();
        if (isset($config->serializer->ad)) {
            $this->_rdfWriter->startDocument($config->serializer->ad);
        } else {
            $this->_rdfWriter->startDocument();
        }
		
		$this->_rdfWriter->setMaxLevel(10);
		
		$this->_serializeType('Ontology specific informations', EF_OWL_ONTOLOGY);
		
		$this->_rdfWriter->setMaxLevel(1);
		
		$this->_serializeType('Classes', EF_OWL_CLASS);
		$this->_serializeType('Datatypes', EF_RDFS_DATATYPE);
		$this->_serializeType('Annotation properties', EF_OWL_ANNOTATION_PROPERTY);
		$this->_serializeType('Datatype properties', EF_OWL_DATATYPE_PROPERTY);
		$this->_serializeType('Object properties', EF_OWL_OBJECT_PROPERTY);
		
		$this->_serializeRest('Instances and untyped data');
		
		$this->_rdfWriter->endDocument();
		
		return $this->_rdfWriter->getContentString();
    }
    
    public function serializeResourceToString($resource, $graphUri, $pretty = false)
    {
        require_once 'Erfurt/Syntax/RdfSerializer/Adapter/RdfXml/StringWriterXml.php';
        require_once 'Erfurt/Syntax/RdfSerializer/Adapter/RdfXml/RdfWriter.php';
        
        $xmlStringWriter = new Erfurt_Syntax_RdfSerializer_Adapter_RdfXml_StringWriterXml();
        $this->_rdfWriter = new Erfurt_Syntax_RdfSerializer_Adapter_RdfXml_RdfWriter($xmlStringWriter);
        
        $this->_store = Erfurt_App::getInstance()->getStore();
        $this->_graphUri = $graphUri;
        $graph = $this->_store->getModel($graphUri);
        
		$this->_rdfWriter->setGraphUri($graphUri);
		
		$base  = $graph->getBaseUri();
		$this->_rdfWriter->setBase($base);
		
		foreach ($this->_store->listNamespacePrefixes($graphUri) as $ns => $prefix) {
            $this->_rdfWriter->addNamespacePrefix($prefix, $ns);
        }
		
		$config = Erfurt_App::getInstance()->getConfig();
        if (isset($config->serializer->ad)) {
            $this->_rdfWriter->startDocument($config->serializer->ad);
        } else {
            $this->_rdfWriter->startDocument();
        }
		
		$this->_rdfWriter->setMaxLevel(1);
		
		$this->_serializeResource($resource);
		
		$this->_rdfWriter->endDocument();
		
		return $this->_rdfWriter->getContentString();
    }
    
    protected function _handleStatement($s, $p, $o, $sType, $oType, $lang = null, $dType = null)
    {
        if (null === $this->_currentSubject) {
            $this->_currentSubject = $s;
            $this->_currentSubjectType = $sType;
        }
        
        if ($s === $this->_currentSubject && $sType === $this->_currentSubjectType) {
            // Put the statement on the list.
            if (!isset($this->_pArray[$p])) {
                $this->_pArray[$p] = array();
            }
            
            if ($oType === 'typed-literal') {
               $oType = 'literal';
            }
            
            $oArray =  array(
                'value' => $o,
                'type'  => $oType
            );
            
            if (null !== $lang) {
                $oArray['lang'] = $lang;
            } else if (null !== $dType) {
                $oArray['datatype'] = $dType;
            }
            
            $this->_pArray[$p][] = $oArray;
        } else {
            $this->_forceWrite();
            
            $this->_currentSubject = $s;
            $this->_currentSubjectType = $sType;
            $this->_pArray = array($p => array());

            if ($oType === 'typed-literal') {
               $oType = 'literal';
            }

            $oArray =  array(
                'value' => $o,
                'type'  => $oType
            );

            if (null !== $lang) {
                $oArray['lang'] = $lang;
            } else if (null !== $dType) {
                $oArray['datatype'] = $dType;
            }

            $this->_pArray[$p][] = $oArray;
        }
    }
    
    protected function _forceWrite()
    {
        if (null === $this->_currentSubject) {
            return;
        }
        
        // Write the statements
        $this->_rdfWriter->serializeSubject($this->_currentSubject, $this->_currentSubjectType, $this->_pArray);
        
        $this->_currentSubject = null;
        $this->_currentSubjectType = null;
        $this->_pArray = array();
    }
    
    /**
	 * Internal function, which takes a type and a description and serializes all statements of this type in a section.
	 *
	 * @param string $description A description for the given class of statements (e.g. owl:Class).
	 * @param string $class The type which to serialize (e.g. owl:Class).
	 */
	protected function _serializeType($description, $class) 
	{	
		$query = new Erfurt_Sparql_SimpleQuery();
		$query->setProloguePart('SELECT DISTINCT ?s ?p ?o');
		$query->addFrom($this->_graphUri);
		$query->setWherePart('WHERE { ?s ?p ?o . ?s <' . EF_RDF_TYPE . '> <' . $class . '> }');
		$query->setLimit(1000);
		
		$offset = 0;
		while (true) {
		    $query->setOffset($offset);
		    
		    $result = $this->_store->sparqlQuery($query, 'extended');

    		if ($offset === 0 && count($result['bindings']) > 0) {
    		    $this->_rdfWriter->addComment($description);
    		}

    		foreach ($result['bindings'] as $row) {
    		    $s = $row['s']['value'];
    		    $p = $row['p']['value'];
    		    $o = $row['o']['value'];
    		    $sType = $row['s']['type'];
    		    $oType = $row['o']['type'];
    		    $lang  = isset($row['o']['xml:lang']) ? $row['o']['xml:lang'] : null;
                $dType = isset($row['o']['datatype']) ? $row['o']['datatype'] : null;

                $this->_handleStatement($s, $p, $o, $sType, $oType, $lang, $dType);
    		}
    		
    		if (count($result['bindings']) < 1000) {
    	        break;
    		}
    		
    		$offset += 1000;	
		}
		
		$this->_forceWrite();
		
		$this->_renderedTypes[] = $class;
	}
	
	protected function _serializeRest($description)
	{
	    $query = new Erfurt_Sparql_SimpleQuery();
		$query->setProloguePart('SELECT DISTINCT ?s ?p ?o');
		$query->addFrom($this->_graphUri);
		
		$where = 'WHERE 
		          { ?s ?p ?o . 
		            OPTIONAL { ?s <' . EF_RDF_TYPE . '> ?o2 } . 
		            FILTER (!bound(?o2) || (';
		
		$count = count($this->_renderedTypes);
		for ($i=0; $i<$count; ++$i) {
		    $where .= '!sameTerm(?o2, <' . $this->_renderedTypes[$i] . '>)';
		    
		    if ($i < $count-1) {
		        $where .= ' && ';
		    }
		}
		
		$where .= '))}';
		
		$query->setWherePart($where);
		$query->setOrderClause('?s');
		$query->setLimit(1000);
	
		$offset = 0;
		while (true) {
		    $query->setOffset($offset);
		    
		    $result = $this->_store->sparqlQuery($query, 'extended');

    		if ($offset === 0 && count($result['bindings']) > 0) {
    		    $this->_rdfWriter->addComment($description);
    		}

    		foreach ($result['bindings'] as $row) {
    		    $s = $row['s']['value'];
    		    $p = $row['p']['value'];
    		    $o = $row['o']['value'];
    		    $sType = $row['s']['type'];
    		    $oType = $row['o']['type'];
    		    $lang  = isset($row['o']['xml:lang']) ? $row['o']['xml:lang'] : null;
                $dType = isset($row['o']['datatype']) ? $row['o']['datatype'] : null;

                $this->_handleStatement($s, $p, $o, $sType, $oType, $lang, $dType);
    		}
    		
    		if (count($result['bindings']) < 1000) {
    	        break;
    		}
    		
    		$offset += 1000;
		}
		
		$this->_forceWrite();
	}
	
	protected function _serializeResource($resource)
	{
	    require_once 'Erfurt/Sparql/SimpleQuery.php';
        $query = new Erfurt_Sparql_SimpleQuery();
        $query->setProloguePart('SELECT ?s ?p ?o');
        $query->addFrom($this->_graphUri);
        $query->setWherePart('WHERE { ?s ?p ?o . FILTER (sameTerm(?s, <'.$resource.'>))}');
        $query->setOrderClause('?s');
        $query->setLimit(1000);
        
        $offset = 0;
        while (true) {
            $query->setOffset($offset);
            
            $result = $this->_store->sparqlQuery($query, 'extended');
            
            foreach ($result['bindings'] as $row) {
                $s     = $row['s']['value'];
                $p     = $row['p']['value'];
                $o     = $row['o']['value'];
                $sType = $row['s']['type'];
                $oType = $row['o']['type'];
                $lang  = isset($row['o']['xml:lang']) ? $row['o']['xml:lang'] : null;
                $dType = isset($row['o']['datatype']) ? $row['o']['datatype'] : null;

                $this->_handleStatement($s, $p, $o, $sType, $oType, $lang, $dType);
            }
            
            if (count($result['bindings']) < 1000) {
    	        break;
    		}
    		
    		$offset += 1000;
        }
        
        $this->_forceWrite();
	}
}