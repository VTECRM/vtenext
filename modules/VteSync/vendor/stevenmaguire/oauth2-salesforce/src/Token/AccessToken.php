<?php

namespace Stevenmaguire\OAuth2\Client\Token;

class AccessToken extends \League\OAuth2\Client\Token\AccessToken
{
    /**
     * All Salesforce Organisation IDs start with this Prefix
     */
    const ORG_ID_PREFIX = '00D';

    /**
     * Instance URL
     *
     * @var string
     */
    private $instanceUrl;

    /**
     * Constructs an access token.
     *
     * @param array $options An array of options returned by the service provider
     *     in the access token request. The `access_token` option is required.
     */
    public function __construct(array $options)
    {
        parent::__construct($options);

        $this->instanceUrl = $options['instance_url'];
    }

    /**
     * Returns Salesforce instance URL related to Access Token
     *
     * @return string
     */
    public function getInstanceUrl()
    {
        return $this->instanceUrl;
    }

    /**
     * Returns Organisation ID related to Access Token
     *
     * @return string|null
     */
    public function getOrgId()
    {
        return preg_match('/' . self::ORG_ID_PREFIX .  '(\w{15}|\w{12})/', $this->getResourceOwnerId(), $result)
            ? $result[0]
            : null;
    }
}
