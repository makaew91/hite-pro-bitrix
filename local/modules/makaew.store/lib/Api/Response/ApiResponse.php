<?php

namespace Makaew\Store\Api\Response;

use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\Web\Json as JsonEncoder;

/**
 * Единый формат API-ответа.
 *
 * Оборачивает данные в стандартную структуру:
 * {
 *   "success": true|false,
 *   "data": {...} | null,
 *   "errors": [{code, message}] | [],
 *   "meta": {timestamp, version}
 * }
 */
class ApiResponse
{
    private const API_VERSION = '1.0';

    /**
     * Успешный ответ.
     */
    public static function success(mixed $data = null, int $statusCode = 200): Json
    {
        $response = new Json([
            'success' => true,
            'data' => $data,
            'errors' => [],
            'meta' => self::getMeta(),
        ]);

        $response->setStatus($statusCode);

        return $response;
    }

    /**
     * Ответ с ошибкой.
     *
     * @param string $message Текст ошибки
     * @param string $code    Код ошибки
     * @param int    $statusCode HTTP-статус
     */
    public static function error(
        string $message,
        string $code = 'ERROR',
        int $statusCode = 400,
    ): Json {
        $response = new Json([
            'success' => false,
            'data' => null,
            'errors' => [
                ['code' => $code, 'message' => $message],
            ],
            'meta' => self::getMeta(),
        ]);

        $response->setStatus($statusCode);

        return $response;
    }

    /**
     * Ответ с несколькими ошибками (валидация).
     *
     * @param array $errors Массив [['code' => '...', 'message' => '...'], ...]
     */
    public static function validationError(array $errors): Json
    {
        $response = new Json([
            'success' => false,
            'data' => null,
            'errors' => $errors,
            'meta' => self::getMeta(),
        ]);

        $response->setStatus(422);

        return $response;
    }

    /**
     * Ответ с пагинацией.
     */
    public static function paginated(array $items, array $pagination): Json
    {
        return self::success([
            'items' => $items,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Метаданные ответа.
     */
    private static function getMeta(): array
    {
        return [
            'timestamp' => date('c'),
            'version' => self::API_VERSION,
        ];
    }
}
