<?php

declare(strict_types=1);

namespace Sept\OAuth2\Client\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sept\OAuth2\Client\Provider\SeptemberFirstUser;

/**
 * @internal
 */
#[CoversClass(SeptemberFirstUser::class)]
final class UserTest extends TestCase
{
    private const USER_ID = '1cc1632f-2349-4d00-8302-5c4c188469cc';

    /**
     * @var mixed[]
     */
    private const DATA = [
        'id' => self::USER_ID,
        'id_alt' => ['9f9f9f9f-0000-4d00-8302-5c4c188469cc'],
        'personal_name' => [
            'surname' => 'Иванов',
            'name' => 'Пётр',
            'patronymic' => 'Сергеевич',
        ],
        'display_name' => 'Пётр Иванов',
        'sex' => 'male',
        'email' => 'p.ivanov@example.com',
        'birthday' => '2000-01-15',
        'avatar' => 'https://avatar.1sept.ru/' . self::USER_ID . '.webp',
        'avatar_max' => 'https://avatar.1sept.ru/' . self::USER_ID . '.max.webp',
        'avatar_version' => 7,
        'avatar_default' => false,
        'address' => [
            'id' => 12345,
            'country_id' => 'RU',
            'city' => 'Муром',
        ],
        'locale' => 'ru_RU',
        'timezone' => 'Europe/Moscow',
    ];

    public function testBasicGetters(): void
    {
        $user = new SeptemberFirstUser(self::DATA);

        self::assertSame(self::USER_ID, $user->getId());
        self::assertSame(['9f9f9f9f-0000-4d00-8302-5c4c188469cc'], $user->getIdAlt());
        self::assertSame('Иванов', $user->getLastName());
        self::assertSame('Пётр', $user->getFirstName());
        self::assertSame('Сергеевич', $user->getMiddleName());
        self::assertSame('Пётр Иванов', $user->getDisplayName());
        self::assertSame('male', $user->getSex());
        self::assertSame('p.ivanov@example.com', $user->getEmail());
        self::assertSame('ru_RU', $user->getLocale());
        self::assertSame('Europe/Moscow', $user->getTimezone());
        self::assertSame(12345, $user->getAddressID());
        self::assertSame('RU', $user->getAddressCountryID());
        self::assertSame('Муром', $user->getAddressCity());
        self::assertSame(self::DATA, $user->toArray());
    }

    public function testMissingFieldsDegradeToNull(): void
    {
        $user = new SeptemberFirstUser(['id' => self::USER_ID]);

        self::assertSame([], $user->getIdAlt());
        self::assertNull($user->getLastName());
        self::assertNull($user->getFirstName());
        self::assertNull($user->getDisplayName());
        self::assertNull($user->getEmail());
        self::assertNull($user->getBirthday());
        self::assertNull($user->getAvatarUrl());
        self::assertNull($user->getAvatarMaxUrl());
        self::assertNull($user->getAvatarVersion());
        self::assertNull($user->getAddressID());
        self::assertNull($user->getAddressCity());
        self::assertNull($user->getPhones());
        self::assertNull($user->getProfileUrl());
        self::assertNull($user->getSnils());
        self::assertNull($user->getLocale());
        self::assertNull($user->getTimezone());
        self::assertNull($user->isDied());
        self::assertNull($user->isDefaultAvatar()); // API не передал признак — «неизвестно»
        self::assertFalse($user->isAddressGeneralDelivery());
    }

    public function testUnexpectedTypesDegradeToNull(): void
    {
        $user = new SeptemberFirstUser([
            'id' => self::USER_ID,
            'personal_name' => 'Иванов', // строка вместо вложенного массива
            'email' => false,
            'phones' => 'нет',
            'sex' => 'other',
        ]);

        self::assertNull($user->getFirstName());
        self::assertNull($user->getLastName());
        self::assertNull($user->getEmail());
        self::assertNull($user->getPhones());
        self::assertNull($user->getSex());
    }

    public function testNumericStringAddressIdIsCoerced(): void
    {
        $user = new SeptemberFirstUser(['address' => ['id' => '12345']]);

        self::assertSame(12345, $user->getAddressID());
    }

    public function testBirthday(): void
    {
        $birthday = new SeptemberFirstUser(['birthday' => '2000-01-15'])->getBirthday();
        self::assertInstanceOf(\DateTime::class, $birthday);
        self::assertSame('2000-01-15', $birthday->format('Y-m-d'));

        self::assertNull(new SeptemberFirstUser(['birthday' => 'not-a-date'])->getBirthday());
        self::assertNull(new SeptemberFirstUser(['birthday' => ''])->getBirthday());
        self::assertNull(new SeptemberFirstUser(['birthday' => ['date' => '2000-01-15']])->getBirthday());
        self::assertNull(new SeptemberFirstUser(['birthday' => 123456])->getBirthday());

        // Относительные и невалидные календарные даты отвергаются
        self::assertNull(new SeptemberFirstUser(['birthday' => 'now'])->getBirthday());
        self::assertNull(new SeptemberFirstUser(['birthday' => 'tomorrow'])->getBirthday());
        self::assertNull(new SeptemberFirstUser(['birthday' => '2000-02-31'])->getBirthday());
    }

    public function testAvatarUrlRejectsDefaultAvatar(): void
    {
        $data = self::DATA;
        self::assertSame($data['avatar'], new SeptemberFirstUser($data)->getAvatarUrl(true));

        $data['avatar_default'] = true;
        $user = new SeptemberFirstUser($data);
        self::assertTrue($user->isDefaultAvatar());
        self::assertNull($user->getAvatarUrl(true));
        self::assertSame($data['avatar'], $user->getAvatarUrl());
    }

    public function testAvatarSizeUrl(): void
    {
        $user = new SeptemberFirstUser(self::DATA);
        $base = 'https://avatar.1sept.ru/' . self::USER_ID;

        self::assertSame($base . '.150.webp?v=7', $user->getAvatarSizeUrl(150));
        self::assertSame($base . '.150@2x.webp?v=7', $user->getAvatarSizeUrl(150, 2));
        self::assertSame($base . '.150.webp', $user->getAvatarSizeUrl(150, 1, false));
        self::assertSame($base . '.150.jpeg?v=7', $user->getAvatarSizeUrl(150, 1, true, 'jpeg'));

        // Размер 0 или отрицательный — сегмент размера (включая множитель @Nx) опускается целиком
        self::assertSame($base . '.webp?v=7', $user->getAvatarSizeUrl(0));
        self::assertSame($base . '.webp?v=7', $user->getAvatarSizeUrl(-1));
        self::assertSame($base . '.webp?v=7', $user->getAvatarSizeUrl(0, 2));
    }

    public function testAvatarSetSizeUrl(): void
    {
        $user = new SeptemberFirstUser(self::DATA);
        $base = 'https://avatar.1sept.ru/' . self::USER_ID;

        self::assertSame(
            $base . '.150.webp?v=7 1x, ' . $base . '.150@2x.webp?v=7 2x, ' . $base . '.150@3x.webp?v=7 3x',
            $user->getAvatarSetSizeUrl(150)
        );
    }

    public function testAvatarMaxUrl(): void
    {
        $user = new SeptemberFirstUser(self::DATA);

        self::assertSame(self::DATA['avatar_max'], $user->getAvatarMaxUrl());
        self::assertSame(self::DATA['avatar_max'] . '?v=7', $user->getAvatarMaxUrl(true));

        // Без avatar_max — null, даже с запрошенной версией
        self::assertNull(new SeptemberFirstUser(['avatar_version' => 7])->getAvatarMaxUrl(true));
        self::assertNull(new SeptemberFirstUser(['avatar_max' => ''])->getAvatarMaxUrl());
    }

    public function testAvatarVersionQuery(): void
    {
        self::assertSame('?v=7', new SeptemberFirstUser(['avatar_version' => 7])->getAvatarVersionQuery());
        self::assertSame('', new SeptemberFirstUser([])->getAvatarVersionQuery());

        // Строковая версия URL-кодируется
        self::assertSame('?v=a%20b%26c', new SeptemberFirstUser(['avatar_version' => 'a b&c'])->getAvatarVersionQuery());
    }

    public function testGetFieldFromArray(): void
    {
        $array = [
            'a' => [
                'b' => [
                    'c' => 'value',
                ],
            ],
            'flat' => 'плоское значение',
        ];

        self::assertSame('value', SeptemberFirstUser::getFieldFromArray('a.b.c', $array));
        self::assertSame(['c' => 'value'], SeptemberFirstUser::getFieldFromArray('a.b', $array));
        self::assertSame('плоское значение', SeptemberFirstUser::getFieldFromArray('flat', $array));
        self::assertNull(SeptemberFirstUser::getFieldFromArray('a.b.missing', $array));
        self::assertNull(SeptemberFirstUser::getFieldFromArray('missing.b', $array));
        self::assertNull(SeptemberFirstUser::getFieldFromArray('flat.sub', $array)); // скаляр вместо массива
        self::assertNull(SeptemberFirstUser::getFieldFromArray('any', null));
        self::assertNull(SeptemberFirstUser::getFieldFromArray('any', 'не массив'));
    }
}
