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

namespace fangface\grid;

use yii\grid\Column;
use yii\helpers\Html;

class CheckboxColumn extends \yii\grid\CheckboxColumn
{

    /**
     * @var string tailor output based on the grid type
     */
    public $gridType = 'default';

    /**
     * Renders the header cell content.
     * The default implementation simply renders [[header]].
     * This method may be overridden to customize the rendering of the header cell.
     * @return string the rendering result
     */
    protected function renderHeaderCellContent()
    {
        if ($this->gridType == 'datatable-select') {
            if ($this->header !== null || !$this->multiple) {
                return Column::renderHeaderCellContent();
            } else {
                $name = rtrim($this->name, '[]') . '_all';
                return Html::checkBox($name, false, ['class' => 'group-checkable']);
            }
        } else {
            return parent::renderHeaderCellContent();
        }
    }

   /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        if ($this->gridType == 'datatable-select') {
            if (!$this->multiple) {
                return Html::radio(rtrim($this->name, '[]'), false, ['value' => $key]);
            } else {
                return Html::checkBox($this->name, false, ['value' => $key]);
            }
        } else {
            return parent::renderDataCellContent($model, $key, $index);
        }
    }
}
