return array(
    'name'              => 'ask-02.rq',
    'group'             => 'RAP Ask Test Cases',
    'query'             => 'SELECT  *
    WHERE
        { ?x ?p1 ?v1 .
          ?y ?p2 ?v2 . 
          FILTER str(?x) = str(?y) .
        }'
);
