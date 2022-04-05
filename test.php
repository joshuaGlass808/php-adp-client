<?php 
require_once 'vendor/autoload.php';

use Jlg\ADP\Client;

$c = [   
            'client_id'     => '673b4719-6402-4388-a36f-375537adca55',
            'client_secret' => 'f03a93a1-0dd5-4c07-bddd-4011e75e689a',
            'org_name'      => 'Rivermaid Trading Compa',
            'ssl_cert_path' => '/etc/ssl/adp/rivermaid_trading_compa_auth.pem',
            'ssl_key_path'  => '/etc/ssl/adp/rivermaid_trading_compa_auth.key',
            //'server_url'    => 'https://api.adp.com/'
        ];

        $adp = new Client($c);

        $filter = "workers/workAssignments/assignmentStatus/statusCode/codeValue eq 'A'";
        $params = [
            'query' => [
                '$filter' => $filter,
                '$top'    => 100, // amount of records to grab
                '$skip'   => 0 // how many records to skip.
            ]
        ];
       var_dump(json_decode($adp->get('hr/v2/workers', $params)->getBody()->getContents()));