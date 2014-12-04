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

namespace fangface\models\db\client;

use fangface\db\ActiveRecord;

/**
 * Active Record class to the main db.{prefix}dbResources table
 *
 * @property integer $id primary key
 * @property string $resourceName Database connection name
 * @property string $dbDriver Driver used for connection
 * @property string $dbDsn DSN of the database connection
 * @property string $dbUser	Database connection username
 * @property string $dbPass	Database connection password
 * @property string $dbPrefix Prefix for tables in the database
 * @property string $dbCharset Character set of the database connection
 * @property string $dbAfterOpen Commands to run after the connection is initially established
 * @property string $dbClass Class through which the connection should be established
 * @property string $createdAt Date and time record was created
 * @property integer $createdBy	User id of user that created record
 * @property string $modifiedAt Date and time record was last modified
 * @property integer $modifiedBy User id of last user to modify record
 */
class DbResource extends ActiveRecord
{

    protected static $dbResourceName    = 'dbClient';

}
