<?php

namespace Makaew\Store\Agent;

use Bitrix\Main\Loader;
use Bitrix\Sale\Order;

/**
 * Агент уведомлений о необработанных заказах.
 *
 * Запускается по расписанию (каждые 30 мин).
 * Проверяет заказы в статусе "N" (новый) старше 1 часа
 * и отправляет напоминание менеджерам.
 *
 * Регистрация агента:
 * CAgent::AddAgent(
 *     '\Makaew\Store\Agent\OrderNotificationAgent::run();',
 *     'makaew.store',
 *     'N',          // не периодический (сам себя перерегистрирует)
 *     1800,         // интервал 30 минут
 *     '',
 *     'Y',
 *     ''
 * );
 */
class OrderNotificationAgent
{
    /** Статус «Новый заказ» */
    private const STATUS_NEW = 'N';

    /** Время без обработки для срабатывания (секунды) */
    private const UNPROCESSED_THRESHOLD = 3600;

    /** Почтовое событие для уведомления */
    private const MAIL_EVENT_TYPE = 'MAKAEW_ORDER_UNPROCESSED';

    /**
     * Точка входа агента.
     *
     * @return string Имя метода для повторного вызова
     */
    public static function run(): string
    {
        if (!Loader::includeModule('sale')) {
            return static::class . '::run();';
        }

        $unprocessedOrders = self::getUnprocessedOrders();

        if (!empty($unprocessedOrders)) {
            self::notifyManagers($unprocessedOrders);
        }

        return static::class . '::run();';
    }

    /**
     * Получить необработанные заказы старше порогового времени.
     */
    private static function getUnprocessedOrders(): array
    {
        $thresholdDate = new \Bitrix\Main\Type\DateTime();
        $thresholdDate->add('-' . self::UNPROCESSED_THRESHOLD . ' seconds');

        $orders = [];

        $dbOrders = Order::getList([
            'filter' => [
                '=STATUS_ID' => self::STATUS_NEW,
                '<DATE_INSERT' => $thresholdDate,
            ],
            'select' => ['ID', 'DATE_INSERT', 'PRICE', 'CURRENCY', 'USER_ID'],
            'order' => ['DATE_INSERT' => 'ASC'],
            'limit' => 50,
        ]);

        while ($order = $dbOrders->fetch()) {
            $orders[] = [
                'ID' => $order['ID'],
                'DATE' => $order['DATE_INSERT']->format('d.m.Y H:i'),
                'PRICE' => number_format($order['PRICE'], 0, '.', ' '),
                'CURRENCY' => $order['CURRENCY'],
                'USER_ID' => $order['USER_ID'],
            ];
        }

        return $orders;
    }

    /**
     * Отправить уведомление менеджерам.
     */
    private static function notifyManagers(array $orders): void
    {
        $orderList = '';
        foreach ($orders as $order) {
            $orderList .= sprintf(
                "- Заказ #%d от %s на сумму %s %s\n",
                $order['ID'],
                $order['DATE'],
                $order['PRICE'],
                $order['CURRENCY']
            );
        }

        $fields = [
            'COUNT' => count($orders),
            'ORDER_LIST' => $orderList,
            'ADMIN_URL' => $_SERVER['SERVER_NAME'] . '/bitrix/admin/sale_order.php',
        ];

        \CEvent::Send(self::MAIL_EVENT_TYPE, SITE_ID, $fields);
    }
}
