<?php

namespace ReturnsPortal\Migrations;

use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\DataBase\Migrate; 
use ReturnsPortal\Models\ReturnModel;
use ReturnsPortal\Models\ReturnItem;

class CreateReturnsTables extends Migrate
{
    public function up()
    {
        /** @var DataBase $db */
        $db = pluginApp(DataBase::class);

        // Create returns table
        if (!$db->hasTable(ReturnModel::class)) {
            $db->createTable(ReturnModel::class);
        }

        // Create return items table
        if (!$db->hasTable(ReturnItem::class)) {
            $db->createTable(ReturnItem::class);
        }
    }

    public function down()
    {
        /** @var DataBase $db */
        $db = pluginApp(DataBase::class);

        if ($db->hasTable(ReturnItem::class)) {
            $db->dropTable(ReturnItem::class);
        }

        if ($db->hasTable(ReturnModel::class)) {
            $db->dropTable(ReturnModel::class);
        }
    }
}


