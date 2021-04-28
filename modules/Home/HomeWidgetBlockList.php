<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/**
 * @author MAK
 */

global $mod_strings;
global $app_strings;
global $theme;
global $current_language; // crmv@202987
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

require_once('include/home.php');

$homeObj=new Homestuff;

Zend_Json::$useBuiltinEncoderDecoder = true;
$widgetInfoList = Zend_Json::decode($_REQUEST['widgetInfoList']);
$widgetHTML = array();
$smarty=new VteSmarty();
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);

foreach ($widgetInfoList as $widgetInfo) {
	$widgetType = $widgetInfo['widgetType'];
	$widgetId = $widgetInfo['widgetId'];
	if($widgetType == 'URL'){
		$url = $homeObj->getWidgetURL($widgetId);
		if(strpos($url, "://") === false){
			$url = "http://".trim($url);
		}
		$smarty->assign("URL",$url);
		$smarty->assign("WIDGETID", $widgetId);
		$html = $smarty->fetch("Home/HomeWidgetURL.tpl");
	//crmv@25466
	}elseif ($widgetType == 'SDKIframe'){
		$sdkiframe = SDK::getHomeIframe($widgetId);
		$url = $sdkiframe['url'];
		$url = str_replace('$CURRENT_LANGUAGE$', $current_language, $url); // crmv@202987
		$size = $sdkiframe['size'];
		if ($sdkiframe['iframe']) {
			$smarty->assign("URL",$url);
			$smarty->assign("SIZE",$size);
			$smarty->assign("WIDGETID", $widgetId);
 			$smarty->assign("STUFFTYPE", $widgetType); //crmv@3079m
			$html = $smarty->fetch("Home/HomeWidgetURL.tpl");
		} else {
			// check if there is a protocol specified
			if (strpos($url, "://") === false) {
				// we use this trick to catch php output
				if (ob_start()) {
					require($url);
					$html = ob_get_contents();
					ob_end_clean();
				} else {
					// display an error message?
				}
			} else {
				$html = file_get_contents($url);
			}
		}
	//crmv@25466e
	//crmv@25314	//crmv@29079
	}elseif($widgetType == 'Iframe'){
		$url = $homeObj->getIframeURL($widgetId);
		if (strpos($url, "index.php") == 0) {
			//continue
		} elseif (strpos($url, "://") === false) {
			$url = "http://".trim($url);
		}
		$smarty->assign("URL",$url);
		$smarty->assign("WIDGETID", $widgetId);
		$smarty->assign("STUFFTYPE", $widgetType); //crmv@3079m
		$html = $smarty->fetch("Home/HomeWidgetURL.tpl");
	//crmv@25314e	//crmv@29079e
	// crmv@30014
	}elseif($widgetType == 'Charts' && vtlib_isModuleActive('Charts')){
		$chartted = $homeObj->getChartDetails($widgetId);
		$chid = $chartted['chartid'];
		if (!empty($chid)) {
			$chartInst = CRMEntity::getInstance('Charts');
			$chartInst->retrieve_entity_info($chid, 'Charts');
			$chartInst->homestuffid = $widgetId;
			$chartInst->homestuffsize = empty($chartted['size']) ? 1 : $chartted['size'];
			$html = $chartInst->renderHomeBlock();
		}
	// crmv@30014e
	}else{
		$homestuff_values=$homeObj->getHomePageStuff($widgetId,$widgetType);
		$html = '';
        //crmv@208472
	}
	$smarty->assign("HOME_STUFFTYPE",$widgetType);
	$smarty->assign("HOME_STUFFID",$widgetId);
	$smarty->assign("HOME_STUFF",$homestuff_values);
	$smarty->assign("THEME", $theme);
	$smarty->assign("IMAGE_PATH", $image_path);

	$html .= $smarty->fetch("Home/HomeBlock.tpl");
	$widgetHTML[$widgetId] = $html;
}
echo Zend_JSON::encode($widgetHTML);
?>