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

namespace fangface\validators;

class StrengthValidator extends \kartik\password\StrengthValidator
{

    /**
     * @var array the default rule settings
     */
    private static $_rules = [
        self::RULE_MIN => [
            'msg' => '{attribute} should contain at least {n, plural, one{one character} other{# characters}} ({found} found)!',
            'int' => true
        ],
        self::RULE_MAX => [
            'msg' => '{attribute} should contain at most {n, plural, one{one character} other{# characters}} ({found} found)!',
            'int' => true
        ],
        self::RULE_LEN => [
            'msg' => '{attribute} should contain exactly {n, plural, one{one character} other{# characters}} ({found} found)!',
            'int' => true
        ],
        self::RULE_USER => [
            'msg' => '{attribute} cannot contain the username',
            'bool' => true
        ],
        self::RULE_EMAIL => [
            'msg' => '{attribute} cannot contain an email address',
            'match' => '/^([\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+\.)*[\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+@((((([a-z0-9]{1}[a-z0-9\-]{0,62}[a-z0-9]{1})|[a-z])\.)+[a-z]{2,6})|(\d{1,3}\.){3}\d{1,3}(\:\d{1,5})?)$/i',
            'bool' => true
        ],
        self::RULE_LOW => [
            'msg' => '{attribute} should contain at least {n, plural, one{one lower case character} other{# lower case characters}} ({found} found)!',
            'match' => '![a-z]!',
            'int' => true
        ],
        self::RULE_UP => [
            'msg' => '{attribute} should contain at least {n, plural, one{one upper case character} other{# upper case characters}} ({found} found)!',
            'match' => '![A-Z]!',
            'int' => true
        ],
        self::RULE_NUM => [
            'msg' => '{attribute} should contain at least {n, plural, one{one numeric / digit character} other{# numeric / digit characters}} ({found} found)!',
            'match' => '![\d]!',
            'int' => true
        ],
        self::RULE_SPL => [
            'msg' => '{attribute} should contain at least {n, plural, one{one special character} other{# special characters}} ({found} found)!',
            'match' => '![\W]!',
            'int' => true
        ]
    ];

    /**
     * Validation of the attribute
     *
     * @param Model $object
     * @param string $attribute
     */
    public function validateAttribute($object, $attribute)
    {
        $value = $object->$attribute;
        if (!is_string($value)) {
            $this->addError($object, $attribute, $this->strError);
            return;
        }
        $label = $object->getAttributeLabel($attribute);
        $username = '';
        if ($this->userAttribute) {
            $username = $object[$this->userAttribute];
        }
        $temp = [];

        foreach (self::$_rules as $rule => $setup) {
            $param = "{$rule}Error";
            if ($rule === self::RULE_USER && $this->hasUser && $username && strpos($value, $username) > 0) {
                $this->addError($object, $attribute, $this->$param, ['attribute' => $label]);
            } elseif ($rule === self::RULE_EMAIL && $this->hasEmail && preg_match($setup['match'], $value, $matches)) {
                $this->addError($object, $attribute, $this->$param, ['attribute' => $label]);
            } elseif (!empty($setup['match']) && $rule !== self::RULE_EMAIL && $rule !== self::RULE_USER) {
                $count = preg_match_all($setup['match'], $value, $temp);
                if ($count < $this->$rule) {
                    $this->addError($object, $attribute, $this->$param, [
                        'attribute' => $label,
                        'found' => $count
                    ]);
                }
            } else {
                $length = strlen($value);
                $test = false;

                if ($rule === self::RULE_LEN) {
                    $test = ($length !== $this->$rule);
                } elseif ($rule === self::RULE_MIN) {
                    $test = ($length < $this->$rule);
                } elseif ($rule === self::RULE_MAX) {
                    $test = ($length > $this->$rule);
                }

                if ($this->$rule !== null && $test) {
                    $this->addError($object, $attribute, $this->$param, [
                        'attribute' => $label . ' (' . $rule . ' , ' . $this->$rule . ')',
                        'found' => $length
                    ]);
                }
            }
        }
    }

    /**
     * Client validation
     *
     * @param Model $object
     * @param string $attribute
     * @param View $view
     * @return string javascript method
     */
    public function clientValidateAttribute($object, $attribute, $view)
    {
        return '';
    }
}
