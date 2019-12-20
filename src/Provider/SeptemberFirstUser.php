<?php

namespace Sept\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class SeptemberFirstUser implements ResourceOwnerInterface
{
    /**
     * Массив с данными о пользователе
     */
    protected $data;

    /**
     * Конструктор
     */
    public function __construct(array $response)
    {
        $this->data = $response;
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
     * Данные о пользователе в виде массива
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * ID пользователя
     */
    public function getId(): ?string
    {
        return $this->getField('id');
    }

    /**
     * {@inheritdoc}
     */
    public function getLastName(): ?string
    {
        return $this->getField('personal_name.surname');
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstName(): ?string
    {
        return $this->getField('personal_name.name');
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddleName(): ?string
    {
        return $this->getField('personal_name.patronymic');
    }

    /**
     * Девичья фамилия пользователя (только для женского пола)
     *
     * @return string|null
     */
    public function getMaidenName(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): ?string
    {
        return $this->getField('display_name');
    }

    /**
     * {@inheritdoc}
     */
    public function getSex(): ?string
    {
        return $this->getField('sex');
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail(): ?string
    {
        return $this->getField('email');
    }

    /**
     * {@inheritdoc}
     */
    public function getBirthday(): ?\DateTime
    {
        return !empty($this->data['birthday']) ? new \DateTime($this->data['birthday']) : null;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getProfileUrl(): ?string
    {
        return $this->getField('profile_url');
    }

    /**
     * Номер телефона
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return null;
    }

    /**
     * Локаль
     *
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return null;
    }

    /**
     * Имя временной зоны (от -24 до 24)
     * @example Europe/Moscow
     *
     * @return string|null
     */
    public function getTimezone(): ?string
    {
        return $this->getField('timezone');
    }
}
