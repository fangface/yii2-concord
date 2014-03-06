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

namespace Concord\Tests\Unit;

use Concord\Tests\Models\TestCase as TestCase;

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
        $var = array('a'=>100, 'b'=>200);
        $debug = \Concord\Tools::debug($var, 'My Label', false);
        $this->assertInternalType('string', $actual);
    }


    /**
     * Test getClientId returns 0 when no client has been set
     */
    public function testGetClientIdForNonClient()
    {
        $id = \Concord\Tools::getClientId();
        $this->assertEquals(0, $id);
    }


    /**
     * Test getClientCode returns '' when no client has been set
     */
    public function testGetClientIdForNonClient()
    {
        $code = \Concord\Tools::getClientCode();
        $this->assertEquals('', $code);
    }


    /**
     * Test is_closure function
     */
    public function testIsClosure()
    {
        $this->assertFalse(\Concord\Tools::is_closure(array()));
        $this->assertFalse(\Concord\Tools::is_closure(5));
        $this->assertFalse(\Concord\Tools::is_closure('string'));
        $this->assertFalse(\Concord\Tools::is_closure(new \Concord\Tests\Models\Item()));
        $this->assertTrue(\Concord\Tools::is_closure(function(){$a=1;}));
    }

}
