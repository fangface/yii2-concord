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

namespace fangface\debug;

use fangface\web\View;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\ViewContextInterface;
use yii\debug\LogTarget;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Extends Yii Debug Module to suppoer ajax debug requests updating the debug toolbar
 */
class Module extends \yii\debug\Module implements BootstrapInterface, ViewContextInterface
{
    /**
     * Override view path to point back to yii2-debug
     * {@inheritDoc}
     * @see \yii\base\Module::getViewPath()
     */
    public function getViewPath()
    {
        return Yii::getAlias(Yii::getAlias('@base') . '/vendor/yiisoft/yii2-debug/views');
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        $this->logTarget = Yii::$app->getLog()->targets['debug'] = new LogTarget($this);

        if (Yii::$app->getRequest()->getIsAjax()) {
            if (YII_DEBUG_AJAX) {
                // delay attaching event handler to the view component after it is fully configured
                $app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app) {
                    $app->getView()->on(View::EVENT_END_AJAX_DEBUG, [$this, 'renderToolbar']);
                });
            }
        } else {
            // delay attaching event handler to the view component after it is fully configured
            $app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app) {
                $app->getView()->on(View::EVENT_END_BODY, [$this, 'renderToolbar']);
            });
        }

        $app->getUrlManager()->addRules([
            [
                'class' => 'yii\web\UrlRule',
                'route' => $this->id,
                'pattern' => $this->id,
            ],
            [
                'class' => 'yii\web\UrlRule',
                'route' => $this->id . '/<controller>/<action>',
                'pattern' => $this->id . '/<controller:[\w\-]+>/<action:[\w\-]+>',
            ]
        ], false);
    }

    /**
     * Renders mini-toolbar at the end of page body.
     *
     * @param \yii\base\Event $event
     */
    public function renderToolbar($event)
    {
        if (!$this->checkAccess()) {
            return;
        }

        $url = Url::toRoute(['/' . $this->id . '/default/toolbar',
            'tag' => $this->logTarget->tag,
        ]);

        if (!Yii::$app->getRequest()->getIsAjax()) {
            echo '<div id="yii-debug-toolbar-wrapper">';
        }

        echo '<div id="yii-debug-toolbar" data-url="' . Html::encode($url) . '" style="display:none" class="yii-debug-toolbar-bottom"></div>';

        /* @var $view View */
        $view = $event->sender;

        // echo is used in order to support cases where asset manager is not available
        echo '<style>' . $view->renderPhpFile(Yii::getAlias('@base/vendor/yiisoft/yii2-debug/assets/toolbar.css')) . '</style>';
        echo '<script>' . $view->renderPhpFile(Yii::getAlias('@base/vendor/yiisoft/yii2-debug/assets/toolbar.js')) . '</script>';

        if (!Yii::$app->getRequest()->getIsAjax()) {
            echo '</div>';
        }

    }

}
