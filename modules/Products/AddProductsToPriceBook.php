<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@57339  crmv@128983 crmv@111998 crmv@208173*/

require_once('modules/Products/Products.php');

global $app_strings, $mod_strings, $current_language, $theme, $log, $current_user, $default_charset, $adb, $table_prefix;
$current_module_strings = return_module_language($current_language, 'Products');

$pricebook_id = vtlib_purify($_REQUEST['pricebook_id']);
$currency_id = vtlib_purify($_REQUEST['currency_id']);
if ($currency_id == null) $currency_id = fetchCurrency($current_user->id);
$parenttab = getParentTab();

$theme_path="themes/{$theme}/";
$image_path=$theme_path."images/";
require_once('modules/VteCore/layout_utils.php');	//crmv@30447

if(getFieldVisibilityPermission('Products',$current_user->id,'unit_price') != '0'){
	echo "<link rel='stylesheet' type='text/css' href='themes/$theme/style.css'>";
	echo "<table border='0' cellpadding='5' cellspacing='0' width='100%' height='450px'><tr><td align='center'>";
	echo "<div style='border: 3px solid rgb(153, 153, 153); background-color: rgb(255, 255, 255); width: 55%; position: relative; z-index: 10000000;'>

		<table border='0' cellpadding='5' cellspacing='0' width='98%'>
		<tbody><tr>
		<td rowspan='2' width='11%'><img src='". resourcever('denied.gif') ."' ></td>
		<td style='border-bottom: 1px solid rgb(204, 204, 204);' nowrap='nowrap' width='70%'><span class='genHeaderSmall'>{$app_strings['LBL_UNIT_PRICE_NOT_PERMITTED']}</span></td>
		</tr>
		<tr>
		<td class='small' align='right' nowrap='nowrap'>
		<a href='javascript:window.history.back();'>{$app_strings['LBL_GO_BACK']}</a><br>
		</td>
		</tr>
		</tbody></table>
		</div>";
	echo "</td></tr></table>";
	exit();
}

$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

$LVU = ListViewUtils::getInstance();

$pricebookname = getPriceBookName($pricebook_id);

$smarty= new VteSmarty();
$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);

$focus = CRMEntity::getInstance('Products');

if (isset($_REQUEST['order_by']))
	$order_by = $adb->sql_escape_string($_REQUEST['order_by']);

$url_string = ''; // assigning http url string
$sorder = 'ASC';  // Default sort order
if(isset($_REQUEST['sorder']) && $_REQUEST['sorder'] != '')
	$sorder = $adb->sql_escape_string($_REQUEST['sorder']);


//Retreive the list of Products
$list_query = $LVU->getListQuery("Products");

$list_query .=  " AND {$table_prefix}_products.discontinued<>0";

if(isset($order_by) && $order_by != '') {
	$list_query .= ' ORDER BY '.$order_by.' '.$sorder;
}


$exludeIdsQuery = " AND vte_products.productid NOT IN (select productid from {$table_prefix}_pricebookproductrel
		 INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_pricebookproductrel.productid
		 WHERE {$table_prefix}_crmentity.setype='Products' AND {$table_prefix}_crmentity.deleted=0 AND pricebookid= ?)";

if(isset($_REQUEST['start']) && $_REQUEST['start'] != '') {
	$start = $_REQUEST['start'];
} else {
	$start=1;
}

$res = $adb->query($list_query . $exludeIdsQuery, [$pricebook_id]);
$total_rows = $adb->num_rows($res);

$navigation_array = $LVU->getNavigationValues($start, $total_rows, '20');
$start_rec = $navigation_array['start'];
$end_rec = $navigation_array['end_val'];
$record_string = $app_strings['LBL_SHOWING']." " .$start_rec." - ".$end_rec." " .$app_strings['LBL_LIST_OF'] ." ".$total_rows;
$navigationOutput = $LVU->getTableHeaderNavigation($navigation_array, $url_string,"Products","AddProductsToPriceBook",'');
$smarty->assign("RECORD_COUNTS", $record_string);
$smarty->assign("NAVIGATION", $navigationOutput);
$smarty->assign("PRICEBOOK_ID", $pricebook_id);

if($navigation_array['end_val'] != 0) {
	$showRows = $navigation_array['end_val'] - $navigation_array['start'] + 1;
	$list_result = $adb->limitpQuery($list_query . $exludeIdsQuery, $navigation_array['start'],$showRows, []);
	$num_rows = $adb->num_rows($list_result);
} else {
	$num_rows = 0;
}

//Buttons Add To PriceBook and Cancel
$buttons_row = '';

$submit_button = '<input class="crmbutton small save" type="submit" value="'
	.$mod_strings['LBL_ADD_PRODUCTS_PRICEBOOK']
	.'" onclick="return addToPriceBook()"/>';

$cancel_button = '&nbsp;<input title="'
	.$app_strings['LBL_CANCEL_BUTTON_TITLE']
	.'" accessKey="' .$app_strings['LBL_CANCEL_BUTTON_KEY']
	.'" class="crmbutton small cancel" onclick="window.history.back()" type="button" name="button" value="'
	.$app_strings['LBL_CANCEL_BUTTON_LABEL'].'">';

if($num_rows != $num_prod_rows && $num_rows > 0) {
	$buttons_row .=  $submit_button;
}
$buttons_row .= $cancel_button;

$smarty->assign("HEADER_BUTTONS", $buttons_row);

//Retreive the List View Table Header

$list_header = '';
$list_header .= '<tr>';
$list_header .='<td class="lvtCol"><input type="checkbox" name="selectall" onClick=\'toggleSelect(this.checked,"selected_id");updateAllListPrice();updateSelectedCheckboxes()\'></td>';
$list_header .= '<td class="lvtCol">'.$mod_strings['LBL_LIST_PRODUCT_NAME'].'</td>';
if(getFieldVisibilityPermission('Products', $current_user->id, 'productcode') == '0')
	$list_header .= '<td class="lvtCol">'.$mod_strings['LBL_PRODUCT_CODE'].'</td>';
if(getFieldVisibilityPermission('Products', $current_user->id, 'unit_price') == '0')
	$list_header .= '<td class="lvtCol">'.$mod_strings['LBL_PRODUCT_UNIT_PRICE'].'</td>';
$list_header .= '<td class="lvtCol">'.$mod_strings['LBL_PB_LIST_PRICE'].'</td>';
$list_header .= '</tr>';

$smarty->assign("LISTHEADER", $list_header);

$new_prod_array = [];
$unit_price_array = [];
$field_name_array = [];
$entity_id_array = [];

//crmv@111998
for($i=0; $i<$num_rows; $i++)
{
	$entity_id = $adb->query_result($list_result,$i,"crmid");
	$new_prod_array[] = $entity_id;
	$entity_id_array[$entity_id] = $i;
}
//crmv@111998e

$prod_price_list = $InventoryUtils->getPricesForProducts($currency_id, $new_prod_array);

$list_body ='';
for($i=0; $i<count($new_prod_array); $i++)
{
	$log->info("Products :: Showing the List of products to be added in price book");
	$entity_id = $new_prod_array[$i];

	$list_body .= '<tr class="lvtColData" onmouseover="this.className=\'lvtColDataHover\'" onmouseout="this.className=\'lvtColData\'" bgcolor="white">';
	$unit_price = formatUserNumber($prod_price_list[$entity_id]); // crmv@173281
	$field_name = $entity_id."_listprice";
	$unit_price_array[]="'".$unit_price."'";
	$field_name_array[]="'".$field_name."'";

	$list_body .= '<td><INPUT type="checkbox" NAME="selected_id" id="check_'.$entity_id.'" value= '.$entity_id
		.' onClick=\'togglePBSelectAll(this.name,"selectall");updateListPrice("'.$unit_price.'","'.$field_name.'",this);updateSelectedCheckboxes()\'></td>'; //crmv@111998
	$list_body .= '<td><label for="check_'.$entity_id.'">'.$adb->query_result($list_result,$entity_id_array[$entity_id],"productname").'</label></td>';

	if(getFieldVisibilityPermission('Products', $current_user->id, 'productcode') == '0')
		$list_body .= '<td>'.$adb->query_result($list_result,$entity_id_array[$entity_id],"productcode").'</td>';
	if(getFieldVisibilityPermission('Products', $current_user->id, 'unit_price') == '0')
		$list_body .= '<td id="'. $entity_id .'_unit_price">'.$unit_price.'</td>'; //crmv@111998

	$list_body .='<td>';
	if(isPermitted("PriceBooks","EditView","") == 'yes')
		$list_body .= '<div class="dvtCellInfo"><input class="detailedViewTextBox" type="text" name="'.$field_name.'" style="visibility:hidden;" id="'.$field_name.'"></div>';
	else
		$list_body .= '<input class="detailedViewTextBox dvtCellInfoOff" type="text" name="'.$field_name.'" style="visibility:hidden;" readonly id="'.$field_name.'">';
	$list_body .= '</td></tr>';
}

$smarty->assign("UNIT_PRICE_ARRAY",implode(",",$unit_price_array));
$smarty->assign("FIELD_NAME_ARRAY",implode(",",$field_name_array));

if($order_by !='')
	$url_string .="&order_by=".$order_by;
if($sorder !='')
	$url_string .="&sorder=".$sorder;

$smarty->assign("LISTENTITY", $list_body);
$smarty->assign("CATEGORY", $parenttab);


if($_REQUEST['ajax'] !='')
	$smarty->display("AddProductsToPriceBookContents.tpl");
else
	$smarty->display("AddProductsToPriceBook.tpl");
