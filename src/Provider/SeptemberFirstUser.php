<?php

declare(strict_types=1);

namespace Sept\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * Пользователь Первого сентября.
 *
 * Политика типов едина для всех геттеров: значение неожиданного типа
 * деградирует в null (без исключений и без зависимости от zend.assertions).
 */
class SeptemberFirstUser implements ResourceOwnerInterface
{
    public const string AVATAR_BASE = 'https://avatar.1sept.ru';

    /**
     * @var string Формат даты рождения в ответе API
     */
    public const string BIRTHDAY_FORMAT = '!Y-m-d';

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
        $id = $this->getStringField('id');
        \assert(null !== $id && '' !== $id, 'ID must be a non-empty string');

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
        $altIds = $this->getArrayField('id_alt') ?? [];

        return array_values(array_filter($altIds, static fn ($id): bool => \is_string($id) && '' !== $id));
    }

    /**
     * Фамилия.
     */
    public function getLastName(): ?string
    {
        return $this->getStringField('personal_name.surname');
    }

    /**
     * Имя.
     */
    public function getFirstName(): ?string
    {
        return $this->getStringField('personal_name.name');
    }

    /**
     * Отчество.
     */
    public function getMiddleName(): ?string
    {
        return $this->getStringField('personal_name.patronymic');
    }

    /**
     * Девичья фамилия
     * (текущая версия API не передаёт это поле, поэтому всегда возвращается null).
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
        return $this->getStringField('display_name');
    }

    /**
     * Пол.
     *
     * @return 'male'|'female'|null
     */
    public function getSex(): ?string
    {
        $sex = $this->getStringField('sex');

        return \in_array($sex, ['male', 'female'], true) ? $sex : null;
    }

    /**
     * Регалии.
     */
    public function getRegalia(): ?string
    {
        return $this->getStringField('regalia');
    }

    /**
     * Умер.
     */
    public function isDied(): ?bool
    {
        return $this->getBoolField('is_died');
    }

    /**
     * Эл. адрес.
     */
    public function getEmail(): ?string
    {
        return $this->getStringField('email');
    }

    /**
     * Дата рождения.
     *
     * Строка, не являющаяся датой формата BIRTHDAY_FORMAT
     * (в том числе относительные даты вроде `now`), даёт null.
     */
    public function getBirthday(): ?\DateTime
    {
        $birthday = $this->getStringField('birthday');
        if (null === $birthday || '' === $birthday) {
            return null;
        }

        $date = \DateTime::createFromFormat(static::BIRTHDAY_FORMAT, $birthday);
        $errors = \DateTime::getLastErrors();

        if (false === $date || (\is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date;
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

        return $this->getStringField('avatar');
    }

    /**
     * URL аватарки определённого размера (<img src="…" width="size" height="size">).
     *
     * @param int  $size            Размер от 1 до 1990 ($size x $size — квадрат); при $size <= 0 сегмент размера (и множитель) опускается
     * @param int  $ratioMultiplier Множитель разрешения картинки: 1 (по умолчанию), 2 или 3
     * @param bool $addVersion      Использовать версию аватарки для улучшенного кэширования
     */
    public function getAvatarSizeUrl(int $size, int $ratioMultiplier = 1, bool $addVersion = true, string $format = 'webp'): ?string
    {
        return static::AVATAR_BASE . '/' . $this->getId()
            . static::avatarSizeSegment($size, $ratioMultiplier) . '.' . $format
            . ($addVersion ? $this->getAvatarVersionQuery() : '');
    }

    /**
     * URL аватарки для экранов разных разрешений (для <img srcset="…" width="size" height="size">).
     *
     * @param int  $size       Размер от 1 до 1990 ($size x $size — квадрат)
     * @param bool $addVersion Использовать версию аватарки для улучшенного кэширования
     */
    public function getAvatarSetSizeUrl(int $size, bool $addVersion = true, string $format = 'webp'): string
    {
        $prefix = static::AVATAR_BASE . '/' . $this->getId();
        $suffix = '.' . $format . ($addVersion ? $this->getAvatarVersionQuery() : '');

        $set = [];
        foreach ([1, 2, 3] as $ratio) {
            $set[] = $prefix . static::avatarSizeSegment($size, $ratio) . $suffix . ' ' . $ratio . 'x';
        }

        return implode(', ', $set);
    }

    /**
     * URL аватарки c максимальным размером.
     *
     * @param bool $addVersion Использовать версию аватарки для улучшенного кэширования
     *
     * @example https://avatar.1sept.ru/12121212-3456-7243-2134-432432144221.max.webp?v=12345
     */
    public function getAvatarMaxUrl(bool $addVersion = false): ?string
    {
        $avatar = $this->getStringField('avatar_max');
        if (null === $avatar || '' === $avatar) {
            return null;
        }

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

        return \is_int($version) || \is_string($version) ? $version : null;
    }

    /**
     * Является ли аватарка заглушкой
     * (null — API не передал признак).
     */
    public function isDefaultAvatar(): ?bool
    {
        return $this->getBoolField('avatar_default');
    }

    /**
     * Query строка c версией аватарки (улучшает кэширование).
     *
     * @example ?v=12345;
     */
    public function getAvatarVersionQuery(): string
    {
        $version = $this->getAvatarVersion();

        return null !== $version ? '?v=' . rawurlencode((string) $version) : '';
    }

    /**
     * URL публичной страницы профиля.
     *
     * @example https://vk.com/hello
     */
    public function getProfileUrl(): ?string
    {
        return $this->getStringField('link');
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
        // @phpstan-ignore return.type (API гарантирует форму списка телефонов)
        return $this->getArrayField('phones');
    }

    /**
     * СНИЛС
     *
     * @example 123-456-789 01
     */
    public function getSnils(): ?string
    {
        return $this->getStringField('passport.snils');
    }

    /**
     * Локаль (языковые и др. настройки).
     *
     * @example ru_RU
     */
    public function getLocale(): ?string
    {
        return $this->getStringField('locale');
    }

    /**
     * Имя временной зоны.
     *
     * @example Europe/Moscow
     */
    public function getTimezone(): ?string
    {
        return $this->getStringField('timezone');
    }

    /**
     * ID адреса.
     *
     * @example 12345
     */
    public function getAddressID(): ?int
    {
        return $this->getIntField('address.id');
    }

    /**
     * ID страны адреса.
     *
     * @example RU
     */
    public function getAddressCountryID(): ?string
    {
        return $this->getStringField('address.country_id');
    }

    /**
     * ID региона страны адреса.
     *
     * @example MOW
     */
    public function getAddressRegionID(): ?string
    {
        return $this->getStringField('address.region_id');
    }

    /**
     * Почтовый индекс
     *
     * @example 123456
     */
    public function getAddressPostalcode(): ?string
    {
        return $this->getStringField('address.postal_code');
    }

    /**
     * Район.
     *
     * @example Октябрьский район
     */
    public function getAddressArea(): ?string
    {
        return $this->getStringField('address.area');
    }

    /**
     * Город.
     *
     * @example Муром
     */
    public function getAddressCity(): ?string
    {
        return $this->getStringField('address.city');
    }

    /**
     * Улица.
     *
     * @example ул. Профсоюзная
     */
    public function getAddressStreet(): ?string
    {
        return $this->getStringField('address.street');
    }

    /**
     * Здание, сооружение, дом, владение, объект незавершенного строительства.
     *
     * @example д. 5
     */
    public function getAddressHouse(): ?string
    {
        return $this->getStringField('address.house');
    }

    /**
     * Строение.
     *
     * @example стр. 5
     */
    public function getAddressBuilding(): ?string
    {
        return $this->getStringField('address.building');
    }

    /**
     * Помещение в пределах здания, сооружения (Квартира, офис, помещение и т.д.).
     *
     * @example кв. 1б | оф. 13 | помещ. 17
     */
    public function getAddressFlat(): ?string
    {
        return $this->getStringField('address.flat');
    }

    /**
     * До востребования.
     *
     * @example true
     */
    public function isAddressGeneralDelivery(): bool
    {
        return $this->getBoolField('address.general_delivery') ?? false;
    }

    /**
     * Абонентский ящик (А/Я).
     *
     * @example а/я 123
     */
    public function getAddressPostalBox(): ?string
    {
        return $this->getStringField('address.postal_box');
    }

    /**
     * Организация по адресу.
     *
     * @example Школа №5
     */
    public function getAddressOrganization(): ?string
    {
        return $this->getStringField('address.organization');
    }

    /**
     * Почтовый адрес в строку (без индекса).
     *
     * @example ул. Гагарина, д.5, кв. 21, Нижний Новгород
     */
    public function getAddressInline(): ?string
    {
        return $this->getStringField('address.inline');
    }

    /**
     * ID страны (анкета).
     *
     * @example RU
     */
    public function getLocationCountryID(): ?string
    {
        return $this->getStringField('location.country_id');
    }

    /**
     * Название страны (анкета).
     *
     * @example Россия
     */
    public function getLocationCountryName(): ?string
    {
        return $this->getStringField('location.country_name');
    }

    /**
     * Название страны по английски (анкета).
     *
     * @example Russia
     */
    public function getLocationCountryNameEnglish(): ?string
    {
        return $this->getStringField('location.country_name_eng');
    }

    /**
     * ID региона страны (анкета).
     *
     * @example MOW
     */
    public function getLocationRegionID(): ?string
    {
        return $this->getStringField('location.region_id');
    }

    /**
     * Название региона страны (анкета).
     *
     * @example Москва
     */
    public function getLocationRegionName(): ?string
    {
        return $this->getStringField('location.region_name');
    }

    /**
     * Название региона страны по английски (анкета).
     *
     * @example Moscow
     */
    public function getLocationRegionNameEnglish(): ?string
    {
        return $this->getStringField('location.region_name_eng');
    }

    /**
     * Значение массива (многомерного).
     *
     * @param string $key Ключ поля (например: `email` или `name.first` — вложенность оформляется точкой)
     */
    public static function getFieldFromArray(string $key, mixed $array): mixed
    {
        $value = $array;
        foreach (explode('.', $key) as $part) {
            if (!\is_array($value) || !isset($value[$part])) {
                return null;
            }

            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Сегмент размера аватарки (`.150`, `.150@2x` или пустая строка при $size <= 0).
     */
    protected static function avatarSizeSegment(int $size, int $ratioMultiplier): string
    {
        if ($size <= 0) {
            return '';
        }

        return '.' . $size . (($ratioMultiplier > 1) ? '@' . $ratioMultiplier . 'x' : '');
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

    /**
     * Строковое поле: строка либо null.
     */
    protected function getStringField(string $key): ?string
    {
        $value = $this->getField($key);

        return \is_string($value) ? $value : null;
    }

    /**
     * Целочисленное поле: int либо null (числовая строка приводится к int).
     */
    protected function getIntField(string $key): ?int
    {
        $value = $this->getField($key);
        if (\is_int($value)) {
            return $value;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Булево поле: null, если поле отсутствует, иначе приведение к bool.
     */
    protected function getBoolField(string $key): ?bool
    {
        $value = $this->getField($key);

        return null === $value ? null : (bool) $value;
    }

    /**
     * Поле-массив: массив либо null.
     *
     * @return mixed[]|null
     */
    protected function getArrayField(string $key): ?array
    {
        $value = $this->getField($key);

        return \is_array($value) ? $value : null;
    }
}
