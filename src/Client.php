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
     * temp path to cache the temp access token.
     *
     * @var string
     */
    protected string $tmpJsonFile = '/tmp/jlg-adp-token-cache.json';

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
        $this->checkAndResetConnection();
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
        $params = [];
        if (!empty($select)) {
            $params['query'] = [
                '$select' => implode(',', $select)
            ];
        }

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
        ?int $skip = null,
        ?int $top = null,
        bool $count = false,
        array $select = []
    ): HttpResponse {
        $params = [];
        $query = [];

        if (!empty($filters)) {
            $query['$filter'] = implode(' and ', $filters);
        }

        if (!empty($select)) {
            $query['$select'] = implode(',', $select);
        }

        if ($skip !== null) {
            $query['$skip'] = $skip;
        }

        if ($top !== null) {
            $query['$top'] = $top;
        }

        if ($count) {
            $query['$count'] = $count;
        }

        if (!empty($query)) {
            $params['query'] = $query;
        }
       
        return $this->get('hr/v2/workers', $params);
    }

    /**
     * Get event notification messages. Generally used in Long Polling situations
     *
     * @return HttpResponse
     */
    public function getEventNotificationMessages(): HttpResponse
    {
        return $this->get('core/v1/event-notification-messages');
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
     * @param array<array> $params
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
     * @return object|null
     */
    public static function getContents(\Psr\Http\Message\ResponseInterface $response): ?object
    {
        return json_decode($response->getBody()->getContents());
    }

    /**
     * Make connection to ADP to get the access_token for future requests.
     *
     *
     * @property object $access_token
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
        $date = date('Y-m-d H:i:s', $time);
        $this->accessToken = $tokenData->access_token;
        $this->tokenType = $tokenData->token_type;
        $this->expiresAt = $date;
        $this->scope = $tokenData->scope;

        $fileData = [
            'expires_at'   => $date,
            'token_type'   => $this->tokenType,
            'access_token' => $this->accessToken
        ];

        $fp = fopen($this->tmpJsonFile, 'w');
        fwrite($fp, json_encode($fileData));
        fclose($fp);
    }

    /**
     * Check that our token is not expired. If it is, reset connection
     *
     * @return void
     */
    protected function checkAndResetConnection(): void
    {
        if (
            $this->expiresAt !== null 
            && date('Y-m-d H:i:s') < $this->expiresAt
            && $this->accessToken !== null
        ) {
            return;
        }

        if (
            file_exists($this->tmpJsonFile)
            && filesize($this->tmpJsonFile) > 0
        ) {
            $fp = fopen($this->tmpJsonFile, 'r');
            $data = json_decode(fread($fp, filesize($this->tmpJsonFile)));
            fclose($fp);
    
            if (
                isset($data->expires_at) 
                && isset($data->access_token)
                && isset($data->token_type)
                && date('Y-m-d H:i:s') < $data->expires_at
            ) {
                $this->expiresAt = $data->expires_at;
                $this->accessToken = $data->access_token;
                $this->tokenType = $data->token_type;
                return;
            }

            $this->expiresAt = null;
            $this->accessToken = null;
            $this->tokenType = null;
            unlink($this->tmpJsonFile);
        }

        $this->setConnection();
    }
}
