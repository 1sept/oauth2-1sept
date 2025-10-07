<?php

declare(strict_types=1);

namespace Sept\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * Провайдер данных Первого сентября.
 */
class SeptemberFirstProvider extends GenericProvider
{
    use BearerAuthorizationTrait;

    /**
     * @var string Сервер аутентификации (Личный кабинет Первое сентября)
     */
    public const string AUTH_BASE = 'https://my.1sept.ru';

    /**
     * @var string API Первое сентября
     */
    public const string API_BASE = 'https://api.1sept.ru';

    /**
     * @var string[] Разрешения (scopes) по умолчанию
     */
    public const array SCOPES_DEFAULT = ['profile'];

    /**
     * @var string Разделитель перечня запрашиваемых разрешений
     */
    public const string SCOPES_SEPARATOR = ' ';

    /**
     * @var string Путь авторизации
     */
    public const string AUTHORIZE_PATH = '/oauth/authorize';

    /**
     * @var string Путь получения токена
     */
    public const string ACCESS_TOKEN_PATH = '/oauth/access_token';

    /**
     * @var string Путь получения данных пользователя
     */
    public const string USERINFO_PATH = '/2.0/userinfo';

    /**
     * Constructor.
     *
     * @param mixed[]  $options
     * @param object[] $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        $authBase = $options['authBase'] ?? static::AUTH_BASE;
        \assert(\is_string($authBase), 'Option `authBase` must be a string');

        $apiBase = $options['apiBase'] ?? static::API_BASE;
        \assert(\is_string($apiBase), 'Option `apiBase` must be a string');

        $defaultOptions = [
            'urlAuthorize' => $authBase . static::AUTHORIZE_PATH,
            'urlAccessToken' => $apiBase . static::ACCESS_TOKEN_PATH,
            'urlResourceOwnerDetails' => $apiBase . static::USERINFO_PATH,
            'scopes' => static::SCOPES_DEFAULT,
            'scopeSeparator' => static::SCOPES_SEPARATOR,
        ];

        parent::__construct(array_merge($defaultOptions, $options), $collaborators);
    }

    /**
     * Checks a provider response for errors.
     *
     * @param mixed[]|string $data — Parsed response data
     *
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (isset($data['error']) && \is_string($data['error']) && '' !== $data['error']) {
            throw new IdentityProviderException($data['error'] . ((isset($data['message']) && \is_string($data['message']) && '' !== $data['message']) ? ': ' . $data['message'] : ''), 0, $response);
        }
    }

    /**
     * Generates a resource owner object from a successful resource owner details request.
     *
     * @param mixed[] $response
     */
    protected function createResourceOwner(array $response, AccessToken $token): SeptemberFirstUser
    {
        return new SeptemberFirstUser($response);
    }
}
