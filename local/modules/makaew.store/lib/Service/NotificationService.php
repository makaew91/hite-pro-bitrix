<?php

namespace Makaew\Store\Service;

use Bitrix\Main\Mail\Event as MailEvent;

/**
 * Сервис отправки уведомлений.
 *
 * Централизует отправку email-уведомлений через
 * почтовые события Битрикс. Предоставляет типизированные
 * методы для каждого типа уведомления.
 */
class NotificationService
{
    /**
     * Уведомление о новом заказе (менеджеру).
     */
    public static function notifyNewOrder(int $orderId, float $sum, string $customerName): void
    {
        MailEvent::send([
            'EVENT_NAME' => 'MAKAEW_NEW_ORDER',
            'LID' => SITE_ID,
            'C_FIELDS' => [
                'ORDER_ID' => $orderId,
                'ORDER_SUM' => number_format($sum, 0, '.', ' ') . ' ₽',
                'CUSTOMER_NAME' => $customerName,
                'ORDER_URL' => '/bitrix/admin/sale_order_view.php?ID=' . $orderId,
            ],
        ]);
    }

    /**
     * Уведомление об изменении статуса заказа (покупателю).
     */
    public static function notifyOrderStatusChange(
        int $orderId,
        string $newStatus,
        string $customerEmail,
    ): void {
        $statusNames = [
            'N' => 'Принят',
            'P' => 'Оплачен',
            'F' => 'Выполнен',
            'D' => 'Отменён',
        ];

        MailEvent::send([
            'EVENT_NAME' => 'MAKAEW_ORDER_STATUS',
            'LID' => SITE_ID,
            'C_FIELDS' => [
                'ORDER_ID' => $orderId,
                'STATUS_NAME' => $statusNames[$newStatus] ?? $newStatus,
                'EMAIL' => $customerEmail,
            ],
        ]);
    }

    /**
     * Уведомление о возврате товара в наличие (подписчикам).
     */
    public static function notifyBackInStock(int $productId, string $productName): void
    {
        MailEvent::send([
            'EVENT_NAME' => 'MAKAEW_BACK_IN_STOCK',
            'LID' => SITE_ID,
            'C_FIELDS' => [
                'PRODUCT_ID' => $productId,
                'PRODUCT_NAME' => $productName,
                'PRODUCT_URL' => '/catalog/?ELEMENT_ID=' . $productId,
            ],
        ]);
    }

    /**
     * Административное уведомление (произвольное).
     */
    public static function notifyAdmin(string $subject, string $message): void
    {
        MailEvent::send([
            'EVENT_NAME' => 'MAKAEW_ADMIN_NOTIFICATION',
            'LID' => SITE_ID,
            'C_FIELDS' => [
                'SUBJECT' => $subject,
                'MESSAGE' => $message,
            ],
        ]);
    }
}
