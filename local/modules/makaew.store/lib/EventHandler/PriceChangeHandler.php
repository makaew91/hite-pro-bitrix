<?php

namespace Makaew\Store\EventHandler;

use Bitrix\Main\Event;
use Bitrix\Main\Diag\Debug;
use Bitrix\Catalog\PriceTable;

/**
 * Обработчик изменения цен товаров.
 *
 * Логирует все изменения цен для аудита и аналитики.
 * Подключается к событию OnPriceUpdate модуля catalog.
 */
class PriceChangeHandler
{
    /** @var string Путь к файлу лога */
    private const LOG_FILE = '/local/logs/price_changes.log';

    /**
     * Обработка изменения цены.
     *
     * @param Event $event Событие содержит FIELDS и ID
     */
    public static function onPriceUpdate(Event $event): void
    {
        $id = $event->getParameter('id');
        $fields = $event->getParameter('fields');

        if (empty($fields['PRICE'])) {
            return;
        }

        // Получаем предыдущее значение
        $oldPrice = PriceTable::getList([
            'filter' => ['=ID' => $id],
            'select' => ['PRICE', 'PRODUCT_ID', 'CATALOG_GROUP_ID'],
        ])->fetch();

        if (!$oldPrice) {
            return;
        }

        $newPrice = (float) $fields['PRICE'];
        $prevPrice = (float) $oldPrice['PRICE'];

        // Логируем только реальные изменения
        if (abs($newPrice - $prevPrice) < 0.01) {
            return;
        }

        $changePercent = $prevPrice > 0
            ? round(($newPrice - $prevPrice) / $prevPrice * 100, 1)
            : 0;

        $direction = $newPrice > $prevPrice ? '↑' : '↓';

        $logEntry = sprintf(
            "[%s] Product #%d, PriceType #%d: %.2f → %.2f (%s%s%%) | User: %d",
            date('Y-m-d H:i:s'),
            $oldPrice['PRODUCT_ID'],
            $oldPrice['CATALOG_GROUP_ID'],
            $prevPrice,
            $newPrice,
            $direction,
            abs($changePercent),
            $GLOBALS['USER']?->GetID() ?? 0
        );

        Debug::writeToFile($logEntry, '', self::LOG_FILE);

        // Оповещение при резком изменении (>20%)
        if (abs($changePercent) > 20) {
            self::notifyAboutSignificantChange(
                (int) $oldPrice['PRODUCT_ID'],
                $prevPrice,
                $newPrice,
                $changePercent
            );
        }
    }

    /**
     * Уведомить о значительном изменении цены.
     */
    private static function notifyAboutSignificantChange(
        int $productId,
        float $oldPrice,
        float $newPrice,
        float $changePercent,
    ): void {
        $fields = [
            'PRODUCT_ID' => $productId,
            'OLD_PRICE' => number_format($oldPrice, 2, '.', ' '),
            'NEW_PRICE' => number_format($newPrice, 2, '.', ' '),
            'CHANGE_PERCENT' => abs($changePercent) . '%',
            'ADMIN_URL' => '/bitrix/admin/cat_product_edit.php?ID=' . $productId,
        ];

        \CEvent::Send('MAKAEW_PRICE_CHANGE_ALERT', SITE_ID, $fields);
    }
}
