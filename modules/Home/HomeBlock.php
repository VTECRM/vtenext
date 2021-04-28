<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/


global $mod_strings;
global $app_strings;
global $theme;
global $current_language; // crmv@202987
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

require_once('include/home.php');

$homeObj=new Homestuff;
$smarty=new VteSmarty();
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);

if(!empty($_REQUEST['homestuffid'])){
	$stuffid = $_REQUEST['homestuffid'];
}
if(!empty($_REQUEST['blockstufftype'])){
	$stufftype = $_REQUEST['blockstufftype'];
}

if($stufftype == 'URL'){
	$url = $homeObj->getWidgetURL($stuffid);
	if(strpos($url, "://") === false){
		$url = "http://".trim($url);
	}
	$smarty->assign("URL",$url);
	$smarty->assign("WIDGETID", $stuffid);
	$smarty->display("Home/HomeWidgetURL.tpl");
//crmv@25466
} elseif ($stufftype == 'SDKIframe') {
	$sdkiframe = SDK::getHomeIframe($stuffid);
	$url = $sdkiframe['url'];
	$url = str_replace('$CURRENT_LANGUAGE$', $current_language, $url); // crmv@202987
	$size = $sdkiframe['size'];
	if ($sdkiframe['iframe']) {
		$smarty->assign("URL",$url);
		$smarty->assign("SIZE",$size);
		$smarty->assign("WIDGETID", $stuffid);
		$smarty->assign("STUFFTYPE", $stufftype); //crmv@3079m
		$smarty->display("Home/HomeWidgetURL.tpl");
	} else {
		// check if there is a protocol specified
		if (strpos($url, "://") === false) {
			require($url);
		} else {
			echo file_get_contents($url);
		}
	}
//crmv@25466e
//crmv@25314	//crmv@29079
}elseif($stufftype == 'Iframe'){
	$url = $homeObj->getIframeURL($stuffid);
	if (strpos($url, "index.php") == 0) {
		//continue
	} elseif (strpos($url, "://") === false) {
		$url = "http://".trim($url);
	}
	$smarty->assign("URL",$url);
	$smarty->assign("WIDGETID", $stuffid);
	$smarty->assign("STUFFTYPE", $stufftype); //crmv@3079m
	$smarty->display("Home/HomeWidgetURL.tpl");
//crmv@25314e	//crmv@29079e
// crmv@30014
}elseif($stufftype == 'Charts' && vtlib_isModuleActive('Charts')){
	$chartted = $homeObj->getChartDetails($stuffid);
	$chid = $chartted['chartid'];
	if (!empty($chid)) {
		$chartInst = CRMEntity::getInstance('Charts');
		$chartInst->setCacheField('chart_file_home');
		$chartInst->retrieve_entity_info($chid, 'Charts');
		$chartInst->homestuffid = $stuffid;

		$chartInst->homestuffsize = empty($chartted['size']) ? 1 : $chartted['size'];
		$chartInst->reloadReport(); // when clicking on reload, reload report data
		echo $chartInst->renderHomeBlock();
	}
// crmv@30014e
}else{
	$homestuff_values=$homeObj->getHomePageStuff($stuffid,$stufftype);
    //crmv@208472
}

//crmv@208472
$smarty->assign("HOME_STUFFTYPE",$stufftype);
$smarty->assign("HOME_STUFFID",$stuffid);
$smarty->assign("HOME_STUFF",$homestuff_values);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);

$smarty->display("Home/HomeBlock.tpl");
?>