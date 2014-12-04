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

namespace fangface\tests\models;

use fangface\db\ActiveAttributeRecord;

/**
 * Active Record class for the clients dbClient.{prefix}attributeValues table
 * and associated EAV support tables
 *
 * @property string $field1
 * @property string $field2
 * @property string $createdAt Date and time record was created
 * @property integer $createdBy	User id of user that created record
 * @property string $modifiedAt Date and time record was last modified
 * @property integer $modifiedBy User id of last user to modify record

 */
class CustomerAttributes extends ActiveAttributeRecord
{

    protected static $dbResourceName    = 'dbClient';

    /**
     * @var integer Specify the entityId for this attribute class if it us using a shared set of attribute tables
     */
    protected $entityId                = 1;

    /**
     * @var string attribute entities class - repoint to test class so that it can specify dbCLIENT as the relevant db connection
     */
    protected $attributeEntitiesClass    = 'fangface\tests\models\eav\AttributeEntities';

    /**
     * @var string attribute definitions model - repoint to test class so that it can specify dbCLIENT as the relevant db connection
     */
    protected $attributeDefinitionsClass = 'fangface\tests\models\eav\AttributeDefinitions';

    /**
     * @var string rattribute values class - epoint to test class so that it can specify dbCLIENT as the relevant db connection
     */
    protected $attributeValuesClass      = 'fangface\tests\models\eav\AttributeValues';

}
