<?php

namespace Makaew\Store;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

/**
 * ORM-сущность для таблицы makaew_store_material.
 *
 * Хранит каталог материалов с параметрами расхода.
 *
 * @method static \Bitrix\Main\ORM\Query\Result getList(array $parameters = [])
 * @method static \Bitrix\Main\ORM\Data\AddResult add(array $data)
 * @method static \Bitrix\Main\ORM\Data\UpdateResult update(int $primary, array $data)
 * @method static \Bitrix\Main\ORM\Data\DeleteResult delete(int $primary)
 */
class MaterialTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'makaew_store_material';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('id'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new StringField('name'))
                ->configureRequired()
                ->configureSize(255),

            (new StringField('code'))
                ->configureRequired()
                ->configureSize(255)
                ->configureUnique(),

            (new IntegerField('category_id'))
                ->configureNullable(),

            (new StringField('unit'))
                ->configureRequired()
                ->configureDefaultValue('шт')
                ->configureSize(50),

            (new FloatField('consumption_rate'))
                ->configureNullable()
                ->configureScale(4),

            (new StringField('consumption_unit'))
                ->configureNullable()
                ->configureSize(50),

            (new TextField('description'))
                ->configureNullable(),

            (new IntegerField('sort'))
                ->configureRequired()
                ->configureDefaultValue(500),

            (new BooleanField('active'))
                ->configureValues(0, 1)
                ->configureDefaultValue(1),

            (new DatetimeField('created_at'))
                ->configureRequired()
                ->configureDefaultValue(static fn() => new DateTime()),

            (new DatetimeField('updated_at'))
                ->configureRequired()
                ->configureDefaultValue(static fn() => new DateTime()),

            // Связь с категорией
            (new Reference(
                'category',
                MaterialCategoryTable::class,
                Join::on('this.category_id', 'ref.id')
            ))->configureJoinType('LEFT'),
        ];
    }

    /**
     * Получить активные материалы по категории.
     */
    public static function getActiveByCategory(int $categoryId, int $limit = CATALOG_PAGE_SIZE): \Bitrix\Main\ORM\Query\Result
    {
        return static::getList([
            'filter' => [
                '=active' => 1,
                '=category_id' => $categoryId,
            ],
            'order' => ['sort' => 'ASC', 'name' => 'ASC'],
            'limit' => $limit,
        ]);
    }

    /**
     * Найти материал по символьному коду.
     */
    public static function getByCode(string $code): ?array
    {
        $row = static::getList([
            'filter' => ['=code' => $code],
            'limit' => 1,
        ])->fetch();

        return $row ?: null;
    }
}
