# September First OAuth2 client provider
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

This package provides [September First](https://api.1sept.ru) integration for [OAuth2 Client](https://github.com/thephpleague/oauth2-client) by the League.

## Installation

Just execute:
```sh
composer require 1sept/oauth2-1sept
```

## Usage

```php
$provider = new \Sept\OAuth2\Client\Provider\SeptemberFirstProvider([
    'clientId' => 'client_id',
    'clientSecret' => 'secret',
    'redirectUri' => 'https://example.org/oauth-endpoint',
]);
```
