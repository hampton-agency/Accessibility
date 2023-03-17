<?php

namespace hampton\accessibility\jobs;

use hampton\accessibility\Accessibility;

use Craft;
use craft\queue\BaseJob;

class AccessibilityTask extends BaseJob {

    // Public Properties
    public $error = null;

    // Public Methods
    public function execute($queue): void {
        $sections = Accessibility::$plugin->getSettings()->filterSections;
        $totalPages = 0;
        $i = 0;
        $scanId = date("YmdHis").rand(10000,99999);  // 20 digit string using 12 digits of date and a random string

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $foundIssues = null;

        //Get the total pages for progress bar
        foreach($sections as $sectionID) {
            $entries = \craft\elements\Entry::find()
              ->sectionId($sectionID)
              ->all();

            $totalPages = $totalPages + count($entries);
        }

        //Get the issues for each section
        foreach($sections as $key => $sectionID){
            $entries = \craft\elements\Entry::find()
            ->sectionId($sectionID)
            ->all();

            if( isset($entries) && count($entries) > 0 ) foreach($entries as $key => $entry){

                $foundIssues = accessibility::getInstance()->services->setAccessibilityIssues($scanId,$entry);
                //mail('steven@hampton.agency', 'Accessibility plugin entry test', $key, $headers);

                //Set the progress to loop key
                $this->setProgress(
                    $queue, $i / $totalPages, "complete"
                );

                $i++;

            }
        }

        if($foundIssues) {
            $message = "<h1>Accessibility Queued Job</h1><p>Task ran successfully</p>";
        } else {
            $message = "<h1>Accessibility Queued Job</h1><p>Task Ran, no issues found!</p>";
        }
    }

    // Protected Methods
    protected function defaultDescription(): string {
        return Craft::t('accessibility', 'Accessibility task running in background');
    }
}
