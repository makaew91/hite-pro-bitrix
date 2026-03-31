<?php

namespace Makaew\Store\Api\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\Main\Request;
use Makaew\Store\Service\CatalogService;

/**
 * REST API контроллер каталога.
 *
 * Endpoints:
 *   makaew:store.api.catalog.list     — список товаров
 *   makaew:store.api.catalog.detail   — детальная информация
 *   makaew:store.api.catalog.search   — поиск по названию
 *   makaew:store.api.catalog.sections — разделы каталога
 */
class CatalogController extends Controller
{
    /**
     * Настройка фильтров для экшенов.
     */
    protected function getDefaultPreFilters(): array
    {
        return [
            new ActionFilter\HttpMethod([
                ActionFilter\HttpMethod::METHOD_GET,
                ActionFilter\HttpMethod::METHOD_POST,
            ]),
            new ActionFilter\Csrf(false), // REST API без CSRF
        ];
    }

    /**
     * Конфигурация экшенов.
     */
    public function configureActions(): array
    {
        return [
            'list' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
                ],
            ],
            'detail' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
                ],
            ],
            'search' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
                ],
            ],
            'sections' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
                ],
            ],
        ];
    }

    /**
     * GET /api/catalog/list
     *
     * Параметры: section_id, page, per_page
     */
    public function listAction(Request $request): ?array
    {
        $service = new CatalogService();

        $sectionId = (int) $request->get('section_id');
        $page = max(1, (int) ($request->get('page') ?? 1));
        $perPage = min(API_MAX_LIMIT, max(1, (int) ($request->get('per_page') ?? API_DEFAULT_LIMIT)));

        $filter = [];
        if ($sectionId > 0) {
            $filter['SECTION_ID'] = $sectionId;
            $filter['INCLUDE_SUBSECTIONS'] = 'Y';
        }

        $result = $service->getProducts($filter, $page, $perPage);

        return [
            'items' => array_map([$this, 'formatProduct'], $result['items']),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $result['total'],
                'pages' => $result['pages'],
            ],
        ];
    }

    /**
     * GET /api/catalog/detail
     *
     * Параметры: code (символьный код товара)
     */
    public function detailAction(Request $request): ?array
    {
        $code = trim($request->get('code') ?? '');
        if (empty($code)) {
            $this->addError(new Error('Parameter "code" is required', 'MISSING_CODE'));

            return null;
        }

        $service = new CatalogService();
        $product = $service->getProductByCode($code);

        if ($product === null) {
            $this->addError(new Error('Product not found', 'NOT_FOUND'));

            return null;
        }

        return $this->formatProductDetail($product);
    }

    /**
     * GET /api/catalog/search
     *
     * Параметры: q (строка поиска), limit
     */
    public function searchAction(Request $request): ?array
    {
        $query = trim($request->get('q') ?? '');
        if (mb_strlen($query) < 2) {
            $this->addError(new Error('Query must be at least 2 characters', 'QUERY_TOO_SHORT'));

            return null;
        }

        $limit = min(API_MAX_LIMIT, max(1, (int) ($request->get('limit') ?? API_DEFAULT_LIMIT)));

        $service = new CatalogService();
        $items = $service->search($query, $limit);

        return [
            'query' => $query,
            'items' => array_map([$this, 'formatProduct'], $items),
            'count' => count($items),
        ];
    }

    /**
     * GET /api/catalog/sections
     *
     * Параметры: parent_id (null = корневые)
     */
    public function sectionsAction(Request $request): array
    {
        $parentId = $request->get('parent_id');
        $parentId = ($parentId !== null) ? (int) $parentId : null;

        $service = new CatalogService();
        $sections = $service->getSections($parentId);

        return [
            'sections' => array_map(function (array $section) {
                return [
                    'id' => (int) $section['ID'],
                    'name' => $section['NAME'],
                    'code' => $section['CODE'],
                    'picture' => $section['PICTURE'] ? \CFile::GetPath($section['PICTURE']) : null,
                    'description' => $section['DESCRIPTION'] ?? null,
                ];
            }, $sections),
        ];
    }

    /**
     * Форматирование товара для API (список).
     */
    private function formatProduct(array $item): array
    {
        return [
            'id' => (int) $item['ID'],
            'name' => $item['NAME'],
            'code' => $item['CODE'],
            'preview_text' => $item['PREVIEW_TEXT'] ?? null,
            'image' => $item['PREVIEW_PICTURE'] ? \CFile::GetPath($item['PREVIEW_PICTURE']) : null,
        ];
    }

    /**
     * Форматирование товара для API (детальная).
     */
    private function formatProductDetail(array $product): array
    {
        $properties = [];
        foreach ($product['PROPERTIES'] ?? [] as $code => $prop) {
            if (empty($prop['VALUE'])) {
                continue;
            }
            $properties[$code] = [
                'name' => $prop['NAME'],
                'value' => $prop['VALUE'],
            ];
        }

        return [
            'id' => (int) $product['ID'],
            'name' => $product['NAME'],
            'code' => $product['CODE'],
            'preview_text' => $product['PREVIEW_TEXT'] ?? null,
            'detail_text' => $product['DETAIL_TEXT'] ?? null,
            'image' => $product['DETAIL_PICTURE'] ? \CFile::GetPath($product['DETAIL_PICTURE']) : null,
            'properties' => $properties,
        ];
    }
}
