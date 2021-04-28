<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@115268 crmv@185786 */
global $sdk_mode, $current_user, $adb, $table_prefix;
switch($sdk_mode) {
	case 'insert':
		break;
	case 'insert.after':
	case 'dynaform.insert.after':
		if ($sdk_mode == 'insert.after') {
			$form = $_REQUEST;
			$parentid = $this->id;
			$processesid = '';
		} else {
			$fieldname = $field['fieldname'];
			$parentid = getSingleFieldValue($table_prefix.'_processes', 'related_to', 'processesid', $form['record']);
			$processesid = $form['record'];
		}
		
		// updload from $_FILES
		$documents = array();
		$files = $_FILES[$fieldname];
		if (!empty($files)) {
			$_tmp_files = $_FILES;
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
						$document = CRMEntity::getInstance('Documents');
						$documentid = $document->createDocumentFromArrayFile($file, null, $parentid);
						if (!empty($processesid)) $document->insertintonotesrel($processesid,$documentid);
						$documents[] = $documentid;
					}
				}
			}
			$_FILES = $_tmp_files;
			unset($_FILES[$fieldname]);	// remove in order to prevend duplicates
		}
		
		// upload from cache/upload (EditViewConditionals reload)
		$key = $form[$fieldname.'_key'];
		if (!empty($key)) {
			$folder = 'cache/upload/'.$key;
			if (file_exists($folder)) {
				if ($handle = opendir($folder)) {
					while (false !== ($entry = readdir($handle))) {
						if ($entry != "." && $entry != "..") {
							$document = CRMEntity::getInstance('Documents');
							$documentid = $document->createDocumentFromPathFile($folder.'/'.$entry, null, $parentid);
							if (!empty($processesid)) $document->insertintonotesrel($processesid,$documentid);
							$documents[] = $documentid;
						}
					}
					closedir($handle);
					FSUtils::deleteFolder($folder);
				}
			}
		}
		break;
	case 'detail':
		break;
	case 'edit':
		$key = (isset($_REQUEST[$fieldname.'_key'])) ? $_REQUEST[$fieldname.'_key'] : substr(md5(rand()),0,5);
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $key;
		$folder = 'cache/upload/'.$key;
		$uploaded_files = array();
		if (file_exists($folder)) {
			if ($handle = opendir($folder)) {
				while (false !== ($entry = readdir($handle))) {
					if ($entry != "." && $entry != "..") $uploaded_files[] = $entry;
				}
				closedir($handle);
			}
		}
		$fieldvalue[] = $uploaded_files;
		break;
	case 'relatedlist':
	case 'list':
		break;
}