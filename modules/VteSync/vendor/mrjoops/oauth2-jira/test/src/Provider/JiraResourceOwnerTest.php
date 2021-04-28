<?php

namespace Mrjoops\OAuth2\Client\Test\Provider;

use Mrjoops\OAuth2\Client\Provider\JiraResourceOwner;

class JiraResourceOwnerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Raw response
     *
     * @var array
     */
    protected $response;

    protected function setUp() {
        $this->response = json_decode(<<<ETX
{
  "self": "http://your-domain.atlassian.net/rest/api/3/user?username=mia",
  "key": "mia",
  "accountId": "99:27935d01-92a7-4687-8272-a9b8d3b2ae2e",
  "name": "mia",
  "emailAddress": "mia@example.com",
  "avatarUrls": {
    "48x48": "http://your-domain.atlassian.net/secure/useravatar?size=large&ownerId=mia",
    "24x24": "http://your-domain.atlassian.net/secure/useravatar?size=small&ownerId=mia",
    "16x16": "http://your-domain.atlassian.net/secure/useravatar?size=xsmall&ownerId=mia",
    "32x32": "http://your-domain.atlassian.net/secure/useravatar?size=medium&ownerId=mia"
  },
  "displayName": "Mia Krystof",
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
, true);
    }

    
    public function testEmail()
    {
        $user = new JiraResourceOwner($this->response);

        $this->assertEquals($user->getEmail(), 'mia@example.com');
    }

    public function testId()
    {
        $user = new JiraResourceOwner($this->response);

        $this->assertEquals($user->getId(), '99:27935d01-92a7-4687-8272-a9b8d3b2ae2e');
    }

    public function testName()
    {
        $user = new JiraResourceOwner($this->response);

        $this->assertEquals($user->getName(), 'Mia Krystof');
    }

    public function testNickname()
    {
        $user = new JiraResourceOwner($this->response);

        $this->assertEquals($user->getNickname(), 'mia');
    }
}
