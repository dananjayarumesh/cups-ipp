<?php

namespace Smalot\Cups\Transport;

use GuzzleHttp\Psr7\Uri;
use Http\Client\Common\Plugin\AddHostPlugin;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin\DecoderPlugin;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpClient;
use Http\Client\Socket\Client as SocketHttpClient;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Psr\Http\Message\RequestInterface;
use Smalot\Cups\CupsException;

/**
 * Class Client
 *
 * @package Smalot\Cups\Transport
 */
class Client implements HttpClient
{

    const SOCKET_URL = 'unix:///var/run/cups/cups.sock';

    const AUTHTYPE_BASIC = 'basic';

    const AUTHTYPE_DIGEST = 'digest';

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $authType;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * Client constructor.
     *
     * @param array $socketClientOptions
     */
    public function __construct($socketClientOptions = [])
    {
        $messageFactory = new GuzzleMessageFactory();
        $socketClient = new SocketHttpClient($messageFactory, $socketClientOptions);
        $host = preg_match(
          '/unix:\/\//',
          $socketClientOptions['remote_socket']
        ) ? 'http://localhost' : $socketClientOptions['remote_socket'];
        $this->httpClient = new PluginClient(
          $socketClient, [
            new ErrorPlugin(),
            new ContentLengthPlugin(),
            new DecoderPlugin(),
            new AddHostPlugin(new Uri($host)),
          ]
        );

        $this->authType = self::AUTHTYPE_BASIC;
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return $this
     */
    public function setAuthentication($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * @param string $authType
     *
     * @return $this
     */
    public function setAuthType($authType)
    {
        $this->authType = $authType;

        return $this;
    }

    /**
     * (@inheritdoc}
     */
    public function sendRequest(RequestInterface $request)
    {
        if ($this->username || $this->password) {
            switch ($this->authType) {
                case self::AUTHTYPE_BASIC:
                    $pass = base64_encode($this->username.':'.$this->password);
                    $authentication = 'Basic '.$pass;
                    break;

                case self::AUTHTYPE_DIGEST:
                    throw new CupsException('Auth type not supported');

                default:
                    throw new CupsException('Unknown auth type');
            }

            $request = $request->withHeader('Authorization', $authentication);
        }

        return $this->httpClient->sendRequest($request);
    }

    /**
     * @return self
     */
    public static function create()
    {
        return new static(
          [
            'remote_socket' => self::SOCKET_URL,
          ]
        );
    }
}
