return array(
    'name'              => 'ask-02.rq',
    'group'             => 'RAP Ask Test Cases',
    'query'             => 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
    PREFIX  foaf:       <http://xmlns.com/foaf/0.1/>

    SELECT ?name
    WHERE {
      ?x rdf:type foaf:Person .
      ?x foaf:name ?name .
    }'
);


