<?php 

namespace Jlg\ADP;

use Jlg\ADP\Exceptions\ADPClientValidationException;

use GuzzleHttp\{
    Client as Http,
    Psr7\Response as HttpResponse
};

class Client
{
    /**
     * base config
     *
     * @var array<mixed>
     */
    protected array $baseConfig = [];

    /**
     * Access Token
     *
     * @var string|null
     */
    protected ?string $accessToken = null;

    /**
     * Token type, usually Bearer
     *
     * @var string|null
     */
    protected ?string $tokenType = null;

    /**
     * Expires at date string
     *
     * @var string|null
     */
    protected ?string $expiresAt = null;

    /**
     * Scope - usually set to API
     *
     * @var string|null
     */
    protected ?string $scope = null;

    /**
     * Construct ADP connection client
     * 
     * @param array<mixed> $config - needs this shape:
     * [
     *   'client_id'     => '********-****-****-****-************',
     *   'client_secret' => '********-****-****-****-************',
     *   'org_name'      => 'ADP Org Name',
     *   'ssl_cert_path' => '/etc/ssl/adp/company_auth.pem',
     *   'ssl_key_path'  => '/etc/ssl/adp/company_auth.key',
     *   'server_url'    => 'https://api.adp.com/'
     *  ]
     */
    public function __construct(array $config)
    {
        $missing = [];
        $neededKeys = [
            'client_id',
            'client_secret',
            'org_name',
            'ssl_cert_path',
            'ssl_key_path',
            'server_url'
        ];

        foreach ($neededKeys as $key) {
            if (!isset($config[$key])) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            $msg = 'Missing keys from config: ' . implode(', ', $missing);
            throw new ADPClientValidationException($msg);
        }

        $this->baseConfig = $config;
        $this->setConnection();
    }

    /**
     * Make requests to ADP.
     * 
     * @param string $url - example => "hr/v2/workers"
     * @param array<array> $parameters - payload for the request
     * @return HttpResponse
     */
    public function apiCall(string $callType, string $url, array $parameters): HttpResponse
    {
        $this->checkAndResetConnection();
        $callType = strtolower($callType);

        if (!in_array($callType, ['get', 'post'])) {
            $msg = "Invalid|unsupported http request type {$callType}. Valid options are (get|post)";
            throw new ADPClientValidationException($msg);
        }

        $http = new Http([
            'headers' => [
                'Authorization' => "{$this->tokenType} {$this->accessToken}",
            ],
            'base_uri' => $this->baseConfig['server_url'],
            'cert'     => $this->baseConfig['ssl_cert_path'],
            'ssl_key'  => $this->baseConfig['ssl_key_path'],
        ]);

        return $http->{$callType}($url, $parameters);
    }

    /**
     * Convienence wrapper for GET requests around apiCall()
     * 
     * @param string $url - example => "hr/v2/workers"
     * @param array<array> $parameters - payload for the request
     * @return HttpResponse
     */
    public function get(string $url, array $parameters = []): HttpResponse
    {
        return $this->apiCall('get', $url, $parameters);
    }

    /**
     * Convienence wrapper for POST requests around apiCall()
     * 
     * @param string $url - example => "hr/v2/workers"
     * @param array<array> $parameters - payload for the request
     * @return HttpResponse
     */
    public function post(string $url, array $parameters = []): HttpResponse
    {
        return $this->apiCall('post', $url, $parameters);
    }

    /**
     * Get workers api meta data
     * 
     * https://developers.adp.com/articles/api/hcm-offrg-wfn/hcm-offrg-wfn-hr-workers-v2-workers/apiexplorer?operation=GET/hr/v2/workers/meta
     *
     * @return HttpResponse
     */
    public function getWorkersMeta(): HttpResponse
    {
        return $this->get('hr/v2/workers/meta');
    }

    /**
     * Get a single worker
     * 
     * https://developers.adp.com/articles/api/hcm-offrg-wfn/hcm-offrg-wfn-hr-workers-v2-workers/apiexplorer?operation=GET/hr/v2/workers/{aoid}
     *
     * @param string $aoid
     * @param array<string> $select
     * @return HttpResponse
     */
    public function getWorker(string $aoid, array $select = []): HttpResponse
    {
        $params = [
            'query' => [
                '$select' => implode(',', $select)
            ]
        ];

        return $this->get("hr/v2/workers/{$aoid}", $params);
    }

    /**
     * Simple wrapper around the Workers API
     * 
     * https://developers.adp.com/articles/api/hcm-offrg-wfn/hcm-offrg-wfn-hr-workers-v2-workers/apiexplorer?operation=GET/hr/v2/workers
     *
     * @param array<string> $filters
     * @param integer $skip
     * @param integer $top
     * @param boolean $count
     * @param array<string> $select
     * @return HttpResponse
     */
    public function getWorkers(
        array $filters = [],
        int $skip = 0,
        int $top = 100,
        bool $count = false,
        array $select = [],
    ): HttpResponse {
        $params = [
            'query' => [
                '$filter' => implode(' and ', $filters),
                '$top'    => $top,
                '$skip'   => $skip,
                '$select' => implode(',', $select),
                '$count'  => $count
            ]
        ];
       
        return $this->get('hr/v2/workers', $params);
    }

    /**
     * Get work assignment modification api meta data
     * 
     * https://developers.adp.com/articles/api/hcm-offrg-wfn/hcm-offrg-wfn-hr-workers-work-assignment-management-v2-workers-work-assignment-management/apiexplorer?operation=GET/events/hr/v1/worker.work-assignment.modify/meta
     *
     * @return HttpResponse
     */
    public function getWorkAssignmentMeta(): HttpResponse
    {
        return $this->get('events/​hr/​v1/​worker.work-assignment.modify/​meta');
    }
 
    /**
     * Modify work assignments
     * 
     * https://developers.adp.com/articles/api/hcm-offrg-wfn/hcm-offrg-wfn-hr-workers-work-assignment-management-v2-workers-work-assignment-management/apiexplorer?operation=POST/events/hr/v1/worker.work-assignment.modify
     *
     * @param array $params
     * @return HttpResponse
     */
    public function modifyWorkAssignment(array $params): HttpResponse
    {
        return $this->post('events/​hr/​v1/​worker.work-assignment.modify', $params);
    }

    /**
     * get contents from guzzle Http response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return object
     */
    public static function getContents(\Psr\Http\Message\ResponseInterface $response): object
    {
        return json_decode($response->getBody()->getContents());
    }

    /**
     * Make connection to ADP to get the access_token for future requests.
     *
     *
     * @property mixed object::$access_token
     * @return void
     */
    protected function setConnection(): void
    {
        $params = [
            'cert'        => $this->baseConfig['ssl_cert_path'],
            'ssl_key'     => $this->baseConfig['ssl_key_path'],
            'form_params' => [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->baseConfig['client_id'],
                'client_secret' => $this->baseConfig['client_secret']
            ]
        ];

        $client = new Http([
            'base_uri' => $this->baseConfig['server_url'],
            'headers'  => [
                'User-Agent' => 'adp-connection-php/1.0.1'
            ],
        ]);

        $tokenData = self::getContents($client->post('auth/oauth/v2/token', $params));
        $time = strtotime("+{$tokenData->expires_in} seconds");
        $this->accessToken = $tokenData->access_token;
        $this->tokenType = $tokenData->token_type;
        $this->expiresAt = date('Y-m-d H:i:s', $time);
        $this->scope = $tokenData->scope;
    }

    /**
     * Check that our token is not expired. If it is, reset connection
     *
     * @return void
     */
    protected function checkAndResetConnection(): void
    {
        if (
            $this->expiresAt === null
            || date('Y-m-d H:i:s') >= $this->expiresAt
        ) {
            $this->setConnection();
        }
    }
}
