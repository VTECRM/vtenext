<?php namespace Flipbox\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class HubSpotResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * Raw response
     *
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param array  $response
     */
    public function __construct(array $response = array())
    {
        $this->response = $response;
    }

    /**
     * Get resource owner id
     *
     * @return string|null
     */
    public function getHubId()
    {
        return $this->getValueByKey($this->response, 'hub_id');
    }

    /**
     * Get resource expires
     *
     * @return int|null
     */
    public function getExpires()
    {
        return $this->getValueByKey($this->response, 'expires_in');
    }

    /**
     * Get resource owner app id
     *
     * @return int|null
     */
    public function getAppId()
    {
        return $this->getValueByKey($this->response, 'app_id');
    }

    /**
     * Get resource owner email
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->getValueByKey($this->response, 'user');
    }

    /**
     * Get resource owner id
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getValueByKey($this->response, 'user_id');
    }

    /**
     * Get resource owner domain
     *
     * @return int|null
     */
    public function getDomain()
    {
        return $this->getValueByKey($this->response, 'hub_domain');
    }

    /**
     * Get resource owner scopes
     *
     * @return array|null
     */
    public function getScopes()
    {
        return $this->getValueByKey($this->response, 'scopes');
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
