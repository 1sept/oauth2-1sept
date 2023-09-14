<?php

declare(strict_types=1);

namespace Sept\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
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
     * @var string[] Разрешения (scopes) по умолчанию
     */
    const SCOPES_DEFAULT = ['profile'];

    /**
     * @var string Разделитель перечня запрашиваемых разрешений
     */
    const SCOPES_SEPARATOR = ' ';

    /**
     * @var string Путь авторизации
     */
    const AUTHORIZE_PATH = '/oauth/authorize';

    /**
     * @var string Путь получения токена
     */
    const ACCESS_TOKEN_PATH = '/oauth/access_token';

    /**
     * @var string Путь получения данных пользователя
     */
    const USERINFO_PATH = '/2.0/userinfo';

    /**
     * Undocumented function
     *
     * @param mixed[] $options
     * @param object[] $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        $defaultOptions = [
            'urlAuthorize' => static::AUTH_BASE.static::AUTHORIZE_PATH,
            'urlAccessToken' => static::API_BASE.static::ACCESS_TOKEN_PATH,
            'urlResourceOwnerDetails' => static::API_BASE.static::USERINFO_PATH,
            'scopes' => static::SCOPES_DEFAULT,
            'scopeSeparator' => static::SCOPES_SEPARATOR,
        ];

        parent::__construct(array_merge($defaultOptions, $options), $collaborators);
    }

    /**
     * Checks a provider response for errors.
     *
     * @param ResponseInterface $response
     * @param mixed[]|string $data — Parsed response data
     * @return void
     *
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (! empty($data['error'])) {
            throw new IdentityProviderException($data['error'].': '.$data['message'], 0, $response);
        }
    }

    /**
     * Generates a resource owner object from a successful resource owner details request.
     *
     * @param mixed[] $response
     * @param AccessToken $token
     * @return SeptemberFirstUser
     */
    protected function createResourceOwner(array $response, AccessToken $token): SeptemberFirstUser
    {
        return new SeptemberFirstUser($response);
    }
}
