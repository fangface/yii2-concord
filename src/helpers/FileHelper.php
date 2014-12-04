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

class FileHelper extends \yii\helpers\FileHelper
{

    public static function is_dir($file)
    {
        return @is_dir($file);
	}

	public static function is_file($file)
	{
	    return @is_file($file);
	}

	public static function file_exists($file)
	{
	    return @file_exists($file);
	}

	public static function filesize($file)
	{
	    return @filesize($file);
	}

	public static function unlink($file)
	{
        if (static::file_exists($file)) {
	       return @unlink($file);
        }
        return true;
	}

	public static function rmdir($dir)
	{
        if (static::file_exists($dir)) {
            if (static::is_dir($dir)) {
                return @rmdir($dir);
            }
            return false;
        }
        return true;
	}

	public static function filemtime($filename)
	{
	    return @filemtime($filename);
	}

	public static function copy($from, $to)
	{
		return @copy($from, $to);
	}

	public static function rename($from, $to)
	{
	    return @rename($from, $to);
	}

	public static function makeFile($filename, $content = null)
	{
		if ($handle = fopen($filename, 'w')) {
		    if ($content !== null) {
		        fwrite($handle, $content);
		    }
			fclose($handle);
			return true;
		}
		return false;
	}

	public static function readFile($filename)
	{
		return file_get_contents($filename);
	}

	public static function writefile($filename, $content)
	{
		return @file_put_contents($filename, $content);
	}

    /**
     * Removes a directory (and all its content) recursively.
     * @param string $dir the directory to be deleted recursively.
     * @param array $options options for directory remove. Valid options are:
     * @return boolean success
     *
     * - traverseSymlinks: boolean, whether symlinks to the directories should be traversed too.
     *   Defaults to `false`, meaning the content of the symlinked directory would not be deleted.
     *   Only symlink would be removed in that default case.
     */
    public static function removeDirectory($dir, $options = [])
    {
        if (static::file_exists($dir)) {
            if (!is_dir($dir)) {
                return false;
            }
            if (!is_link($dir) || isset($options['traverseSymlinks']) && $options['traverseSymlinks']) {
                if (!($handle = opendir($dir))) {
                    return false;
                }
                while (($file = readdir($handle)) !== false) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    $path = $dir . DIRECTORY_SEPARATOR . $file;
                    if (is_dir($path)) {
                        static::removeDirectory($path, $options);
                    } else {
                        static::unlink($path);
                    }
                }
                closedir($handle);
            }
            clearstatcache();
            if (is_link($dir)) {
                static::unlink($dir);
            } else {
                $result = static::rmdir($dir);
                if (!$result) {
                    // wait for 1/2 second
                    usleep(500000);
                    clearstatcache();
                    $result = static::rmdir($dir);
                }
            }
            clearstatcache();
            if (static::file_exists($dir)) {
                return false;
            }
        }
        return true;
    }
}
