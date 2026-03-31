<?php

namespace Makaew\Store;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

/**
 * ORM-сущность для таблицы makaew_store_material_category.
 *
 * Древовидный справочник категорий материалов.
 *
 * @method static \Bitrix\Main\ORM\Query\Result getList(array $parameters = [])
 * @method static \Bitrix\Main\ORM\Data\AddResult add(array $data)
 * @method static \Bitrix\Main\ORM\Data\UpdateResult update(int $primary, array $data)
 * @method static \Bitrix\Main\ORM\Data\DeleteResult delete(int $primary)
 */
class MaterialCategoryTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'makaew_store_material_category';
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

            (new IntegerField('parent_id'))
                ->configureNullable(),

            (new IntegerField('depth_level'))
                ->configureRequired()
                ->configureDefaultValue(0),

            (new IntegerField('sort'))
                ->configureRequired()
                ->configureDefaultValue(500),

            (new BooleanField('active'))
                ->configureValues(0, 1)
                ->configureDefaultValue(1),

            // Связь с родительской категорией
            (new Reference(
                'parent',
                self::class,
                Join::on('this.parent_id', 'ref.id')
            ))->configureJoinType('LEFT'),
        ];
    }

    /**
     * Получить дерево категорий.
     *
     * @return array Категории, сгруппированные по parent_id
     */
    public static function getTree(): array
    {
        $rows = static::getList([
            'filter' => ['=active' => 1],
            'order' => ['sort' => 'ASC', 'name' => 'ASC'],
        ])->fetchAll();

        $tree = [];
        foreach ($rows as $row) {
            $parentId = $row['parent_id'] ?? 0;
            $tree[$parentId][] = $row;
        }

        return $tree;
    }

    /**
     * Получить дочерние категории.
     */
    public static function getChildren(int $parentId): array
    {
        return static::getList([
            'filter' => [
                '=active' => 1,
                '=parent_id' => $parentId,
            ],
            'order' => ['sort' => 'ASC'],
        ])->fetchAll();
    }
}
