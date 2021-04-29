<?php

namespace Sept\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * Пользователь Первого сентября
 */
class SeptemberFirstUser implements ResourceOwnerInterface
{
    const AVATAR_BASE = 'https://avatar.1sept.ru';

    /**
     * @var array Массив с данными о пользователе
     */
    protected $data;

    public function __construct(array $response)
    {
        $this->data = $response;
    }

    /**
     * Массив с данными о пользователе
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * ID пользователя (UUID)
     *
     * @return string
     * @example '1cc1632f-2349-4d00-8302-5c4c188469cc'
     */
    public function getId(): string
    {
        return $this->getField('id');
    }

    /**
     * Устаревшие ID пользователя (UUID)
     * (остаются после объединения уч. записей)
     *
     * @return array<string>
     */
    public function getIdAlt(): array
    {
        return $this->getField('id_alt') ?? [];
    }

    /**
     * Фамилия
     *
     * @var string|null
     */
    public function getLastName(): ?string
    {
        return $this->getField('personal_name.surname');
    }

    /**
     * Имя
     *
     * @var string|null
     */
    public function getFirstName(): ?string
    {
        return $this->getField('personal_name.name');
    }

    /**
     * Отчество
     *
     * @var string|null
     */
    public function getMiddleName(): ?string
    {
        return $this->getField('personal_name.patronymic');
    }

    /**
     * Девичья фамилия
     *
     * @return string|null
     */
    public function getMaidenName(): ?string
    {
        return null;
    }

    /**
     * Отображаемое имя
     *
     * @return string|null
     */
    public function getDisplayName(): ?string
    {
        return $this->getField('display_name');
    }

    /**
     * Пол
     *
     * @return 'male'|'female'|null
     */
    public function getSex(): ?string
    {
        return $this->getField('sex');
    }

    /**
     * Умер
     *
     * @return bool|null
     */
    public function isDied(): ?bool
    {
        return $this->getField('is_died');
    }

    /**
     * Эл. адрес
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->getField('email');
    }

    /**
     * Дата рождения
     *
     * @return \DateTime|null
     */
    public function getBirthday(): ?\DateTime
    {
        return !empty($this->data['birthday']) ? new \DateTime($this->data['birthday']) : null;
    }

    /**
     * URL аватарки (150x150)
     *
     * @param bool $addVersion Использовать версию аватарки для улучшенного кэширования
     * @return string|null
     */
    public function getAvatarUrl(bool $addVersion = true): ?string
    {
        return $this->getField('avatar') . ($addVersion ? $this->getAvatarVersionQuery() : '');
    }

    /**
     * URL аватарки определённого размера (<img src="…" width="size" height="size">)
     *
     * @param int $size Размер от 1 до 1990 ($size x $size — квадрат)
     * @param int $ratioMultiplier Множитель разрешения картинки: 1 (по умолчанию), 2 или 3
     * @param bool $addVersion Использовать версию аватарки для улучшенного кэширования
     * @return string|null
     */
    public function getAvatarSizeUrl(int $size, int $ratioMultiplier = 1, bool $addVersion = true): ?string
    {
        $ratio = ($ratioMultiplier > 1) ? '@' . $ratioMultiplier . 'x' : '';
        $url = static::AVATAR_BASE .'/'. $this->getId() . ($size ? '.' : '') . $size . $ratio . '.jpeg';
        return $url . ($addVersion ? $this->getAvatarVersionQuery() : '');
    }

    /**
     * URL аватарки для экранов разных разрешений (<img srcset="…" width="size" height="size">)
     *
     * @param int $size Размер от 1 до 1990 ($size x $size — квадрат)
     * @param bool $addVersion Использовать версию аватарки для улучшенного кэширования
     * @return string
     */
    public function getAvatarSetSizeUrl(int $size, bool $addVersion = true): string
    {
        return $this->getAvatarSizeUrl($size, 1, $addVersion) . ' 1x, '
             . $this->getAvatarSizeUrl($size, 2, $addVersion) . ' 2x, '
             . $this->getAvatarSizeUrl($size, 3, $addVersion) . ' 3x';
    }

    /**
     * URL аватарки c максимальным размером
     *
     * @param bool $useVersion Использовать версию аватарки для улучшенного кэширования
     * @return string|null
     */
    public function getAvatarMaxUrl(bool $addVersion = false): ?string
    {
        return $this->getField('avatar_max') . ($addVersion ? $this->getAvatarVersionQuery() : '');
    }

    /**
     * Версия аватарки
     * Изменение версии сигнализирует об обновлении аватарки.
     *
     * @return int | null
     */

    public function getAvatarVersion(): ?int
    {
        return $this->getField('avatar_version');
    }

    /**
     * Является ли аватарка заглушкой
     *
     * @return boolean
     */
    public function isDefaultAvatar(): bool
    {
        return (bool) $this->getField('avatar_default');
    }

    /**
     * Query cтрока c версией аватарки (улучшает кэширование)
     *
     * @return string
     */
    public function getAvatarVersionQuery(): string
    {
        $url = '';
        if ($version = $this->getField('avatar_version')) {
            $url .= '?v=' . $version;
        }
        return $url;
    }

    /**
     * URL публичной страницы профиля
     *
     * @return string|null
     */
    public function getProfileUrl(): ?string
    {
        return $this->getField('link');
    }

    /**
     * Номера телефонов
     *
     * @return array|null
     */
    public function getPhones(): ?array
    {
        return $this->getField('phones');
    }

    /**
     * СНИЛС
     *
     * @return string|null
     */
    public function getSnils(): ?string
    {
        return $this->getField('passport.snils');
    }

    /**
     * Локаль (языковые и др. настройки)
     *
     * @return string|null
     * @example ru_RU
     */
    public function getLocale(): ?string
    {
        return $this->getField('locale');
    }

    /**
     * Имя временной зоны
     *
     * @return string|null
     * @example Europe/Moscow
     */
    public function getTimezone(): ?string
    {
        return $this->getField('timezone');
    }

    /**
     * Почтовый адрес в строку
     *
     * @return string|null
     * @example ул. Гагарина, д.5, кв. 21, Нижний Новгород
     */
    public function getAddressInline(): ?string
    {
        return $this->getField('address.inline');
    }

    /**
     * Почтовый индекс
     *
     * @return string|null
     * @example 123456
     */
    public function getAddressPostalcode(): ?string
    {
        return $this->getField('address.postal_code');
    }

    /**
     * Элемент массива данных о пользователе
     *
     * @param string $key Ключ поля (например: email или name.first — вложенность оформляется точкой)
     * @return mixed|null
     */
    protected function getField(string $key)
    {
        return static::getFieldFromArray($key, $this->data);
    }

    /**
     * Значение массива (многомерного)
     *
     * @param string $key Ключ поля (например: `email` или `name.first` — вложенность оформляется точкой)
     * @return mixed|null
     */
    public static function getFieldFromArray(string $key, ?array $array)
    {
        if (strpos($key, '.')) { // key.subKey.subSubKey
            list ($key, $subKey) = explode('.', $key, 2);
            return isset($array[$key]) ? static::getFieldFromArray($subKey, $array[$key]) : null;
        }

        return isset($array[$key]) ? $array[$key] : null;
    }
}
