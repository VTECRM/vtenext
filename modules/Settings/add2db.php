<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

$uploaddir = $root_directory ."/storage/logo/" ;
$saveflag="true";
$nologo_specified="true";
$error_flag ="";
$nologo_specified="false";
$binFile = $_FILES['binFile']['name'];

if (isset($_REQUEST['binFile_hidden'])) {
	$filename = $_REQUEST['binFile_hidden'];
} else {
	$filename = ltrim(basename(" ".$binFile));
}

$filename = from_html(preg_replace('/\s+/', '_', $filename));	//crmv@37727
$filetype= $_FILES['binFile']['type'];
$filesize = $_FILES['binFile']['size'];
$filetype_array=explode("/",$filetype);
$file_type_val=strtolower($filetype_array[1]);

if ($filesize != 0) {
	//Checking whether the file is an image or not
	if (($file_type_val == "jpeg" ) || ($file_type_val == "png") || ($file_type_val == "jpg" ) ||  ($file_type_val == "pjpeg" ) || ($file_type_val == "x-png") ) {
		if (stristr($binFile, '.gif') != FALSE) {
			$savelogo="false";
			$error_flag ="1";
		} elseif ($result!=false) {
			$savelogo="true";
		}
	} else {
		$savelogo="false";
		$error_flag ="1";
	}
} else {
	$savelogo="false";
	if ($filename != "") $error_flag ="2";
}

$errorCode =  $_FILES['binFile']['error'];
if($errorCode == 4) {
	$savelogo="false";
	$errorcode="";
	$error_flag="5";
	$nologo_specified="true";
} elseif($errorCode == 2) {
	$error_flag ="3";
	$savelogo="false";
	$nologo_specified="false";
} elseif($errorCode == 3 ) {
	$error_flag ="4";
	$savelogo="false";
	$nologo_specified="false";
}

if($savelogo=="true") {
	move_uploaded_file($_FILES['binFile']['tmp_name'],$uploaddir.$filename);	//crmv@37727
}

if ($saveflag=="true") {
	global $adb, $table_prefix;
	
	$organization_name=from_html($_REQUEST['organization_name']);
	$org_name=$_REQUEST['org_name'];
	$organization_address=from_html($_REQUEST['organization_address']);
	$organization_city=from_html($_REQUEST['organization_city']);
	$organization_state=from_html($_REQUEST['organization_state']);
	$organization_code=from_html($_REQUEST['organization_code']);
	$organization_country=from_html($_REQUEST['organization_country']);
	$organization_phone=from_html($_REQUEST['organization_phone']);
	$organization_fax=from_html($_REQUEST['organization_fax']);
	$organization_website=from_html($_REQUEST['organization_website']);
	$organization_logo=from_html($_REQUEST['organization_logo']);
	//crmv@start
	$organization_banking=$_REQUEST['organization_banking'];
	$organization_vat_registration_number=$_REQUEST['organization_vat_registration_number'];
	$organization_rea=$_REQUEST['organization_rea'];
	$organization_issued_capital=$_REQUEST['organization_issued_capital'];
	//crmv@end

	$organization_logoname=$filename;
	if(!isset($organization_logoname))
		$organization_logoname="";

	$sql="SELECT organizationname, logoname FROM ".$table_prefix."_organizationdetails WHERE organizationname = ?";
	$result = $adb->pquery($sql, array($org_name));
	$org_name = decode_html($adb->query_result($result,0,'organizationname'));
	$org_logo = $adb->query_result($result,0,'logoname');

	if ($org_name=='') {
		$params = array(
			$organization_name, $organization_address, $organization_city, $organization_state, $organization_code,
			$organization_country, $organization_phone, $organization_fax, $organization_website, $organization_logoname,
			$organization_banking,$organization_vat_registration_number,$organization_rea,$organization_issued_capital
		);
		$sql="INSERT INTO ".$table_prefix."_organizationdetails
				(organizationname, address, city, state, code, country, phone, fax, website, logoname, crmv_banking, crmv_vat_registration_number, crmv_rea, crmv_issued_capital) 
				VALUES (".generateQuestionMarks($params).")";
	} else {
		if($savelogo=="true") {
			$organization_logoname=$filename;
		} elseif($savelogo=="false" && $error_flag=="") {
			$savelogo="true";
			$organization_logoname=$_REQUEST['PREV_FILE'];
		} else {
			$organization_logoname=$_REQUEST['PREV_FILE'];
		}
		
		if($nologo_specified=="true") {
			$savelogo="true";
			$organization_logoname=$org_logo;
		}
		
		//crmv@start
		$sql = "UPDATE ".$table_prefix."_organizationdetails
				SET organizationname = ?, address = ?, city = ?, state = ?, code = ?, country = ?, 
				phone = ?, fax = ?, website = ?, logoname = ?, crmv_banking = ?, crmv_vat_registration_number = ?, crmv_rea = ?, crmv_issued_capital = ? 
				WHERE organizationname = ?";
		$params = array(
			$organization_name, $organization_address, $organization_city, $organization_state, $organization_code,
			$organization_country, $organization_phone, $organization_fax, $organization_website, decode_html($organization_logoname),
			$organization_banking, $organization_vat_registration_number, $organization_rea, $organization_issued_capital, $org_name
		);
		//crmv@end
	}
	$adb->pquery($sql, $params);

	if($savelogo=="true") {
		header("Location: index.php?parenttab=Settings&module=Settings&action=OrganizationConfig");
	} elseif($savelogo=="false") {
		header("Location: index.php?parenttab=Settings&module=Settings&action=EditCompanyDetails&flag=".$error_flag);
	}
	
}
