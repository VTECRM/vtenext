<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@198038 */

require_once('vtlib/thirdparty/dZip.inc.php');

/**
 * Wrapper class over dZip.
 * @package vtlib
 */
class Vtecrm_Zip extends dZip {
    /**
     * Push out the file content for download.
     * @param $zipFileName
     */
    public function forceDownload($zipFileName) {
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false);
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=".basename($zipFileName).";" );
        //header("Content-Transfer-Encoding: binary");

        // crmv@192935
        $disk_file_size = filesize($zipFileName);
        header("Content-Length: ".$disk_file_size);
        readfile($zipFileName);
        // crmv@192935e
    }

    /**
     * Get relative path (w.r.t base)
     */
    private function __getRelativePath($basepath, $srcpath) {
        $base_realpath = $this->__normalizePath(realpath($basepath));
        $src_realpath  = $this->__normalizePath(realpath($srcpath));
        $search_index  = strpos($src_realpath, $base_realpath);
        $relpath = null;
        if($search_index === 0) {
            $startindex = strlen($base_realpath)+1;
            // On windows $base_realpath ends with / and On Linux it will not have / at end!
            if(strrpos($base_realpath, '/') == strlen($base_realpath)-1) $startindex -= 1;
            $relpath = substr($src_realpath, $startindex);
        }
        return $relpath;
    }

    /**
     * Check and add '/' directory separator
     */
    private function __fixDirSeparator($path) {
        if($path != '' && (strripos($path, '/') != strlen($path)-1)) $path .= '/';
        return $path;
    }

    /**
     * Normalize the directory path separators.
     */
    private function __normalizePath($path) {
        if($path && strpos($path, '\\')!== false) $path = preg_replace("/\\\\/", "/", $path);
        return $path;
    }

    /**
     * Copy the directory on the disk into zip file.
     */
    public function copyDirectoryFromDisk($dirname, $zipdirname=null, $excludeList=null, $basedirname=null) {
        $dir = opendir($dirname);
        if(strripos($dirname, '/') != strlen($dirname)-1)
            $dirname .= '/';

        if($basedirname === null) $basedirname = realpath($dirname);

        while(false !== ($file = readdir($dir))) {
            if($file != '.' && $file != '..' &&
                $file != '.svn' && $file != 'CVS') {
                // Exclude the file/directory
                if(!empty($excludeList) && in_array("{$dirname}{$file}", $excludeList))
                    continue;

                if(is_dir("{$dirname}{$file}")) {
                    $this->copyDirectoryFromDisk("{$dirname}{$file}", $zipdirname, $excludeList, $basedirname);
                } else {
                    $zippath = $dirname;
                    if($zipdirname !== null && $zipdirname != '') {
                        $zipdirname = $this->__fixDirSeparator($zipdirname);
                        $zippath = $zipdirname.$this->__getRelativePath($basedirname, $dirname);
                    }
                    $this->copyFileFromDisk($dirname, $zippath, $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Copy the disk file into the zip.
     */
    public function copyFileFromDisk($path, $zippath, $file) {
        $path = $this->__fixDirSeparator($path);
        $zippath = $this->__fixDirSeparator($zippath);
        //crmv@sdk
        if (!array_key_exists("$zippath$file",$this->centraldirs)) {
            $this->addFile("{$path}{$file}", "{$zippath}{$file}");
        }
        //crmv@sdk e
    }
}
