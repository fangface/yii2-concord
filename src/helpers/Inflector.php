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

namespace fangface\helpers;

/**
 * Inflector extends yii2 built in Inflector
 */
class Inflector extends \yii\helpers\Inflector
{

    /**
     * Returns a string with all spaces converted to given replacement,
     * non word characters removed and the rest of characters transliterated.
     *
     * If intl extension isn't available uses fallback that converts latin characters only
     * and removes the rest. You may customize characters map via $transliteration property
     * of the helper.
     *
     * Slightly different to static::slug() in that underscores and URL slashes are permitted to
     * remain
     *
     * @param string $string An arbitrary string to convert
     * @param string $replacement The replacement to use for spaces
     * @param boolean $lowercase whether to return the string in lowercase or not. Defaults to `true`.
     * @return string The converted string.
     */
    public static function toAsciiUrl($string, $replacement = '-', $lowercase = true)
    {
        $string = static::transliterate($string);
        $string = preg_replace('/[^a-zA-Z0-9\/_\s—–-]+/u', '', $string);
        $string = preg_replace('/[=\s—–-]+/u', $replacement, $string);
        $string = trim($string, $replacement);
        return $lowercase ? strtolower($string) : $string;
    }

    /**
     * Returns a string with all spaces converted to given replacement,
     * non word characters removed and the rest of characters transliterated.
     *
     * If intl extension isn't available uses fallback that converts latin characters only
     * and removes the rest. You may customize characters map via $transliteration property
     * of the helper.
     *
     * Slightly different to static::slug() in that underscores and dots are permitted to
     * remain
     *
     * @param string $string An arbitrary string to convert
     * @param string $replacement The replacement to use for spaces
     * @param boolean $lowercase whether to return the string in lowercase or not. Defaults to `true`.
     * @return string The converted string.
     */
    public static function toAsciiFile($string, $replacement = '-', $lowercase = true)
    {
        $parts = explode('.', $string);
        foreach ($parts as $k => $v) {
            if ($v) {
                $v = static::transliterate($v);
                $v = preg_replace('/[^a-zA-Z0-9_=\s—–-]+/u', '', $v);
                $v = preg_replace('/[=\s—–-]+/u', $replacement, $v);
                $v = trim($v, $replacement);
                $parts[$k] = $v;
            }
        }
        $string = implode('.', $parts);
        return $lowercase ? strtolower($string) : $string;
    }



}
