<?php

declare(strict_types=1);

namespace Sept\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

/**
 * Провайдер данных Первого сентября.
 */
class SeptemberFirstProvider extends GenericProvider
{
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
     * @var int Предельная длина текста ошибки, взятого из тела ответа
     */
    private const int ERROR_MESSAGE_MAX_LENGTH = 500;

    /**
     * Constructor.
     *
     * @param mixed[]  $options
     * @param object[] $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        $authBase = $options['authBase'] ?? static::AUTH_BASE;
        if (!\is_string($authBase) || '' === $authBase) {
            throw new \InvalidArgumentException('Option `authBase` must be a non-empty string');
        }

        $apiBase = $options['apiBase'] ?? static::API_BASE;
        if (!\is_string($apiBase) || '' === $apiBase) {
            throw new \InvalidArgumentException('Option `apiBase` must be a non-empty string');
        }

        unset($options['authBase'], $options['apiBase']);

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
     * Ошибкой считается любой HTTP-статус >= 400, а также «содержательное»
     * поле `error` в теле ответа (непустая строка или непустой объект);
     * ложные маркеры (`""`, `0`, `false`, `[]`) при успешном статусе ошибкой не являются.
     *
     * Тело ответа попадает в текст исключения, только если это осмысленный текст ошибки:
     * при аварии на стороне сервера (502/503 от nginx) тело — это HTML-страница,
     * и в сообщение вместо неё идёт статус ответа. Целиком тело остаётся в `getResponseBody()`.
     *
     * @param mixed[]|string $data — Parsed response data
     *
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        $statusCode = $response->getStatusCode();
        $error = \is_array($data) ? ($data['error'] ?? null) : null;
        $hasError = (\is_string($error) && '' !== $error) || (\is_array($error) && [] !== $error);

        if ($statusCode < 400 && !$hasError) {
            return;
        }

        if (\is_string($error) && '' !== $error) {
            $message = $error;
        } elseif (\is_array($error) && [] !== $error) {
            $detail = $error['message'] ?? $error['error_description'] ?? $error['description'] ?? $error['code'] ?? null;
            $message = \is_scalar($detail) ? (string) $detail : (string) json_encode($error, JSON_UNESCAPED_UNICODE);
        } elseif (\is_string($data) && '' !== trim($data) && !str_starts_with(ltrim($data), '<')) {
            $message = mb_substr(trim($data), 0, self::ERROR_MESSAGE_MAX_LENGTH);
        } else {
            $reasonPhrase = $response->getReasonPhrase();
            $message = 'HTTP ' . $statusCode . ('' !== $reasonPhrase ? ' ' . $reasonPhrase : '');
        }

        if (\is_array($data) && isset($data['message']) && \is_string($data['message']) && '' !== $data['message'] && $data['message'] !== $message) {
            $message .= ': ' . $data['message'];
        }

        throw new IdentityProviderException($message, $statusCode, $data);
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
