<?php

namespace Flipbox\OAuth2\Client\Provider;

use Flipbox\OAuth2\Client\Provider\Exception\HubSpotIdentityProviderException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class HubSpot extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Domain
     *
     * @var string
     */
    public $domain = 'https://app.hubspot.com';

    /**
     * Api domain
     *
     * @var string
     */
    public $apiDomain = 'https://api.hubapi.com';

    /**
     * @var array
     */
    protected $defaultScopes = [];

    /**
     * @inheritdoc
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->domain . '/oauth/authorize';
    }

    /**
     * @inheritdoc
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->apiDomain . '/oauth/v1/token';
    }

    /**
     * @inheritdoc
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->apiDomain . '/oauth/v1/access-tokens/' . $token->getToken();
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultScopes()
    {
        return $this->defaultScopes;
    }

    /**
     * @inheritdoc
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }

    /**
     * @inheritdoc
     */
    protected function getAuthorizationHeaders($token = null)
    {
        if ($token === null) {
            return [];
        }
        return [
            'Authorization' => sprintf("Bearer %s", $token)
        ];
    }

    /**
     * @inheritdoc
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw HubSpotIdentityProviderException::clientException($response, $data);
        } elseif (isset($data['error'])) {
            throw HubSpotIdentityProviderException::oauthException($response, $data);
        }
    }

    /**
     * @inheritdoc
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new HubSpotResourceOwner($response);
    }
}
