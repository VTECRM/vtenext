<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/home.php');
require_once('modules/Rss/Rss.php');
$oHomestuff=new Homestuff();
if(!empty($_REQUEST['stufftype'])){
	$oHomestuff->stufftype=$_REQUEST['stufftype'];
}

if(!empty($_REQUEST['stufftitle'])){
	if(strlen($_REQUEST['stufftitle'])>100){
		$temp_str = substr($_REQUEST['stufftitle'],0,97)."...";
		$oHomestuff->stufftitle= $temp_str;
	}else{
		$oHomestuff->stufftitle=$_REQUEST['stufftitle'];
	}
	// Remove HTML/PHP tags from the input
	if(isset($oHomestuff->stufftitle)) {
		$oHomestuff->stufftitle = strip_tags($oHomestuff->stufftitle);
	}
}

if(!empty($_REQUEST['selmodule'])){
	$oHomestuff->selmodule=$_REQUEST['selmodule'];
}

if(!empty($_REQUEST['maxentries'])){
	$oHomestuff->maxentries=$_REQUEST['maxentries'];
}

if(!empty($_REQUEST['selFiltername'])){
	$oHomestuff->selFiltername=$_REQUEST['selFiltername'];
}

if(!empty($_REQUEST['fldname'])){
	$oHomestuff->fieldvalue=$_REQUEST['fldname'];
}

if(!empty($_REQUEST['txtRss'])){
	$ooRss=new VteRSS();
	if($ooRss->setRSSUrl($_REQUEST['txtRss'])){
		$oHomestuff->txtRss=$_REQUEST['txtRss'];
	}else{
		return false;
	}
}

if(!empty($_REQUEST['txtURL'])){
	$oHomestuff->txtURL = $_REQUEST['txtURL'];
}
if(isset($_REQUEST['seldashbd']) && $_REQUEST['seldashbd']!=""){
	$oHomestuff->seldashbd=$_REQUEST['seldashbd'];
}

// crmv@30014
if(isset($_REQUEST['selchart']) && $_REQUEST['selchart']!=""){
	$oHomestuff->selchart=$_REQUEST['selchart'];
}
// crmv@30014e

if(isset($_REQUEST['seldashtype']) && $_REQUEST['seldashtype']!=""){
	$oHomestuff->seldashtype=$_REQUEST['seldashtype'];
}

if(isset($_REQUEST['seldeftype']) && $_REQUEST['seldeftype']!=""){
	$seldeftype=$_REQUEST['seldeftype'];
	$defarr=explode(",",$seldeftype);
	$oHomestuff->defaultvalue=$defarr[0];
	$deftitlehash=$defarr[1];
	$oHomestuff->defaulttitle=str_replace("#"," ",$deftitlehash);
}

$loaddetail=$oHomestuff->addStuff();
echo $loaddetail;
?>