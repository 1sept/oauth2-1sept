<?php

namespace Sept\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;

class SeptemberFirstProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Личный кабинет Первое сентября
     *
     * @var string
     */
    const AUTH_BASE = 'https://my.1sept.ru';

    /**
     * API Первое сентября
     *
     * @var string
     */
    const API_BASE = 'https://api.1sept.ru';
    const API_VERSION = '2.0';

    public function getBaseAuthorizationUrl(): string
    {
        return static::AUTH_BASE.'/oauth/authorize';
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return static::API_BASE.'/oauth/access_token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return static::API_BASE.'/'.static::API_VERSION.'/userinfo';
    }

    public function getDefaultScopes(): array
    {
        return ['basic'];
    }

    protected function getScopeSeparator(): string
    {
        return ' ';
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) {
            throw new IdentityProviderException($data['error'].': '.$data['message'], null, $response);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token): SeptemberFirstUser
    {
        return new SeptemberFirstUser($response);
    }
}
