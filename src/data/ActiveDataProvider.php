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

namespace fangface\data;

use yii\db\QueryInterface;


/**
 * Extends yii\data\ActiveDataProvider to allow for a count of models
 * ignoring any front-end user filters they may have been applied
 */
class ActiveDataProvider extends \yii\data\ActiveDataProvider
{

    private $_totalUnfilteredCount;
    private $_baseFilter;
    private $_baseParams = [];

    /**
     * Returns the total number of data models excluding filters.
     * @return integer total number of possible unfiltered data models.
     */
    public function getTotalUnfilteredCount()
    {
        if ($this->_totalUnfilteredCount === null) {
            if ($this->query->where == $this->_baseFilter && $this->query->params == $this->_baseParams) {
                // same as normal total count, which may have already been loaded
                $this->_totalUnfilteredCount = $this->getTotalCount();
            } else {
                $this->_totalUnfilteredCount = $this->prepareTotalUnfilteredCount();
            }
        }
        return $this->_totalUnfilteredCount;
    }

    /**
     * Sets the total number of unfiltered data models.
     * @param integer $value the total number of unfiltered data models.
     */
    public function setTotalUnfilteredCount($value)
    {
        $this->_totalUnfilteredCount = $value;
    }

    /**
     * Set base filter and params that should remain for use when obtaining an unfiltered count
     * @param \yii\db\ActiveQuery $query
     */
    public function setBaseFilterByQuery($query)
    {
        $this->setBaseFilter($query->where);
        $this->setBaseParams($query->params);
    }

    /**
     * Set the base filter that should remain for use when obtaining an unfiltered count
     * @param array|null $value
     */
    public function setBaseFilter($value)
    {
        $this->_baseFilter = $value;
    }

    /**
     * Set the base params that should remain for use when obtaining an unfiltered count
     * @param array $value
     */
    public function setBaseParams($value)
    {
        $this->_baseParams = $value;
    }

    /**
     * Run the query to obtain a total count with no filters applied
     * @param integer $value the total number of unfiltered data models.
     */
    protected function prepareTotalUnfilteredCount()
    {
        if (!$this->query instanceof QueryInterface) {
            throw new InvalidConfigException('The "query" property must be an instance of a class that implements the QueryInterface e.g. yii\db\Query or its subclasses.');
        }
        $query = clone $this->query;
        return (int) $query->where($this->_baseFilter, $this->_baseParams)->limit(-1)->offset(-1)->orderBy([])->count('*', $this->db);
    }
}