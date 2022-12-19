<?php

namespace hampton\accessibility\variables;

use hampton\accessibility\Accessibility;

use Craft;

/**
 * @author    Hampton
 * @package   Accessibility
 * @since     1.0.0
 */
class AccessibilityVariable {
  
    public function getAccessibilityIssues() {
        return accessibility::getInstance()->services->getAccessibilityIssues();
    }

    public function setAccessibilityIssues() {
        return accessibility::getInstance()->services->setAccessibilityIssues();
    }
}
