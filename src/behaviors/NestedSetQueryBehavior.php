<?php

/**
 *
 * @link https://github.com/creocoder/yii2-nested-set-behavior
 * @copyright Copyright (c) 2013 Alexander Kochetov
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace fangface\behaviors;

use fangface\db\ActiveRecord;
use yii\base\Behavior;
use yii\db\ActiveQuery;

/**
 *
 * @author Alexander Kochetov <creocoder@gmail.com>
 */
class NestedSetQueryBehavior extends Behavior
{

    /**
     * @var ActiveQuery the owner of this behavior.
     */
    public $owner;

    /**
     * @var boolean
     */
    public $hasManyRoots = false;

    /**
     * @var boolean
     */
    public $hasPaths = false;

    /**
     * @var string
     */
    public $rootAttribute = 'root';

    /**
     * @var string
     */
    public $leftAttribute = 'lft';

    /**
     * @var string
     */
    public $rightAttribute = 'rgt';

    /**
     * @var string
     */
    public $levelAttribute = 'level';

    /**
     * @var string
     */
    public $nameAttribute = 'name';

    /**
     * @var string
     */
    public $pathAttribute = 'path';


    /**
     * Gets root node(s)
     *
     * @return \yii\db\ActiveQuery the owner
     */
    public function roots()
    {
        /** @var $modelClass ActiveRecord */
        $modelClass = $this->owner->modelClass;
        $this->owner->andWhere($modelClass::getDb()->quoteColumnName($this->leftAttribute) . '=1');
        return $this->owner;
    }

}
