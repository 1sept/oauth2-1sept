<?php

declare(strict_types=1);

namespace Sept\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * Пользователь Первого сентября.
 */
class SeptemberFirstUser implements ResourceOwnerInterface
{
    public const string AVATAR_BASE = 'https://avatar.1sept.ru';

    /**
     * @var mixed[] Массив с данными о пользователе
     */
    protected array $data;

    /**
     * @param mixed[] $response
     */
    public function __construct(array $response)
    {
        $this->data = $response;
    }

    /**
     * Массив с данными о пользователе.
     *
     * @return mixed[]
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * ID пользователя (UUID).
     *
     * @example '1cc1632f-2349-4d00-8302-5c4c188469cc'
     */
    public function getId(): string
    {
        $id = $this->getField('id');
        \assert(\is_string($id) && '' !== $id, 'ID must be a non-empty string');

        return $id;
    }

    /**
     * Устаревшие ID пользователя (UUID)
     * (остаются после объединения уч. записей).
     *
     * @return string[]
     */
    public function getIdAlt(): array
    {
        $altIds = $this->getField('id_alt') ?? [];
        \assert(\is_array($altIds), 'Atl IDs list must be an array');
        array_walk($altIds, static function ($id): void {
            \assert(\is_string($id) && '' !== $id, 'Atl ID must be a non-empty string');
        });

        /** @var string[] $altIds */
        return $altIds;
    }

    /**
     * Фамилия.
     */
    public function getLastName(): ?string
    {
        $lastName = $this->getField('personal_name.surname');
        \assert(\is_string($lastName) || null === $lastName, 'Last name must be a string or null');

        return $lastName;
    }

    /**
     * Имя.
     */
    public function getFirstName(): ?string
    {
        $firstName = $this->getField('personal_name.name');
        \assert(\is_string($firstName) || null === $firstName, 'First name must be a string or null');

        return $firstName;
    }

    /**
     * Отчество.
     */
    public function getMiddleName(): ?string
    {
        $middleName = $this->getField('personal_name.patronymic');
        \assert(\is_string($middleName) || null === $middleName, 'Middle name must be a string or null');

        return $middleName;
    }

    /**
     * Девичья фамилия.
     */
    public function getMaidenName(): ?string
    {
        return null;
    }

    /**
     * Отображаемое имя.
     */
    public function getDisplayName(): ?string
    {
        $displayName = $this->getField('display_name');
        \assert(\is_string($displayName) || null === $displayName, 'Display name must be a string or null');

        return $displayName;
    }

    /**
     * Пол.
     *
     * @return 'male'|'female'|null
     */
    public function getSex(): ?string
    {
        $sex = $this->getField('sex');
        \assert((\is_string($sex) && \in_array($sex, ['male', 'female'], true)) || null === $sex, 'Sex must be a string or null');

        return $sex;
    }

    /**
     * Регалии.
     */
    public function getRegalia(): ?string
    {
        $regalia = $this->getField('regalia');
        \assert(\is_string($regalia) || null === $regalia, 'Regalia must be a string or null');

        return $regalia;
    }

    /**
     * Умер.
     */
    public function isDied(): ?bool
    {
        $isDied = $this->getField('is_died');
        \assert(\is_bool($isDied) || null === $isDied, 'Death status must be a boolean or null');

        return $isDied;
    }

    /**
     * Эл. адрес.
     */
    public function getEmail(): ?string
    {
        $email = $this->getField('email');
        \assert(\is_string($email) || null === $email, 'Email must be a string or null');

        return $email;
    }

    /**
     * Дата рождения.
     */
    public function getBirthday(): ?\DateTime
    {
        $birthday = $this->getField('birthday');
        \assert(\is_string($birthday) || null === $birthday, 'Birthday must be a string or null');

        return null !== $birthday ? new \DateTime($birthday) : null;
    }

    /**
     * URL аватарки (150x150).
     *
     * @example https://avatar.1sept.ru/12121212-3456-7243-2134-432432144221.webp?v=12345
     */
    public function getAvatarUrl(bool $rejectDefaultAvatar = false): ?string
    {
        if ($rejectDefaultAvatar && ($this->isDefaultAvatar() ?? false)) {
            return null;
        }

        $avatar = $this->getField('avatar');
        \assert(\is_string($avatar) || null === $avatar, 'Avatar must be a string or null');

        return $avatar;
    }

    /**
     * URL аватарки определённого размера (<img src="…" width="size" height="size">).
     *
     * @param int  $size            Размер от 1 до 1990 ($size x $size — квадрат)
     * @param int  $ratioMultiplier Множитель разрешения картинки: 1 (по умолчанию), 2 или 3
     * @param bool $addVersion      Использовать версию аватарки для улучшенного кэширования
     */
    public function getAvatarSizeUrl(int $size, int $ratioMultiplier = 1, bool $addVersion = true, string $format = 'webp'): ?string
    {
        $ratio = ($ratioMultiplier > 1) ? '@' . $ratioMultiplier . 'x' : '';
        $url = static::AVATAR_BASE . '/' . $this->getId() . (((bool) $size) ? '.' : '') . $size . $ratio . '.' . $format;

        return $url . ($addVersion ? $this->getAvatarVersionQuery() : '');
    }

    /**
     * URL аватарки для экранов разных разрешений (для <img srcset="…" width="size" height="size">).
     *
     * @param int  $size       Размер от 1 до 1990 ($size x $size — квадрат)
     * @param bool $addVersion Использовать версию аватарки для улучшенного кэширования
     */
    public function getAvatarSetSizeUrl(int $size, bool $addVersion = true, string $format = 'webp'): string
    {
        return $this->getAvatarSizeUrl($size, 1, $addVersion, $format) . ' 1x, '
            . $this->getAvatarSizeUrl($size, 2, $addVersion, $format) . ' 2x, '
            . $this->getAvatarSizeUrl($size, 3, $addVersion, $format) . ' 3x';
    }

    /**
     * URL аватарки c максимальным размером
     *
     * @param bool $addVersion Использовать версию аватарки для улучшенного кэширования
     *
     * @example https://avatar.1sept.ru/12121212-3456-7243-2134-432432144221.max.webp?v=12345
     */
    public function getAvatarMaxUrl(bool $addVersion = false): ?string
    {
        $avatar = $this->getField('avatar_max');
        if (null === $avatar) {
            return null;
        }

        \assert(\is_string($avatar), 'Avatar must be a string or null');

        return $avatar . ($addVersion ? $this->getAvatarVersionQuery() : '');
    }

    /**
     * Версия аватарки.
     *
     * Изменение версии сигнализирует об обновлении аватарки.
     */
    public function getAvatarVersion(): int|string|null
    {
        $version = $this->getField('avatar_version');
        \assert(\is_int($version) || \is_string($version) || null === $version, 'Avatar version must be an integer, string or null');

        return $version;
    }

    /**
     * Является ли аватарка заглушкой.
     */
    public function isDefaultAvatar(): ?bool
    {
        return (bool) $this->getField('avatar_default');
    }

    /**
     * Query строка c версией аватарки (улучшает кэширование).
     *
     * @example ?v=12345;
     */
    public function getAvatarVersionQuery(): string
    {
        $query = '';

        $version = $this->getAvatarVersion();
        if (null !== $version) {
            $query .= '?v=' . $version;
        }

        return $query;
    }

    /**
     * URL публичной страницы профиля.
     *
     * @example https://vk.com/hello
     */
    public function getProfileUrl(): ?string
    {
        $link = $this->getField('link');
        \assert(\is_string($link), 'Link must be a string or null');

        return $link;
    }

    /**
     * Номера телефонов.
     *
     * @return array<int, array<string, string>>|null
     *
     * @example [
     *   [
     *     "canonical" => "+79161234567",
     *     "number" => "+7 (916) 123-45-67",
     *     "type" => "mobile"
     *   ],
     *   …
     * ]
     */
    public function getPhones(): ?array
    {
        $phones = $this->getField('phones');
        \assert(\is_array($phones) || null === $phones, 'Phones must be an array or null');

        /** @var array<int, array<string, string>>|null $phones */
        return $phones;
    }

    /**
     * СНИЛС
     *
     * @example 123-456-789 01
     */
    public function getSnils(): ?string
    {
        $snils = $this->getField('passport.snils');
        \assert(\is_string($snils), 'SNILS must be a string or null');

        return $snils;
    }

    /**
     * Локаль (языковые и др. настройки).
     *
     * @example ru_RU
     */
    public function getLocale(): ?string
    {
        $locale = $this->getField('locale');
        \assert(\is_string($locale), 'Locale must be a string or null');

        return $locale;
    }

    /**
     * Имя временной зоны.
     *
     * @example Europe/Moscow
     */
    public function getTimezone(): ?string
    {
        $timezone = $this->getField('timezone');
        \assert(\is_string($timezone), 'Timezone must be a string or null');

        return $timezone;
    }

    /**
     * ID адреса.
     *
     * @example 12345
     */
    public function getAddressID(): ?int
    {
        $id = $this->getField('address.id');
        \assert(\is_int($id) || null === $id, 'ID must be an integer or null');

        return $id;
    }

    /**
     * ID страны адреса.
     *
     * @example RU
     */
    public function getAddressCountryID(): ?string
    {
        $countryId = $this->getField('address.country_id');
        \assert(\is_string($countryId) || null === $countryId, 'Country ID must be a string or null');

        return $countryId;
    }

    /**
     * ID региона страны адреса.
     *
     * @example MOW
     */
    public function getAddressRegionID(): ?string
    {
        $regionId = $this->getField('address.region_id');
        \assert(\is_string($regionId) || null === $regionId, 'Region ID must be a string or null');

        return $regionId;
    }

    /**
     * Почтовый индекс
     *
     * @example 123456
     */
    public function getAddressPostalcode(): ?string
    {
        $postalCode = $this->getField('address.postal_code');
        \assert(\is_string($postalCode) || null === $postalCode, 'Postal code must be a string or null');

        return $postalCode;
    }

    /**
     * Район.
     *
     * @example Октябрьский район
     */
    public function getAddressArea(): ?string
    {
        $area = $this->getField('address.area');
        \assert(\is_string($area) || null === $area, 'Area must be a string or null');

        return $area;
    }

    /**
     * Город.
     *
     * @example Муром
     */
    public function getAddressCity(): ?string
    {
        $city = $this->getField('address.city');
        \assert(\is_string($city) || null === $city, 'City must be a string or null');

        return $city;
    }

    /**
     * Улица.
     *
     * @example ул. Профсоюзная
     */
    public function getAddressStreet(): ?string
    {
        $street = $this->getField('address.street');
        \assert(\is_string($street) || null === $street, 'Street must be a string or null');

        return $street;
    }

    /**
     * Здание, сооружение, дом, владение, объект незавершенного строительства.
     *
     * @example д. 5
     */
    public function getAddressHouse(): ?string
    {
        $house = $this->getField('address.house');
        \assert(\is_string($house) || null === $house, 'House must be a string or null');

        return $house;
    }

    /**
     * Строение.
     *
     * @example стр. 5
     */
    public function getAddressBuilding(): ?string
    {
        $building = $this->getField('address.building');
        \assert(\is_string($building) || null === $building, 'Building must be a string or null');

        return $building;
    }

    /**
     * Помещение в пределах здания, сооружения (Квартира, офис, помещение и т.д.).
     *
     * @example кв. 1б | оф. 13 | помещ. 17
     */
    public function getAddressFlat(): ?string
    {
        $flat = $this->getField('address.flat');
        \assert(\is_string($flat) || null === $flat, 'Flat must be a string or null');

        return $flat;
    }

    /**
     * До востребования.
     *
     * @example true
     */
    public function isAddressGeneralDelivery(): bool
    {
        return (bool) $this->getField('address.general_delivery');
    }

    /**
     * Абонентский ящик (А/Я).
     *
     * @example а/я 123
     */
    public function getAddressPostalBox(): ?string
    {
        $postalBox = $this->getField('address.postal_box');
        \assert(\is_string($postalBox) || null === $postalBox, 'Postal box must be a string or null');

        return $postalBox;
    }

    /**
     * Организация по адресу.
     *
     * @example Школа №5
     */
    public function getAddressOrganization(): ?string
    {
        $organization = $this->getField('address.organization');
        \assert(\is_string($organization) || null === $organization, 'Organization must be a string or null');

        return $organization;
    }

    /**
     * Почтовый адрес в строку (без индекса).
     *
     * @example ул. Гагарина, д.5, кв. 21, Нижний Новгород
     */
    public function getAddressInline(): ?string
    {
        $addressInline = $this->getField('address.inline');
        \assert(\is_string($addressInline) || null === $addressInline, 'Inline must be a string or null');

        return $addressInline;
    }

    /**
     * ID страны (анкета).
     *
     * @example RU
     */
    public function getLocationCountryID(): ?string
    {
        $countryId = $this->getField('location.country_id');
        \assert(\is_string($countryId) || null === $countryId, 'Country ID must be a string or null');

        return $countryId;
    }

    /**
     * Название страны (анкета).
     *
     * @example Россия
     */
    public function getLocationCountryName(): ?string
    {
        $countryName = $this->getField('location.country_name');
        \assert(\is_string($countryName) || null === $countryName, 'Country name must be a string or null');

        return $countryName;
    }

    /**
     * Название страны по английски (анкета).
     *
     * @example Russia
     */
    public function getLocationCountryNameEnglish(): ?string
    {
        $countryNameEnglish = $this->getField('location.country_name_eng');
        \assert(\is_string($countryNameEnglish) || null === $countryNameEnglish, 'Country name (english) must be a string or null');

        return $countryNameEnglish;
    }

    /**
     * ID региона страны (анкета).
     *
     * @example MOW
     */
    public function getLocationRegionID(): ?string
    {
        $regionId = $this->getField('location.region_id');
        \assert(\is_string($regionId) || null === $regionId, 'Region ID must be a string or null');

        return $regionId;
    }

    /**
     * Название региона страны (анкета).
     *
     * @example Москва
     */
    public function getLocationRegionName(): ?string
    {
        $regionName = $this->getField('location.region_name');
        \assert(\is_string($regionName) || null === $regionName, 'Region name must be a string or null');

        return $regionName;
    }

    /**
     * Название региона страны по английски (анкета).
     *
     * @example Moscow
     */
    public function getLocationRegionNameEnglish(): ?string
    {
        $regionNameEnglish = $this->getField('location.region_name_eng');
        \assert(\is_string($regionNameEnglish) || null === $regionNameEnglish, 'Region name (english) must be a string or null');

        return $regionNameEnglish;
    }

    /**
     * Значение массива (многомерного).
     *
     * @param string $key Ключ поля (например: `email` или `name.first` — вложенность оформляется точкой)
     */
    public static function getFieldFromArray(string $key, mixed $array): mixed
    {
        if ((bool) strpos($key, '.')) { // key.sub_key.sub_sub_key
            [$key, $subKey] = explode('.', $key, 2);

            if (\is_array($array) && isset($array[$key])) {
                return static::getFieldFromArray($subKey, $array[$key]);
            }

            return null;
        }

        if (\is_array($array) && isset($array[$key])) {
            return $array[$key];
        }

        return null;
    }

    /**
     * Элемент массива данных о пользователе.
     *
     * @param string $key Ключ поля (например: email или name.first — вложенность оформляется точкой)
     */
    protected function getField(string $key): mixed
    {
        return static::getFieldFromArray($key, $this->data);
    }
}
