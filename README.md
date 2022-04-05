# Simple PHP ADP Client

## Getting started

```zsh
composer install <insert package once created>
```

## Basic usage

```php
<?php

$config = [
    'client_id'     => '********-****-****-****-************',
    'client_secret' => '********-****-****-****-************',
    'org_name'      => 'ADP Org Name',
    'ssl_cert_path' => '/etc/ssl/adp/company_auth.pem',
    'ssl_key_path'  => '/etc/ssl/adp/company_auth.key',
    'server_url'    => 'https://api.adp.com/'
];

$adp = new \ADP\Client($config);

$filter = "workers/workAssignments/assignmentStatus/statusCode/codeValue eq 'A'";
$params = [
    'query' => [
        '$filter' => $filter,
        '$top'    => 100, // amount of records to grab
        '$skip'   => 0 // how many records to skip.
    ]
];

$httpResults =  json_decode($adp->get('hr/v2/workers', $params));
$workers = ($httpResults) ? $httpResults->workers : [];
```
