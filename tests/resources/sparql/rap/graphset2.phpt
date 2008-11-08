return array(
    'name'              => 'ask-02.rq',
    'group'             => 'RAP Ask Test Cases',
    'query'             => 'PREFIX foaf: <http://xmlns.com/foaf/0.1/>
    PREFIX data: <http://example.org/foaf/>

    SELECT ?nick
    WHERE
      {
         GRAPH data:bobFoaf {
             ?x foaf:mbox <mailto:bob@work.example>.
             ?x foaf:nick ?nick }
      }'
);
