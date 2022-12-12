<?php
/**
 * Accessibility plugin for Craft CMS 3.x
 *
 * Check your entries for common accessibility issues
 *
 * @link      www.hampton.agency
 * @copyright Copyright (c) 2022 Hampton
 */

namespace hampton\accessibility\models;

use hampton\accessibility\Accessibility;

use Craft;
use craft\base\Model;

/**
 * @author    Hampton
 * @package   Accessibility
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    public bool $debugMode = false;
    public string $debugEmail = '';
    public array $filterSections = array();

    // Public Methods
    protected function defineRules(): array {
        $rules = parent::defineRules();
        
        $rules[] = ['debugEmail', 'string'];
        return $rules;
    }
}
