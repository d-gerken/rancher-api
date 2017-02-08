<?php

namespace RancherApi\Client;

use GuzzleHttp\Exception\ClientException;

class Client
{
    /**
     * @var string
     */
    private $accessKey;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var \GuzzleHttp\Client
     */
    private $httpClient;

    /**
     * @param string $endpoint
     * @param string $accessKey
     * @param string $secretKey
     */
    public function __construct($endpoint, $accessKey, $secretKey)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->httpClient = new \GuzzleHttp\Client([
            'base_uri'=> $endpoint
        ]);
    }

    /**
     * @var string $path
     */
    public function get($path = '/')
    {
        return $this->request($path);
    }

    /**
     *
     */
    public function post()
    {

    }

    /**
     * @param string $path
     * @param string $method
     * @param array $options
     * @return string
     */
    protected function request($path, $method = 'GET', array $options = [])
    {
        /* Add auth-data to options array */
        $options['auth'] = [$this->accessKey, $this->secretKey];

        /* Trigger request and return response as string */
        try {
            $response = $this
                ->httpClient
                ->request($method, $path, $options);
        } catch (ClientException $exception) {
            switch ($exception->getCode()) {
                case 401:
                    throw new \Exception('Authentication data is invalid');
                    break;
                default:
                    throw new \Exception($exception->getMessage());
            }
        }
        return $response->getBody()->getContents();
    }
}
