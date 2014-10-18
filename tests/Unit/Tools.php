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

namespace fangface\concord\tests\Unit;

use fangface\concord\tests\models\TestCase as TestCase;
use fangface\concord\tests\models\Item;
use fangface\concord\Tools;

/**
 * Test Concord Tools class
 */
class Tools extends TestCase
{

    /**
     * Test debug method does not fail
     */
    public function testDebugDoesNotFail()
    {
        $var = array('a' => 100, 'b' => 200);
        $debug = Tools::debug($var, 'My Label', false);
        $this->assertInternalType('string', $actual);
    }


    /**
     * Test getClientId returns 0 when no client has been set
     */
    public function testGetClientIdForNonClient()
    {
        $id = Tools::getClientId();
        $this->assertEquals(0, $id);
    }


    /**
     * Test getClientCode returns '' when no client has been set
     */
    public function testGetClientCodeForNonClient()
    {
        $code = Tools::getClientCode();
        $this->assertEquals('', $code);
    }


    /**
     * Test is_closure function
     */
    public function testIsClosure()
    {
        $this->assertFalse(Tools::is_closure(array()));
        $this->assertFalse(Tools::is_closure(5));
        $this->assertFalse(Tools::is_closure('string'));
        $this->assertFalse(Tools::is_closure(new Item));
        $this->assertTrue(Tools::is_closure(function(){$a=1;}));
    }

}
