<?php

/**
 *	Filemanager PHP connector
 *
 *	filemanager.php
 *	use for ckeditor filemanager plug-in by Core Five - http://labs.corefive.com/Projects/FileManager/
 *
 *	@license	MIT License
 *	@author		Riaan Los <mail (at) riaanlos (dot) nl>
 *	@copyright	Authors
 */

require_once('filemanager.config.php');
require_once('filemanager.class.php');
// crmv@10621 crmv@198780
require_once('../../../../../config.php');
chdir($root_directory);
include_once('include/utils/utils.php');
// crmv@10621e crmv@198780e
$fm = new Filemanager($config);

$response = '';

if(!auth()) {
	$fm->error($fm->lang('AUTHORIZATION_REQUIRED'));
}

if(!isset($_GET)) {
	$fm->error($fm->lang('INVALID_ACTION'));
} else {

	if(isset($_GET['mode']) && $_GET['mode']!='') {

		switch($_GET['mode']) {

			default:

				$fm->error($fm->lang('MODE_ERROR'));
				break;

			case 'getinfo':
				$_GET['path'] = str_replace('..', '', $_GET['path']);	//crmv@fix
				if($fm->getvar('path')) {
					$response = $fm->getinfo();
				}
				break;

			case 'getfolder':
				$_GET['path'] = str_replace('..', '', $_GET['path']);	//crmv@fix
				if($fm->getvar('path')) {
					$response = $fm->getfolder();
				}
				break;

			case 'rename':
				$_GET['old'] = str_replace('..', '', $_GET['old']);		//crmv@fix
				$_GET['new'] = str_replace('..', '', $_GET['new']);		//crmv@fix
				if($fm->getvar('old') && $fm->getvar('new')) {
					$response = $fm->rename();
				}
				break;

			case 'delete':
				$_GET['path'] = str_replace('..', '', $_GET['path']);	//crmv@fix
				if($fm->getvar('path')) {
					$response = $fm->delete();
				}
				break;

			case 'addfolder':
				$_GET['path'] = str_replace('..', '', $_GET['path']);	//crmv@fix
				$_GET['name'] = str_replace('..', '', $_GET['name']);	//crmv@fix
				if($fm->getvar('path') && $fm->getvar('name')) {
					$response = $fm->addfolder();
				}
				break;

			case 'download':
				$_GET['path'] = str_replace('..', '', $_GET['path']);	//crmv@fix
				if($fm->getvar('path')) {
					$fm->download();
				}
				break;

		}

	} else if(isset($_POST['mode']) && $_POST['mode']!='') {

		switch($_POST['mode']) {

			default:

				$fm->error($fm->lang('MODE_ERROR'));
				break;

			case 'add':
				$_POST['currentpath'] = str_replace('..', '', $_POST['currentpath']);	//crmv@fix
				if($fm->postvar('currentpath')) {
					$fm->add();
				}
				break;

		}

	}
}

echo Zend_json::encode($response);
die();

?>