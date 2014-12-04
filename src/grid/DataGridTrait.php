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

use fangface\grid\ActionColumn;
use fangface\grid\DataColumn;
use fangface\helpers\Html;
use yii\base\InvalidConfigException;
use yii\i18n\Formatter;
use yii\grid\Column;


trait DataGridTrait {

    /**
     * @var \fangface\data\ActiveDataProvider|\yii\data\DataProviderInterface, the data provider for the view.
     * This property is required.
     */
    public $dataProvider;

    /**
     * @var string the default data column class if the class name is not explicitly specified when configuring a data column.
     * Defaults to 'fangface\grid\DataColumn'.
     */
    public $dataColumnClass;

    /**
     * @var \yii\base\Model the model that keeps the user-entered filter data. When this property is set,
     * the grid view will enable column-based filtering. Each data column by default will display a text field
     * at the top that users can fill in to filter the data.
     *
     * Note that in order to show an input field for filtering, a column must have its [[DataColumn::attribute]]
     * property set or have [[DataColumn::filter]] set as the HTML code for the input field.
     *
     * When this property is not set (null) the filtering feature is disabled.
     */
    public $filterModel;

    /**
     * @var array|Formatter the formatter used to format model attribute values into displayable texts.
     * This can be either an instance of [[Formatter]] or an configuration array for creating the [[Formatter]]
     * instance. If this property is not set, the "formatter" application component will be used.
     */
    public $formatter;

    /**
     * @var array grid column configuration. Each array element represents the configuration
     * for one particular grid column. For example,
     *
     * ```php
     * [
     *     ['class' => SerialColumn::className()],
     *     [
     *         'class' => DataColumn::className(),
     *         'attribute' => 'name',
     *         'format' => 'text',
     *         'label' => 'Name',
     *     ],
     *     ['class' => CheckboxColumn::className()],
     * ]
     * ```
     *
     * If a column is of class [[DataColumn]], the "class" element can be omitted.
     *
     * As a shortcut format, a string may be used to specify the configuration of a data column
     * which only contains "attribute", "format", and/or "label" options: `"attribute:format:label"`.
     * For example, the above "name" column can also be specified as: `"name:text:Name"`.
     * Both "format" and "label" are optional. They will take default values if absent.
     */
    public $columns = [];

    /**
     * @var boolean should page summary be shown
     */
    public $showPageSummary = false;

    /**
     * @var string the HTML display when the content of a cell is empty
     */
    public $emptyCell = '&nbsp;';


    /**
     * Initialise the data grid traits
     */
    protected function dataGridInit()
    {
        if ($this->dataProvider === null) {
            throw new InvalidConfigException('The "dataProvider" property must be set.');
        }
        if ($this->formatter == null) {
            $this->formatter = \Yii::$app->getFormatter();
        } elseif (is_array($this->formatter)) {
            $this->formatter = \Yii::createObject($this->formatter);
        }
        if (!$this->formatter instanceof Formatter) {
            throw new InvalidConfigException('The "formatter" property must be either a Format object or a configuration array.');
        }
    }

    /**
     * Creates column objects and initializes them.
     * @param boolean $allowSelectAction
     * @param array $selecActionColumn
     */
    protected function initColumns($allowSelectAction = false, $selecActionColumn = [])
    {
        if (empty($this->columns)) {
            $this->guessColumns();
        }

        if ($allowSelectAction) {
            // add select row checkbox column to the start of the columns
            array_unshift($this->columns, $selecActionColumn);
        }
        foreach ($this->columns as $i => $column) {
            if (is_string($column)) {
                $column = $this->createDataColumn($column);
            } else {
                $column = \Yii::createObject(array_merge([
                    'class' => $this->dataColumnClass ? : DataColumn::className(),
                    'grid' => $this,
                ], $column));
            }
            if (!$column->visible) {
                unset($this->columns[$i]);
                continue;
            }
            $this->columns[$i] = $column;
        }
    }

    /**
     * This function tries to guess the columns to show from the given data
     * if [[columns]] are not explicitly specified.
     */
    protected function guessColumns()
    {
        $models = $this->dataProvider->getModels();
        $model = reset($models);
        if (is_array($model) || is_object($model)) {
            foreach ($model as $name => $value) {
                $this->columns[] = $name;
            }
        }
    }

    /**
     * Creates a [[DataColumn]] object based on a string in the format of "attribute:format:label".
     * @param string $text the column specification string
     * @return DataColumn the column instance
     * @throws InvalidConfigException if the column specification is invalid
     */
    protected function createDataColumn($text)
    {
        if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
            throw new InvalidConfigException('The column must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"');
        }

        return \Yii::createObject([
            'class' => $this->dataColumnClass ? : DataColumn::className(),
            'grid' => $this,
            'attribute' => $matches[1],
            'format' => isset($matches[3]) ? $matches[3] : 'text',
            'label' => isset($matches[5]) ? $matches[5] : null,
        ]);
    }

    /**
     * Renders the column group HTML.
     * @return bool|string the column group HTML or `false` if no column group should be rendered.
     */
    public function renderColumnGroup()
    {
        $requireColumnGroup = false;
        foreach ($this->columns as $column) {
            /* @var $column Column */
            if (!empty($column->options)) {
                $requireColumnGroup = true;
                break;
            }
        }
        if ($requireColumnGroup) {
            $cols = [];
            foreach ($this->columns as $column) {
                $cols[] = Html::tag('col', '', $column->options);
            }
            return Html::tag('colgroup', "\n\t" . implode("\n\t", $cols) . "\n");
        } else {
            return '';
        }
    }

    /**
     * Renders the table header.
     * @return string the rendering result.
     */
    public function renderTableHeader()
    {
        $cells = [];
        foreach ($this->columns as $column) {
            /* @var $column Column */
            if ($this->useSortingLinks || !property_exists($column, 'enableSorting')) {
                $cells[] = $column->renderHeaderCell();
            } else {
                $sorting = $column->enableSorting;
                $column->enableSorting = false;
                $cells[] = $column->renderHeaderCell();
                $column->enableSorting = $sorting;
            }
        }
        $content = Html::tag('tr', "\n\t" . implode("\n\t", $cells) . "\n", $this->headerRowOptions);
        return $content;
    }

    /**
     * Renders the filter.
     * @return string the rendering result.
     */
    public function renderFilters()
    {
        if ($this->filterModel !== null) {
            $cells = [];
            foreach ($this->columns as $column) {
                /* @var $column Column */
                $cells[] = $column->renderFilterCell();
            }
            return Html::tag('tr', "\n\t" . implode("\n\t", $cells) . "\n", $this->filterRowOptions);
        } else {
            return '';
        }
    }

    /**
     * Check to see if a column type exists
     * @param DataColumn|string $class
     * @param string $attribute [optional] if specified the attribute must exist in the class, default null
     * @param string $value [optional] if specified the attribute value must match this, default null
     * @param string $returnAttribute [optional] if specified the attribute is returned rather than true, default null
     * @return boolean
     */
    public function hasColumnType($class, $attribute = null, $value = null, $returnAttribute = null)
    {
        $result = false;
        foreach ($this->columns as $column) {
            if ($column instanceof $class) {
                if ($attribute !== null) {
                    if (isset($column->$attribute)) {
                        if ($value !== null) {
                            if ($column->$attribute == $value) {
                                $result = true;
                                break;
                            }
                        } else {
                            $result = true;
                            break;
                        }
                    }
                } else {
                    $result = true;
                    break;
                }
            }
        }
        if ($result && $returnAttribute != null) {
            if (isset($column->$returnAttribute)) {
                return $column->$returnAttribute;
            }
            $result = false;
        }
        return $result;
    }

    /**
     * Get the position within the columns array for the specified attribute or column class
     * @param string $attribute [optional] look for this attribute
     * @param DataColumn|string $class [optional] look for the first column with this column class type
     * @return integer|false
     */
    public function getColumnPosition($attribute = null, $class = null)
    {
        $pos = -1;
        foreach ($this->columns as $k => $column) {
            $pos++;
            if ($attribute !== null) {
                if (isset($column->attribute) && $column->attribute == $attribute) {
                    return $pos;
                } elseif (is_string($k) && $k == $attribute) {
                    return $pos;
                }
            } elseif ($class != null) {
                if ($column instanceof $class) {
                    return $pos;
                }
            }
        }
        return false;
    }

    /**
     * Get the position of the first DataColumn
     * @param DataColumn|string $class [optional] look for specific column class
     * default null looks for \yii\grid\DataColumn
     * @param boolean $checkSortable [optional] only consider sortable columns default false
     * @param boolean $returnName [optional] return attribute name instead of position default false
     * @return integer|false
     */
    public function getFirstDataColumnPosition($class = null, $checkSortable = false, $returnName = false)
    {
        $class = ($class != null ? : \yii\grid\DataColumn::className());
        $pos = -1;
        foreach ($this->columns as $k => $column) {
            $pos++;
            if ($column instanceof $class) {
                if ($checkSortable) {
                    if (isset($column->enableSorting) && $column->enableSorting) {
                        return ($returnName ? (isset($column->attribute) ? $column->attribute : (is_string($k) ? $k : $pos)) : $pos);
                    }
                } else {
                    return ($returnName ? (isset($column->attribute) ? $column->attribute : (is_string($k) ? $k : $pos)) : $pos);
                }
            }
        }
        return false;
    }

    /**
     * Return the sort column and direction active in the dataProvider
     * @param boolean $position [optional] should column position be returned
     * rather than attribute name default false
     * @return array
     */
    public function getSortColumnFromDataProvider($position = false)
    {
        $orders = $this->dataProvider->getSort()->getOrders();
        if ($orders) {
            $order = array_slice($orders, 0, 1);
            $sortAttribute = key($order);
            $sortDirection = $order[key($order)];
            if ($position) {
                $sortPosition = $this->getColumnPosition($sortAttribute);
                if ($sortPosition !== false) {
                    return [$sortPosition, ($sortDirection == SORT_DESC ? 'desc' : 'asc')];
                }
            } else {
                return [$sortAttribute, ($sortDirection == SORT_DESC ? 'desc' : 'asc')];
            }
        }
        $sortPosition = $this->getFirstDataColumnPosition(null, true, !$position);
        if ($sortPosition !== false) {
            return [$sortPosition, 'asc'];
        }
        return [];
    }
}