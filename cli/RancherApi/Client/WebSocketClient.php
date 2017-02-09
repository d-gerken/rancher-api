<?php


namespace RancherApi\Client;


class WebSocketClient
{
    private $host;
    private $port;
    private $path;
    private $origin;
    private $token;

    private $socket;
    private $connected = false;

    public function __construct($host, $port, $path, $origin = false, $token = false)
    {
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->origin = $origin;
        $this->token = $token;
    }

    public function connect()
    {
        $key = base64_encode($this->getRandomString());
        $header = "GET " . $this->path . " HTTP/1.1\r\n";
        $header .= "Upgrade: websocket\r\n";
        $header .= "Connection: Upgrade\r\n";
        $header .= "Sec-WebSocket-Version: 13\r\n";
        $header .= "Sec-WebSocket-Key: ".$key."\r\n";
        $header .= "Host: ".$this->host."\r\n";
        if($this->token)
        {
            $header .= "Authorization: Bearer " . $this->token . "\r\n";
        }
        $header .= "\r\n";

        $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 2);
        socket_set_timeout($this->socket, 5);
        @fwrite($this->socket, $header);

        $response = @fread($this->socket, 1000);

        preg_match('#Sec-WebSocket-Accept:\s(.*)$#mU', $response, $matches);
        if ($matches) {
            $keyAccept = trim($matches[1]);
            $expectedResponse = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            $this->connected = ($keyAccept === $expectedResponse) ? true : false;
        }

        return $this->connected;
    }

    private function getRandomString($length = 10)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"§$%&/()=[]{}';
        $chars = array();
        for($i = 0; $i < $length; $i++)
        {
            $chars[] = $characters[mt_rand(0, strlen($characters)-1)];
        }
        array_push($chars, ' ', ' ', ' ', ' ', ' ', ' ');
        array_push($chars, rand(0,9), rand(0,9), rand(0,9));
        shuffle($chars);
        $randomString = trim(implode('', $chars));
        $randomString = substr($randomString, 0, $length);
        return $randomString;
    }

    function __destruct()
    {
        @fclose($this->socket);
    }


}