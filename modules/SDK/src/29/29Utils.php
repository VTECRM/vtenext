<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class UitypeFileUtils extends SDKExtendableClass {
	
	function uploadTempFiles() {
		global $upload_badext;
		if (!empty($_FILES)) {
			foreach($_FILES as $fieldname => $files) {
				if (isset($_REQUEST[$fieldname.'_key'])) {
					$folder = 'cache/upload/'.$_REQUEST[$fieldname.'_key'];
					@mkdir($folder);
				}
				$file_array = array();
				$file_count = count($files['name']);
				$file_keys = array_keys($files);
				for ($fi=0; $fi<$file_count; $fi++) {
					foreach ($file_keys as $key) {
						$file_array[$fi][$key] = $files[$key][$fi];
					}
				}
				if (!empty($file_array)) {
					foreach($file_array as $file) {
						if ($file['error'] == UPLOAD_ERR_OK) {
							$filename = $file['name'];
							$filename = from_html(preg_replace('/\s+/', '_', $filename));
							$binFile = sanitizeUploadFileName($filename, $upload_badext);
							$filename = ltrim(basename(" ".$binFile)); //allowed filename like UTF-8 characters
							$upload_status = move_uploaded_file($file['tmp_name'],$folder.'/'.$filename);
						}
					}
				}
				unset($_FILES[$fieldname]);	// remove in order to prevend duplicates
			}
		}
	}
}