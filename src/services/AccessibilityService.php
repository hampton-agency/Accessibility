<?php

namespace hampton\accessibility\services;

use hampton\accessibility\Accessibility;

use Craft;
use Entry;
use craft\base\Component;
use craft\helpers\StringHelper;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementExporter;
use craft\base\Field;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;

use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\HtmlNormalizer;

class AccessibilityService extends Component {


    // Public Methods
    public function setAccessibilityIssues($scanId,$entry) {
        $entries = [];

        if( isset($entry) && isset($scanId) ){

            $severity = 0;
            $found_issue = false;
            $all_issues = array();

            $whitelist = accessibility::getInstance()->services->getWhitelist();

            //Content for reported errors
            $clickHereText = '"Click here" text found in page content, this type of link can be confusing when a screen reader reads it out of context.';
            $poorLinkText = '"Read more" text found in page content. This can be confusing for screen reader users as it offers no context to the link’s destination.';
            $altText = 'Ensure all images have alt tags to improve accessibility and SEO.';
            $allCapsText = 'Uppercased text found in content. Some screen readers will announce all-caps individual letters so TALK becomes T. A. L. K. and not the word.';
            $ampersandText = 'Try to use the word 'and' rather than the ampersand symbol as some screen readers cannot decipher it.';
            $underlineText = 'Underlined text symbolises a link on the web, so underlined text that is not a link can confuse users. Try formatting your text as a heading, or use bold or italics to add emphasis instead.';

                //$this->checkEntry($entry->id);

            $ch = curl_init($entry->url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, TRUE); // We'll parse redirect url from header.
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE); // We want to just get redirect url but not to follow it.
            $response = curl_exec($ch);
            curl_close($ch);

            if( StringHelper::contains($response, "Twig Runtime Error") ) {
                //do something to report twig error
            } else {

                //Convert the response into html and get the body only
                $dom = StringHelper::explode($response, "<body");

                //get all the img tags in the response
                $imagetags = preg_match_all('/<img\s+[^>]*src="([^"]*)"[^>]*>/', $response, $imgs);

                $domLength = count($dom);
                $response = $dom[$domLength - 1];

                //Search for read more in page
                if( preg_match("/read more/i", $response) ){
                    $found_issue = true;
                    $issue = array();
                    $issue["Name"] = $poorLinkText;
                    $issue_severity = 2;
                    $issue["Severity"] = $issue_severity;
                    $issue["Type"] = "Poor link text";
                    $all_issues[] = $issue;
                    if($severity<$issue_severity) $severity = $issue_severity;
                }

                //Search for click here in page
                if( preg_match("/click here/i", $response) ){
                    $found_issue = true;
                    $issue = array();
                    $issue["Name"] = $clickHereText;
                    $issue_severity = 3;
                    $issue["Severity"] = $issue_severity;
                    $issue["Type"] = "Click here";
                    $all_issues[] = $issue;
                    if($severity<$issue_severity) $severity = $issue_severity;
                }

                //Check for ampersands in content
                $ampCheck = preg_match_all("/&/i", html_entity_decode($response), $ampersands);

                foreach($ampersands as $ampKey => $ampersand) {
                    if( !in_array($ampersand, $whitelist) ){
                        $found_issue = true;
                        $issue = array();
                        $issue["Name"] = $ampersandText;
                        $issue_severity = 2;
                        $issue["Severity"] = $issue_severity;
                        $issue["Type"] = "Ampersands";
                        $all_issues[] = $issue;
                        if($severity<$issue_severity) $severity = $issue_severity;
                    }
                }

                $matches = [];
                $cleanMatches = [];

                $foundAltIssue = false;

                //remove all the images from the string to check for uppercase
                foreach($imgs as $imgsKey => $images) {
                    foreach($images as $key => $img) {

                        //Check if image has alt text attribute
                        preg_match_all('/alt="([^&]+")/', $img, $alts);

                        if( count($alts[0]) > 0 ) {
                            foreach( $alts[0] as $altKey => $alt ) {
                                if( strlen($alt) < 7 ) {
                                    echo "no alt content <br>";
                                    $foundAltIssue = true;
                                }
                            }
                        } else {
                            $foundAltIssue = true;
                        }

                        //remove img from response string
                        $response = str_replace($img, ' ', $response);
                    }
                }

                //Return if found empty alt or missing alt
                if( $foundAltIssue ) {
                    $found_issue = true;
                    $issue = array();
                    $issue["Name"] = $altText;
                    $issue_severity = 3;
                    $issue["Severity"] = $issue_severity;
                    $issue["Type"] = "Missing alternative text";
                    $all_issues[] = $issue;
                    if($severity<$issue_severity) $severity = $issue_severity;
                }

                //Check for all caps in content
                $uppercases = preg_match_all("(\b[A-Z][A-Z]+|\b[A-Z]\b)", $response, $matches);

                //Check for a postcode pattern
                $postcodeCheck = preg_match_all("/([Gg][Ii][Rr] 0[Aa]{2})|((([A-Za-z][0-9]{1,2})|(([A-Za-z][A-Ha-hJ-Yj-y][0-9]{1,2})|(([A-Za-z][0-9][A-Za-z])|([A-Za-z][A-Ha-hJ-Yj-y][0-9][A-Za-z]?))))\s?[0-9][A-Za-z]{2})/", $response, $postcode);

                if( $uppercases ) {

                    //Ignore UK postcodes in uppercase
                    if( count($matches) > 0 ) {
                        foreach( $matches[0] as $matchIndex => $match ) {
                            if( strlen($match) >= 2 && !in_array($match, $whitelist) ) {
                                //Check if Internet explorer conditional, Form charset or CSRF Token.
                                switch ($match) {
                                    case "IE":
                                        $isIECheck = preg_match("/if lt IE/", $response);

                                        if(!$isIECheck) {
                                            array_push($cleanMatches, $match);
                                        }
                                        break;

                                    case "CRAFT":
                                        $isCRAFTCheck = preg_match("/CRAFT_CSRF_TOKEN/", $response);

                                        if(!$isCRAFTCheck) {
                                            array_push($cleanMatches, $match);
                                        }
                                        break;

                                    case "UTF":
                                        $isFormCheck = preg_match("/accept-charset/", $response);

                                        if(!$isFormCheck) {
                                            array_push($cleanMatches, $match);
                                        }
                                        break;

                                    default:
                                        if( $postcodeCheck && count($postcode) >= 1 ) {
                                            $foundNeedle = false;

                                            if( count($postcode) > 0 ) {
                                                foreach( $postcode[0] as $code ) {
                                                    if( StringHelper::contains($code, $match) ) {
                                                        $foundNeedle = true;
                                                    }
                                                }
                                            }

                                            if( !$foundNeedle ) {
                                                array_push($cleanMatches, $match);
                                            }
                                        } else {
                                            array_push($cleanMatches, $match);
                                        }

                                        break;
                                }
                            }
                        }
                    }

                    if( count($cleanMatches) >= 1 ) {
                        $found_issue = true;
                        $issue = array();
                        $issue["Name"] = $allCapsText;
                        $issue_severity = 2;
                        $issue["Severity"] = $issue_severity;
                        $issue["Type"] = "All-caps in content.";
                        $all_issues[] = $issue;
                        if($severity<$issue_severity) $severity = $issue_severity;
                    }
                }

                // Save entry and issue details to database
                if($found_issue && isset($all_issues) && sizeof($all_issues)>0 ){

                    Craft::$app->db->createCommand()
                    ->insert('accessibility_scans', array(
                        'scanId'    => (int) $scanId,
                        'entryID'   => $entry->id,
                        'issue'     => json_encode($all_issues),
                        'severity'  => $severity
                    ))->execute();


                }
            }
        }

        return true;
    }


    public function getAccessibilityIssues() {

        $latest_issue = (new \craft\db\Query())
          ->select(['id','scanId'])
          ->from('accessibility_scans')
          ->orderBy('scanId desc')
          ->limit(1)
          ->all();

        $latest_issues = [];

        if( count($latest_issue) > 0 && isset($latest_issue[0]["scanId"]) ) {
            $latest_scan_id = $latest_issue[0]["scanId"];
            $timestamp = substr($latest_scan_id,0,14);

            $latest_issues = (new \craft\db\Query())
              ->select(['id','scanId','entryId','issue','dateCreated'])
              ->from('accessibility_scans')
              ->orderBy('scanId desc')
              ->where(['scanId' => $latest_scan_id])
              ->all();
        }

        return json_decode(json_encode($latest_issues));

    }


    public function checkEntry($entry_id) {
        $query = (new craft\elements\db\ElementQuery($entry_id));

        $export = craft\elements\db\ElementQueryInterface($query);

        $results = $export->export();
    }

    public function getWhitelist() {
        $whiteList = $sections = Accessibility::$plugin->getSettings()->whiteList;
        $whiteList = explode(",", $whiteList);
        return $whiteList;
    }

    public function checkForCSS($page) {

        //Get the css files in html to then inline style
        $cssRegex = '/(?<=href=")[^"]+\.css/';
        $cssUrlsRaw = preg_match_all($cssRegex, $page, $cssUrls);
        $responseCss = "";

        //Loop over css files and append them to the html for inline styles
        foreach( $cssUrls as $cssKey => $css ) {
            if( count($css) > 0 ) {
                $ch = curl_init($css[0]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, TRUE); // We'll parse redirect url from header.
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE); // We want to just get redirect url but not to follow it.
                $r = curl_exec($ch);
                curl_close($ch);

                $page = HtmlNormalizer::fromHtml($page)->render();
                $responseCss = CssInliner::fromHtml($page)->inlineCss($r)->render();
            }
        }


        //Check for uppercase in inline css
        if( preg_match("/uppercase/i", $responseCss) ) {
            $foundUpperCSS = true;
        } else {
            $foundUpperCSS = false;
        }
    }
}
