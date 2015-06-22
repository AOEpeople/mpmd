<?php

class Aoe_Pmd_Model_Rule_OverwritingFiles extends Aoe_Pmd_Model_Rule_Abstract
{

    protected $compare = array();

    protected $_skipDirectories = array('.svn', '.git');

    public function run()
    {
        $code = realpath(Mage::getBaseDir('code')) . DS;
        $lib = realpath(Mage::getBaseDir('lib'));

        $this->compare = array(
            'local vs core'      => array('b' => $code . 'local', 'a' => $code . 'core'),
            'local vs community' => array('b' => $code . 'local', 'a' => $code . 'community'),
            'local vs lib'       => array('b' => $code . 'local', 'a' => $lib),
            'community vs core'  => array('b' => $code . 'community', 'a' => $code . 'core'),
            'community vs lib'   => array('b' => $code . 'community', 'a' => $lib),
        );

        foreach ($this->compare as $key => $configuration) {
            $this->compare[$key]['data'] = Mage::helper('aoepmd')->compareDirectories(
                $configuration['a'],
                $configuration['b'],
                '',
                $this->_skipDirectories
            );
            // update score
            $this->score += (int)$this->getScoreValue('identical_copy_exists') * count($this->compare[$key]['data']['identicalFiles']);
            $this->score += (int)$this->getScoreValue('copy_with_changes_exists') * count($this->compare[$key]['data']['differentFileContent']);
        }
    }

    public function toHtml()
    {

        $targetCorePath = realpath($this->getConfValue('targetCorePath'));

        $output = '';
        foreach ($this->compare as $key => $configuration) {
            if (count($configuration['data']['differentFileContent']) == 0 && count($configuration['data']['identicalFiles']) == 0) {
                continue;
            }
            $tmp = array();
            foreach ($configuration['data']['differentFileContent'] as $file) {
                $diff = Mage::helper('aoepmd')->getFileDiff($configuration['a'] . $file, $configuration['b'] . $file);
                $tmp[] = array(
                    'File'         => $file,
                    'Current Core' => '<pre class="diff">' . htmlspecialchars($diff) . '</pre>'
                );
            }
            foreach ($configuration['data']['identicalFiles'] as $file) {
                $tmp[] = array(
                    'File'         => $file,
                    'Current Core' => 'Identical copy'
                );
            }
            $output .= '<h3>' . $key . '</h3>';

            if (!$targetCorePath || $key == 'local vs community') {
                $output .= $this->processor->getLayout()->createBlock('aoepmd/table')
                           ->setLabels(array('File', 'Current Core'))
                           ->setTable($tmp)
                           ->toHtml();
            } else {

                foreach ($tmp as &$row) {

                    $targetPath = $configuration['b'] . $row['File'];
                    $targetPath = str_replace(Mage::getBaseDir(), '', $targetPath);
                    $targetPath = $targetCorePath . $targetPath;

                    $diff = Mage::helper('aoepmd')->getFileDiff($configuration['a'] . $row['File'], $targetPath);
                    $row['Target Core'] = '<pre class="diff">' . htmlspecialchars($diff) . '</pre>';
                }

                $output .= $this->processor->getLayout()->createBlock('aoepmd/table')
                           ->setLabels(array('File', 'Current Core', 'Target Core'))
                           ->setTable($tmp)
                           ->toHtml();
            }
        }
        return $output;
    }

    /**
     * Get diffs for a list of files
     *
     * @param array $files
     * @param       $baseA
     * @param       $baseB
     *
     * @return array
     */
    public function getDiffs(array $files, $baseA, $baseB)
    {
        $diffs = array();

        foreach ($files as $file) {

            $status = Mage::helper('aoepmd')->compareFiles($baseA . $file, $baseB . $file);

            if ($status == Aoe_Pmd_Helper_Data::DIFFERENT_FILE_CONTENT) {
                $content = Mage::helper('aoepmd')->getFileDiff($baseA . $file, $baseB . $file);
                $content = '<pre class="diff">' . htmlspecialchars($content) . '</pre>';
            } else {
                $content = $status;
            }

            $diffs[$file] = array(
                'File' => $file,
                'Diff' => $content
            );
        }
        return $diffs;
    }
}
