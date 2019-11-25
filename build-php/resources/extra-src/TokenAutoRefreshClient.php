<?php


namespace Criteo\Marketing;

use Criteo\Marketing\Api\AuthenticationApi;
use Criteo\Marketing\TokenAutoRefreshClient\Token;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


define("AUTHORIZATION", 'Authorization');
define("GRANT_TYPE", 'client_credentials');

class TokenAutoRefreshClient implements \GuzzleHttp\ClientInterface
{
    /**
     * Delegate client that makes the call to the server
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * Token objects which contains the value and the expiration time
     * @var Token
     */
    private $token;
    private $authenticationApi;
    private $clientId;
    private $clientSecret;

    /**
     * TokenAutoRefreshClient constructor.
     * @param string $clientId used to get a token against Authentication API
     * @param string $clientSecret used to get a token against Authentication API
     * @param \GuzzleHttp\ClientInterface|null $client
     */
    public function __construct($clientId, $clientSecret, $client = null)
    {
        $this->client = $client ?? new \GuzzleHttp\Client();
        $this->token = null;
        $this->authenticationApi = new AuthenticationApi($this->client);
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    /**
     * Send an HTTP request.
     *
     * @param RequestInterface $request Request to send
     * @param array $options Request options to apply to the given
     *                                  request and to the transfer.
     *
     * @return ResponseInterface
     * @throws GuzzleException
     * @throws \Exception if cannot refresh token
     */
    public function send(RequestInterface $request, array $options = [])
    {
        $requiresAuthentication = $request->getHeader(AUTHORIZATION) != null;
        if (!$requiresAuthentication) {
            return $this->client->send($request, $options);
        } else {
            $requestWithUpdatedAuthorizationHeader = $this->refreshToken($request);
            return $this->client->send($requestWithUpdatedAuthorizationHeader, $options);
        }
    }

    /**
     * Asynchronously send an HTTP request.
     *
     * @param RequestInterface $request Request to send
     * @param array $options Request options to apply to the given
     *                                  request and to the transfer.
     *
     * @return PromiseInterface
     * @throws \Exception if cannot refresh token
     */
    public function sendAsync(RequestInterface $request, array $options = [])
    {
        $requireAuthentication = $request->getHeader(AUTHORIZATION) != null;
        if (!$requireAuthentication) {
            return $this->client->sendAsync($request, $options);
        } else {
            $requestWithUpdatedAuthorizationHeader = $this->refreshToken($request);
            return $this->client->sendAsync($requestWithUpdatedAuthorizationHeader, $options);
        }
    }

    /**
     * Create and send an HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well.
     *
     * @param string $method HTTP method.
     * @param string|UriInterface $uri URI object or string.
     * @param array $options Request options to apply.
     *
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function request($method, $uri, array $options = [])
    {
        return $this->client->request($method, $uri, $options);
    }

    /**
     * Create and send an asynchronous HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well. Use an array to provide a URL
     * template and additional variables to use in the URL template expansion.
     *
     * @param string $method HTTP method
     * @param string|UriInterface $uri URI object or string.
     * @param array $options Request options to apply.
     *
     * @return PromiseInterface
     */
    public function requestAsync($method, $uri, array $options = [])
    {
        return $this->client->requestAsync($method, $uri, $options);
    }

    /**
     * Get a client configuration option.
     *
     * These options include default request options of the client, a "handler"
     * (if utilized by the concrete client), and a "base_uri" if utilized by
     * the concrete client.
     *
     * @param string|null $option The config option to retrieve.
     *
     * @return mixed
     */
    public function getConfig($option = null)
    {
        return $this->client->getConfig($option);
    }

    /**
     * @return Token currently used to authenticate against the API
     */
    public function getToken(): Token
    {
        return $this->token;
    }

    /**
     * @param Token $token
     */
    public function setToken(Token $token): void
    {
        $this->token = $token;
    }

    private function refreshToken(RequestInterface $request): RequestInterface
    {
        if ($this->token == null || !$this->token->isValidEnough()) {
            try {
                $response = $this->authenticationApi->oAuth2TokenPost($this->clientId, $this->clientSecret, GRANT_TYPE);
            } catch (ApiException $e) {
                throw new \Exception('Cannot refresh token automatically. Response from server: ' . $e->getCode() . ' - ' . $e->getResponseBody(), 0, $e);
            }
            $this->token = new Token($response->getAccessToken(), $response->getExpiresIn());
        }
        return $request
            ->withoutHeader(AUTHORIZATION)
            ->withAddedHeader(AUTHORIZATION, 'Bearer ' . $this->token->getValue());
    }
}

namespace Criteo\Marketing\TokenAutoRefreshClient;

use DateTime;

class Token
{
    /**
     * @var DateTime
     */
    private $expiresOn;
    private $value;

    public function __construct($value, $expiresIn)
    {
        $this->expiresOn = $this->computeExpiresOn($expiresIn);
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function computeExpiresOn($expiresIn): DateTime
    {
        $now = new DateTime();
        return $now->modify('+' . $expiresIn . ' second');
    }

    public function isValidEnough(): bool
    {
        $now = new DateTime();
        return $this->expiresOn > $now->modify('+15 second');
    }
}