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

// ensure we get report on all possible php errors
error_reporting(-1);

define('YII_ENV', 'test');
define('YII_ENABLE_ERROR_HANDLER', false);
define('YII_DEBUG', true);

// application path based on default install into vendor structure immediately off of the base
define('APPLICATION_PATH', realpath(__DIR__ . '/../../../../'));

$_SERVER['SCRIPT_NAME'] = substr(__FILE__, strlen(APPLICATION_PATH));
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

// require composer autoloader if available
$composerAutoload = APPLICATION_PATH . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once($composerAutoload);
}

// require yii
$yii_php = APPLICATION_PATH . '/vendor/yiisoft/yii2/yii/Yii.php';
if (is_file($yii_php)) {
    require_once($yii_php);
}

Yii::setAlias('@Concord/Tests', __DIR__);
