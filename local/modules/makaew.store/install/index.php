<?php

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

/**
 * Установщик модуля makaew.store.
 *
 * Отвечает за создание/удаление таблиц БД,
 * регистрацию модуля и зависимостей.
 */
class makaew_store extends CModule
{
    public $MODULE_ID = 'makaew.store';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('MAKAEW_STORE_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('MAKAEW_STORE_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = 'makaew';
        $this->PARTNER_URI = '';
    }

    /**
     * Установка модуля: регистрация + создание таблиц.
     */
    public function DoInstall(): void
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installDB();
    }

    /**
     * Удаление модуля: удаление таблиц + дерегистрация.
     */
    public function DoUninstall(): void
    {
        $this->uninstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    /**
     * Создание таблиц модуля.
     */
    public function installDB(): void
    {
        $connection = Application::getConnection();
        $sqlFile = __DIR__ . '/db/install.sql';

        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $statements = array_filter(
                array_map('trim', explode(';', $sql))
            );

            foreach ($statements as $statement) {
                $connection->queryExecute($statement);
            }
        }
    }

    /**
     * Удаление таблиц модуля.
     */
    public function uninstallDB(): void
    {
        $connection = Application::getConnection();
        $sqlFile = __DIR__ . '/db/uninstall.sql';

        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $statements = array_filter(
                array_map('trim', explode(';', $sql))
            );

            foreach ($statements as $statement) {
                $connection->queryExecute($statement);
            }
        }
    }
}
