<?php

namespace Mpmd\Util;

/**
 * Class Compare
 * @package Mpmd\Util
 *
 * @author Fabrizio Branca
 * @since 2014-11-25
 */
class Compare
{

    const DIFFERENT_FILE_CONTENT = 'differentFileContent';
    const IDENTICAL_FILES = 'identicalFiles';
    const FILE_MISSING_IN_B = 'fileMissingInB';
    const SAME_FILE_BUT_COMMENTS = 'sameFileButComments';
    const FILE_MISSING_IN_A = 'fileMissingInA';
    const SAME_FILE_BUT_WHITESPACE = 'sameFileButWhitespace';

    /**
     * Compare two directories
     *
     * @param        $baseDirA
     * @param        $baseDirB
     * @param string $path
     * @param array  $ignore
     *
     * @return array
     */
    public function compareDirectories($baseDirA, $baseDirB, $path='', $ignore=array())
    {
        $data = array(
            self::DIFFERENT_FILE_CONTENT   => array(),
            self::IDENTICAL_FILES          => array(),
            self::FILE_MISSING_IN_B        => array(),
            self::SAME_FILE_BUT_COMMENTS   => array(),
            self::SAME_FILE_BUT_WHITESPACE => array(),
        );
        if ($handle = opendir($baseDirA . $path)) {
            while (($file = readdir($handle)) !== false) {
                if ($file == '.' || $file == '..' || in_array($file, $ignore)) {
                    continue;
                }

                if (is_dir($baseDirA . $path . DIRECTORY_SEPARATOR . $file)) {
                    $tmp = $this->compareDirectories($baseDirA, $baseDirB, $path . DIRECTORY_SEPARATOR . $file, $ignore);
                    // merge data
                    foreach ($tmp as $key => $value) {
                        $data[$key] = array_merge($data[$key], $value);
                    }
                } else {
                    $aFile = $baseDirA . $path . DIRECTORY_SEPARATOR . $file;
                    $bFile = $baseDirB . $path . DIRECTORY_SEPARATOR . $file;

                    $status = $this->compareFiles($aFile, $bFile);
                    $data[$status][] = $path . DIRECTORY_SEPARATOR . $file;
                }
            }
        }
        return $data;
    }

    /**
     * Compare two files
     *
     * @param $aFile
     * @param $bFile
     *
     * @return string
     */
    public function compareFiles($aFile, $bFile)
    {
        if (!is_file($aFile)) {
            return self::FILE_MISSING_IN_A;
        }
        if (!is_file($bFile)) {
            return self::FILE_MISSING_IN_B;
        }
        if (md5_file($aFile) === md5_file($bFile)) {
            return self::IDENTICAL_FILES;
        }
        $extension = strtolower(pathinfo($aFile, PATHINFO_EXTENSION));
        
        $additionalCheck = in_array($extension, array('php', 'phtml', 'xml'));
        if ($this->getFileContentWithoutWhitespace($aFile) === $this->getFileContentWithoutWhitespace($bFile)) {
            return self::SAME_FILE_BUT_WHITESPACE;
        } elseif ($additionalCheck
            && ($this->getFileContentWithoutComments($aFile) === $this->getFileContentWithoutComments($bFile))
        ) {
            return self::SAME_FILE_BUT_COMMENTS;
        } else {
            return self::DIFFERENT_FILE_CONTENT;
        }
    }

    /**
     * Get file content (without code comments)
     *
     * @param $path
     * @throws \Exception
     */
    public function getFileContentWithoutComments($path)
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'php' || $extension === 'phtml') {
            $fileStr = file_get_contents($path);
            $newStr = '';

            $commentTokens = array(T_COMMENT);

            if (defined('T_DOC_COMMENT')) {
                $commentTokens[] = T_DOC_COMMENT;
            } // PHP 5
            if (defined('T_ML_COMMENT')) {
                $commentTokens[] = T_ML_COMMENT;
            } // PHP 4

            $tokens = token_get_all($fileStr);

            foreach ($tokens as $token) {
                if (is_array($token)) {
                    if (in_array($token[0], $commentTokens)) {
                        continue;
                    }

                    $token = $token[1];
                }

                $newStr .= $token;
            }
            return $newStr;
        } elseif ($extension === 'xml') {
            $fileStr = file_get_contents($path);
            $fileStr = preg_replace('/<!--.*?-->/s', '', $fileStr);
            return $fileStr;
        } else {
            throw new \Exception("$extension is not supported here");
        }
    }
    
    /**
     * Get file content (without whitespace characters)
     * 
     * @param $path
     */
    public function getFileContentWithoutWhitespace($path)
    {
        $fileStr = file_get_contents($path);
        $lines = explode("\n", $fileStr);
        $lines = array_map('trim', $lines);
        $fileStr = implode("\n", $lines);
        return $fileStr;
    }

    /**
     * Get a diff between two files (uses diff command)
     *
     * @param $fileA
     * @param $fileB
     * @return string
     * @throws \Exception
     */
    public function getFileDiff($fileA, $fileB)
    {
        if (!is_file($fileA)) {
            throw new \Exception('Could not find file ' . $fileA);
        }
        if (!is_file($fileB)) {
            throw new \Exception('Could not find file ' . $fileB);
        }
        $result = shell_exec("diff -u -w -B {$fileA} {$fileB}");
        return $result;
    }

    /**
     * Get diffs for a list of files
     *
     * @param $files array
     * @param $baseA string
     * @param $baseB string
     * @param $html bool
     * @return array
     */
    public function getDiffs(array $files, $baseA, $baseB, $html=true)
    {
        $diffs = array();
        foreach ($files as $file) {
            $status = $this->compareFiles($baseA . $file, $baseB . $file);
            if ($status == self::DIFFERENT_FILE_CONTENT) {
                $content = $this->getFileDiff($baseA . $file, $baseB . $file);
                if ($html) {
                    $content = '<pre class="diff">' . htmlspecialchars($content) . '</pre>';
                }
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
