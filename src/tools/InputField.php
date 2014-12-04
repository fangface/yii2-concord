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

namespace fangface\tools;

use yii\helpers\ArrayHelper;

class InputField
{

    const INPUT_TEXT                    = 'textInput';
    const INPUT_TEXTAREA                = 'textarea';
    const INPUT_PASSWORD                = 'passwordInput';
    const INPUT_DROPDOWN_LIST           = 'dropdownList';
    const INPUT_LIST_BOX                = 'listBox';
    const INPUT_CHECKBOX                = 'checkbox';
    const INPUT_RADIO                   = 'radio';
    const INPUT_CHECKBOX_LIST           = 'checkboxList';
    const INPUT_RADIO_LIST              = 'radioList';
    const INPUT_MULTISELECT             = 'multiselect';
    const INPUT_STATIC                  = 'staticInput';
    const INPUT_FILE                    = 'fileInput';
    const INPUT_HTML5                   = 'input';
    const INPUT_WIDGET                  = 'widget';
    const INPUT_RAW                     = 'raw';

    const INPUT_INTEGER                 = 'textInputInteger';
    const INPUT_DECIMAL                 = 'textInputDecimal';
    const INPUT_DATE                    = 'textInputDate';
    const INPUT_DATETIME                = 'textInputDatetime';
    const INPUT_TIME                    = 'textInputTime';
    const INPUT_YEAR                    = 'textInputYear';
    const INPUT_COLOR                   = 'textInputColor';
    const INPUT_READONLY                = 'textInputReadonly';
    const INPUT_CHECKBOX_SWITCH         = 'checkboxSwitch';
    const INPUT_CHECKBOX_ICHECK         = 'checkboxIcheck';
    const INPUT_CHECKBOX_LIST_ICHECK    = 'checkboxListIcheck';
    const INPUT_RADIO_LIST_ICHECK       = 'radioListIcheck';
    const INPUT_PASSWORD_STRENGTH       = 'textInputPasswordStrength';
    const INPUT_SELECT2                 = 'dropdownList2';
    const INPUT_SELECT2_MULTI           = 'dropdownList2Multi';
    const INPUT_SELECT2_TAGS            = 'dropdownList2Tags';
    const INPUT_SELECT_PICKER           = 'dropdownListPicker';
    const INPUT_SELECT_PICKER_MULTI     = 'dropdownListPickerMulti';
    const INPUT_EDITOR_CK               = 'editor_CK';
    const INPUT_EDITOR_BS_WYSIHTML5     = 'editor_BSW5';
    const INPUT_EDITOR_BS_SUMMERNOTE    = 'editor_BSSN';


    /**
     * Return default input field type based on column schema for attribute
     * @param string $attributeName
     * @param \yii\db\ColumnSchema $columnSchema
     * @param array|null $config attributeConfig (active element)
     * @return string
     */
    public static function getDefaultInputFieldType($attributeName, $columnSchema, $config = null)
    {
        if (is_array($config) && $config) {
            $type = ArrayHelper::getValue($config, 'type', '');
            if ($type != '') {
                return $type;
            }
        }

        switch ($attributeName) {
            case 'id':
                $type = self::INPUT_STATIC;
                break;
            case 'created_at':
            case 'createdAt':
            case 'modified_at':
            case 'modifiedAt':
                $type = self::INPUT_STATIC;
                break;
            default:
                switch ($columnSchema->type) {
                    case 'string':
                    // case 'char':
                    // case 'varchar':
                        if (is_array($columnSchema->enumValues) && $columnSchema->enumValues) {
                            $type = self::INPUT_DROPDOWN_LIST;
                        } else {
                            $type = self::INPUT_TEXT;
                        }
                        break;
                    case 'text':
                    //case 'tinytext':
                    //case 'mediumtext':
                    //case 'longtext':
                    case 'binary':
                    //case 'varbinary':
                    //case 'blob':
                    //case 'tinyblob':
                    //case 'mediumblob':
                    //case 'longblob':
                        $type = self::INPUT_TEXTAREA;
                        break;
                    case 'int':
                    case 'integer':
                    case 'tinyint':
                    case 'smallint':
                    case 'mediumint':
                    case 'bigint':
                        if ($columnSchema->size == 1) {
                            //$type = self::INPUT_CHECKBOX_SWITCH;
                            $type = self::INPUT_CHECKBOX_ICHECK;
                            //$type = self::INPUT_CHECKBOX;
                        } else {
                            $type = self::INPUT_INTEGER;
                        }
                        break;
                    case 'float':
                    case 'real':
                    case 'double':
                    case 'decimal':
                    case 'numeric':
                        $type = self::INPUT_DECIMAL;
                        break;
                    case 'date':
                        if ($columnSchema->size == 4) {
                            $type = self::INPUT_YEAR;
                        } else {
                            $type = self::INPUT_DATE;
                        }
                        break;
                    case 'datetime':
                        $type = self::INPUT_DATETIME;
                        break;
                    case 'time':
                        $type = self::INPUT_TIME;
                        break;
                    default:
                        \fangface\Tools::debug($columnSchema, __CLASS__);
                        $type = self::INPUT_TEXT;
                        break;
                }
                break;
        }
        return $type;
    }

    /**
     * Get default widget class based on input field type
     * @param string $type
     * @return string
     */
    public static function getWidgetClassNameFromFieldType($type)
    {
        switch ($type) {
            case self::INPUT_DATE:
                $widgetClass = DatePicker::className();
                break;
            case self::INPUT_DATETIME:
                $widgetClass = DateTimePicker::className();
                break;
            case self::INPUT_COLOR:
                $widgetClass = ColorInput::className();
                break;
            case self::INPUT_SELECT2_MULTI:
            case self::INPUT_SELECT2_TAGS:
            case self::INPUT_SELECT2:
                $widgetClass = Select2::className();
                break;
            case self::INPUT_SELECT_PICKER:
            case self::INPUT_SELECT_PICKER_MULTI:
                $widgetClass = BootstrapSelect::className();
                break;
            case self::INPUT_EDITOR_CK:
                $widgetClass = CKEditor::className();
                break;
            case self::INPUT_EDITOR_BS_WYSIHTML5:
                $widgetClass = BootstrapWysihtml5::className();
                break;
            case self::INPUT_EDITOR_BS_SUMMERNOTE:
                $widgetClass = BootstrapSummernote::className();
                break;
            default:
                $widgetClass = '';
                break;
        }
        return $widgetClass;
    }

    /**
     * Return true if input field type is an editor area
     * @param string $type
     * @return boolean
     */
    public static function getIsEditorFromFieldType($type)
    {
        switch ($type) {
            case InputField::INPUT_EDITOR_CK:
            case InputField::INPUT_EDITOR_BS_WYSIHTML5:
            case InputField::INPUT_EDITOR_BS_SUMMERNOTE:
                return true;
            default:
                return false;
        }
    }
}
