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

use fangface\db\ActiveRecord;
use fangface\tests\models\Country;
use fangface\tests\models\Customer;

/**
 * Active Record class for the clients dbClient.{prefix}addresses table
 *
 * @property integer $id primary key
 * @property integer $customerId ID of customer that the address belongs to
 * @property string $title
 * @property string $forename
 * @property string $surname
 * @property string $jobTitle
 * @property string $company
 * @property string $address1
 * @property string $address2
 * @property string $address3
 * @property string $city
 * @property string $region
 * @property string $countryCode 3 character ISO code for the country
 * @property string $createdAt Date and time record was created
 * @property integer $createdBy	User id of user that created record
 * @property string $modifiedAt Date and time record was last modified
 * @property integer $modifiedBy User id of last user to modify record
 * @property \fangface\tests\models\Country $country
 * @property \fangface\tests\models\Customer $customer
 *
 * @method Address findOne($condition = null) static
 * @method Address[] findAll($condition = null) static
 * @method Address[] findByCondition($condition, $one) static
 */
class Address extends ActiveRecord
{

    protected static $dbResourceName    = 'dbClient';

    public function modelRelationMap()
    {
        return [

            'country' => array(
                'type' => 'hasOne',
                'class' => Country::className(),
                'link' => array(
                    'countryCode' => 'countryCode' // child->var => parent->var (remote => local)
                ),
                'readOnly' => true,
                'canDelete' => false,
                'allToArray' => true,
            ),

            'customer' => array(
                'type' => 'belongsTo',
                'class' => Customer::className(),
                'link' => array(
                    'id' => 'customerId'
                ),
            ),

        ];
    }

}
