<?php

namespace hampton\accessibility\controllers;

use hampton\accessibility\Accessibility;
use hampton\accessibility\jobs\AccessibilityTask;

use Craft;
use craft\web\Controller;
use craft\helpers\UrlHelper;

class DefaultController extends Controller {

    // Public Methods
    public function actionRunTask() {
        \craft\helpers\Queue::push(new AccessibilityTask());

        Craft::$app->getSession()->setNotice('Accessibility task running in background.');

        //Return to the previous page
        Craft::$app->getResponse()->redirect(UrlHelper::cpUrl() . '/accessibility')->send();
    }

    public function actionTest() {
        return accessibility::getInstance()->services->setAccessibilityIssues();
    }
}
