<?php

namespace Makaew\Store\EventHandler;

use Bitrix\Main\Event;
use Bitrix\Main\Data\Cache;

/**
 * Обработчик инвалидации кэша при изменении данных.
 *
 * Автоматически сбрасывает кэш компонентов каталога
 * при добавлении/изменении/удалении элементов инфоблока.
 */
class CacheInvalidationHandler
{
    /** Теги кэша, привязанные к каталогу */
    private const CATALOG_CACHE_TAGS = [
        'iblock_id_' . IBLOCK_CATALOG_ID,
        'catalog_products',
        'catalog_sections',
    ];

    /**
     * Сброс кэша при изменении элемента инфоблока.
     *
     * Привязывается к событиям:
     *   OnAfterIBlockElementAdd
     *   OnAfterIBlockElementUpdate
     *   OnAfterIBlockElementDelete
     */
    public static function onElementChange(array &$arFields): void
    {
        $iblockId = (int) ($arFields['IBLOCK_ID'] ?? 0);

        if ($iblockId !== IBLOCK_CATALOG_ID) {
            return;
        }

        self::clearCatalogCache();
    }

    /**
     * Сброс кэша при изменении раздела.
     *
     * Привязывается к событиям:
     *   OnAfterIBlockSectionAdd
     *   OnAfterIBlockSectionUpdate
     *   OnAfterIBlockSectionDelete
     */
    public static function onSectionChange(array &$arFields): void
    {
        $iblockId = (int) ($arFields['IBLOCK_ID'] ?? 0);

        if ($iblockId !== IBLOCK_CATALOG_ID) {
            return;
        }

        self::clearCatalogCache();
    }

    /**
     * Очистка тегированного кэша каталога.
     */
    private static function clearCatalogCache(): void
    {
        $taggedCache = \Bitrix\Main\Application::getInstance()->getTaggedCache();

        foreach (self::CATALOG_CACHE_TAGS as $tag) {
            $taggedCache->clearByTag($tag);
        }

        // Также очищаем managed cache для каталога
        $cache = Cache::createInstance();
        $cache->cleanDir('/makaew/catalog/');
    }
}
