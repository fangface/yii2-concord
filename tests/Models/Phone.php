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

namespace Concord\Tests\Models;

use yii\db\ActiveRecord;
use Yii;

/**
 * Active Record class for the clients dbClient.{prefix}phones table
 *
 * @property integer $id primary key
 * @property integer $customerId ID of customer that the address belongs to
 * @property string $number
 */
class Phone extends ActiveRecord
{

    public static function getDb()
    {
        $dbResourceName = 'dbClient';
        $isClientResource = true;

        if (Yii::$app->has('dbFactory')) {
            return Yii::$app->get('dbFactory')->getConnection($dbResourceName, true, true, $isClientResource);
        } elseif (Yii::$app->has($dbResourceName)) {
            return Yii::$app->get($dbResourceName);
        }

        throw new \Concord\Db\Exception('Database resource \'' . $dbResourceName . '\' not found');
    }


    public static function tableName()
    {
        $connection = static::getDb();
        $tablePrefix = $connection->tablePrefix;
        return $tablePrefix . 'phones';
    }
}
