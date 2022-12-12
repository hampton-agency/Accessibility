<?php
/**
 * Accessibility plugin for Craft CMS 3.x
 *
 * Check your entries for common accessibility issues
 *
 * @link      www.hampton.agency
 * @copyright Copyright (c) 2022 Hampton
 */

namespace hampton\accessibility\widgets;

use hampton\accessibility\Accessibility;
use hampton\accessibility\assetbundles\accessibilitywidgetwidget\AccessibilityWidgetWidgetAsset;

use Craft;
use craft\base\Widget;

/**
 * Accessibility Widget
 *
 * @author    Hampton
 * @package   Accessibility
 * @since     1.0.0
 */
class AccessibilityWidget extends Widget
{

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $displayTitle = 'Accessibility';

    public $filterSections = null;

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return "Accessibility";
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias("@hampton/accessibility/assetbundles/accessibilitywidgetwidget/dist/img/AccessibilityWidget-icon.svg");
    }

    /**
     * @inheritdoc
     */
    public static function maxColspan(): ?int
    {
        return null;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules = array_merge(
            $rules,
            [
                ['displayTitle', 'string'],
                ['displayTitle', 'default', 'value' => 'Accessibility'],
            ]
        );
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            'accessibility/_components/widgets/AccessibilityWidget_settings',
            [
                'widget' => $this
            ]
        );
    }

    public function getTitle(): ?string
    {
        return $this->displayTitle;
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): ?string
    {
        Craft::$app->getView()->registerAssetBundle(AccessibilityWidgetWidgetAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'accessibility/_components/widgets/AccessibilityWidget_body',
            [
                'displayTitle' => $this->displayTitle,
                'filterSections' => $this->filterSections
            ]
        );
    }
}
