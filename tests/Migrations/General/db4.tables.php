<?php
/**
 * This file is part of the fangface/yii2-concord package
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 *
 * @package fangface/yii2-concord
 * @author Fangface <dev@fangface.net>
 * @copyright Copyright (c) 2014 Fangface <dev@fangface.net>
 * @license https://github.com/fangface/yii2-concord/blob/master/LICENSE.md MIT License
 *
 */

namespace Concord\Tests\Migrations\General;

use yii\db\Schema;

/**
 * Create the 'db4' / 'dbTestRemote1' tables required to perform the unit tests
 */
class db4Tables extends \Concord\Db\Migration
{

    /**
     * (non-PHPdoc)
     * @see \yii\db\Migration::safeUp()
     */
    public function safeUp()
    {
        // drop tables if they already exist
        $this->safeDown();

        // create the tables required for testing
        $prefix = $this->db->tablePrefix;

        // robots table (random table at the end of the dbTestRemote1 connection belonging to client1

        $this->createTable($prefix.'robots', [
            'id'    => "bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
            'name'  => "char(100) NOT NULL DEFAULT ''",
            'type'  => "char(100) NOT NULL DEFAULT ''",
            'year'  => "int(4) NOT NULL DEFAULT '0'",
        ]);

    }


    /**
     * (non-PHPdoc)
     * @see \yii\db\Migration::safeDown()
     */
    public function safeDown()
    {
        $prefix = $this->db->tablePrefix;
        $this->safeDropTable($prefix.'robots');
    }


    /**
     * Drop a table and remain silent if an exception is thrown
     * (table may or may not already exist)
     *
     * @param string $table table name (including prefix)
     */
    public function safeDropTable($table)
    {
        try {
            $this->dropTable($table);
        } catch (\Exception $e) {
            //$e->getMessage();
        }
    }

}
