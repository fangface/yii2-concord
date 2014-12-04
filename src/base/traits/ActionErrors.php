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

namespace fangface\base\traits;

use fangface\Tools;

trait ActionErrors {

    protected $actionErrors             = array();
    protected $actionWarnings           = array();

    /**
     * Returns the number of action errors (those errors logged during predetermined actions such
     * as saveAll() and deleteFull()
     *
     * @return integer number of errors
     */
    public function hasActionErrors()
    {
        return count($this->actionErrors);
    }


    /**
     * Returns the action errors  (those errors logged during predetermined actions such
     * as saveAll() and deleteFull()
     *
     * @return array array of action errors message and code
     */
    public function getActionErrors()
    {
        return $this->actionErrors;
    }


    /**
     * Returns the action errors  (those errors logged during predetermined actions such
     * as saveAll() and deleteFull() as a simplifed message array
     *
     * @param boolean $noAttribute Exclude attribute name from error text
     * @return array Action errors message and code
     */
    public function getBasicActionErrors($noAttribute = false)
    {
        $array = [];
        if ($this->actionErrors) {
            foreach ($this->actionErrors as $k => $error) {
                foreach ($error['message'] as $k2 => $message) {
                    $array[] = ($noAttribute ? '' : ($error['attribute'] ? $error['attribute'] . ' - ' : '')) . $message . ($error['code'] ? ' (' . $error['code'] . ')' : '');
                }
            }
        }
        return $array;
    }


    /**
     * Returns the first action error
     *
     * @return array
     * @see getActionErrors()
     * @see addActionError()
     */
    public function getFirstActionError()
    {
        return ($this->actionErrors ? reset($this->actionErrors) : null);
    }

    /**
     * Adds a new action error
     * @param string $message new error message
     * @param integer $code new error code
     * @param string $attribute attribute to which the error applies
     * @param string $modelName model to which the error applies
     */
    public function addActionError($message, $code = 0, $attribute = '', $modelName = null)
    {
        $message = is_array($message) ? $message : array($message);
        $this->actionErrors[] = array(
            'message' => $message,
            'code' => $code,
            'attribute' => $attribute,
            'model' => ($modelName !== null ? $modelName : (true ? Tools::getClassName($this) : get_called_class()))
        );
    }


    /**
     * Merge an existing array of action errors into the current models
     * action errors
     * @param array $errors array of action errors
     */
    public function mergeActionErrors($errors)
    {
        if (is_array($errors) && $errors) {
            if (!$this->actionErrors) {
                $this->actionErrors = $errors;
            } else {
                $this->actionErrors = array_merge($this->actionErrors, $errors);
            }
        }
    }


    /**
     * Reset action errors, typically called ahead of predefined actions such as saveAll() and deleteFull()
     */
    public function clearActionErrors()
    {
        $this->actionErrors = [];
        $this->actionWarnings = [];
    }

    /**
     * Returns the number of action warnings (those warnings logged during predetermined actions such
     * as saveAll() and deleteFull()
     *
     * @return integer number of warnings
    */
    public function hasActionWarnings()
    {
        return count($this->actionWarnings);
    }


    /**
     * Returns the action warnings  (those warnings logged during predetermined actions such
     * as saveAll() and deleteFull()
     *
     * @return array array of action warnings message and code
     */
    public function getActionWarnings()
    {
        return $this->actionWarnings;
    }


    /**
     * Returns the action warnings  (those warnings logged during predetermined actions such
     * as saveAll() and deleteFull() as a simplifed message array
     *
     * @param boolean $noAttribute Exclude attribute name from error text
     * @return array array of action warnings message and code
     */
    public function getBasicActionWarnings($noAttribute = false)
    {
        $array = [];
        if ($this->actionWarnings) {
            foreach ($this->actionWarnings as $k => $error) {
                foreach ($error['message'] as $k2 => $message) {
                    $array[] = ($noAttribute ? '' : ($error['attribute'] ? $error['attribute'] . ' - ' : '')) . $message . ($error['code'] ? ' (' . $error['code'] . ')' : '');
                }
            }
        }
        return $array;
    }


    /**
     * Returns the first action warning
     *
     * @return array
     * @see getActionWarnings()
     * @see addActionWarning()
     */
    public function getFirstActionWarning()
    {
        return ($this->actionWarnings ? reset($this->actionWarnings) : null);
    }

    /**
     * Adds a new action warning
     * @param string $message new warning message
     * @param integer $code new warning code
     * @param string $attribute attribute to which the error applies
     * @param string $modelName model to which the error applies
     */
    public function addActionWarning($message, $code = 0, $attribute = '', $modelName = null)
    {
        $message = is_array($message) ? $message : array($message);
        $this->actionWarnings[] = array(
            'message' => $message,
            'code' => $code,
            'attribute' => $attribute,
            'model' => ($modelName !== null ? $modelName : (true ? Tools::getClassName($this) : get_called_class()))
        );
    }


    /**
     * Merge an existing array of action warnings into the current models
     * action warnings
     * @param array $warnings array of action warnings
     */
    public function mergeActionWarnings($warnings)
    {
        if (is_array($warnings) && $warnings) {
            if (!$this->actionWarnings) {
                $this->actionWarnings = $warnings;
            } else {
                $this->actionWarnings = array_merge($this->actionWarnings, $warnings);
            }
        }
    }

}
