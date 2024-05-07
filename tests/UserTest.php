<?php declare(strict_types=1);

namespace Sept\OAuth2\Client\Test;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Sept\OAuth2\Client\Provider\SeptemberFirstUser;

#[CoversMethod(SeptemberFirstUser::class, 'getFirstName')]
final class UserTest extends TestCase
{
    public function testCreate(): void
    {
        $name = 'Alex';
        $user = new SeptemberFirstUser([
            'personal_name' => [
                'name' => $name,
            ],
        ]);
        self::assertSame($name, $user->getFirstName());
    }
}
