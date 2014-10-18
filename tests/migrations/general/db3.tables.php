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

namespace fangface\concord\tests\migrations\general;

use fangface\concord\db\Migration;
use yii\db\Schema;

/**
 * Create the 'db3' / 'dbTestClient2' tables required to perform the unit tests
 */
class db3Tables extends Migration
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

        // dbResources table (list of database connections for use by the connection manager for the current client)

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

        // customers table (ilst of customers for the current client)

        $this->createTable($prefix.'customers', [
            'id'            => "bigpk",
            'addressId'     => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
            'phoneId'       => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
            'extraField'    => "string(30) NOT NULL DEFAULT ''",
            'createdAt'     => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
            'createdBy'     => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
            'modifiedAt'    => "datetime NOT NULL default '0000-00-00 00:00:00'",
            'modifiedBy'    => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
        ]);

        // addresses table

        $this->createTable($prefix.'addresses', [
            'id'            => "bigpk",
            'customerId'    => "bigint(20) UNSIGNED NOT NULL DEFAULT '0'",
            'title'         => "string(20) NOT NULL DEFAULT ''",
            'forename'      => "string(30) NOT NULL DEFAULT ''",
            'surname'       => "string(50) NOT NULL DEFAULT ''",
            'jobTitle'      => "string(50) NOT NULL DEFAULT ''",
            'company'       => "string(50) NOT NULL DEFAULT ''",
            'address1'      => "string(50) NOT NULL DEFAULT ''",
            'address2'      => "string(50) NOT NULL DEFAULT ''",
            'address3'      => "string(50) NOT NULL DEFAULT ''",
            'city'          => "string(50) NOT NULL DEFAULT ''",
            'region'        => "string(50) NOT NULL DEFAULT ''",
            'countryCode'   => "string(3) NOT NULL DEFAULT ''",
            'createdAt'     => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
            'createdBy'     => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
            'modifiedAt'    => "datetime NOT NULL default '0000-00-00 00:00:00'",
            'modifiedBy'    => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
        ]);

        $this->createIndex('customerId', $prefix.'addresses', 'customerId');

        // phones table

        $this->createTable($prefix.'phones', [
            'id'            => "bigpk",
            'customerId'    => "bigint(20) UNSIGNED NOT NULL DEFAULT '0'",
            'telno'         => "string(50) NOT NULL DEFAULT ''",
        ]);

        $this->createIndex('customerId', $prefix.'phones', 'customerId');

        // orders table (list of orders for customers for the current client)

        $this->createTable($prefix.'orders', [
            'id'            => "bigpk",
            'customerId'    => "bigint(20) UNSIGNED NOT NULL DEFAULT '0'",
            'field1'        => "string(50) NOT NULL DEFAULT ''",
            'field2'        => "string(50) NOT NULL DEFAULT ''",
            'field3'        => "string(50) NOT NULL DEFAULT ''",
            'createdAt'     => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
            'createdBy'     => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
            'modifiedAt'    => "datetime NOT NULL default '0000-00-00 00:00:00'",
            'modifiedBy'    => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
        ]);

        $this->createIndex('customerId', $prefix.'orders', 'customerId');

        // items table (list of items belonging to orders for customers for the current client)

        $this->createTable($prefix.'items', [
            'id'            => "bigpk",
            'customerId'    => "bigint(20) UNSIGNED NOT NULL DEFAULT '0'",
            'orderId'       => "bigint(20) UNSIGNED NOT NULL DEFAULT '0'",
            'productCode'   => "string(50) NOT NULL DEFAULT ''",
            'quantity'      => "integer NOT NULL DEFAULT '0'",
            'totalValue'    => "decimal(10,2) NOT NULL DEFAULT '0.00'",
            'field1'        => "string(50) NOT NULL DEFAULT ''",
            'field2'        => "string(50) NOT NULL DEFAULT ''",
            'field3'        => "string(50) NOT NULL DEFAULT ''",
        ]);

        $this->createIndex('customerId', $prefix.'items', 'customerId');
        $this->createIndex('orderId', $prefix.'items', 'orderId');

        // products table (list of products for the current client)

        $this->createTable($prefix.'products', [
            'id'            => "bigpk",
            'productCode'   => "string(50) NOT NULL DEFAULT ''",
            'description'   => "string(200) NOT NULL DEFAULT ''",
            'createdAt'     => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
            'createdBy'     => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
            'modifiedAt'    => "datetime NOT NULL default '0000-00-00 00:00:00'",
            'modifiedBy'    => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
        ]);

        $this->createIndex('productCode', $prefix.'products', 'productCode', true);

        // attribute entities table
        $this->createTable($prefix.'attributeEntities', [
            'id'                => "bigpk",
            'entityName'        => "string(100) NOT NULL DEFAULT ''",
        ]);

        $this->createIndex('entityName', $prefix.'attributeEntities', 'entityName', true);

        // attribute definitions table
        $this->createTable($prefix.'attributeDefinitions', [
            'id'                => "bigpk",
            'entityId'          => "bigint(20) UNSIGNED NOT NULL DEFAULT '0'",
            'sortOrder'         => "bigint(16) UNSIGNED NOT NULL DEFAULT '0'",
            'attributeGroup'    => "string(100) NOT NULL DEFAULT 'DEFAULT'",
            'attributeName'     => "string(100) NOT NULL DEFAULT ''",
            'dataType'          => "enum('char','longtext','int','decimal','boolean','date','datetime') NOT NULL DEFAULT 'char'",
            'length'            => "mediumint(5) UNSIGNED NOT NULL DEFAULT '0'",
            'decimals'          => "mediumint(5) UNSIGNED NOT NULL DEFAULT '0'",
            'unsigned'          => "tinyint(1) UNSIGNED NOT NULL DEFAULT '0'",
            'zerofill'          => "tinyint(1) UNSIGNED NOT NULL DEFAULT '0'",
            'isNullable'        => "tinyint(1) UNSIGNED NOT NULL DEFAULT '0'",
            'defaultValue'      => "string(255) NOT NULL DEFAULT ''",
            'deleteOnDefault'   => "tinyint(1) UNSIGNED NOT NULL DEFAULT '0'",
            'lazyLoad'          => "tinyint(1) UNSIGNED NOT NULL DEFAULT '0'",
            'allowUserEdit'     => "tinyint(1) UNSIGNED NOT NULL DEFAULT '0'",
        ]);

        $this->createIndex('entityAtribute', $prefix.'attributeDefinitions', array('entityId', 'attributeName'), true);
        $this->createIndex('entityNameLazy', $prefix.'attributeDefinitions', array('entityId', 'lazyLoad'));
        $this->createIndex('entitySortOrder', $prefix.'attributeDefinitions', array('entityId', 'sortOrder'));
        $this->createIndex('entityGroupOrder', $prefix.'attributeDefinitions', array('entityId', 'attributeGroup', 'sortOrder'));

        // attribute values table
        $this->createTable($prefix.'attributeValues', [
            'entityId'          => "bigint(20) UNSIGNED NOT NULL DEFAULT '0'",
            'objectId'          => "bigint(20) UNSIGNED NOT NULL DEFAULT '0'",
            'attributeId'       => "bigint(20) UNSIGNED NOT NULL DEFAULT '0'",
            'value'             => "longtext NOT NULL",
        ]);

        $this->createIndex('entityObjectAttribute', $prefix.'attributeValues', array('entityId', 'objectId', 'attributeId'), true);
        $this->createIndex('entityAttributeValue', $prefix.'attributeValues', array('entityId','attributeId','value(255)'));

    }


    /**
     * (non-PHPdoc)
     * @see \yii\db\Migration::safeDown()
     */
    public function safeDown()
    {
        $prefix = $this->db->tablePrefix;
        $this->safeDropTable($prefix.'dbResources');
        $this->safeDropTable($prefix.'customers');
        $this->safeDropTable($prefix.'addresses');
        $this->safeDropTable($prefix.'phones');
        $this->safeDropTable($prefix.'orders');
        $this->safeDropTable($prefix.'items');
        $this->safeDropTable($prefix.'products');
        $this->safeDropTable($prefix.'attributeEntities');
        $this->safeDropTable($prefix.'attributeDefinitions');
        $this->safeDropTable($prefix.'attributeValues');
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
