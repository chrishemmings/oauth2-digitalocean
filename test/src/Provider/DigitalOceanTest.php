<?php

namespace ChrisHemmings\OAuth2\Client\Test\Provider;

use ChrisHemmings\OAuth2\Client\Provider\DigitalOcean;
use ChrisHemmings\OAuth2\Client\Token\DigitalOceanAccessToken;
use Mockery as m;
use ReflectionClass;

class DigitalOceanTest extends \PHPUnit_Framework_TestCase
{
    protected $provider;

    protected static function getMethod($name)
    {
        $class = new ReflectionClass('ChrisHemmings\OAuth2\Client\Provider\DigitalOcean');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    protected function setUp()
    {
        $this->provider = new DigitalOcean([
            'clientId'      => 'mock_client_id',
            'clientSecret'  => 'mock_secret',
            'redirectUri'   => 'none',
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
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        $this->assertEquals('/v1/oauth/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];
        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);
        $this->assertEquals('/v1/oauth/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        // lifted from DigitalOcean docs
        $testResponse = [
            'access_token' => '547cac21118ae7',
            'token_type' => 'bearer',
            'expires_in' => 2592000,
            'refresh_token' => '00a3aae641658d',
            'scope' => 'read write',
            'info' => [
                'name' => 'Sammy the Shark',
                'email' => 'sammy@digitalocean.com',
                'uuid' => 'e028b1b918853eca7fba208a9d7e9d29a6e93c57',
            ],
        ];
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn(\json_encode($testResponse));
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);
        /** @var DigitalOceanAccessToken $token */
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $this->assertEquals($testResponse['access_token'], $token->getToken());
        $this->assertEquals(time() + $testResponse['expires_in'], $token->getExpires());
        $this->assertEquals($testResponse['refresh_token'], $token->getRefreshToken());
        $this->assertTrue($token->hasScope('read'));
        $this->assertTrue($token->hasScope('write'));
        $this->assertFalse($token->hasScope('badscope'));
        $this->assertEquals($testResponse['info']['uuid'], $token->getResourceOwnerId());
        $this->assertEquals($testResponse['info'], $token->getInfo());
    }

    public function testUserData()
    {
        $response_data = ['account' => [
            'uuid' => rand(1000, 9999),
            'email' => uniqid(),
            'droplet_limit' => rand(1000, 9999),
            'floating_ip_limit' => rand(1000, 9999),
            'email_verified' => uniqid(),
            'status' => uniqid(),
            'status_message' => uniqid(),
        ]];

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"access_token":"mock_access_token", "token_type":"bearer"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);
        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn(json_encode($response_data));
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);
        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($response_data['account']['uuid'], $user->getId());
        $this->assertEquals($response_data['account']['uuid'], $user->toArray()['uuid']);
        $this->assertEquals($response_data['account']['email'], $user->getEmail());
        $this->assertEquals($response_data['account']['email'], $user->toArray()['email']);
        $this->assertEquals($response_data['account']['droplet_limit'], $user->getDropletLimit());
        $this->assertEquals($response_data['account']['droplet_limit'], $user->toArray()['droplet_limit']);
        $this->assertEquals($response_data['account']['floating_ip_limit'], $user->getFloatingIpLimit());
        $this->assertEquals($response_data['account']['floating_ip_limit'], $user->toArray()['floating_ip_limit']);
        $this->assertEquals($response_data['account']['email_verified'], $user->getEmailVerified());
        $this->assertEquals($response_data['account']['email_verified'], $user->toArray()['email_verified']);
        $this->assertEquals($response_data['account']['status'], $user->getStatus());
        $this->assertEquals($response_data['account']['status'], $user->toArray()['status']);
        $this->assertEquals($response_data['account']['status_message'], $user->getStatusMessage());
        $this->assertEquals($response_data['account']['status_message'], $user->toArray()['status_message']);
    }

    /**
     * @expectedException League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function testExceptionThrownWhenErrorObjectReceived()
    {
        $message = uniqid();
        $status = rand(400, 600);
        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn(' {"error":"'.$message.'"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);
        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);
        $this->provider->setHttpClient($client);
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
