<?php 

namespace ADP;

use GuzzleHttp\{
    Client as Http,
    Psr7\Response as HttpResponse
};

class Client
{
    protected $baseConfig = [];
    protected $accessToken = null;
    protected $tokenType = null;
    protected $expiresAt = null;
    protected $scope = null;

    /**
     * Construct ADP connection client
     * 
     * @param array $config - needs this shape:
     * [
     *    'client_id'        => '********-****-****-****-************',
     *    'client_secret'    => '********-****-****-****-************',
     *    'org_name'         => 'ADP Org Name',
     *    'ssl_cert_path'    => '/etc/ssl/adp/company_auth.pem',
     *    'ssl_key_path'     => '/etc/ssl/adp/company_auth.key',
     *    'server_url' => 'https://api.adp.com/'
     *  ]
     */
    public function __construct(array $config)
    {
        $this->baseConfig = $config;
        $this->setConnection();
    }

    /**
     * Make requests to ADP.
     * 
     * @param string $url - example => "hr/v2/workers"
     * @param array $parameters - payload for the request
     * @return HttpResponse
     */
    public function apiCall(string $callType, string $url, array $parameters): HttpResponse
    {
        $this->checkAndResetConnection();

        $http = new Http([
            'headers' => [
                'Authorization' => "{$this->tokenType} {$this->accessToken}",
            ],
            'base_uri' => $this->baseConfig['token_server_url'],
            'cert'     => $this->baseConfig['ssl_cert_path'],
            'ssl_key'  => $this->baseConfig['ssl_key_path'],
        ]);

        return $http->{$callType}($url, $parameters);
    }

    /**
     * Convienence wrapper for GET requests around apiCall()
     * 
     * @param string $url - example => "hr/v2/workers"
     * @param array $parameters - payload for the request
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
     * @param array $parameters - payload for the request
     * @return HttpResponse
     */
    public function post(string $url, array $parameters = []): HttpResponse
    {
        return $this->apiCall('post', $url, $parameters);
    }

    /**
     * Make connection to ADP to get the access_token for future requests.
     *
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
            'headers' => [
                'User-Agent' => 'adp-connection-php/1.0.1'
            ],
        ]);

        $response = $client->post('auth/oauth/v2/token', $params);
        $tokenData = json_decode($response->getBody()->getContents());

        $this->accessToken = $tokenData->access_token;
        $this->tokenType = $tokenData->token_type; // Bearer
        $this->expiresAt = date('Y-m-d H:i:s', strtotime("+{$tokenData->expires_in} seconds")); // 3600s
        $this->scope = $tokenData->scope; // api
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
