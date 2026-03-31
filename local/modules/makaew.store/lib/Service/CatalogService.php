<?php

namespace Makaew\Store\Service;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;

/**
 * Сервис для работы с каталогом товаров.
 *
 * Инкапсулирует логику выборки из инфоблоков,
 * обеспечивая единый интерфейс для компонентов и API.
 */
class CatalogService
{
    public function __construct()
    {
        Loader::includeModule('iblock');
    }

    /**
     * Получить список товаров с постраничной навигацией.
     *
     * @param array $filter  Фильтр выборки
     * @param int   $page    Номер страницы
     * @param int   $perPage Элементов на странице
     * @return array{items: array, total: int, pages: int}
     */
    public function getProducts(array $filter = [], int $page = 1, int $perPage = CATALOG_PAGE_SIZE): array
    {
        $baseFilter = [
            'IBLOCK_ID' => IBLOCK_CATALOG_ID,
            'ACTIVE' => 'Y',
        ];

        $filter = array_merge($baseFilter, $filter);
        $offset = ($page - 1) * $perPage;

        $query = ElementTable::getList([
            'filter' => $filter,
            'select' => ['ID', 'NAME', 'CODE', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'SORT'],
            'order' => ['SORT' => 'ASC', 'NAME' => 'ASC'],
            'limit' => $perPage,
            'offset' => $offset,
            'count_total' => true,
        ]);

        $total = $query->getCount();
        $items = $query->fetchAll();

        return [
            'items' => $items,
            'total' => $total,
            'pages' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Получить детальную информацию о товаре.
     *
     * @param string $code Символьный код товара
     * @return array|null
     */
    public function getProductByCode(string $code): ?array
    {
        $element = ElementTable::getList([
            'filter' => [
                'IBLOCK_ID' => IBLOCK_CATALOG_ID,
                'CODE' => $code,
                'ACTIVE' => 'Y',
            ],
            'select' => [
                'ID', 'NAME', 'CODE', 'PREVIEW_TEXT', 'DETAIL_TEXT',
                'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'SORT',
                'IBLOCK_SECTION_ID',
            ],
            'limit' => 1,
        ])->fetch();

        if (!$element) {
            return null;
        }

        // Загружаем свойства через старый API для полноценного доступа
        $properties = \CIBlockElement::GetByID($element['ID'])
            ->GetNextElement()
            ->GetProperties();

        $element['PROPERTIES'] = $properties;

        return $element;
    }

    /**
     * Получить разделы каталога.
     *
     * @param int|null $parentId ID родительского раздела (null — корневые)
     * @return array
     */
    public function getSections(?int $parentId = null): array
    {
        $filter = [
            'IBLOCK_ID' => IBLOCK_CATALOG_ID,
            'ACTIVE' => 'Y',
        ];

        if ($parentId !== null) {
            $filter['SECTION_ID'] = $parentId;
        } else {
            $filter['DEPTH_LEVEL'] = 1;
        }

        return SectionTable::getList([
            'filter' => $filter,
            'select' => ['ID', 'NAME', 'CODE', 'PICTURE', 'DESCRIPTION', 'SORT'],
            'order' => ['SORT' => 'ASC', 'NAME' => 'ASC'],
        ])->fetchAll();
    }

    /**
     * Поиск товаров по строке.
     *
     * @param string $query  Поисковый запрос
     * @param int    $limit  Максимум результатов
     * @return array
     */
    public function search(string $query, int $limit = API_DEFAULT_LIMIT): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        return ElementTable::getList([
            'filter' => [
                'IBLOCK_ID' => IBLOCK_CATALOG_ID,
                'ACTIVE' => 'Y',
                '%NAME' => $query,
            ],
            'select' => ['ID', 'NAME', 'CODE', 'PREVIEW_TEXT', 'PREVIEW_PICTURE'],
            'order' => ['NAME' => 'ASC'],
            'limit' => $limit,
        ])->fetchAll();
    }
}
