<?php

namespace hampton\accessibility\models;

use hampton\accessibility\Accessibility;
use Craft;
use craft\base\Model;

class Settings extends Model {
    // Public Properties
    public bool $debugMode = false;
    public string $debugEmail = '';
    public array $filterSections = array();
    public string $whiteList = '';

    // Public Methods
    protected function defineRules(): array {
        $rules = parent::defineRules();

        $rules[] = ['debugEmail', 'string'];
        return $rules;
    }
}
