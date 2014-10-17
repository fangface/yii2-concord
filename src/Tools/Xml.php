<?php

namespace Concord\Tools;

class Xml
{

    private $array;
    private $rootElement;
    private $encodeUTF8;
    private $decodeUTF8;


    public function __construct($rootElement = '')
    {
        $this->rootElement = $rootElement;
        $this->setDefaults();
        $this->reset();
    }


    public function setDefaults()
    {
        $this->encodeUTF8 = true;
        $this->decodeUTF8 = false;
    }


    public function getRootElement()
    {
        return $this->rootElement;
    }


    public function setEncodeUTF8($value)
    {
        $this->encodeUTF8 = $value;
    }


    public function setDecodeUTF8($value)
    {
        $this->decodeUTF8 = $value;
    }


    public function reset()
    {
        $this->resetArray();
    }


    public function resetArray()
    {
        $this->array = array();
    }


    public function setArray($v, $autoFormat = true)
    {
        $this->array = array();
        if ($v && is_array($v)) {
            foreach ($v as $_k => $_v) {
                if ($this->rootElement == '') {
                    $this->rootElement = $_k;
                }
            }
            if ($autoFormat) {
                $this->array[$this->rootElement] = array(
                    '@data' => $this->convertToLocalArray($_v)
                );
            } else {
                $this->array[$this->rootElement] = $_v;
            }
        }
    }


    public function set($k, $v, $attribs = false, $autoFormat = true)
    {
        $allElements = explode(".", $k);
        $evalString = '';
        $evalStringBase = '';
        foreach ($allElements as $e) {
            if ($evalString == '' && $this->rootElement == '') {
                $this->rootElement = $e;
            } elseif ($evalString == '' && $this->rootElement != '' && $this->rootElement != $e) {
                $e = $this->rootElement;
            }
            $evalStringBase = $evalString . '[\'' . $e . '\']';
            $evalString .= '[\'' . $e . '\'][\'@data\']';
            eval('$isSet = @isset($this->array' . $evalString . ');');
            if (!$isSet) {
                eval('$this->array' . $evalString . ' = \'\';');
            }
        }
        if ($autoFormat && is_array($v)) {
            $v = $this->convertToLocalArray($v);
        }
        eval('$this->array' . $evalString . ' = $v;');
        if ($attribs && $evalStringBase != '') {
            eval('$this->array' . $evalStringBase . '[\'@attribs\'] = $attribs;');
        }
    }


    public function setAttribute($k, $attrib, $v)
    {
        if ($k != '' && $attrib != '') {
            $allElements = explode(".", $k);
            $evalString = '';
            $evalStringBase = '';
            foreach ($allElements as $e) {
                if ($evalString == '' && $this->rootElement == '') {
                    $this->rootElement = $e;
                } elseif ($evalString == '' && $this->rootElement != '' && $this->rootElement != $e) {
                    $e = $this->rootElement;
                }
                $evalStringBase = $evalString . '[\'' . $e . '\']';
                $evalString .= '[\'' . $e . '\'][\'@data\']';
                eval('$isSet = @isset($this->array' . $evalString . ');');
                if (!$isSet) {
                    eval('$this->array' . $evalString . ' = \'\';');
                }
            }
            if ($evalStringBase != '') {
                eval('$this->array' . $evalStringBase . '[\'@attribs\'][\'' . $attrib . '\'] = $v;');
            }
        }
    }


    public function setAttributes($k, $attribs)
    {
        if ($k != '' && $attribs && is_array($attribs)) {
            $allElements = explode(".", $k);
            $evalString = '';
            $evalStringBase = '';
            foreach ($allElements as $e) {
                if ($evalString == '' && $this->rootElement == '') {
                    $this->rootElement = $e;
                } elseif ($evalString == '' && $this->rootElement != '' && $this->rootElement != $e) {
                    $e = $this->rootElement;
                }
                $evalStringBase = $evalString . '[\'' . $e . '\']';
                $evalString .= '[\'' . $e . '\'][\'@data\']';
                eval('$isSet = @isset($this->array' . $evalString . ');');
                if (!$isSet) {
                    eval('$this->array' . $evalString . ' = \'\';');
                }
            }
            if ($evalStringBase != '') {
                eval('$this->array' . $evalStringBase . '[\'@attribs\'] = $attribs;');
            }
        }
    }


    public function addToArray($k, $v, $attribs = false, $autoFormat = true)
    {
        $allElements = explode(".", $k);
        $evalString = '';
        $evalStringBase = '';
        foreach ($allElements as $e) {
            if ($evalString == '' && $this->rootElement == '') {
                $this->rootElement = $e;
            } elseif ($evalString == '' && $this->rootElement != '' && $this->rootElement != $e) {
                $e = $this->rootElement;
            }
            $evalStringBase = $evalString . '[\'' . $e . '\']';
            $evalString .= '[\'' . $e . '\'][\'@data\']';
            eval('$isSet = @isset($this->array' . $evalString . ');');
            if (!$isSet) {
                eval('$this->array' . $evalString . ' = \'\';');
            }
        }
        eval('$isArray = is_array($this->array' . $evalString . ');');
        if (!$isArray) {
            eval('$this->array' . $evalString . ' = array();');
        }
        if ($autoFormat && is_array($v)) {
            $v = $this->convertToLocalArray($v);
        }
        eval('$this->array' . $evalString . '[] = array(\'@data\' => $v,\'@attribs\' => $attribs);');
    }


    public function convertToLocalArray($array)
    {
        $retVal = array();
        if ($array && is_array($array)) {
            foreach ($array as $k => $v) {
                $isMulti = false;
                if ($isArray = is_array($v)) {
                    $arrayKeys = array_keys($v);
                    if ($arrayKeys[0] == '0') {
                        $isMulti = true;
                    }
                }
                if ($isArray) {
                    if ($isMulti) {
                        foreach ($array as $k2 => $v2) {
                            $retVal[$k]['@data'][$k2] = $this->convertToLocalArray($v2);
                        }
                    }
                    $retVal[$k]['@data'] = $this->convertToLocalArray($v);
                } else {
                    $retVal[$k]['@data'] = $v;
                }
            }
        }
        return $retVal;
    }


    public function getDocument($forDisplay = false, $incPre = false)
    {
        if ($forDisplay) {
            $retVal = $this->writeXML($this->array, '', 0, true, '  ');
            if ($incPre) {
                return '<pre>' . str_replace(array(
                    '<',
                    '>'
                ), array(
                    '&lt;',
                    '&gt;'
                ), $retVal) . '</pre>';
            } else {
                return str_replace(array(
                    '<',
                    '>'
                ), array(
                    '&lt;',
                    '&gt;'
                ), $retVal);
            }
        } else {
            $retVal = $this->writeXML($this->array);
            return $retVal;
        }
    }


    public function writeXML($array, $branchIn = '', $level = 0, $useLongTag = true, $indentString = "\t")
    {
        $xml = '';
        $indent = ($level > 0 ? $this->repli($indentString, $level) : '');
        $wasArray = false;
        foreach ($array as $k => $v) {
            $branch = ($branchIn != '' ? $branchIn . '.' : '') . $k;
            $isMulti = false;
            if ($isArray = (isset($v['@data']) && is_array($v['@data']))) {
                $arrayKeys = array_keys($v['@data']);
                if ($arrayKeys[0] == '0') {
                    $isMulti = true;
                }
            }
            if ($isMulti) {
                $loopVar = $v['@data'];
                foreach ($loopVar as $k2 => $v2) {
                    if (is_array($v2['@data']) || is_object($v2['@data'])) {
                        $xml .= $indent . '<' . $k . $this->setXMLAttr($v2) . '>' . "\n";
                        $xml .= $this->writeXML($v2['@data'], $branch, ($level + 1), $useLongTag, $indentString);
                        $xml .= $indent . '</' . $k . '>' . "\n";
                    } else {
                        if ((isset($v2['@data']) && $v2['@data']) || (isset($v2['@attribs']) && $v2['@attribs']) || $useLongTag) {
                            $xml .= $indent . '<' . $k . $this->setXMLAttr($v2) . '>';
                            if (isset($v2['@data'])) {
                                $xml .= $this->encode($v2['@data']);
                            }
                            $xml .= '</' . $k . '>' . "\n";
                        } else {
                            $xml .= $indent . '<' . $k . '/>' . "\n";
                        }
                    }
                }
                $wasArray = false;
            } elseif ($isArray) {
                $xml .= $indent . '<' . $k . $this->setXMLAttr($v) . '>' . "\n";
                $xml .= $this->writeXML($v['@data'], $branch, ($level + 1), $useLongTag, $indentString);
                $xml .= $indent . '</' . $k . '>' . "\n";
                $wasArray = true;
            } else {
                if ((isset($v['@data']) && $v['@data']) || (isset($v['@attribs']) && $v['@attribs']) || $useLongTag) {
                    $xml .= $indent . '<' . $k . $this->setXMLAttr($v) . '>';
                    if (isset($v['@data'])) {
                        $xml .= $this->encode($v['@data']);
                    }
                    $xml .= '</' . $k . '>' . "\n";
                } else {
                    $xml .= $indent . '<' . $k . '/>' . "\n";
                }
                $wasArray = false;
            }
        }
        return $xml;
    }


    public function setXMLAttr($element)
    {
        $retVal = '';
        if (isset($element['@attribs']) && $element['@attribs'] && is_array($element['@attribs'])) {
            $loopVar = $element['@attribs'];
            foreach ($loopVar as $k => $v) {
                $retVal .= ' ' . $k . '="' . $this->encode($v, true) . '"';
            }
        }
        return $retVal;
    }


    public function encode($value, $isAttr = false)
    {
        if (!$this->encodeUTF8 || $this->is_utf8($value)) {
            $encoded_data = $value;
        } else {
            $encoded_data = $this->utf8_encode($value);
        }
        $escaped_data = htmlspecialchars($encoded_data);
        if ($escaped_data != $encoded_data) {
            $escaped_data = '<![CDATA[' . $escaped_data . ']]>';
        } else {
            $escaped_data = $encoded_data;
        }
        return $escaped_data;
    }


    public function decode($value)
    {
        if ($this->decodeUTF8 && $this->is_utf8($value)) {
            $retVal = $this->utf8_decode($value);
        } else {
            $retVal = $value;
        }
        return $retVal;
    }


    public function is_utf8($string)
    { // v1.01
        $_is_utf8_split = 4000;
        if (strlen($string) > $_is_utf8_split) {
            // Based on: http://mobile-website.mobi/php-utf8-vs-iso-8859-1-59
            for ($i = 0, $s = $_is_utf8_split, $j = ceil(strlen($string) / $_is_utf8_split); $i < $j; $i++, $s += $_is_utf8_split) {
                if ($this->is_utf8(substr($string, $s, $_is_utf8_split)))
                    return true;
            }
            return false;
        } else {
            return preg_match('%^(?:
				[\x09\x0A\x0D\x20-\x7E]              # ASCII
				| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
				|  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
				| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
				|  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
				|  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
				| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
				|  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
			)*$%xs', $string);
        }
    }


    public function getAttribute($k, $attrib = '')
    {
        return $this->get($k, $attrib);
    }


    public function get($k, $attrib = '', $raw = false, $defaultReturn = false, $isSetCheck = false)
    {
        $retVal = $defaultReturn;
        $wasSet = false;
        if ($k != '' && $this->array) {
            $allElements = explode(".", $k);
            $evalString = '';
            $evalStringBase = '';
            foreach ($allElements as $e) {
                $evalStringBase = $evalString . '[\'' . $e . '\']';
                $evalString .= '[\'' . $e . '\'][\'@data\']';
            }
            if ($attrib != '') {
                eval('$isSet = isset($this->array' . $evalStringBase . '[\'@attribs\']);');
                if ($isSet) {
                    if ($attrib == 'ALL-ATTRIBS') {
                        $wasSet = true;
                        eval('$retVal = $this->array' . $evalStringBase . '[\'@attribs\'];');
                    } else {
                        eval('$isSet = isset($this->array' . $evalStringBase . '[\'@attribs\'][\'' . $attrib . '\']);');
                        if ($isSet) {
                            $wasSet = true;
                            eval('$retVal = $this->array' . $evalStringBase . '[\'@attribs\'][\'' . $attrib . '\'];');
                        }
                    }
                }
            } else {
                eval('$isSet = isset($this->array' . $evalStringBase . '[\'@data\']);');
                if ($isSet) {
                    $wasSet = true;
                    eval('$retVal = $this->array' . $evalStringBase . '[\'@data\'];');
                    if (!$raw) {
                        if (is_array($retVal)) {
                            $retVal = $this->dataOnlyFromArray($retVal);
                        }
                    }
                } else {
                    if ($isSetCheck) {
                        eval('$isSet = isset($this->array' . $evalStringBase . ');');
                        if ($isSet) {
                            $wasSet = true;
                        }
                    }
                }
            }
        }

        if ($isSetCheck) {
            $retVal = $wasSet;
        }

        return $retVal;
    }


    public function get2($k, $defaultReturn = '', $attrib = '', $raw = false)
    {
        return $this->get($k, $attrib, $raw, $defaultReturn);
    }


    public function getIsSet($k, $attrib = '')
    {
        return $this->get($k, $attrib, false, false, true);
    }


    public function getMulti($k)
    {
        $retVal = $this->get($k);
        if ($retVal && is_array($retVal)) {
            $arrayKeys = array_keys($retVal);
            if ($arrayKeys[0] == '0') {
                // leave as is
            } else {
                $retVal = array(
                    $retVal
                );
            }
        }
        return $retVal;
    }


    public function getRaw($k, $attrib = '', $defaultReturn = false)
    {
        return $this->get($k, $attrib, true, $defaultReturn);
    }


    public function dataOnlyFromArray($thisArray)
    {
        $retVal = array();
        if ($thisArray && is_array($thisArray)) {
            foreach ($thisArray as $thisArrayKey => $thisArrayValue) {
                $thisValue = (isset($thisArrayValue['@data']) ? $thisArrayValue['@data'] : $thisArrayValue);
                if (is_array($thisValue) && $thisValue) {
                    $retVal[$thisArrayKey] = $this->dataOnlyFromArray($thisValue);
                } else {
                    $retVal[$thisArrayKey] = ($thisValue == array() ? '' : $thisValue);
                }
            }
        } else {
            $retVal = $thisArray;
        }
        return $retVal;
    }


    public function getArray($dataOnly = false)
    {
        if ($dataOnly && $this->array) {
            return $this->dataOnlyFromArray($this->array);
        } else {
            return $this->array;
        }
    }


    public function repli($strInput, $intCount)
    {
        $strResult = '';
        for ($i = 0; $i < $intCount; $i++) {
            $strResult = $strResult . $strInput;
        }
        return $strResult;
    }


    /**
     * Converts a simpleXML element into an array.
     * Preserves attributes and everything.
     * You can choose to get your elements either flattened, or stored in a custom index that
     * you define.
     * For example, for a given element
     * <field name="someName" type="someType"/>
     * if you choose to flatten attributes, you would get:
     * $array['field']['name'] = 'someName';
     * $array['field']['type'] = 'someType';
     * If you choose not to flatten, you get:
     * $array['field']['@attributes']['name'] = 'someName';
     * _____________________________________
     * Repeating fields are stored in indexed arrays. so for a markup such as:
     * <parent>
     * <child>a</child>
     * <child>b</child>
     * <child>c</child>
     * </parent>
     * you array would be:
     * $array['parent']['child'][0] = 'a';
     * $array['parent']['child'][1] = 'b';
     * ...And so on.
     * _____________________________________
     *
     * @param \SimpleXMLElement $xml
     *        the XML to convert
     * @param boolean $flattenValues
     *        wether to flatten values
     *        or to set them under a particular index.
     *        defaults to false;
     * @param boolean $flattenAttributes
     *        wether to flatten attributes
     *        or to set them under a particular index.
     *        Defaults to false;
     * @param boolean $flattenChildren
     *        wether to flatten children
     *        or to set them under a particular index.
     *        Defaults to false;
     * @param string $valueKey
     *        for values, in case $flattenValues was set to
     *        false. Defaults to "@data"
     * @param string $attributesKey
     *        for attributes, in case $flattenAttributes was set to
     *        false. Defaults to "@attribs"
     * @param string $childrenKey
     *        for children, in case $flattenChildren was set to
     *        false. Defaults to "@data"
     * @return array the resulting array.
     */
    public function simpleXMLToArray($xml, $flattenValues = false, $flattenAttributes = false, $flattenChildren = false, $valueKey = '@data', $attributesKey = '@attribs', $childrenKey = '@data')
    {
        $return = array();
        if (!($xml instanceof \SimpleXMLElement)) {
            return $return;
        }
        $name = $xml->getName();
        $_value = ((string) $xml);
        if (strlen($_value) == 0) {
            $_value = null;
        }
        ;

        if ($_value !== null) {
            if (!$flattenValues) {
                $return[$valueKey] = $_value;
            } else {
                $return = $_value;
            }
        }

        $children = array();
        $first = true;
        foreach ($xml->children() as $elementName => $child) {
            $value = $this->simpleXMLToArray($child, $flattenValues, $flattenAttributes, $flattenChildren, $valueKey, $attributesKey, $childrenKey);
            if (isset($children[$elementName])) {
                if ($first) {
                    $temp = $children[$elementName];
                    unset($children[$elementName]);
                    $children[$elementName]['@data'][] = $temp;
                    $first = false;
                }
                $children[$elementName]['@data'][] = $value;
            } else {
                $children[$elementName] = $value;
            }
        }
        if (count($children) > 0) {
            if (!$flattenChildren) {
                $return[$childrenKey] = $children;
            } else {
                $return = array_merge($return, $children);
            }
        }

        $attributes = array();
        foreach ($xml->attributes() as $name => $value) {
            $attributes[$name] = trim($value);
        }
        if (count($attributes) > 0) {
            if (!$flattenAttributes) {
                $return[$attributesKey] = $attributes;
            } else {
                $return = array_merge($return, $attributes);
            }
        }

        return $return;
    }


    public function simpleXMLStringToArray($XMLData, $rootElement = '', $flattenValues = false, $flattenAttributes = false, $flattenChildren = false, $valueKey = '@data', $attributesKey = '@attribs', $childrenKey = '@data')
    {
        $XMLData = trim($XMLData);
        if ($XMLData == '') {
            return false;
        }

        $rootElement = ($rootElement != '' ? $rootElement : $this->rootElement);

        libxml_clear_errors();
        $_oldValue = libxml_use_internal_errors(true);

        $xml = simplexml_load_string($XMLData);
        $retVal = $this->simpleXMLToArray($xml, $flattenValues, $flattenAttributes, $flattenChildren, $valueKey, $attributesKey, $childrenKey);

        if ($errors = libxml_get_errors()) {
            $retVal = false;
            foreach ($errors as $error) {
                trigger_error('simpleXMLFileToArray error ' . $error->code . ' : ' . trim(str_replace("\n", ' ', $error->message)) . ' : ' . $error->file . ' : ' . $error->line);
            }
        }
        libxml_use_internal_errors($_oldValue);
        libxml_clear_errors();

        if ($retVal) {
            return ($rootElement != '' ? array($rootElement => $retVal) : $retVal);
        } else {
            return false;
        }
    }


    public function simpleXMLFileToArray($XMLFile, $rootElement = '', $flattenValues = false, $flattenAttributes = false, $flattenChildren = false, $valueKey = '@data', $attributesKey = '@attribs', $childrenKey = '@data')
    {
        if ($XMLFile == '') {
            return false;
        }

        $rootElement = ($rootElement != '' ? $rootElement : $this->rootElement);

        libxml_clear_errors();
        $_oldValue = libxml_use_internal_errors(false);

        $xml = simplexml_load_file($XMLFile);
        $retVal = $this->simpleXMLToArray($xml, $flattenValues, $flattenAttributes, $flattenChildren, $valueKey, $attributesKey, $childrenKey);

        if ($errors = libxml_get_errors()) {
            $retVal = false;
            foreach ($errors as $error) {
                trigger_error('simpleXMLFileToArray error ' . $error->code . ' : ' . $error->message . ' : ' . $error->file . ' : ' . $error->line);
            }
        }
        libxml_use_internal_errors($_oldValue);
        libxml_clear_errors();

        if ($retVal) {
            return ($rootElement != '' ? array(
                $rootElement => $retVal
            ) : $retVal);
        } else {
            return false;
        }
    }


    public function readDocumentFromFile($XMLFile, $rootElement = '')
    {
        $rootElement = ($rootElement != '' ? $rootElement : $this->rootElement);
        $this->resetArray();
        $array = $this->simpleXMLFileToArray($XMLFile, $rootElement);
        if ($array) {
            $this->setArray($array, false);
        }
        return ($array ? true : false);
    }


    public function readDocumentFromString($XMLData, $rootElement = '')
    {
        $rootElement = ($rootElement != '' ? $rootElement : $this->rootElement);
        $this->resetArray();
        $array = $this->simpleXMLStringToArray($XMLData, $rootElement);
        if ($array) {
            $this->setArray($array, false);
        }
        return ($array ? true : false);
    }

}
