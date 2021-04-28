<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

	/**
	 * 	Do search and return response in XML or JSON
	 *  - Supports search plugin and categorized responses
	 *  - XML response is depreciated for Safari browser
	 * 
	 *	http://www.Web2Ajax.fr/examples/facebook_searchengine/
	 */
	#@ Protect search
	DEFINE('SEARCHENGINE_LOADED', true) ;
require_once('include/CustomFieldUtil.php');
require_once('include/database/PearDatabase.php');
require_once('include/utils/utils.php');
require_once('user_privileges/default_module_view.php');	
	#@ Define vars
	$search_engine['options']['input'] = $_GET['input'];
	$search_engine['options']['search_limit'] = isset($_GET['limit']) ? (int) $_GET['limit'] : 8;
	$search_engine['options']['input_len'] = strlen($search_engine['options']['input']) ;
	#@ Call plugin search 
	if ( $_GET['plugin'] ) {
		require_once('plugin_'.$_GET['plugin'].'.php');
	} 

	#@ Format result 
	$aResults = array();
	$count = 0;
	$len = $search_engine['options']['input_len'] ;
	if ( $len > 1)
	{
		#@ Count num results in all categories
		$num_total_results = 0 ;
		$search_results = $search_engine['results'];
		$num_total_results = count($search_results);		
		
		#@ Build results array
		@reset($search_results) ;
		global $default_charset;
		if ($search_results){
			foreach ($search_results as $id=>$res){
				$aResults[] = array(
						"id" 	=> $id,
						"value" => $res, 
//						"info"	=> '',
//						"data"  =>Array($id=>$res)	
				);
			}
		}
	}	
	
	header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
	header ("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header ("Pragma: no-cache"); // HTTP/1.0
	
	if (isset($_REQUEST['json']))
	{
		header("Content-Type: application/json");
		$arr['results'] = $aResults;
		echo Zend_Json::encode($arr);		
	}
	else
	{
		header("Content-Type: text/xml");
	
		echo "<?xml version=\"1.0\" encoding=\"$default_charset\" ?><results>";
		for ($i=0;$i<count($aResults);$i++)
		{
			echo "<rs id=\"".$aResults[$i]['id']."\" info=\"".$aResults[$i]['info']."\">".$aResults[$i]['value']."</rs>";
		}
		echo "</results>";
	}
die;	
?>