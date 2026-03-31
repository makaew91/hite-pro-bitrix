<?php

namespace Makaew\Store;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

/**
 * ORM-сущность для таблицы makaew_store_calculation_log.
 *
 * Лог расчётов калькулятора расхода материалов.
 * Используется для аналитики и рекомендаций.
 */
class CalculationLogTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'makaew_store_calculation_log';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('id'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new IntegerField('user_id'))
                ->configureNullable(),

            (new IntegerField('material_id'))
                ->configureRequired(),

            (new FloatField('area'))
                ->configureRequired()
                ->configureScale(2),

            (new FloatField('calculated_amount'))
                ->configureRequired()
                ->configureScale(2),

            (new TextField('params'))
                ->configureNullable(),

            (new DatetimeField('created_at'))
                ->configureRequired()
                ->configureDefaultValue(static fn() => new DateTime()),

            // Связь с материалом
            (new Reference(
                'material',
                MaterialTable::class,
                Join::on('this.material_id', 'ref.id')
            ))->configureJoinType('LEFT'),
        ];
    }

    /**
     * Записать результат расчёта.
     */
    public static function log(
        int $materialId,
        float $area,
        float $calculatedAmount,
        ?int $userId = null,
        ?array $params = null,
    ): \Bitrix\Main\ORM\Data\AddResult {
        return static::add([
            'user_id' => $userId,
            'material_id' => $materialId,
            'area' => $area,
            'calculated_amount' => $calculatedAmount,
            'params' => $params ? json_encode($params, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }
}
