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

use fangface\tests\models\TestCase;


/**
 * This is the base db connection test case class for all Concord database unit tests
 */
abstract class ConnectionTestCase extends TestCase
{

    /**
     * Close down any db connections setup by the connection manager
     * @see \fangface\tests\TestCase::tearDown()
     */
    protected function localPreTearDown()
    {
        // close down any db connections setup by the connection manager
        $dbFactory = \Yii::$app->get('dbFactory');
        $resourceArray = $dbFactory->getResourceArray();
        $resources = $resourceArray['Resources'];
        foreach ($resources as $resourceName => $resource) {
            $dbFactory->disconnectResource($resourceName);
        }
    }

}
