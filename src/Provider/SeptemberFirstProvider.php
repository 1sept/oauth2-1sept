<?php

declare(strict_types=1);

namespace Sept\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;

/**
 * Провайдер данных Первого сентября
 */
class SeptemberFirstProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * @var string Сервер аутентификации (Личный кабинет Первое сентября)
     */
    const AUTH_BASE = 'https://my.1sept.ru';

    /**
     * @var string API Первое сентября
     */
    const API_BASE = 'https://api.1sept.ru';

    /**
     * @var string Версия API
     */
    const API_VERSION = '2.0';

    /**
     * @inheritDoc
     */
    public function getBaseAuthorizationUrl(): string
    {
        return static::AUTH_BASE.'/oauth/authorize';
    }

    /**
     * @inheritDoc
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return static::API_BASE.'/oauth/access_token';
    }

    /**
     * @inheritDoc
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return static::API_BASE.'/'.static::API_VERSION.'/userinfo';
    }

    /**
     * @inheritDoc
     */
    public function getDefaultScopes(): array
    {
        return ['profile'];
    }

    /**
     * @inheritDoc
     */
    protected function getScopeSeparator(): string
    {
        return ' ';
    }

    /**
     * @inheritDoc
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (!empty($data['error'])) {
            throw new IdentityProviderException($data['error'].': '.$data['message'], null, $response);
        }
    }

    /**
     * @inheritDoc
     */
    protected function createResourceOwner(array $response, AccessToken $token): SeptemberFirstUser
    {
        return new SeptemberFirstUser($response);
    }
}
