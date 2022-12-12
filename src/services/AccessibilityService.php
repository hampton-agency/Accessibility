<?php

namespace hampton\accessibility\services;

use hampton\accessibility\Accessibility;

use Craft;
use Entry;
use craft\base\Component;
use craft\helpers\StringHelper;

use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\HtmlNormalizer;

class AccessibilityService extends Component {

    // Public Methods
    public function setAccessibilityIssues() {

        // TODO check if scan has ran today


        $sections = Accessibility::$plugin->getSettings()->filterSections;
        $entries = [];
        $scanId = date("YmdHis").rand(10000,99999);  // 20 digit string using 12 digits of date and a random string

        foreach($sections as $sectionID){
            if( isset($sectionID) ) {
                $entries = \craft\elements\Entry::find()
                  ->sectionId($sectionID)
                  ->all();
                $found_issue = false;
                $all_issues = array();
                $severity = 0;
                if( isset($entries) && count($entries) > 0 ) {
                    foreach($entries as $key => $entry) {
                        $found_issue = false;
                        $all_issues = array();
                        if( isset($entry) ) {
                            $ch = curl_init($entry->url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                            curl_setopt($ch, CURLOPT_HEADER, TRUE); // We'll parse redirect url from header.
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE); // We want to just get redirect url but not to follow it.
                            $response = curl_exec($ch);
                            curl_close($ch);

                            if( StringHelper::contains($response, "Twig Runtime Error") ) {
                                //do something
                            } else {

                                //Get the css files in html to then inline style
                                $cssRegex = '/(?<=href=")[^"]+\.css/';
                                $cssUrlsRaw = preg_match_all($cssRegex, $response, $cssUrls);

                                //Loop over css files and append them to the html for inline styles
                                foreach( $cssUrls as $cssKey => $css ) {
                                    $ch = curl_init($css[0]);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                                    curl_setopt($ch, CURLOPT_HEADER, TRUE); // We'll parse redirect url from header.
                                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE); // We want to just get redirect url but not to follow it.
                                    $r = curl_exec($ch);
                                    curl_close($ch);

                                    $response = HtmlNormalizer::fromHtml($response)->render();
                                    $responseCss = CssInliner::fromHtml($response)->inlineCss($r)->render();
                                }


                                //Check for uppercase in inline css
                                if( preg_match("/uppercase/i", $responseCss) ) {
                                    $foundUpper = true;
                                } else {
                                    $foundUpper = false;
                                }

                                //Convert the response into html and get the body only
                                $dom = StringHelper::explode($response, "<body");

                                //get all the img tags in the response
                                $imagetags = preg_match_all('/<img\s+[^>]*src="([^"]*)"[^>]*>/', $response, $imgs);

                                if(count($dom) >= 1) {
                                    $response = $dom[1];

                                    //Search for read more in page
                                    if( preg_match("/read more/i", $response) ){
                                        $found_issue = true;
                                        $issue = array();
                                        $issue["Name"] = "\"Read more\" text found in page content, this type of link can be confusing when a screen reader reads it out of context.";
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
                                        $issue["Name"] = "\"Click here\" text found in page content, this type of link can be confusing when a screen reader reads it out of context.";
                                        $issue_severity = 3;
                                        $issue["Severity"] = $issue_severity;
                                        $issue["Type"] = "Poor link text";
                                        $all_issues[] = $issue;
                                        if($severity<$issue_severity) $severity = $issue_severity;
                                    }

                                    //Check for ampersands in content
                                    if( preg_match("/&/i", html_entity_decode($response)) ){
                                        $found_issue = true;
                                        $issue = array();
                                        $issue["Name"] = "Ampersand symbol found in content, use 'and' and not the ampersand as some screen readers can not decipher it. Ampersands are also destractors and stand out more.";
                                        $issue_severity = 2;
                                        $issue["Severity"] = $issue_severity;
                                        $issue["Type"] = "Ampersand in content";
                                        $all_issues[] = $issue;
                                        if($severity<$issue_severity) $severity = $issue_severity;
                                    }

                                    $matches = [];
                                    $cleanMatches = [];

                                    $altMessage = "Make sure only decorative images have empty alt tags.";

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

                                    if( $foundAltIssue ) {
                                        $found_issue = true;
                                        $issue = array();
                                        $issue["Name"] = $altMessage;
                                        $issue_severity = 3;
                                        $issue["Severity"] = $issue_severity;
                                        $issue["Type"] = "Missing alt tags.";
                                        $all_issues[] = $issue;
                                        if($severity<$issue_severity) $severity = $issue_severity;
                                    }

                                    //Check for all caps in content
                                    $uppercases = preg_match_all("(\b[A-Z][A-Z]+|\b[A-Z]\b)", $response, $matches);

                                    //Check for a postcode pattern
                                    $postcodeCheck = preg_match_all("/([Gg][Ii][Rr] 0[Aa]{2})|((([A-Za-z][0-9]{1,2})|(([A-Za-z][A-Ha-hJ-Yj-y][0-9]{1,2})|(([A-Za-z][0-9][A-Za-z])|([A-Za-z][A-Ha-hJ-Yj-y][0-9][A-Za-z]?))))\s?[0-9][A-Za-z]{2})/", $response, $postcode);

                                    if( $uppercases ) {

                                        //Ignore UK postcodes in uppercase
                                        foreach( $matches[0] as $matchIndex => $match ) {
                                            if( strlen($match) >= 2 ) {
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

                                                            foreach( $postcode[0] as $code ) {
                                                                if( StringHelper::contains($code, $match) ) {
                                                                    $foundNeedle = true;
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

                                        if( count($cleanMatches) >= 1 ) {
                                            $found_issue = true;
                                            $issue = array();
                                            $issue["Name"] = "Uppercased text will be read out as individual letters, So CSS is read as C.S.S not as the word.";
                                            $issue_severity = 2;
                                            $issue["Severity"] = $issue_severity;
                                            $issue["Type"] = "Avoid using all caps.";
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
                        }
                    }
                }
            }
        }

        return true;
    }


    public function getAccessibilityIssues() {

      $latest_issue = (new \craft\db\Query())
        ->select(['id','dateCreated','scanId','entryId','issue'])
        ->from('accessibility_scans')
        ->orderBy('scanId desc')
        ->limit(1)
        ->all();

      $latest_scan_id = $latest_issue[0]["scanId"];
      $timestamp = substr($latest_scan_id,0,14);

      $latest_issues = (new \craft\db\Query())
        ->select(['id','scanId','entryId','issue','dateCreated'])
        ->from('accessibility_scans')
        ->orderBy('scanId desc')
        ->where(['scanId' => $latest_scan_id])
        ->all();

    return json_decode(json_encode($latest_issues));

    }
}
