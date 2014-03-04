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

namespace Concord\Db;

/**
 * mysql has a long standing bug where the case of the table will switch
 * to lower case on Windows during the use of "create index" so we work around
 * this here by extending \yii\db\Migration and making use of ALTER TABLE during
 * createIndex() instead of CREATE INDEX
 * @see \yii\db\Migration
 */
class Migration extends \yii\db\Migration
{


    /**
     * Builds and executes a SQL statement for creating a new index.
     * @param string $name the name of the index. The name will be properly quoted by the method.
     * @param string $table the table that the new index will be created for. The table name will be properly quoted by the method.
     * @param string $column the column(s) that should be included in the index. If there are multiple columns, please separate them
     * by commas or use an array. The column names will be properly quoted by the method.
     * @param boolean $unique whether to add UNIQUE constraint on the created index.
     */
    public function createIndex($name, $table, $column, $unique = false)
    {
        if (in_array($this->db->getDriverName(), array('mysql', 'mysqli')) && $table != strtolower($table)) {
            echo "    > create (via alter table)" . ($unique ? ' unique' : '') . " index $name on $table (" . implode(',', (array)$column) . ") ...";
            $time = microtime(true);
            $sql = 'ALTER TABLE ' . $this->db->quoteTableName($table)
                . ' ADD ' . ($unique ? 'UNIQUE INDEX' : 'INDEX') . ' ' . $this->db->quoteTableName($name)
                . ' (' . $this->db->getQueryBuilder()->buildColumns($column) . ')';
            $this->db->createCommand($sql)->execute();
            echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
        } else {
            parent::createIndex($name, $table, $column, $unique);
        }
    }

}
