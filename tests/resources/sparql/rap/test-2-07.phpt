return array(
    'name'              => 'ask-02.rq',
    'group'             => 'RAP Ask Test Cases',
    'query'             => 'SELECT  *
    WHERE
        { ?x ?y ?z1 .
          ?x ?y ?z2 . 
          FILTER ( ?z1 = ?z2 ) .
        }'
);
