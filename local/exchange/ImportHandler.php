<?php

namespace Makaew\Store\Exchange;

use Bitrix\Main\Loader;
use Bitrix\Main\Diag\Debug;

/**
 * Кастомный обработчик импорта из 1С (CommerceML).
 *
 * Расширяет стандартный механизм обмена Битрикс,
 * добавляя логику маппинга свойств, обработку изображений
 * и синхронизацию кастомных данных.
 *
 * Подключается через обработчик события OnBeforeCatalogImport1C.
 */
class ImportHandler
{
    /** @var Logger */
    private Logger $logger;

    /** @var PriceMapper */
    private PriceMapper $priceMapper;

    /** Маппинг XML-свойств 1С → свойства инфоблока */
    private const PROPERTY_MAP = [
        'ТорговаяМарка' => 'BRAND',
        'Артикул' => 'ARTICLE',
        'ЕдиницаИзмерения' => 'UNIT',
        'РасходНаМ2' => 'CONSUMPTION_RATE',
        'ЕдиницаРасхода' => 'CONSUMPTION_UNIT',
        'Вес' => 'WEIGHT',
        'ОбъемУпаковки' => 'PACKAGE_VOLUME',
        'Цвет' => 'COLOR',
    ];

    public function __construct()
    {
        $this->logger = new Logger();
        $this->priceMapper = new PriceMapper();
    }

    /**
     * Обработка импорта каталога.
     *
     * Вызывается при загрузке XML из 1С.
     * Модифицирует данные перед записью в инфоблок.
     *
     * @param array &$arFields Данные элемента
     * @param array $arProperties Свойства из XML
     * @return bool true для продолжения импорта
     */
    public function handleImport(array &$arFields, array $arProperties): bool
    {
        $xmlId = $arFields['XML_ID'] ?? '';
        $this->logger->info("Processing import for XML_ID: {$xmlId}");

        // Маппинг свойств 1С → инфоблок
        $arFields['PROPERTIES'] = $this->mapProperties($arProperties);

        // Транслитерация символьного кода
        if (empty($arFields['CODE']) && !empty($arFields['NAME'])) {
            $arFields['CODE'] = $this->generateCode($arFields['NAME']);
        }

        // Обработка изображений из XML
        if (!empty($arFields['DETAIL_PICTURE'])) {
            $arFields['DETAIL_PICTURE'] = $this->processImage($arFields['DETAIL_PICTURE']);
        }

        // Активация элемента при импорте
        $arFields['ACTIVE'] = 'Y';

        $this->logger->info("Import processed: {$arFields['NAME']} (XML_ID: {$xmlId})");

        return true;
    }

    /**
     * Маппинг свойств из формата 1С в свойства инфоблока.
     */
    private function mapProperties(array $xmlProperties): array
    {
        $mappedProperties = [];

        foreach ($xmlProperties as $xmlName => $value) {
            if (isset(self::PROPERTY_MAP[$xmlName])) {
                $iblockCode = self::PROPERTY_MAP[$xmlName];
                $mappedProperties[$iblockCode] = $this->castPropertyValue($iblockCode, $value);
            }
        }

        return $mappedProperties;
    }

    /**
     * Приведение типа значения свойства.
     */
    private function castPropertyValue(string $code, mixed $value): mixed
    {
        return match ($code) {
            'CONSUMPTION_RATE', 'WEIGHT' => (float) str_replace(',', '.', $value),
            'COLOR' => is_array($value) ? $value : [$value],
            default => $value,
        };
    }

    /**
     * Генерация символьного кода из названия.
     */
    private function generateCode(string $name): string
    {
        $params = [
            'max_len' => 100,
            'change_case' => 'L',
            'replace_space' => '-',
            'replace_other' => '-',
            'delete_repeat_replace' => true,
            'use_google' => false,
        ];

        $code = \CUtil::translit($name, 'ru', $params);

        return $code ?: 'item-' . uniqid();
    }

    /**
     * Обработка изображения из XML.
     *
     * Проверяет файл, ресайзит при необходимости.
     */
    private function processImage(array $imageData): array
    {
        // Ограничение размера до 1200px по большей стороне
        $maxDimension = 1200;

        if (!empty($imageData['tmp_name']) && file_exists($imageData['tmp_name'])) {
            $imageInfo = getimagesize($imageData['tmp_name']);
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];

                if ($width > $maxDimension || $height > $maxDimension) {
                    $imageData['MAX_WIDTH'] = $maxDimension;
                    $imageData['MAX_HEIGHT'] = $maxDimension;
                }
            }
        }

        return $imageData;
    }
}
