<?php

namespace hampton\accessibility\jobs;

use hampton\accessibility\Accessibility;

use Craft;
use craft\queue\BaseJob;

class AccessibilityTask extends BaseJob {

    // Public Properties
    public $someAttribute = 'Some Default';

    // Public Methods
    public function execute($queue): void {
        $getIssues = accessibility::getInstance()->services->setAccessibilityIssues();

        if($getIssues) {
            $message = "<h1>Accessibility Queued Job</h1><p>Task ran successfully</p>";
        } else {
            $message = "<h1>Accessibility Queued Job</h1><p>Task failed!</p>";
        }

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        mail('steven@hampton.agency, ewan@hampton.agency', 'Accessibility plugin queued job', $message, $headers);
    }

    // Protected Methods
    protected function defaultDescription(): string {
        return Craft::t('accessibility', 'Accessibility task was run');
    }
}
