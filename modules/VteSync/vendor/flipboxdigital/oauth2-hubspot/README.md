# HubSpot Provider for OAuth 2.0 Client
[![Latest Version](https://img.shields.io/github/release/flipbox/oauth2-hubspot.svg?style=flat-square)](https://github.com/flipbox/oauth2-hubspot/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/flipbox/oauth2-hubspot/master.svg?style=flat-square)](https://travis-ci.org/flipbox/oauth2-hubspot)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/flipbox/oauth2-hubspot.svg?style=flat-square)](https://scrutinizer-ci.com/g/flipbox/oauth2-hubspot/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/flipbox/oauth2-hubspot.svg?style=flat-square)](https://scrutinizer-ci.com/g/flipbox/oauth2-hubspot)
[![Total Downloads](https://img.shields.io/packagist/dt/flipboxdigital/oauth2-hubspot.svg?style=flat-square)](https://packagist.org/packages/flipboxdigital/oauth2-hubspot)

This package provides HubSpot OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/flipbox/oauth2-client).

## Installation

To install, use composer:

```
composer require flipboxdigital/oauth2-hubspot
```

## Usage

Usage is the same as The League's OAuth client, using `\Flipbox\OAuth2\Client\Provider\HubSpot` as the provider.

### Authorization Code Flow

```php
$provider = new Flipbox\OAuth2\Client\Provider\HubSpot([
    'clientId'          => '{hubspot-client-id}',
    'clientSecret'      => '{hubspot-client-secret}',
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
        printf('Hello %s!', $user->getEmail());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

### Managing Scopes

When creating your HubSpot authorization URL, you can specify the state and scopes your application may authorize.

```php
$options = [
    'state' => 'OPTIONAL_CUSTOM_CONFIGURED_STATE',
    'scope' => ['contacts','content'] // array or string
];

$authorizationUrl = $provider->getAuthorizationUrl($options);
```
If neither are defined, the provider will utilize internal defaults.

At the time of authoring this documentation, the [following scopes are available](https://developers.hubspot.com/docs/methods/oauth2/initiate-oauth-integration#scopes).

- contacts
- content
- reports
- social
- automation
- timeline
- forms
- files
- hubdb
- transactional-email

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/flipbox/oauth2-hubspot/blob/master/CONTRIBUTING.md) for details.


## Credits

- [Flipbox Digital](https://github.com/flipbox)


## License

The MIT License (MIT). Please see [License File](https://github.com/flipbox/oauth2-hubspot/blob/master/LICENSE) for more information.
