<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@198038 */

require_once('vtlib/thirdparty/dUnzip2.inc.php');

/**
 * Provides API to make working with zip file extractions easy
 * @package vtlib
 */
class Vtecrm_Unzip extends dUnzip2 {

    /**
     * Check if the file path is directory
     * @param String Zip file path
     * @return bool
     */
    public function isdir($filepath) {
        return substr($filepath, -1, 1) == "/";
    }

    /**
     * Check existence of path in the given array
     * @access private
     */
    private function checkPathInArray($path, $pathArray) {
        foreach($pathArray as $checkPath) {
            if(strpos($path, $checkPath) === 0)
                return true;
        }
        return false;
    }

    /**
     * Extended unzipAll function (look at base class)
     * Allows you to rename while unzipping and handle exclusions.
     * @access private
     */
    public function unzipAllEx($targetDir=false, $includeExclude=false, $renamePaths=false, $ignoreFiles=false,
                        $baseDir="", $applyChmod=0777){
        if($targetDir === false)
            $targetDir = dirname(__FILE__)."/";

        // We want to always maintain the structure
        $maintainStructure = true;

        if($renamePaths === false) $renamePaths = [];

        /*
         * Setup includeExclude parameter
         * FORMAT:
         * Array(
         * 'include'=> Array('zipfilepath1', 'zipfilepath2', ...),
         * 'exclude'=> Array('zipfilepath3', ...)
         * )
         *
         * DEFAULT: If include is specified only files under the specified path will be included.
         * If exclude is specified folders or files will be excluded.
         */

        if($includeExclude === false) $includeExclude = [];

        $list = $this->getList();
        if(sizeof($list)) foreach($list as $fileName=>$trash){
            // Should the file be ignored?
            if($includeExclude['include'] &&
                !$this->checkPathInArray($fileName, $includeExclude['include'])) {
                // Do not include something not specified in include
                continue;
            }
            if($includeExclude['exclude'] &&
                $this->checkPathInArray($fileName, $includeExclude['exclude'])) {
                // Do not include something not specified in include
                continue;
            }
            // END

            $dirname  = dirname($fileName);

            // Rename the path with the matching one (as specified)
            if(!empty($renamePaths)) {
                foreach($renamePaths as $lookup => $replace) {
                    if(strpos($dirname, $lookup) === 0) {
                        $dirname = substr_replace($dirname, $replace, 0, strlen($lookup));
                        break;
                    }
                }
            }
            // END

            $outDN = "{$targetDir}/{$dirname}";

            if(substr($dirname, 0, strlen($baseDir)) != $baseDir)
                continue;

            if(!is_dir($outDN) && $maintainStructure){
                $str = "";
                $folders = explode("/", $dirname);
                foreach($folders as $folder){
                    $str = $str?"$str/$folder":$folder;
                    if(!is_dir("$targetDir/$str")){
                        $this->debugMsg(1, "Creating folder: $targetDir/$str");
                        mkdir("$targetDir/$str");
                        if($applyChmod)
                            chmod("$targetDir/$str", $applyChmod);
                    }
                }
            }
            if(substr($fileName, -1, 1) == "/")
                continue;

            $this->unzip($fileName, "{$targetDir}/{$dirname}/".basename($fileName), $applyChmod);
        }
    }
}
