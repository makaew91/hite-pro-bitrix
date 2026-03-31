<?php

namespace Makaew\Store\Api\Middleware;

use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

/**
 * Middleware аутентификации для REST API.
 *
 * Проверяет авторизацию пользователя.
 * Поддерживает Bearer-токен и сессионную авторизацию.
 *
 * Использование в контроллере:
 *   'prefilters' => [new AuthMiddleware()]
 */
class AuthMiddleware extends Base
{
    /** @var bool Требовать авторизацию */
    private bool $required;

    public function __construct(bool $required = true)
    {
        $this->required = $required;
        parent::__construct();
    }

    public function onBeforeAction(Event $event): ?EventResult
    {
        global $USER;

        // Проверяем Bearer-токен в заголовке
        $authHeader = $this->getAction()
            ->getController()
            ->getRequest()
            ->getHeader('Authorization');

        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);

            if ($this->validateToken($token)) {
                return null; // Авторизация пройдена
            }

            $this->addError(new Error('Invalid token', 'INVALID_TOKEN'));

            return new EventResult(EventResult::ERROR);
        }

        // Fallback на сессионную авторизацию Битрикс
        if ($USER && $USER->IsAuthorized()) {
            return null;
        }

        if ($this->required) {
            $this->addError(new Error('Authentication required', 'AUTH_REQUIRED'));

            return new EventResult(EventResult::ERROR);
        }

        return null;
    }

    /**
     * Валидация Bearer-токена.
     *
     * В реальном проекте здесь проверка JWT или API-ключа из БД.
     * Для демо — проверка наличия пользователя по токену.
     */
    private function validateToken(string $token): bool
    {
        // Ищем пользователя по хранимому токену (UF_API_TOKEN)
        $user = \CUser::GetList(
            $by = 'ID',
            $order = 'ASC',
            ['UF_API_TOKEN' => $token, 'ACTIVE' => 'Y'],
            ['FIELDS' => ['ID']]
        )->Fetch();

        if ($user) {
            // Авторизуем пользователя в текущей сессии
            global $USER;
            $USER->Authorize($user['ID']);

            return true;
        }

        return false;
    }
}
