<?php

use Criteo\Marketing\Configuration;
use Criteo\Marketing\ObjectSerializer;
use Criteo\Marketing\TokenAutoRefreshClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;

class TokenAutoRefreshClientTest extends TestCase
{
    private $server = 'api.example.com';
    private $userAgent = 'OpenAPI-Generator/1.0.0/PHP';
    private $clientId = 'client_id_example';
    private $clientSecret = 'client_secret_example';
    private $grantType = 'client_credentials';
    private $host;
    private $freshToken = "FlRtEbSkHaTyOjKiEwNy";

    protected function setUp()
    {
        parent::setUp();
        $this->host = 'https://' . $this->server . '/marketing';
        $configuration = new Configuration();
        $configuration->setHost($this->host);
        $configuration->setUserAgent($this->userAgent);
        Configuration::setDefaultConfiguration($configuration);
    }

    public function testDoesNotInterceptRequestWhenNoAuthorizationHeader()
    {
        $token = "ValidNt6YdhrsgtwTasFWqO2gBr840";

        $mockClient = Mockery::mock(TokenAutoRefreshClient::class);
        $mockClient->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function ($req) {
                $this->assertEquals('POST', $req->getMethod());
                $this->assertStringContainsString('oauth2/token', $req->getUri()->getPath());
                $this->assertStringContainsString($this->clientSecret, $req->getBody()->getContents());
                return true;
            }),
                Mockery::on(function ($opts) {
                    // Ignore options arguments for assertion
                    return true;
                }))
            ->andReturns($this->oAuthValidResponse($token));


        // Call Authentication endpoint which does not require Authorization header
        $response = (new Criteo\Marketing\Api\AuthenticationApi($mockClient))
            ->oAuth2TokenPost($this->clientId, $this->clientSecret, $this->grantType);


        $this->assertInstanceOf("\Criteo\Marketing\Model\InlineResponse200", $response);
        $this->assertEquals($token, $response->getAccessToken());
    }

    public function testAlwaysSendValidBearerToken()
    {
        $expiredToken = "EjXhPbIgRcEiDoTiOjKsEuN";
        $responseFromOAuthEndpoint = function ($req) {
            $this->assertStringContainsString("oauth2/token", $req->getUri()->getPath());
            return $this->oAuthValidResponse($this->freshToken);
        };
        $targetPath = 'v1/valid/endpoint';
        $client = $this->setupMockClient($targetPath, $this->freshToken, $responseFromOAuthEndpoint);
        $request = $this->aRequest($expiredToken, $targetPath);


        $response = $client->send($request);


        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDoesNotRefreshValidToken()
    {
        $targetPath = 'v1/valid/endpoint';
        $responseFromTargetEndpoint = function ($req) use ($targetPath) {
            $this->assertStringContainsString($targetPath, $req->getUri()->getPath());
            $this->assertStringEndsWith($this->freshToken, $req->getHeader('Authorization')[0]);
            return $this->anyResponse();
        };
        $mockDelegateClient = Mockery::mock(\GuzzleHttp\Client::class);
        $mockDelegateClient->shouldReceive('send')
            ->once()
            ->withAnyArgs()
            ->andReturnUsing($responseFromTargetEndpoint);

        $client = new TokenAutoRefreshClient($this->clientId, $this->clientSecret, $mockDelegateClient);
        // set a valid token
        $client->setToken(new TokenAutoRefreshClient\Token($this->freshToken, 299));
        $request = $this->aRequest($this->freshToken, $targetPath);


        $response = $client->send($request);


        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRefreshTokenAboutToExpire()
    {
        $aboutToExpireToken = "AboutToEjXhPbIgRcEiDoTiOjKsEuN";
        $expiresInSeconds = 10;
        $responseFromOAuthEndpoint = function ($req) {
            $this->assertStringContainsString("oauth2/token", $req->getUri()->getPath());
            return $this->oAuthValidResponse($this->freshToken);
        };
        $targetPath = 'v1/valid/endpoint';
        $client = $this->setupMockClient($targetPath, $this->freshToken, $responseFromOAuthEndpoint);
        $client->setToken(new TokenAutoRefreshClient\Token($aboutToExpireToken, $expiresInSeconds));
        $request = $this->aRequest($aboutToExpireToken, $targetPath);


        $response = $client->send($request);


        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testThrowsExceptionIfCannotRefreshToken()
    {
        $expiredToken = "AboutToEjXhPbIgRcEiDoTiOjKsEuN";
        $responseFromOAuthEndpoint = function ()  {
            return $this->oAuthInValidResponse();
        };
        $targetPath = 'v1/valid/endpoint';
        $client = $this->setupMockClient($targetPath, $this->freshToken, $responseFromOAuthEndpoint);
        $request = $this->aRequest($expiredToken, $targetPath);


        $this->expectException(Exception::class);
        $client->send($request);
    }

    private function setupMockClient($targetPath, $freshToken, Closure $responseFromOAuthEndpoint): TokenAutoRefreshClient
    {
        $responseFromTargetEndpoint = function ($req) use ($targetPath, $freshToken) {
            $this->assertStringContainsString($targetPath, $req->getUri()->getPath());
            $this->assertStringEndsWith($freshToken, $req->getHeader('Authorization')[0]);
            return $this->anyResponse();
        };
        $mockDelegateClient = Mockery::mock(\GuzzleHttp\Client::class);
        $mockDelegateClient->shouldReceive('send')
            ->twice()
            ->withAnyArgs()
            ->andReturnUsing($responseFromOAuthEndpoint, $responseFromTargetEndpoint);

        return new TokenAutoRefreshClient($this->clientId, $this->clientSecret, $mockDelegateClient);
    }

    private function oAuthValidResponse($validToken = null): Response
    {
        $responseHeaders = ['content-type' => 'application/json;charset=UTF-8', 'expires' => '-1'];
        $validToken = $validToken ?? "eNwSiNt6YdhrswTuitjgtwTasFbWqO2gBr840";
        $responseBody = \GuzzleHttp\json_encode(ObjectSerializer::sanitizeForSerialization(
            array(
                'access_token' => $validToken,
                'token_type' => 'bearer',
                'expires_in' => '299'
            )));
        return $this->anyResponse(200, $responseHeaders, $responseBody);
    }

    private function oAuthInValidResponse(): Response
    {
        $responseBody = \GuzzleHttp\json_encode(ObjectSerializer::sanitizeForSerialization(
            array(
                'error' => 'invalid_grant',
                'error_description' => 'The client_id or client_secret is incorrect.'
            )));
        return $this->anyResponse(400, null, $responseBody);
    }

    private function anyResponse($httpCode = 200, $headers = null, $body = null): Response
    {
        $responseHeaders = $headers ?? ['content-type' => 'application/json;charset=UTF-8'];
        $responseBody = $body ?? \GuzzleHttp\json_encode(ObjectSerializer::sanitizeForSerialization(
                array(
                    'AdvertiserId' => 42,
                    'AdvertiserName' => 'Boby'
                )));
        return new Response($httpCode, $responseHeaders, $responseBody);
    }

    private function aRequest(string $token, $endpoint): Request
    {
        $request = new Request(
            'POST',
            new Uri($this->host . '/' . $endpoint . ''),
            array(
                'User-Agent' => $this->userAgent,
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Host' => $this->server,
                'Authorization' => 'Bearer ' . $token
            ),
            "whatever"
        );
        return $request;
    }
}

?>