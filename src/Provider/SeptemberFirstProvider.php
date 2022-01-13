<?php

declare(strict_types=1);

namespace Sept\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * Провайдер данных Первого сентября
 */
class SeptemberFirstProvider extends GenericProvider
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

    public function __construct(array $options = [], array $collaborators = [])
    {
        $defaultOptions = [
            'urlAuthorize' => static::AUTH_BASE.'/oauth/authorize',
            'urlAccessToken' => static::API_BASE.'/oauth/access_token',
            'urlResourceOwnerDetails' => static::API_BASE.'/'.static::API_VERSION.'/userinfo',
            'scopes' => ['profile'],
            'scopeSeparator' => ' ',
        ];

        parent::__construct(array_merge($defaultOptions, $options), $collaborators);
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
