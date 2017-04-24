<?php

namespace RFM\Storage;

/**
 *    BaseStorage PHP class
 *
 *    Base abstract class created to define base methods
 *
 *    @license    MIT License
 *    @author        Pavel Solomienko <https://github.com/servocoder/>
 *    @copyright    Authors
 */

abstract class BaseStorage
{
    use StorageTrait;

    /**
     * BaseStorage constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        // fix display non-latin chars correctly
        // https://github.com/servocoder/RichFilemanager/issues/7
        setlocale(LC_CTYPE, 'en_US.UTF-8');

        // fix for undefined timezone in php.ini
        // https://github.com/servocoder/RichFilemanager/issues/43
        if(!ini_get('date.timezone')) {
            date_default_timezone_set('GMT');
        }

        $this->setConfig($config);
    }

    /**
     * @inheritdoc
     */
    public function setConfig($options)
    {
        app()->configure('storage.' . $this->getName(), $options);

        // update logger configuration
        if ($this->config('logger.enabled') === true) {
            logger()->enabled = true;
        }
        if (is_string($this->config('logger.file'))) {
            logger()->file = $this->config('logger.file');
        }
    }

    /**
     * Clean string to retrieve correct file/folder name.
     * @param string $string
     * @param array $allowed
     * @return array|mixed
     */
    public function normalizeString($string, $allowed = [])
    {
        $allow = '';
        if(!empty($allowed)) {
            foreach ($allowed as $value) {
                $allow .= "\\$value";
            }
        }

        if($this->config('security.normalizeFilename') === true) {
            // Remove path information and dots around the filename, to prevent uploading
            // into different directories or replacing hidden system files.
            // Also remove control characters and spaces (\x00..\x20) around the filename:
            $string = trim(basename(stripslashes($string)), ".\x00..\x20");

            // Replace chars which are not related to any language
            $replacements = [' '=>'_', '\''=>'_', '/'=>'', '\\'=>''];
            $string = strtr($string, $replacements);
        }

        if($this->config('options.charsLatinOnly') === true) {
            // transliterate if extension is loaded
            if(extension_loaded('intl') === true && function_exists('transliterator_transliterate')) {
                $options = 'Any-Latin; Latin-ASCII; NFD; [:Nonspacing Mark:] Remove; NFC;';
                $string = transliterator_transliterate($options, $string);
            }
            // clean up all non-latin chars
            $string = preg_replace("/[^{$allow}_a-zA-Z0-9]/u", '', $string);
        }

        // remove double underscore
        $string = preg_replace('/[_]+/', '_', $string);

        return $string;
    }

    /**
     * Check whether given mime type is image.
     *
     * @param string $mime
     * @return bool
     */
    public function isImageMimeType($mime)
    {
        $imagesMime = [
            "image/jpeg",
            "image/png",
            "image/gif",
            "image/bmp",
            "image/svg+xml",
        ];
        return in_array($mime, $imagesMime);
    }
}