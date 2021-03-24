<?php

namespace Ingenico\Ingenico\Plugin\View;

use \Magento\Framework\View\Asset\Minification;

class MinificationPlugin
{
    /**
     * Exclude static componentry files from being minified.
     *
     * Using the config node `minify_exclude` is not an option because it does
     * not get merged but overridden by subsequent modules.
     *
     * @see \Magento\Framework\View\Asset\Minification::XML_PATH_MINIFICATION_EXCLUDES
     *
     * @param Minification $subject
     * @param string[] $excludes
     * @param string $contentType
     * @return string[]
     */
    public function afterGetExcludes(Minification $subject, array $excludes, $contentType)
    {
        // Here we are checking if not “js” then skip it
        if ($contentType !== 'js') {
            return $excludes; // Skip if contentType is not “js”
        }

        // Array variable adds path to exclude minify
        $excludes[] = 'https://www.paynimo.com/';
        return $excludes;
    }
}
