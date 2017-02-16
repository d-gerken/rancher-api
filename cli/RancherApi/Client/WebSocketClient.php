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

    public function __construct($host, $port, $path, $origin = false, $token)
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
        $header = "GET " . $this->path . "?token=" . $this->token . " HTTP/1.1\r\n";
        $header .= "Upgrade: websocket\r\n";
        $header .= "Connection: Upgrade\r\n";
        $header .= "Sec-WebSocket-Version: 13\r\n";
        $header .= "Sec-WebSocket-Key: ".$key."\r\n";
        $header .= "Host: ".$this->host.":".$this->port."\r\n";
        $header .= "\r\n";

        $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 2);
        socket_set_timeout($this->socket, 1);
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

    public function read()
    {
        /**
         * @RFC 6455
         */
        $data = array();
        $firstByte = @fread($this->socket, 1); // FIN 1Bit, RSV1 1Bit, RSV2 1Bit, RSV3 1Bit, OPCODE(4Bit)
        $fin = $firstByte & chr(0x80); // Indicates that this is the final fragment in a message.  The first fragment MAY also be the final fragment.
        $opcode = $firstByte & chr(0xF);
        $secondByte = @fread($this->socket, 1); // MASK 1Bit + PAYLOAD LEN (7Bit)
        $mask = $secondByte & chr(0x80);
        if($mask == chr(0x01) || $opcode === chr(0x08)) // RFC "The client must close a connection if it detects a masked frame." or termination byte sent
        {
            @fclose($this->socket);
            return false;
        }

        switch($opcode)
        {
            case chr(0x00): //This frame continues the payload.
                $data['type'] = "TODO";
                break;

            case chr(0x01): //This frame includes UTF-8 text data.
                $data['type'] = 'text';
                break;

            case chr(0x02): //This frame includes binary data.
                $data['type'] = 'binary';
                break;

            case chr(0x09): //This frame is a ping.
                $data['type'] = 'ping';
                break;

            case chr(0x0A): //This frame is a pong.
                $data['type'] = 'pong';
                break;

            default:
                return false;
        }

        $payloadLength = chr(0x7F) & $secondByte;
        // If 126, the following 2 bytes are the payload length. If 127 thw following8 byte are the payload length.
        $offset = (($payloadLength === chr(0x7E)) ? 2 : ($payloadLength === chr(0x7F) ? 8 : NULL));
        $payloadLength = ($offset) ? @fread($this->socket, $offset) : $payloadLength;

        $bytesToRead = hexdec(bin2hex($payloadLength));
        $data['payload'] = @fread($this->socket, $bytesToRead);
        return $data;
    }

    /**
     * Close socket
     * @return bool
     */
    public function close()
    {
        @fclose($this->socket);
        return true;
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


    /**
     * Close socket if not already done
     */
    function __destruct()
    {
        @fclose($this->socket);
    }


}