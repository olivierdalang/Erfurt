return array(
    'name'              => 'ask-02.rq',
    'group'             => 'RAP Ask Test Cases',
    'query'             => 'PREFIX  foaf: <http://xmlns.com/foaf/0.1/>
    SELECT  ?mbox
    WHERE
        { ?x foaf:name "Johnny Lee Outlaw" .
          ?x foaf:mbox ?mbox .
        }'
);

