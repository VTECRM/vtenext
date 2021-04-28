<?php

namespace Mrjoops\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Tool\QueryBuilderTrait;
use Mockery as m;

class JiraTest extends \PHPUnit_Framework_TestCase
{
    use QueryBuilderTrait;

    protected $provider;

    protected function setUp()
    {
        $this->provider = new \Mrjoops\OAuth2\Client\Provider\Jira([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('prompt', $query);
        $this->assertArrayHasKey('audience', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testScopes()
    {
        $scopeSeparator = ' ';
        $options = ['scope' => [uniqid(), uniqid()]];
        $query = ['scope' => implode($scopeSeparator, $options['scope'])];
        $url = $this->provider->getAuthorizationUrl($options);
        $encodedScope = $this->buildQueryString($query);
        $this->assertContains($encodedScope, $url);
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/oauth/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')
                 ->andReturn('{"access_token":"mock_access_token", "scope":"read:jira-work", "token_type":"bearer"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testOwnerData()
    {
        $id = uniqid();
        $email = uniqid();
        $name = uniqid();
        $nickname = uniqid();

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse
            ->shouldReceive('getBody')
            ->andReturn('access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token&otherKey={1234}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);
        
        $resourceResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $resourceResponse->shouldReceive('getBody')
            ->andReturn('[{"id":"mock_id","name":"mock_name","avatarUrl":"mock_avatarUrl","scopes":["mock_scopes"]}]');
        $resourceResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $resourceResponse->shouldReceive('getStatusCode')->andReturn(200);
        
        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')
            ->andReturn(<<<ETX
{
  "self": "http://your-domain.atlassian.net/rest/api/3/user?username=mia",
  "key": "mia",
  "accountId": "$id",
  "name": "$nickname",
  "emailAddress": "$email",
  "avatarUrls": {
    "48x48": "http://your-domain.atlassian.net/secure/useravatar?size=large&ownerId=mia",
    "24x24": "http://your-domain.atlassian.net/secure/useravatar?size=small&ownerId=mia",
    "16x16": "http://your-domain.atlassian.net/secure/useravatar?size=xsmall&ownerId=mia",
    "32x32": "http://your-domain.atlassian.net/secure/useravatar?size=medium&ownerId=mia"
  },
  "displayName": "$name",
  "active": true,
  "timeZone": "Australia/Sydney",
  "groups": {
    "size": 3,
    "items": []
  },
  "applicationRoles": {
    "size": 1,
    "items": []
  }
}
ETX
            );
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(3)
            ->andReturn($postResponse, $resourceResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($id, $user->getId());
        $this->assertEquals($id, $user->toArray()['accountId']);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($email, $user->toArray()['emailAddress']);
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($name, $user->toArray()['displayName']);
        $this->assertEquals($nickname, $user->getNickname());
        $this->assertEquals($nickname, $user->toArray()['name']);
    }

    public function testExceptionThrownWhenErrorObjectReceived()
    {
        $status = rand(400, 600);
        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"message": "Validation Failed","errors": [{"resource": "Issue","field": "title","code": "missing_field"}]}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);
        $this->provider->setHttpClient($client);
        
        try {
            $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\League\OAuth2\Client\Provider\Exception\IdentityProviderException::class, $e);
        }
    }

    public function testExceptionThrownWhenOAuthErrorReceived()
    {
        $status = 200;
        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"error": "error_collection","error_description": "The code passed is incorrect or expired.","error_uri": "https://docs.atlassian.com/jira/REST/schema/error-collection#"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);
        $this->provider->setHttpClient($client);
        
        try {
            $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\League\OAuth2\Client\Provider\Exception\IdentityProviderException::class, $e);
        }
    }

    public function testExceptionThrownWhenAskingForResourceOwner()
    {
        $status = 200;
        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('<html><body>some unexpected response.</body></html>');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'text/html']);
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);
        $this->provider->setHttpClient($client);
        
        $token = new \League\OAuth2\Client\Token\AccessToken(['access_token' => 'mock_access_token']);

        try {
            $this->provider->getResourceOwnerDetailsUrl($token);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\UnexpectedValueException::class, $e);
        }
    }
}
