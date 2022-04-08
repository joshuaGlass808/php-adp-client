# Simple PHP ADP Client

[![Latest Stable Version](http://poser.pugx.org/jlg/php-adp-client/v)](https://packagist.org/packages/jlg/php-adp-client) [![Total Downloads](http://poser.pugx.org/jlg/php-adp-client/downloads)](https://packagist.org/packages/jlg/php-adp-client) [![License](http://poser.pugx.org/jlg/php-adp-client/license)](https://packagist.org/packages/jlg/php-adp-client)

## Getting started

```zsh
composer require jlg/php-adp-client
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

$adp = new \Jlg\ADP\Client($config);

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

### Methods
  - `$adp->getWorkersMeta();`: sends a GET request to retrieve workers api meta data.
  - `$adp->getWorker($aoid, $select);`: gets a single worker based on workers AOID, and optional select array can be passed as a secondary argument. `$aoid` = string, `$select` = array - default `[]`
  - `$adp->getWorkers($filters, $skip, $top, $count, $select);`: gets all workers in your ADP Org. `$filters` = array - default `[]`, `$skip` = int - default `0`, `$top` = int - default `100`, `$count` = bool - default `false`, `$select` = array - default `[]`
  - `$adp->getWorkAssignmentMeta();`: sends a GET request to retrieve Work-Assignment api meta data.
  - `$adp->modifyWorkAssignment($params);`: sends a POST request to modify a workers work assignment. `$params` = array - default `[]`
  - `$adp->get($url, $requestPayload);`: sends a GET request to which ever ADP API endpoint you would like to use. `$url` = string, `$requestPayload` = array - default `[]`
  - `$adp->post($url, $requestPayload);`: sends a POST request to which ever ADP API endpoint you would like to use. `$url` = string, `$requestPayload` = array - default `[]`
  - `$adp->apiCall($requestType, $url, $requestPayload);`: sends an HTTP request to which ADP API endpoint specified in the `$url` parameter. `$requestType` = string, `$url` = string, `$requestPayload` = array - default `[]`

### Additional Information
  - Please refer to [ADP API Documentation Explorer](https://developers.adp.com/articles/api/hcm-offrg-wfn/apiexplorer "ADP API Explorer") for additional details on request parameters and what to expect from the response.
  - This is using [Guzzle](https://github.com/guzzle/guzzle "Guzzle") for the Http abstraction. You will need to call `->getBody()->getContents()` on the response from one of the methods listed above if you would like to get the contents of the ADP Api response.
  - If you are missing something in your config or any other type of validation, you will be met with an exception like this: `PHP Fatal error:  Uncaught Jlg\ADP\Exceptions\ADPClientValidationException: [0]: Missing keys from config: server_url`


