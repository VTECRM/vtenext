<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@138011 */

/**
 * @class FSUtils
 *
 * Utility functions for files and directory
 *
 */
class FSUtils {

	/**
	 * Recursively delete a folder and its content
	 *
	 * @param string $dir The directory to delete
	 * @return bool False in case of errors
	 */
	static public function deleteFolder($dir) {
		if (is_dir($dir) && ($handle = opendir($dir))) {
			while (false !== ($file = readdir($handle))) {
				if (in_array($file,array('.','..'))) continue;
				elseif(is_file($dir.'/'.$file))	@unlink($dir.'/'.$file);
				elseif (is_dir($dir.'/'.$file)) self::deleteFolder($dir.'/'.$file);
			}
			closedir($handle);
			return @rmdir($dir);
		}
		return false;
	}
	
	/**
	 * Copies files and non-empty directories.
	 *
	 * @param string $src The source file or directory
	 * @param string $dst Return false if there were errors
	 * @return bool False in case of errors
	 */
	static public function rcopy($src, $dst) {
		$ret = true;
		if (is_dir($src)) {
			@mkdir($dst, 0755);
			$files = scandir($src);
			foreach ($files as $file) {
				if ($file != "." && $file != "..") {
					$r = self::rcopy("$src/$file", "$dst/$file");
					if (!$r) $ret = false;
				}
			}
		} elseif (file_exists($src)) {
			$ret = copy($src, $dst);
		}
		return $ret;
	}

	/**
	 * Removes the UTF8 BOM (Byte-Order-Mark) from the beginning of a file
	 *
	 * @param string $filePath Path to the file to alter, must be writeable
	 * @return bool False in case of errors
	 */
	static public function removeBOM($filePath) {
		
		if(!file_exists($filePath)) return false;
		if(!is_writable($filePath)) return false;

		$bom = pack('H*','EFBBBF');
		$lbom = strlen($bom);
		
		$handle = fopen($filePath, 'r');
		if ($handle) {
			$start = fread($handle, $lbom);
			fclose($handle);
			if ($start === $bom) {
				// it has BOM, nuke it from orbit!!!
				// read file in memory... should instead use streams 
				// and a temporary file to use less memory
				$content = file_get_contents($filePath);
				$r = file_put_contents($filePath, substr($content, $lbom));
				if ($r === false) return false;
			}
		}
		return true;
	}
	
}


// ----- COMPATIBILITY FUNCTIONS -----

/**
 * @deprecated
 * Please use the FSUtils::deleteFolder() function now.
 * Shameful function to delete a folder.
 */
function folderDetete($dir) {
	return FSUtils::deleteFolder($dir);
}

/**
 * @deprecated
 * Please use the FSUtils::deleteFolder() function now.
 */
function rcopy($src, $dst) {
	return FSUtils::rcopy($src, $dst);
}