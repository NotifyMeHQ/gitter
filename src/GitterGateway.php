<?php

/*
 * This file is part of NotifyMe.
 *
 * (c) Alt Three LTD <support@alt-three.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NotifyMeHQ\Gitter;

use GuzzleHttp\Client;
use NotifyMeHQ\Contracts\GatewayInterface;
use NotifyMeHQ\NotifyMe\HttpGatewayTrait;
use NotifyMeHQ\NotifyMe\Response;

class GitterGateway implements GatewayInterface
{
    use HttpGatewayTrait;

    /**
     * Gateway api endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://api.gitter.im';

    /**
     * Gitter api version.
     *
     * @var string
     */
    protected $version = 'v1';

    /**
     * Create a new gitter gateway instance.
     *
     * @param \GuzzleHttp\Client $client
     * @param string[]           $config
     *
     * @return void
     */
    public function __construct(Client $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * Send a notification.
     *
     * @param string $to
     * @param string $message
     *
     * @return \NotifyMeHQ\Contracts\ResponseInterface
     */
    public function notify($to, $message)
    {
        $params = ['text' => $message]

        return $this->commit($this->buildUrlFromString("rooms/{$to}/chatMessages"), $params);
    }

    /**
     * Commit a HTTP request.
     *
     * @param string[] $params
     *
     * @return mixed
     */
    protected function commit($url, array $params)
    {
        $success = false;

        $rawResponse = $this->client->post($url, [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'headers'         => [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer '.$this->config['token'],
            ],
            'json' => $params,
        ]);

        $response = [];

        switch ($rawResponse->getStatusCode()) {
            case 201:
                $success = true;
                break;
            case 400:
                $response['error'] = 'Incorrect request values.';
                break;
            case 404:
                $response['error'] = 'Invalid room.';
                break;
            default:
                $response['error'] = $this->responseError($rawResponse);
        }

        return $this->mapResponse($success, $response);
    }

    /**
     * Map HTTP response to response object.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return \NotifyMeHQ\Contracts\ResponseInterface
     */
    protected function mapResponse($success, $response)
    {
        return (new Response())->setRaw($response)->map([
            'success' => $success,
            'message' => $success ? 'Message sent' : $response['error'],
        ]);
    }

    /**
     * Get the default json response.
     *
     * @param \GuzzleHttp\Message\ResponseInterface $rawResponse
     *
     * @return array
     */
    protected function jsonError($rawResponse)
    {
        $msg = 'API Response not valid.';
        $msg .= " (Raw response API {$rawResponse->getBody()})";

        return [
            'error' => $msg,
        ];
    }

    /**
     * Get the request url.
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->endpoint.'/'.$this->version;
    }
}
