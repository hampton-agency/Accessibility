<?php
/**
 * Accessibility plugin for Craft CMS 3.x
 *
 * Check your entries for common accessibility issues
 *
 * @link      www.hampton.agency
 * @copyright Copyright (c) 2022 Hampton
 */

namespace hampton\accessibility\fields;

use hampton\accessibility\Accessibility;
use hampton\accessibility\assetbundles\accessibilityfieldfield\AccessibilityFieldFieldAsset;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Db;
use yii\db\Schema;
use craft\helpers\Json;

class AccessibilityField extends Field {

    // Public Properties
    public string $someAttribute = 'Some Default';

    // Static Methods
    public static function displayName(): string {
        return Craft::t('accessibility', 'AccessibilityField');
    }

    // Public Methods
    public function defineRules(): array {
        $rules = parent::defineRules();

        $rules[] = ['someAttribute', 'string'];
        return $rules;
    }

    public function getContentColumnType(): string {
        return Schema::TYPE_STRING;
    }

    public function normalizeValue($value, ElementInterface $element = null): string {
        return $value;
    }

    public function serializeValue($value, ElementInterface $element = null): string {
        return parent::serializeValue($value, $element);
    }

    public function getSettingsHtml(): string {
        // Render the settings template
        return Craft::$app->getView()->renderTemplate(
            'accessibility/_components/fields/AccessibilityField_settings',
            [
                'field' => $this,
            ]
        );
    }

    public function getInputHtml($value, ElementInterface $element = null): string {
        // Register our asset bundle
        Craft::$app->getView()->registerAssetBundle(AccessibilityFieldFieldAsset::class);

        // Get our id and namespace
        $id = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        // Variables to pass down to our field JavaScript to let it namespace properly
        $jsonVars = [
            'id' => $id,
            'name' => $this->handle,
            'namespace' => $namespacedId,
            'prefix' => Craft::$app->getView()->namespaceInputId(''),
            ];
        $jsonVars = Json::encode($jsonVars);
        Craft::$app->getView()->registerJs("$('#{$namespacedId}-field').AccessibilityAccessibilityField(" . $jsonVars . ");");

        // Render the input template
        return Craft::$app->getView()->renderTemplate(
            'accessibility/_components/fields/AccessibilityField_input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'id' => $id,
                'namespacedId' => $namespacedId,
            ]
        );
    }
}
