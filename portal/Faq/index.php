<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/Zend/Json.php');
require_once('Faq/Utils.php');
require_once('include/utils/utils.php');

/* crmv@173271 TODO: migrate to class */

$block = 'Faq';
$faq_display = array();

// HEADER
$search_text = portal_purify($search_text);
$smarty->assign('SEARCHTEXT',$search_text);

$SearchCombo = getSearchCombo();
$smarty->assign('SEARCHCOMBO',$SearchCombo);

//

@include ("../PortalConfig.php");
if (! isset ( $_SESSION ['customer_id'] ) || $_SESSION ['customer_id'] == '') {
	@header ( "Location: $Authenticate_Path/login.php" );
	exit ();
}
include ("include.php");

// This is added first because when we add new comment, the comments will be added first and then Faq list will be retrieved
if ($_REQUEST ['fun'] == 'faq_updatecomment') {
	include ("Faq/SaveFaqComment.php");
}

$customerid = $_SESSION ['customer_id'];
$sessionid = $_SESSION ['customer_sessionid'];

$params = Array (
	Array (
		'id' => "$customerid",
		'sessionid' => "$sessionid" 
	)
);
$result = $client->call ( 'get_KBase_details', $params, $Server_Path, $Server_Path );

// crmv@184489
$category_array = $result['faqcategory'];
$faq_array = isset($result['faq']) ? $result['faq'] : null; // crmv@185488
$product_array = $result['product'];
// crmv@184489e

$_SESSION ['product_array'] = $product_array;
$_SESSION ['category_array'] = $category_array;
$_SESSION ['faq_array'] = $faq_array;

$search_text = $_REQUEST ['search_text'];

if (empty($_REQUEST['fun']) || !isset($_REQUEST['fun'])) { // crmv@184489
	if (! empty ( $faq_array )){
		$faq_display = getLatestlyCreatedFaqList ();
	}
} elseif ($_REQUEST ['fun'] == 'faqs') {
	if ($_REQUEST ['category_index'] != '') {
		$faq_display = ListFaqsPerCategory ( $_REQUEST ['category_index'] );
	} elseif ($_REQUEST ['productid'] != '') {
		$faq_display = ListFaqsPerProduct ( $_REQUEST ['productid'] );
	} else {
		echo 'Wrong parameters';
	}
} elseif ($_REQUEST ['fun'] == 'search') {
	$search_text = $_REQUEST ['search_text'];
	$search_category = explode ( ":", $_REQUEST ['search_category'] );
	$searchlist .= getSearchResult ( $search_text, $search_category [1], $search_category [0] );
// 	echo $searchlist;
	$faq_display = $searchlist;
} elseif ($_REQUEST ['fun'] == 'faq_detail') {
	include ("Faq/FaqDetail.php");
} elseif ($_REQUEST ['fun'] == 'faq_updatecomment') {
	?>
<script>
		var faqid = <?php echo Zend_Json::encode($_REQUEST['faqid']); ?>;
		window.location.href = "index.php?module=Faq&action=index&fun=faq_detail&faqid="+faqid
	</script>
<?php
}

$smarty->assign('FAQARRAY',$faq_array);
$smarty->assign('CATEGORYARRAY',$category_array);
$smarty->assign('PRODUCTARRAY',$product_array);


if($_REQUEST ['fun'] != 'faq_detail'){
$smarty->assign('FAQDISPLAY',$faq_display);
// $smarty->assign('LINKS',$links_arr);
// $smarty->assign('MODULE','HelpDesk');
// $smarty->assign('MINE_SELECTED',$mine_selected);
// $smarty->assign('ALL_SELECTED',$all_selected);

$smarty->assign('MODULE',$_REQUEST['module']);
$smarty->display('FaqList.tpl');
}
