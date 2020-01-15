<?php

namespace Sept\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * Пользователь Первого сентября
 */
class SeptemberFirstUser implements ResourceOwnerInterface
{
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
     * URL картинки
     *
     * @param bool $rejectEmptyAvatar Если true, то пустые аватарки (заглушки) не отдаются
     * @return string|null
     */
    public function getAvatarUrl(bool $rejectEmptyAvatar = false): ?string
    {
        if (empty($this->data['avatar']) or ((!isset($this->data['has_avatar']) or !$this->data['has_avatar']) and $rejectEmptyAvatar)) {
            return null;
        }

        return $this->getField('avatar');
    }

    /**
     * Адрес аватарки 50x50
     *
     * @param bool $rejectEmptyAvatar Если true, то пустые аватарки (заглушки) не отдаются
     * @return string|null
     */
    public function getAvatar50Url(bool $rejectEmptyAvatar = false): ?string
    {
        return $this->getAvatarUrl($rejectEmptyAvatar);
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
     * Номер телефона
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->getField('phone');
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
