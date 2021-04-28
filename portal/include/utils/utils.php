<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/utils/VteCsrf.php'); // crmv@171581
require_once('SDK/SDK.php');

global $result;
global $client;

function checkFileAccess($filepath) {
	$root_directory = '';

	// Set the base directory to compare with
	$use_root_directory = $root_directory;
	if(empty($use_root_directory)) {
		$use_root_directory = realpath(dirname(__FILE__).'/../../.');
	}

	$realfilepath = realpath($filepath);

	/** Replace all \\ with \ first */
	$realfilepath = str_replace('\\\\', '\\', $realfilepath);
	$rootdirpath  = str_replace('\\\\', '\\', $use_root_directory);

	/** Replace all \ with / now */
	$realfilepath = str_replace('\\', '/', $realfilepath);
	$rootdirpath  = str_replace('\\', '/', $rootdirpath);

	if(stripos($realfilepath, $rootdirpath) !== 0) {
		die("Sorry! Attempt to access restricted file.");
	}
	return true;
}

// crmv@168297
function loadTranslations($language = null) {
	global $app_strings, $default_language;
	
	if (is_null($language)) $language = $default_language;
	
	include("language/$language.lang.php");
	SDK::loadTranslations($language);
}
// crmv@168297e

function getTranslatedString($str)
{
	global $app_strings;
	return (isset($app_strings[$str]))?$app_strings[$str]:$str;
}

function getblock_fieldlist($block_array)
{
	$detail = array();
	
	$list='';$z=0;
	$field_count=count($block_array);
	if($field_count != 0)
	{
		for($i=0;$i<$field_count;$i++,$z++)
		{
			$blockname = $block_array[$i]['blockname'];
			$fieldlabel = $block_array[$i]['fieldlabel'];
			/*crmv57342*/
			$fieldname =  $block_array[$i]['fieldname'];
			$data = $block_array[$i]['fieldvalue'];
			if($fieldlabel == 'Note'){
    			$data = html_entity_decode($data);
    		}
    		if($data =='') $data ='&nbsp;';
			/*crmv@57342*/
			$detail[$blockname][] = array(getTranslatedString($fieldlabel),$data,$fieldname);
		}
	}
	return $detail;
}

// The function to get html format list data
// input array
// output htmlsource list array
//only for product
function getblock_fieldlistview_product($block_array,$module)
{
	if ($block_array == '') {
		return 'LBL_NOT_AVAILABLE';
	}
	
	$header_label = array(
		getTranslatedString($module),
		getTranslatedString('QUOTE_RELATED').getTranslatedString($module),
		getTranslatedString('INVOICE_RELATED').getTranslatedString($module)
	);

	$return = array();
	for($k=0;$k<=2;$k++)
	{
		$block_array = array(
			0 => $block_array[$k],
			1 => $block_array[$k]
		);
		$return[$header_label[$k]] = getblock_fieldlistview($block_array,$module);
	}
	return $return;
}

// The function to get html format list data
// input array
// output htmlsource list array
//for quotes,notes and invoice
function getblock_fieldlistview($block_array,$block)
{
	if ($block_array[0] == "#MODULE INACTIVE#") {
		return 'MODULE_INACTIVE';
	} elseif ($block_array == '') {
		return 'LBL_NOT_AVAILABLE';
	}
	
	//crmv@167234
	$header = array();
	$entries_arr = array();
	$links_arr = array();
	// crmv@167234e
	
	$header_arr = $block_array[0][$block]['head'][0];
	$nooffields = is_array($header_arr) ? count($header_arr) : ''; // crmv@167234
	if($nooffields!='')
	{
		$header = array();
		for($i=0; $i<$nooffields; $i++)
		{
			$header_value = $header_arr[$i]['fielddata'];
			$header[] = $header_value;
		}
	}
	
	$data_arr = $block_array[1][$block]['data'];
	$noofdata = is_array($data_arr) ? count($data_arr) : ''; // crmv@167234
	if($noofdata != '')
	{
		$entries_arr = array();
		$links_arr = array();
		for($j=0;$j<$noofdata;$j++)
		{
			$row = array();
			for($i=0;$i<$nooffields;$i++)
			{
				$data = $data_arr[$j][$i]['fielddata'];
				if($block == 'Documents' && $j == 0 && $i == 1){
					$row[] = $data;
				}else{
					if (empty($link)) {
						preg_match('/<a href="(.+)">/', $data, $match);
						$link = $match[1];
					}
					$data = strip_tags($data);	// in order to remove links
					if($data == '') $data ='&nbsp;';
					$row[] = $data;
				}
			}
			$entries_arr[] = $row;
			$links_arr[] = $link;
			$link = ''; // crmv@81291
		}
	}
	return array('HEADER'=>$header, 'ENTRIES'=>$entries_arr, 'LINKS'=>$links_arr);
}

/* 	Function to Show the languages in the Login page
*	Takes an array from PortalConfig.php file $language
*	Returns a list of available Language 	
*/
function getPortalLanguages() {
	global $languages,$default_language;
	foreach($languages as $name => $label) {
		if(strcmp($default_language,$name) == 0){
			$list .= '<option value='.$name.' selected>'.$label.'</option>';
		} else {
			$list .= '<option value='.$name.'>'.$label.'</option>';
		}
	}
	return $list;
}
/*	Function to set the Current Language
 * 	Sets the Session with the Current Language
 */
function setPortalCurrentLanguage() {
	global $default_language;
	if(isset($_REQUEST['login_language']) && $_REQUEST['login_language'] != ''){
		$_SESSION['portal_login_language'] = $_REQUEST['login_language'];
	} else {
		$_SESSION['portal_login_language'] = $default_language;
	}
}

/*	Function to get the Current Language
 * 	Returns the Current Language
 */
function getPortalCurrentLanguage() {
	global $default_language;
	if(isset($_SESSION['portal_login_language']) && $_SESSION['portal_login_language'] != ''){
		$default_language = $_SESSION['portal_login_language'];
	} elseif (!$default_language) { // crmv@167855
		$default_language = 'en_us';
	}
	return $default_language;
}


/** HTML Purifier global instance */
$__htmlpurifier_instance = false;

/*
 * Purify (Cleanup) malicious snippets of code from the input
 *
 * @param String $value
 * @param Boolean $ignore Skip cleaning of the input
 * @return String
 */
function portal_purify($input, $ignore=false) {
    global $default_charset, $__htmlpurifier_instance;
 
    $use_charset = $default_charset; 
    $value = $input; 
    if($ignore === false) {    	 
        // Initialize the instance if it has not yet done
        if(empty($use_charset)) $use_charset = 'UTF-8';
  
        if($__htmlpurifier_instance === false) {
            require_once('include/htmlpurifier/library/HTMLPurifier.auto.php'); // crmv@148761
            $config = HTMLPurifier_Config::createDefault();
            $config->set('Core.Encoding', $use_charset);
            $config->set('Cache.SerializerPath', "test/cache");
	
            $__htmlpurifier_instance = new HTMLPurifier($config);
        }
        if($__htmlpurifier_instance){
           $value = $__htmlpurifier_instance->purify($value);
        }
    }
    return $value;
}
// crmv@5946
/*
 * Collegare un allegato in qualsiasi modulo
 */
function AddAttachmentStandard()
{
	global $client, $Server_Path;
	$potentialid = $_REQUEST['potentialid'];
	$ownerid = $_SESSION['customer_id'];

	$filename = $_FILES['customerfile']['name'];
	$filetype = $_FILES['customerfile']['type'];
	$filesize = $_FILES['customerfile']['size'];
	$fileerror = $_FILES['customerfile']['error'];
	if (isset($_REQUEST['customerfile_hidden'])) {
		$filename = $_REQUEST['customerfile_hidden'];
	}

	$upload_error = '';
	if($fileerror == 4)
	{
		$upload_error = getTranslatedString('LBL_GIVE_VALID_FILE');
	}
	elseif($fileerror == 2)
	{
		$upload_error = getTranslatedString('LBL_UPLOAD_FILE_LARGE');
	}
	elseif($fileerror == 3)
	{
		$upload_error = getTranslatedString('LBL_PROBLEM_UPLOAD');
	}

	//Copy the file in temp and then read and pass the contents of the file as a string to db
	global	$upload_dir;
	if(!is_dir($upload_dir)) {
		echo getTranslatedString('LBL_NOTSET_UPLOAD_DIR');
		exit;
	}
	if($filesize > 0)
	{
		if(move_uploaded_file($_FILES["customerfile"]["tmp_name"],$upload_dir.'/'.$filename))
		{
			$filecontents = base64_encode(fread(fopen($upload_dir.'/'.$filename, "r"), $filesize));
		}

		$customerid = $_SESSION['customer_id'];
		$sessionid = $_SESSION['customer_sessionid'];

		$params = Array(Array(
				'id'=>"$customerid",
				'sessionid'=>"$sessionid",
				'potentialid'=>"$potentialid",
				'filename'=>"$filename",
				'filetype'=>"$filetype",
				'filesize'=>"$filesize",
				'filecontents'=>"$filecontents",
				'module'=>'Potentials',
				'key'=>'potentialid'
				));
		
		if($filecontents != ''){
			//add_attachment($module,$primarykey,$input_array)
			$commentresult = $client->call('add_attachment', $params, $Server_Path, $Server_Path);
		}else{
			echo getTranslatedString('LBL_FILE_HAS_NO_CONTENTS');
			exit();
		}
	}
	else
	{
		$upload_error = getTranslatedString('LBL_UPLOAD_VALID_FILE');
	}

	return $upload_error;
}
/*
 * Vedere gli allegati di una opportunit�
 * crmv@5946
 */
function getPotentialAttachmentsList($potentialid)
{
	global $client;

	$customer_name = $_SESSION['customer_name'];
	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];
	$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'potentialid'=>"$potentialid"));
	$result = $client->call('get_potential_attachments',$params);

	return $result;
}

// crmv@5946e

// crmv168297 - moved here

//crmv@18049
function get_logo($mode){
	include_once('version.php');
	global $enterprise_mode,$enterprise_project;
	$logo_path = 'images/';
	if ($mode == 'favicon')
		$extension = 'ico';
	else		
		$extension = 'png';
	if ($mode == 'project')
		$logo_path.=$enterprise_project.".".$extension;
	else
		$logo_path.=$enterprise_mode."_".$mode.".".$extension;
	return $logo_path;
}
//crmv@18049e

function preprint($arr,$die=false) {
	echo '<pre>';
	print_r($arr);
	echo '</pre>';
	if ($die) die;
}

// crmv@173271
function getModulesPermissions($customerid) {
	global $client, $Server_path;
	
	if (!$_SESSION['__mod_permissions']) {
		$rwperm = $client->call('get_modules_permissions',array('customerid' => $customerid),$Server_path,$Server_path);
		$err = $client->getError();
		if (!$err) {
			$_SESSION['__mod_permissions'] = $rwperm;
		}
	}
	
	return $_SESSION['__mod_permissions'];
}

function getModulePermissions($customerid, $module) {
	$perm = getModulesPermissions($customerid);
	return $perm[$module];
}
// crmv@173271e