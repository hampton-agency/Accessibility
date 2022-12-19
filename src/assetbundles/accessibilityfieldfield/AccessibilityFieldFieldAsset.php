<?php
/**
 * Accessibility plugin for Craft CMS 3.x
 *
 * Check your entries for common accessibility issues
 *
 * @link      www.hampton.agency
 * @copyright Copyright (c) 2022 Hampton
 */

namespace hampton\accessibility\assetbundles\accessibilityfieldfield;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Hampton
 * @package   Accessibility
 * @since     1.0.0
 */
class AccessibilityFieldFieldAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@hampton/accessibility/assetbundles/accessibilityfieldfield/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/AccessibilityField.js',
        ];

        $this->css = [
            'css/AccessibilityField.css',
        ];

        parent::init();
    }
}
