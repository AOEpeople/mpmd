<?php

namespace Mpmd\Util;

/**
 * Class Report
 *
 * @author Fabrizio Branca
 * @since 2014-11-26
 */
class Report extends HtmlRenderer {

    public function __construct() {
        $this->addJsFile('http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js');
        $this->addJsContent('
            $(function () {
                $("pre.diff").before("<a class=\\"diff-toggle\\">[Toggle]</a>").hide();
                $(".diff-toggle").click(function () {
                    $(this).next().toggle();
                    return false;
                });
            })
        ');
        $this->addCssContent('
            body { font-family: Arial, sans-serif; font-size: 10pt; padding: 20px; }
            a, a:visited, a:hover { color: #E26912; text-decoration: underline; }
            table { background-color: whiteSmoke;  border-collapse: collapse; border-spacing: 0; margin-bottom: 10px; margin-top: 3px; border: 1px solid #fff; }
            table td, table th { padding: 0 20px; line-height: 20px; color: #0084B4; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px; border-bottom: 1px solid #fff; border-top: 1px solid #fff; text-align: left; }
            table td:hover { background-color: #fff; }
            table th { background-color: #ccc; color: #333; font-weight: bold; }
            pre.diff { color: black; font-size: smaller; line-height: normal; }
            .invalid { background-color: white; }
            .ok { text-align: center; }
        ');
    }

    public function addDiffData(array $data, array $diffs, $labelA, $labelB, $idPrefix='') {

        $htmlTableRenderer = new HtmlTableRenderer();

        if (array_key_exists(Compare::FILE_MISSING_IN_A, $data)) {
            $this->addHeadline("Additional files in $labelB", 3, array('id' => $idPrefix . Compare::FILE_MISSING_IN_A));
            $this->addTag('p', 'Since this would also include extra modules and themes this is currently skipped');
        }

        if (array_key_exists(Compare::FILE_MISSING_IN_B, $data)) {
            $this->addHeadline("Missing files in $labelB", 3, array('id' => $idPrefix . Compare::FILE_MISSING_IN_B));
            $missingFiles = array();
            foreach ($data[Compare::FILE_MISSING_IN_B] as $file) {
                $missingFiles[] = array('File' => $file);
            }
            $this->addBody($htmlTableRenderer->render($missingFiles, array('File')));
        }

        if (array_key_exists(Compare::IDENTICAL_FILES, $data)) {
            if (count($data[Compare::IDENTICAL_FILES]) > 0) {
                $this->addHeadline("Identical files in both locations", 3, array('id' => $idPrefix . Compare::IDENTICAL_FILES));
                $identicalFiles = array();
                foreach ($data[Compare::IDENTICAL_FILES] as $files) {
                    $identicalFiles[] = array('File' => $files);
                }
                $this->addBody($htmlTableRenderer->render($identicalFiles, array('File')));
            }
        }

        foreach (array(Compare::DIFFERENT_FILE_CONTENT, Compare::SAME_FILE_BUT_COMMENTS) as $section) {
            if (array_key_exists($section, $data)) {
                if (count($data[$section]) > 0) {
                    $this->addHeadline("Changed files ($section)", 3, array('id' => $idPrefix . $section));
                    $this->addBody($htmlTableRenderer->render($diffs[$section], array('File', 'Diff')));
                }
            }
        }
    }

} 