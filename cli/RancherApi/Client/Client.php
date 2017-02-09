<?php

namespace RancherApi\Client;

use GuzzleHttp\Exception\ClientException;
use RancherApi\Exception\BadContentException;

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
     * @return array
     */
    public function get($path = '/')
    {
        return $this->request($path);
    }

    /**
     * @param string $path
     * @param array $data
     * @return array
     */
    public function post($path = '/', array $data = [])
    {
        /* Set data to json-array */
        $options['json'] = $data;

        return $this->request($path, 'POST', $options);
    }

    /**
     * @param string $url
     * @param string $token
     */
    public function runWebsocket($url, $token)
    {
        $url = str_replace("ws://", "", $url);
        list($socket, $path) = preg_split("@(?=/)@", $url, 2, PREG_SPLIT_DELIM_CAPTURE);
        list($host, $port) = explode(":", $socket);
        $webSocketClient = new WebSocketClient($host, $port, $path, false, $token);
        $webSocketClient->connect();
        while($data = $webSocketClient->read())
        {
            if($data['type'] == "text")
            {
                echo base64_decode($data['payload']);
            }
        }
        $webSocketClient->close();
    }

    /**
     * @param $path
     * @param string $method
     * @param array $options
     * @return mixed
     * @throws BadContentException
     * @throws \Exception
     */
    protected function request($path, $method = 'GET', array $options = [])
    {
        /* Add auth-data to options array */
        $options['auth'] = [$this->accessKey, $this->secretKey];

        /* Filter method */
        if (substr($path, 0, 1) != '/') {
            $path = '/' . $path;
        }
        $path = 'v2-beta' . $path;

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

        /* Decode json response */
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        if (!$data) {
            throw new BadContentException('Response could not be decoded');
        }
        return $data;
    }
}
