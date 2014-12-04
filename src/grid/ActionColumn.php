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

class ActionColumn extends \yii\grid\ActionColumn
{

    /**
     * @var string tailor output based on the grid type
     */
    public $gridType = 'default';

    /**
     * @var string|array|boolean the HTML code representing a filter input (e.g. a text field, a dropdown list)
     * that is used for this data column. This property is effective only when [[GridView::filterModel]] is set.
     *
     * - If this property is not set, a text field will be generated as the filter input;
     * - If this property is an array, a dropdown list will be generated that uses this property value as
     *   the list options.
     * - If you don't want a filter for this data column, set this value to be false.
     */
    public $filter = false;

    /**
     * @inheritdoc
     */
    protected function renderFilterCellContent()
    {
        if ($this->gridType == 'datatable') {
            if (is_string($this->filter)) {
                return $this->filter;
            } elseif ($this->filter === true) {
                // default search and reset button for all filters
                return '<div class="margin-bottom-5"><button class="btn btn-sm blue filter-submit margin-bottom"><i class="fa fa-search"></i> Search</button></div><button class="btn btn-sm red filter-cancel"><i class="fa fa-times"></i> Reset</button>';
            }
        }
        return parent::renderFilterCellContent();
    }

}
