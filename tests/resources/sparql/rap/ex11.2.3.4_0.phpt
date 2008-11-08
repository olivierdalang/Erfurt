return array(
    'name'              => 'ask-02.rq',
    'group'             => 'RAP Ask Test Cases',
    'query'             => 'PREFIX a:      <http://www.w3.org/2000/10/annotation-ns#>
    PREFIX dc:     <http://purl.org/dc/elements/1.1/>
    PREFIX foaf:   <http://xmlns.com/foaf/0.1/>

    SELECT ?given ?family
     WHERE { ?annot  a:annotates  <http://www.w3.org/TR/rdf-sparql-query/> .
             ?annot  dc:creator   ?c .
             OPTIONAL { ?c  foaf:given   ?given ; foaf:family  ?family } .
             FILTER isBlank(?c)
           }'
);


