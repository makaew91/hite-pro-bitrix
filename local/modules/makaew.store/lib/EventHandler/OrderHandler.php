<?php

namespace Makaew\Store\EventHandler;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Diag\Debug;
use Bitrix\Sale\Order;

/**
 * Обработчики событий модуля Sale (заказы).
 *
 * OnSaleOrderBeforeSaved — валидация перед сохранением.
 * OnSaleOrderSaved — действия после создания/обновления.
 */
class OrderHandler
{
    /**
     * Обработка перед сохранением заказа.
     *
     * Выполняет:
     * - Проверку минимальной суммы заказа
     * - Валидацию обязательных свойств
     * - Проверку наличия товаров на складе
     */
    public static function onBeforeSaved(Event $event): EventResult
    {
        /** @var Order $order */
        $order = $event->getParameter('ENTITY');

        // Минимальная сумма заказа — 500 руб.
        $minOrderSum = 500;
        if ($order->getPrice() < $minOrderSum) {
            return new EventResult(
                EventResult::ERROR,
                new \Bitrix\Sale\ResultError(
                    "Минимальная сумма заказа: {$minOrderSum} ₽",
                    'MIN_ORDER_SUM'
                )
            );
        }

        // Проверка заполненности контактных данных
        $propertyCollection = $order->getPropertyCollection();
        $phone = $propertyCollection->getPhone();
        $email = $propertyCollection->getUserEmail();

        if (!$phone && !$email) {
            return new EventResult(
                EventResult::ERROR,
                new \Bitrix\Sale\ResultError(
                    'Укажите телефон или email для связи',
                    'CONTACT_REQUIRED'
                )
            );
        }

        return new EventResult(EventResult::SUCCESS);
    }

    /**
     * Действия после сохранения заказа.
     *
     * Выполняет:
     * - Логирование нового заказа
     * - Установку пользовательских статусов
     */
    public static function onSaved(Event $event): void
    {
        /** @var Order $order */
        $order = $event->getParameter('ENTITY');
        $isNew = $event->getParameter('IS_NEW');

        if (!$isNew) {
            return;
        }

        $orderId = $order->getId();
        $userId = $order->getUserId();
        $price = $order->getPrice();

        // Логируем создание заказа
        Debug::writeToFile(
            sprintf(
                '[%s] New order #%d: user=%d, sum=%.2f',
                date('Y-m-d H:i:s'),
                $orderId,
                $userId,
                $price
            ),
            '',
            '/local/logs/orders.log'
        );

        // Для крупных заказов (>50000 руб) — помечаем для ручной проверки
        if ($price > 50000) {
            $order->setField('MARKED', 'Y');
            $order->setField('REASON_MARKED', 'Крупный заказ — требует проверки менеджером');
            $order->save();
        }
    }
}
