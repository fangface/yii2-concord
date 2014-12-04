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

namespace fangface\tests\migrations\general;

use fangface\db\Migration;
use yii\db\Schema;

/**
 * Create the 'db' / 'dbTestMain' tables required to perform the unit tests
 */
class dbTables extends Migration
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

        // dbResources table (list of database connections for use by the connection manager by default)

        if (in_array($this->db->getDriverName(), array('mysql', 'mysqli'))) {

            // be more specific
            $this->createTable($prefix.'dbResources', [
                'id'            => "bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
                'resourceName'  => "char(100) NOT NULL DEFAULT ''",
                'dbDriver'      => "char(10) NOT NULL DEFAULT 'Pdo'",
                'dbDsn'         => "char(255) NOT NULL DEFAULT ''",
                'dbUser'        => "char(100) NOT NULL DEFAULT ''",
                'dbPass'        => "char(255) NOT NULL DEFAULT ''",
                'dbPrefix'      => "char(100) NOT NULL DEFAULT ''",
                'dbCharset'     => "char(100) NOT NULL DEFAULT 'utf8'",
                'dbAfterOpen'   => "char(100) NOT NULL DEFAULT ''",
                'dbClass'       => "char(100) NOT NULL DEFAULT ''",
                'createdAt'     => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
                'createdBy'     => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
                'modifiedAt'    => "datetime NOT NULL default '0000-00-00 00:00:00'",
                'modifiedBy'    => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
            ]);

        } else {

            // untested use of UNSIGNED, NOT NULL and DEFAULT in none mysql DBs
            $this->createTable($prefix.'dbResources', [
                'id'            => "bigpk",
                'resourceName'  => "string(100) NOT NULL DEFAULT ''",
                'dbDriver'      => "string(10) NOT NULL DEFAULT 'Pdo'",
                'dbDsn'         => "string NOT NULL DEFAULT ''",
                'dbUser'        => "string(100) NOT NULL DEFAULT ''",
                'dbPass'        => "string NOT NULL DEFAULT ''",
                'dbPrefix'      => "string(100) NOT NULL DEFAULT ''",
                'dbCharset'     => "string(100) NOT NULL DEFAULT 'utf8'",
                'dbAfterOpen'   => "string(100) NOT NULL DEFAULT ''",
                'dbClass'       => "string(100) NOT NULL DEFAULT ''",
                'createdAt'     => "datetime NOT NULL default '0000-00-00 00:00:00'",
                'createdBy'     => "bigint UNSIGNED NOT NULL DEFAULT '0'",
                'modifiedAt'    => "datetime NOT NULL default '0000-00-00 00:00:00'",
                'modifiedBy'    => "bigint UNSIGNED NOT NULL DEFAULT '0'",
            ]);
        }

        $this->createIndex('resourceName', $prefix.'dbResources', 'resourceName', true);

        /**
         * clients (list of clients including connection info to the primary client
         * db for use generally but also by the connection manager in a multi-tenant environment)
         */

        // be more specific
        $this->createTable($prefix.'clients', [
            'id'                => "bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
            'clientCode'        => "char(50) NOT NULL DEFAULT ''",
            'clientName'        => "char(100) NOT NULL DEFAULT ''",
            'dbDriver'          => "string(10) NOT NULL DEFAULT 'Pdo'",
            'dbDsn'             => "string NOT NULL DEFAULT ''",
            'dbUser'            => "string(100) NOT NULL DEFAULT ''",
            'dbPass'            => "string NOT NULL DEFAULT ''",
            'dbPrefix'          => "string(100) NOT NULL DEFAULT ''",
            'dbCharset'         => "string(100) NOT NULL DEFAULT 'utf8'",
            'dbAfterOpen'       => "string(100) NOT NULL DEFAULT ''",
            'dbClass'           => "string(100) NOT NULL DEFAULT ''",
            'createdAt'         => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
            'createdBy'         => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
            'modifiedAt'        => "datetime NOT NULL default '0000-00-00 00:00:00'",
            'modifiedBy'        => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
        ]);

        $this->createIndex('clientCode', $prefix.'clients', 'clientCode', true);

        // countries table used by all clients

        $this->createTable($prefix.'countries', [
            'id'            => "bigpk",
            'countryCode'   => "string(3) NOT NULL DEFAULT ''",
            'shortName'     => "string(200) NOT NULL DEFAULT ''",
            'longName'      => "string(200) NOT NULL DEFAULT ''",
            'createdAt'     => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
            'createdBy'     => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
            'modifiedAt'    => "datetime NOT NULL default '0000-00-00 00:00:00'",
            'modifiedBy'    => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
        ]);

        $this->createIndex('countryCode', $prefix.'countries', 'countryCode', true);

    }


    /**
     * (non-PHPdoc)
     * @see \yii\db\Migration::safeDown()
     */
    public function safeDown()
    {
        $prefix = $this->db->tablePrefix;
        $this->safeDropTable($prefix.'dbResources');
        $this->safeDropTable($prefix.'clients');
        $this->safeDropTable($prefix.'countries');
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
