<?php

namespace Mrjoops\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Mrjoops\OAuth2\Client\Provider\Exception\JiraIdentityProviderException;
use Psr\Http\Message\ResponseInterface;

class Jira extends AbstractProvider
{
    use ArrayAccessorTrait;
    use BearerAuthorizationTrait;
    
    /**
     *
     * @var string URL used for non-OAuth API calls
     */
    protected $apiUrl;

    /**
     * Check a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  array $data Parsed response data
     *
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw JiraIdentityProviderException::clientException($response, $data);
        } elseif (isset($data['error'])) {
            throw JiraIdentityProviderException::oauthException($response, $data);
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array $response
     * @param AccessToken $token
     *
     * @return \League\OAuth2\Client\Provider\ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new JiraResourceOwner($response);
    }
    
    /**
     *
     * @return string URL used for non-OAuth API calls
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * Get access token url to retrieve token
     *
     * @param  array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://accounts.atlassian.com/oauth/token';
    }

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return 'https://accounts.atlassian.com/authorize?audience=api.atlassian.com&prompt=consent';
    }

    /**
     * Get the default scopes used by this provider.
     *
     * This should not be a complete list of all scopes, but the minimum
     * required for the provider user interface!
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return ['jira:read-user'];
    }
    
    /**
     * Get provider url to fetch user details
     *
     * @param AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        $request = $this->getAuthenticatedRequest(
            self::METHOD_GET,
            'https://api.atlassian.com/oauth/token/accessible-resources',
            $token
        );

        $response = $this->getParsedResponse($request);

        if (false === is_array($response)) {
            throw new \UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }
        
        $cloudId = $this->getValueByKey($response, '0.id');
        
        $this->setApiUrl('https://api.atlassian.com/ex/jira/'.$cloudId);

        return $this->getApiUrl().'/rest/api/3/myself';
    }
    
    /**
     * Returns the string that should be used to separate scopes when building
     * the URL for requesting an access token.
     *
     * @return string Scope separator, defaults to ' '
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }
    
    /**
     *
     * @param string $url URL used for non-OAuth API calls
     */
    protected function setApiUrl($url)
    {
        $this->apiUrl = $url;
    }
}
