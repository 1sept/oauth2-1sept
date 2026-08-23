<?php

declare(strict_types=1);

namespace Sept\OAuth2\Client\Test;

use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sept\OAuth2\Client\Provider\SeptemberFirstProvider;
use Sept\OAuth2\Client\Provider\SeptemberFirstUser;

/**
 * @internal
 */
#[CoversClass(SeptemberFirstProvider::class)]
final class ProviderTest extends TestCase
{
    /**
     * @param mixed[] $options
     */
    #[DataProvider('provideUrlsCases')]
    public function testUrls(array $options, string $authorizeUrl, string $tokenUrl): void
    {
        $provider = $this->makeProvider($options);

        self::assertSame($authorizeUrl, $provider->getBaseAuthorizationUrl());
        self::assertSame($tokenUrl, $provider->getBaseAccessTokenUrl([]));
    }

    /**
     * @return iterable<string, array{mixed[], string, string}>
     */
    public static function provideUrlsCases(): iterable
    {
        yield 'defaults' => [
            [],
            'https://my.1sept.ru/oauth/authorize',
            'https://api.1sept.ru/oauth/access_token',
        ];

        yield 'custom bases' => [
            ['authBase' => 'https://my.test', 'apiBase' => 'https://api.test'],
            'https://my.test/oauth/authorize',
            'https://api.test/oauth/access_token',
        ];
    }

    public function testInvalidAuthBaseIsRejectedLoudly(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makeProvider(['authBase' => '']);
    }

    public function testAuthorizationUrlContainsDefaultScope(): void
    {
        $url = $this->makeProvider()->getAuthorizationUrl();

        self::assertStringStartsWith('https://my.1sept.ru/oauth/authorize?', $url);
        self::assertStringContainsString('scope=profile', $url);
        self::assertStringContainsString('client_id=test-client', $url);
    }

    public function testScopesOptionIsHonored(): void
    {
        $url = $this->makeProvider(['scopes' => ['profile', 'email', 'phones']])->getAuthorizationUrl();

        self::assertStringContainsString('scope=profile%20email%20phones', $url);
    }

    public function testPkceMethodOptionIsHonored(): void
    {
        $url = $this->makeProvider(['pkceMethod' => 'S256'])->getAuthorizationUrl();

        self::assertStringContainsString('code_challenge=', $url);
        self::assertStringContainsString('code_challenge_method=S256', $url);

        self::assertStringNotContainsString('code_challenge', $this->makeProvider()->getAuthorizationUrl());
    }

    public function testSuccessfulResponsePassesCheck(): void
    {
        $this->expectNotToPerformAssertions();

        $this->checkResponse($this->makeProvider(), new Response(200), ['id' => 'uuid']);
    }

    public function testFalsyErrorValueOnSuccessDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();

        $provider = $this->makeProvider();
        foreach (['', false, 0, [], null] as $falsyError) {
            $this->checkResponse($provider, new Response(200), ['access_token' => 'x', 'error' => $falsyError]);
        }
    }

    public function testStringErrorThrowsWithStatusCodeAndParsedBody(): void
    {
        $data = ['error' => 'invalid_grant', 'message' => 'Код авторизации истёк'];

        try {
            $this->checkResponse($this->makeProvider(), new Response(400), $data);
            self::fail('Expected IdentityProviderException');
        } catch (IdentityProviderException $e) {
            self::assertSame('invalid_grant: Код авторизации истёк', $e->getMessage());
            self::assertSame(400, $e->getCode());
            self::assertSame($data, $e->getResponseBody());
        }
    }

    public function testStructuredErrorObjectYieldsReadableMessage(): void
    {
        $data = ['error' => ['code' => 401, 'message' => 'invalid_token']];

        try {
            $this->checkResponse($this->makeProvider(), new Response(401), $data);
            self::fail('Expected IdentityProviderException');
        } catch (IdentityProviderException $e) {
            self::assertSame('invalid_token', $e->getMessage());
            self::assertSame(401, $e->getCode());
            self::assertSame($data, $e->getResponseBody());
        }
    }

    public function testErrorKeyInSuccessStatusStillThrows(): void
    {
        $this->expectException(IdentityProviderException::class);

        $this->checkResponse($this->makeProvider(), new Response(200), ['error' => 'invalid_request']);
    }

    public function testNonJsonErrorResponseThrowsWithBodyPreserved(): void
    {
        $html = '<html>Forbidden</html>';

        try {
            $this->checkResponse($this->makeProvider(), new Response(403, [], $html), $html);
            self::fail('Expected IdentityProviderException');
        } catch (IdentityProviderException $e) {
            self::assertSame($html, $e->getMessage());
            self::assertSame(403, $e->getCode());
            self::assertSame($html, $e->getResponseBody());
        }
    }

    public function testEmptyErrorBodyFallsBackToReasonPhrase(): void
    {
        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('Service Unavailable');
        $this->expectExceptionCode(503);

        $this->checkResponse($this->makeProvider(), new Response(503), '');
    }

    public function testCreateResourceOwner(): void
    {
        $provider = $this->makeProvider();
        $response = ['id' => '1cc1632f-2349-4d00-8302-5c4c188469cc', 'email' => 'p.ivanov@example.com'];

        $user = new \ReflectionMethod($provider, 'createResourceOwner')
            ->invoke($provider, $response, new AccessToken(['access_token' => 'mock']))
        ;

        self::assertInstanceOf(SeptemberFirstUser::class, $user);
        self::assertSame('1cc1632f-2349-4d00-8302-5c4c188469cc', $user->getId());
        self::assertSame($response, $user->toArray());
    }

    /**
     * @param mixed[] $options
     */
    private function makeProvider(array $options = []): SeptemberFirstProvider
    {
        return new SeptemberFirstProvider($options + [
            'clientId' => 'test-client',
            'clientSecret' => 'test-secret',
            'redirectUri' => 'https://example.com/callback',
        ]);
    }

    /**
     * @param mixed[]|string $data
     *
     * @throws IdentityProviderException
     */
    private function checkResponse(SeptemberFirstProvider $provider, Response $response, array|string $data): void
    {
        new \ReflectionMethod($provider, 'checkResponse')->invoke($provider, $response, $data);
    }
}
