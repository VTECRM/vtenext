# Jira Provider for OAuth 2.0 Client

[![Latest Version](https://img.shields.io/github/tag/mrjoops/oauth2-jira.svg?style=flat-square)](https://github.com/mrjoops/oauth2-jira/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/mrjoops/oauth2-jira/develop.svg?style=flat-square)](https://travis-ci.org/mrjoops/oauth2-jira)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/mrjoops/oauth2-jira.svg?style=flat-square)](https://scrutinizer-ci.com/g/mrjoops/oauth2-jira/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/mrjoops/oauth2-jira.svg?style=flat-square)](https://scrutinizer-ci.com/g/mrjoops/oauth2-jira)

This package provides Jira OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require mrjoops/oauth2-jira
```

## Usage

Usage is the same as The League's OAuth client, using `\Mrjoops\OAuth2\Client\Provider\Jira` as the provider.

### Authorization Code Flow

```php
$provider = new Mrjoops\OAuth2\Client\Provider\Jira([
    'clientId'          => '{jira-client-id}',
    'clientSecret'      => '{jira-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url',
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getNickname());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

### Managing Scopes

When creating your Jira authorization URL, you can specify the state and scopes your application may authorize.

```php
$options = [
    'state' => 'OPTIONAL_CUSTOM_CONFIGURED_STATE',
    'scope' => ['read:jira-user','read:jira-work] // array or string
];

$authorizationUrl = $provider->getAuthorizationUrl($options);
```
If neither are defined, the provider will utilize internal defaults.

At the time of authoring this documentation, the [following scopes are available](https://developer.atlassian.com/cloud/jira/platform/oauth-2-authorization-code-grants-3lo-for-apps/#implementing-oauth-2-0-authorization-code-grants).

- read:jira-user
- read:jira-work
- write:jira-work
- manage:jira-project
- manage:jira-configuration

### Jira Cloud API call

Since your Jira Cloud API URL vary, you can get it using the `getApiUrl()` method of the provider.

```php
$request = $provider->getAuthenticatedRequest(
    \Mrjoops\OAuth2\Client\Provider\Jira::METHOD_GET,
    $provider->getApiUrl().'/rest/api/3/myself',
    $token
);

```

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/mrjoops/oauth2-jira/blob/develop/CONTRIBUTING.md) for details.

## Credits

- [Alexandre Lahure](https://github.com/mrjoops)
- [All Contributors](https://github.com/mrjoops/oauth2-jira/contributors)

## License

The MIT License (MIT). Please see [License File](https://github.com/mrjoops/oauth2-jira/blob/develop/LICENSE) for more information.
