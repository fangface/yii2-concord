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

namespace Concord\Tests\Unit\Db;

use Concord\Models\Db\Client;
use Concord\Tests\Models\CustomerAttributes;
use Concord\Tests\Models\DbTestCase as DbTestCase;

/**
 * Test Concord Active Record add-on for Yii2
 */
class A3ActiveAttributeRecordTest extends DbTestCase
{
    use \Concord\Base\Traits\ServiceGetter;

    /**
     * Test ability to get attribute record schema
     */
    public function testGetEntityAttributeListAsStructure()
    {
        $client = Client::find(1);
        $this->assertInstanceOf('Concord\Models\Db\Client', $client);
        $this->setService('client', $client);

        $customerAtributes = New CustomerAttributes();
        $stru = $customerAtributes->GetEntityAttributeListAsStructure();
        $this->assertInternalType('array', $stru);
        $this->assertEquals(6, count($stru));
        $this->assertTrue(array_key_exists('field1', $stru));
        $this->assertTrue(array_key_exists('field2', $stru));
        $this->assertTrue(array_key_exists('createdAt', $stru));
        $this->assertTrue(array_key_exists('createdBy', $stru));
        $this->assertTrue(array_key_exists('modifiedAt', $stru));
        $this->assertTrue(array_key_exists('modifiedBy', $stru));
        $field = $stru['field1'];
        $this->assertInternalType('array', $field);
        $this->assertTrue(array_key_exists('columnDefault', $field));
    }

}
