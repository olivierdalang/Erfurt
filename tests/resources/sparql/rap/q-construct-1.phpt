return array(
    'name'              => 'ask-02.rq',
    'group'             => 'RAP Ask Test Cases',
    'query'             => 'PREFIX ns: <http://example.org/ns#>
    PREFIX :   <http://example.org/>

    CONSTRUCT
        { ?x ns:knows ?y  }
    WHERE
        { ?x ns:loves ?y }'
);

