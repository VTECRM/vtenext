<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@208173 */

/**Function to get the top 5 Potentials order by Amount in Descending Order
 *return array $values - array with the title, header and entries like  Array('Title'=>$title,'Header'=>$listview_header,'Entries'=>$listview_entries) where as listview_header and listview_entries are arrays of header and entity values which are returned from function getListViewHeader and getListViewEntries
*/
function getTopPotentials($maxval,$calCnt)
{
    global $table_prefix;
    global $adb;
    global $current_language;
    global $current_user;
    global $display_empty_home_blocks;

    require_once("data/Tracker.php");
    require_once('modules/Potentials/Potentials.php');
    require_once('include/logging.php');
    require_once('include/ListView/ListView.php');

	$log = LoggerManager::getLogger();
	$log->debug("Entering getTopPotentials() method ...");

	$current_module_strings = return_module_language($current_language, "Potentials");
	
	$LVU = ListViewUtils::getInstance();

	$title = [];
	$title[] = 'myTopOpenPotentials.gif';
	$title[] = $current_module_strings['LBL_TOP_OPPORTUNITIES'];
	$title[] = 'home_mypot';
	$where = "AND {$table_prefix}_potential.sales_stage not in ('Closed Won','Closed Lost','{$current_module_strings['Closed Won']}','{$current_module_strings['Closed Lost']}') AND {$table_prefix}_crmentity.smownerid='{$current_user->id}'";
	$header = [];
	$header[] = $current_module_strings['LBL_LIST_OPPORTUNITY_NAME'];
	$currencyid = fetchCurrency($current_user->id);
	$rate_symbol = getCurrencySymbolandCRate($currencyid);
	$rate = $rate_symbol['rate'];
	$curr_symbol = $rate_symbol['symbol'];
        $header[] = $current_module_strings['LBL_LIST_AMOUNT'].'('.$curr_symbol.')';
	$list_query = $LVU->getListQuery("Potentials", $where);
	$list_query .=" ORDER BY amount DESC";
	
	if($calCnt == 'calculateCnt') {
		$list_result_rows = $adb->query(mkCountQuery($list_query));
		return $adb->query_result($list_result_rows, 0, 'count');
	}
	$list_result=$adb->limitQuery($list_query,0,$maxval);
	
	$open_potentials_list = [];
	$noofrows = $adb->num_rows($list_result);

	$entries = [];
	if ($noofrows) {
		for($i=0;$i<$noofrows;$i++) 
		{
            $open_potentials_list[] = [
                'name' => $adb->query_result($list_result,$i,'potentialname'),
                'id' => $adb->query_result($list_result,$i,'potentialid'),
                'amount' => $adb->query_result($list_result,$i,'amount'),
            ];
			$potentialid = $adb->query_result($list_result,$i,'potentialid');
			$potentialname = $adb->query_result($list_result,$i,'potentialname');
			$Top_Potential = (strlen($potentialname) > 20) ? (substr($potentialname,0,20).'...') : $potentialname;
			$value = [];
			$value[]='<a href="index.php?action=DetailView&module=Potentials&record='.$potentialid.'">'.$Top_Potential.'</a>';
			//crmv@92519
			$val = convertFromMasterCurrency($adb->query_result_no_html($list_result,$i,'amount'),$rate);
			$value[] = formatUserNumber($val);
			//crmv@92519e
			$entries[$potentialid]=$value;
		}
	}
	
	$search_qry = "&query=true&Fields0={$table_prefix}_crmentity.smownerid&Condition0=e&Srch_value0={$current_user->column_fields['user_name']}&Fields1={$table_prefix}_potential.sales_stage&Condition1=k&Srch_value1=closed&searchtype=advance&search_cnt=2&matchtype=all"; // crmv@157122
			
	$values = [
	    'ModuleName'=>'Potentials',
        'Title'=>$title,
        'Header'=>$header,
        'Entries'=>$entries,
        'search_qry'=>$search_qry
    ];

	if ( ($display_empty_home_blocks && count($open_potentials_list) == 0 ) || (count($open_potentials_list)>0) )
	{
		$log->debug("Exiting getTopPotentials method ...");
		return $values;		
	}
}