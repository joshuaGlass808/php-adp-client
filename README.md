# Simple PHP ADP Client
[![Latest Stable Version](http://poser.pugx.org/jlg/php-adp-client/version)](https://packagist.org/packages/jlg/php-adp-client) [![Total Downloads](http://poser.pugx.org/jlg/php-adp-client/downloads)](https://packagist.org/packages/jlg/php-adp-client) [![License](http://poser.pugx.org/jlg/php-adp-client/license)](https://packagist.org/packages/jlg/php-adp-client)

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

$httpResults =  $adp::getContents($adp->get('hr/v2/workers', $params));
$workers = ($httpResults) ? $httpResults->workers : [];
```

### Methods
  - #### `getWorkersMeta(): HttpResponse`
     + sends a GET request to retrieve workers api meta data.
     
     ```php 
     $adp->getWorkersMeta();
     ```
  - #### `getWorker(string $aoid, array $select = []): HttpResponse`
     + gets a single worker based on workers AOID
     + an optional select array can be passed as a secondary argument.
     
     ```php
     $adp->getWorker($aoid, $select);
     ```
  - #### `getWorkers(array $filters = [], int $skip = 0, int $top = 100, bool $count = false, array $select = []): HttpResponse`
     + gets all workers, but only returns `$top` records. 
     + You can use the `$skip` as a way of moving through all your users.
     
     ```php
     $adp->getWorkers($filters, $skip, $top, $count, $select);
     ```
     + or you may need to get more than `$top`
     
     ```php     
     $workers = [];
     $filters = ["workers/workAssignments/assignmentStatus/statusCode/codeValue eq 'A'"]; // you probably want to use a filter :)
     $top = 100;
     $skip = 0; 
     
     while (($results = $adp::getContents($adp->getWorkers($filters, $skip, $top)) !== null) {
         $workers = array_merge($workers, $results->workers);
         $skip += $top;
     }
     
     return $workers;
     ```
  - #### `getWorkAssignmentMeta(): HttpResponse`
    + sends a GET request to retrieve Work-Assignment api meta data.
     
     ```php
     $adp->getWorkAssignmentMeta();
     ```
  - #### `modifyWorkAssignment(array $params = []): HttpResponse`
    + sends a POST request to modify a workers work assignment.
     
     ```php
     $adp->modifyWorkAssignment($params);
     ```
  - #### `get(string $url, array $requestPayload = []): HttpResponse`
    + sends a GET request to which ever ADP API endpoint you would like to use.
     
     ```php
     $adp->get($url, $requestPayload);
     ```
  - #### `post(string $url, array $requestPayload = []): HttpResponse`
    + sends a POST request to which ever ADP API endpoint you would like to use.
     
     ```php
     $adp->post($url, $requestPayload);
     ```
  - #### `apiCall(string $requestType, string $url, array $requestPayload = []): HttpResponse`
    + sends an HTTP request to which ADP API endpoint specified in the `$url` parameter.
    + `$requestType` needs to be either `'get'` or `'put'`
     
     ```php
     $adp->apiCall('get', 'hr/v2/workers', []);
     ```
  - #### `static getContents(HttpResponse $response): HttpResponse`
    + gets the contents from a guzzle Http Response.
    ```php
    $res = $adp::getContents($adp->getWorkers());
    ```

### Additional Information
  - Please refer to [ADP API Documentation Explorer](https://developers.adp.com/articles/api/hcm-offrg-wfn/apiexplorer "ADP API Explorer") for additional details on request parameters and what to expect from the response.
  - This is using [Guzzle](https://github.com/guzzle/guzzle "Guzzle") for the Http abstraction. You will need to call `->getBody()->getContents()` on the response from one of the methods listed above if you would like to get the contents of the ADP Api response.
  - If you are missing something in your config or any other type of validation, you will be met with an exception like this: `PHP Fatal error:  Uncaught Jlg\ADP\Exceptions\ADPClientValidationException: [0]: Missing keys from config: server_url`


