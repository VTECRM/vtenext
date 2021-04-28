<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@44323

require_once('modules/Calendar/Activity.php');
//crmv@203484 removed including file
require_once('include/ListView/SimpleListView.php');

// vte_potential is used to store customer information.
class Potentials extends CRMEntity {
	var $log;
	var $db;

	var $module_name="Potentials";
	var $table_name;
	var $table_index= 'potentialid';

	var $tab_name = Array();
	var $tab_name_index = Array();
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();

	var $column_fields = Array();

	var $sortby_fields = Array('potentialname','amount','closingdate','smownerid','accountname');

	// This is the list of vte_fields that are in the lists.
	var $list_fields = Array(
			'Potential'=>Array('potential'=>'potentialname'),
			'Related to'=>Array('potential'=>'related_to'),
			'Sales Stage'=>Array('potential'=>'sales_stage'),
			'Amount'=>Array('potential'=>'amount'),
			'Expected Close Date'=>Array('potential'=>'closingdate'),
			'Assigned To'=>Array('crmentity','smownerid')
			);

	var $list_fields_name = Array(
			'Potential'=>'potentialname',
			'Related to'=>'related_to',
			'Sales Stage'=>'sales_stage',
			'Amount'=>'amount',
			'Expected Close Date'=>'closingdate',
			'Assigned To'=>'assigned_user_id');

	var $list_link_field= 'potentialname';

	var $search_fields = Array(
			'Potential'=>Array('potential'=>'potentialname'),
			'Related To'=>Array('potential'=>'related_to'),
			'Expected Close Date'=>Array('potential'=>'closedate')
			);

	var $search_fields_name = Array(
			'Potential'=>'potentialname',
			'Related To'=>'related_to',
			'Expected Close Date'=>'closingdate'
			);

	var $required_fields =  array();

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('assigned_user_id', 'createdtime', 'modifiedtime', 'potentialname', 'related_to');

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'potentialname';
	var $default_sort_order = 'ASC';
	//crmv@10759
	var $search_base_field = 'potentialname';
	//crmv@10759 e
	//var $groupTable = Array('vte_potentialgrouprelation','potentialid');
	function __construct() {
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix."_potential";
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_potential',$table_prefix.'_potentialscf');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_potential'=>'potentialid',$table_prefix.'_potentialscf'=>'potentialid');
		$this->customFieldTable = Array($table_prefix.'_potentialscf', 'potentialid');
		//crmv@44187 - relaton tables with specific modules
		$this->extra_relation_tables = array(
			'Contacts' => array(
				'relation_table' => "{$table_prefix}_contpotentialrel",
				'relation_table_id' => 'potentialid',
				'relation_table_otherid' => 'contactid',
				//relation_table_module
			//relation_table_othermodule
			),
			'Accounts' => array(
				'relation_table' => "{$table_prefix}_accpotentialrel",
				'relation_table_id' => 'potentialid',
				'relation_table_otherid' => 'accountid',
			),
		);
		//crmv@44187e
		$this->log = LoggerManager::getLogger('potential');
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('Potentials');
	}

	/** Function to export the Opportunities records in CSV Format
	* @param reference variable - order by is passed when the query is executed
	* @param reference variable - where condition is passed when the query is executed
	* Returns Export Potentials Query.
	*/
	function create_export_query($where,$oCustomView,$viewId)	//crmv@31775
	{
		global $log;
		global $current_user;
		global $table_prefix;
		$log->debug("Entering create_export_query(". $where.") method ...");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery("Potentials", "detail_view");
		$fields_list = getFieldsListFromQuery($sql);

		$query = "SELECT $fields_list,case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name
				FROM ".$table_prefix."_potential
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_potential.potentialid
				LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_crmentity.smownerid=".$table_prefix."_users.id
				LEFT JOIN ".$table_prefix."_account on ".$table_prefix."_potential.related_to=".$table_prefix."_account.accountid
				LEFT JOIN ".$table_prefix."_contactdetails on ".$table_prefix."_potential.related_to=".$table_prefix."_contactdetails.contactid
				LEFT JOIN ".$table_prefix."_potentialscf on ".$table_prefix."_potentialscf.potentialid=".$table_prefix."_potential.potentialid
                LEFT JOIN ".$table_prefix."_groups
        	        ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				LEFT JOIN ".$table_prefix."_campaign
					ON ".$table_prefix."_campaign.campaignid = ".$table_prefix."_potential.campaignid";

		//crmv@31775
		$reportFilter = $oCustomView->getReportFilter($viewId);
		if ($reportFilter) {
			$tableNameTmp = $oCustomView->getReportFilterTableName($reportFilter,$current_user->id);
			$query .= " INNER JOIN $tableNameTmp ON $tableNameTmp.id = {$table_prefix}_crmentity.crmid";
		}
		//crmv@31775e

		$query .= $this->getNonAdminAccessControlQuery('Potentials',$current_user);
		$where_auto = "  ".$table_prefix."_crmentity.deleted = 0 ";

		if($where != "")
			$query .= "  WHERE ($where) AND ".$where_auto;
		else
			$query .= "  WHERE ".$where_auto;
		$query = $this->listQueryNonAdminChange($query, 'Potentials');
		$log->debug("Exiting create_export_query method ...");
		return $query;

	}

	/**
	 * Returns a list of the associated contacts
	 */
	function get_contacts($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user;//crmv@203484 removed global singlepane
		global $table_prefix;
		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		$log->debug("Entering get_contacts(".$id.") method ...");
		$this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);
        vtlib_setup_modulevars($related_module, $other);
		$parenttab = getParentTab();

		if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

		$button = '';

		$accountid = $this->column_fields['related_to'];
		$search_string = "&fromPotential=true&acc_id=$accountid";

		if($actions) {
			$button .= $this->get_related_buttons($currentModule, $id, $related_module, $actions); // crmv@43864
		}

		$query = 'select case when ('.$table_prefix.'_users.user_name is not null) then '.$table_prefix.'_users.user_name else '.$table_prefix.'_groups.groupname end as user_name,
					'.$table_prefix.'_contactdetails.accountid,'.$table_prefix.'_potential.potentialid, '.$table_prefix.'_potential.potentialname, '.$table_prefix.'_contactdetails.contactid,
					'.$table_prefix.'_contactdetails.lastname, '.$table_prefix.'_contactdetails.firstname, '.$table_prefix.'_contactdetails.title, '.$table_prefix.'_contactdetails.department,
					'.$table_prefix.'_contactdetails.email, '.$table_prefix.'_contactdetails.phone, '.$table_prefix.'_crmentity.crmid, '.$table_prefix.'_crmentity.smownerid,
					'.$table_prefix.'_crmentity.modifiedtime , '.$table_prefix.'_account.accountname from '.$table_prefix.'_potential
					inner join '.$table_prefix.'_potentialscf on '.$table_prefix.'_potentialscf.potentialid = '.$table_prefix.'_potential.potentialid
					inner join '.$table_prefix.'_contpotentialrel on '.$table_prefix.'_contpotentialrel.potentialid = '.$table_prefix.'_potential.potentialid
					inner join '.$table_prefix.'_contactdetails on '.$table_prefix.'_contpotentialrel.contactid = '.$table_prefix.'_contactdetails.contactid
					inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_contactdetails.contactid
					left join '.$table_prefix.'_account on '.$table_prefix.'_account.accountid = '.$table_prefix.'_contactdetails.accountid
					left join '.$table_prefix.'_groups on '.$table_prefix.'_groups.groupid='.$table_prefix.'_crmentity.smownerid
					left join '.$table_prefix.'_users on '.$table_prefix.'_crmentity.smownerid='.$table_prefix.'_users.id
					where '.$table_prefix.'_potential.potentialid = '.$id.' and '.$table_prefix.'_crmentity.deleted=0';

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_contacts method ...");
		return $return_value;
	}

	// crmv@53923
	function postSaveAmount() {
		global $adb, $table_prefix;
		global $current_user, $currentModule;

		if (isset($this->column_fields['amount']) && $this->existingAmount != $this->column_fields['amount']) {
			$adb->pquery("insert into {$table_prefix}_potential_amounts (potentialid, amountdate, amount) VALUES (?,?,?)", array($this->id, date('Y-m-d H:i:s'), $this->column_fields['amount']));
			// clear chart cache
			if (vtlib_isModuleActive('Charts')) {
				require_once('modules/Potentials/PotentialsCharts.php'); // crmv@53923

				$oldCurrentModule = $currentModule;
				$currentModule = 'Charts';
				$chartInst = new PotentialsCharts($this);
				$chartInst->column_fields['chartname'] = 'AmountHistory';
				$cname = $chartInst->generateFileName();
				@unlink($cname);
				$chartInst->column_fields['chartname'] = 'ProductLines';
				$cname = $chartInst->generateFileName();
				@unlink($cname);
				$currentModule = $oldCurrentModule;
			}
		}
	}
	
	function save_module($module) {
		$this->postSaveAmount();
	}
	// crmv@53923e

	// crmv@44323
	// overridden to detect changes
	function save($module_name,$longdesc=false,$offline_update=false,$triggerEvent=true) {

		// crmv@53923
		$this->existingAmount = null;
		if ($this->id > 0) {
			$this->existingAmount = getSingleFieldValue($this->table_name, 'amount', $this->table_index, $this->id);
		}
		// crmv@53923e

		// updating the status
		if ($this->id > 0 && array_key_exists('sales_stage', $_REQUEST)) {
			$closingStates = array('Closed Won', 'Closed Lost');
			$oldStatus = getSingleFieldValue($this->table_name, 'sales_stage', $this->table_index, $this->id);
			$newStatus = $_REQUEST['sales_stage'];
			if ($oldStatus != $newStatus && in_array($newStatus, $closingStates)) {
				// force the eff close date
				$_REQUEST['eff_closingdate'] = getDisplayDate(date('Y-m-d'));
				$this->column_fields['eff_closingdate'] = $_REQUEST['eff_closingdate'];
			}
		}

		parent::save($module_name,$longdesc,$offline_update,$triggerEvent);
	}
	// crmv@44323e

	//crmv@44187 crmv@44323
	function PopupQueryChange($query, $params) {
		global $table_prefix;
		if ($_REQUEST['popup_mode'] == 'pot_select_competitor') {
			$potentialid = $_REQUEST['from_crmid'];
			$query = preg_replace('/order by/i', "AND {$table_prefix}_account.account_type = 'Competitor' ORDER BY", $query);
		} elseif ($_REQUEST['popup_mode'] == 'pot_select_partners') {
			$query = preg_replace('/order by/i', "AND {$table_prefix}_account.account_type != 'Competitor' ORDER BY", $query);
		}
		return $query;
	}


	// get info for the product lines used, without taxes, fares and adjustments
	function getProdLinesInfo($recordid, $module = 'Quotes') {
		global $adb, $table_prefix, $default_decimals_num;
		// use only one qoteid
		if (!$recordid) return;

		$rm = RelationManager::getInstance();
		$IUtils = InventoryUtils::getInstance();
		
		$focus = CRMEntity::getInstance($module);
		$focus->retrieve_entity_info($recordid, $module);
		$focus->id = $recordid;
		
		$prodDetails = $IUtils->getAssociatedProducts($module, $focus);
		
		
		$lines = array();
		$megatotal = 0.0;
		$megacost = 0.0;
		// consider only the price - discount, taxes are stripped
		foreach ($prodDetails as $i=>$prod) {
			$prodid = $prod['hdnProductId'.$i];
			
			$line = $rm->getRelatedIds('Products', $prodid, 'ProductLines');
			$lineid = $line[0];
			if (empty($lineid)) $lineid = 0; // no product line
			
			$prodtotal = $prod['totalAfterDiscount'.$i];
			$linecost =  $prod['unit_cost'.$i] * $prod['qty'.$i];
			
			if (!array_key_exists($lineid, $lines)) {
				if ($lineid == 0) {
					$lname = 'None';
				} else {
					$lname = getEntityName('ProductLines', $lineid, true);
				}

				$lines[$lineid] = array(
					'productlineid' => $lineid,
					'linename' => $lname,
					'products' => array($prodid),
					'total' => $prodtotal,
					'total_cost' => $linecost,
					'margin' => '', // calculated later
				);
			} else {
				$lines[$lineid]['products'][] = $prodid;
				$lines[$lineid]['total'] += $prodtotal;
				$lines[$lineid]['total_cost'] += $linecost;
			}
			$megatotal += $prodtotal;
			$megacost += $linecost;
		}
		
		// now check for global discounts (but ignore taxes and roundups)
		$totalinfo = array(
			'nettotal' => floatval($megatotal),
			's_h_amount' => 0,
			'discount_percent' => $focus->column_fields['hdnDiscountPercent'],
			'discount_amount' => $focus->column_fields['hdnDiscountAmount'],
			'adjustment' => 0,
			'taxes' => array(),
			'shtaxes' => array(),
		);

		// calculate totals
		$totalPrices = $IUtils->calcInventoryTotals($totalinfo);
		$totalDiscount = $totalPrices['total_discount'];
		$totalAfterDiscount = $totalPrices['price_discount'];
		if ($totalAfterDiscount != $megatotal) {
		
			// calculate the new amount spreading the global discount
			$discountPerc = $totalDiscount/$megatotal;
			foreach ($lines as &$line) {
				$line['total'] = $line['total'] * (1-$discountPerc);
			}
		
			// calculate the megatotal now again, after the discounts have been applied
			$megatotal = 0;
			foreach ($lines as &$line) {
				$megatotal += $line['total'];
			}
		}
		
		// calculate the margin
		$totalmargin = 0;
		foreach ($lines as &$line) {
			if ($line['total_cost'] != 0) {
				$line['margin'] = ($line['total'] - $line['total_cost']) / $line['total'];
			}
		}
		
		($megatotal > 0) ? $linesmargin = ($megatotal-$megacost)/$megatotal : $linesmargin = 0;
		$result = array('list'=>$lines, 'linestotal'=>$megatotal, 'linesmargin'=>$linesmargin, 'countprods'=>count($prodDetails), 'quote'=>$focus->column_fields);
		
		// save locally also
		$this->prodLineInfo = $result;
		
		return $result;
	}

	//crmv@45699 crmv@53923 crmv@104975
	function getExtraDetailTabs() {
		global $app_strings;
		
		$return = array(
			array('label'=>$app_strings['Players'],'href'=>'','onclick'=>"potPanelClickTab(this, 'potPanelRelations')"),
			array('label'=>getTranslatedString('ProductLines','ProductLines'),'href'=>'','onclick'=>"potPanelClickTab(this, 'potPanelLines')"),
		);
		if (vtlib_isModuleActive('Charts')) {
			$return[] = array('label'=>getTranslatedString('Charts','Charts'),'href'=>'','onclick'=>"potPanelClickTab(this, 'potPanelCharts')");
		}
		$others = parent::getExtraDetailTabs() ?: array();

		return array_merge($return, $others);
	}
	//crmv@45699e crmv@104975e

	function getExtraDetailBlock() {
		global $adb, $table_prefix;
		global $mod_strings, $app_strings, $currentModule, $current_user, $theme;
		
		$smarty = new VteSmarty();

		$smarty->assign('APP', $app_strings);
		$smarty->assign('MOD', $mod_strings);
		$smarty->assign('MODULE', $currentModule);
		$smarty->assign('THEME', $theme);
		$smarty->assign('ID', $this->id);
		

		$rm = RelationManager::getInstance();

		// picklist values
		$plistValues = getAssignedPicklistValues('contact_roles', $current_user->roleid, $adb, 'Potentials');
		$smarty->assign('CONTACT_ROLES', $plistValues);

		$plistValues = getAssignedPicklistValues('partner_roles', $current_user->roleid, $adb, 'Potentials');
		$smarty->assign('PARTNER_ROLES', $plistValues);

		$accountid = $this->column_fields['related_to'];
		if ($accountid > 0) {
			$relatedType = getSalesEntityType($accountid);
		}
		
		$r = $adb->pquery("select fieldid from {$table_prefix}_field where tabid = ? and fieldname = ?", array(getTabid('Contacts'), 'email'));
		if ($r) $email_fieldid = $adb->query_result_no_html($r, 0, 'fieldid');
		
		$contacts = array();
		if ($relatedType == 'Accounts') {
			// get account fieldid
			$contactIds = $rm->getRelatedIds($relatedType, $accountid, 'Contacts');
			foreach ($contactIds as $cid) {
				$contInfo = CRMEntity::getInstance('Contacts');
				//crmv@117067
				$ret = $contInfo->retrieve_entity_info($cid, 'Contacts',false);
				if(in_array($ret,array('LBL_RECORD_DELETE','LBL_RECORD_NOT_FOUND'))) continue;
				//crmv@117067e
				// get info from relation
				$res = $adb->pquery("select * from {$table_prefix}_contpotentialrel where potentialid = ? and contactid = ?", array($this->id, $cid));
				$relInfo = $adb->FetchByAssoc($res, -1, false);
				$contacts[] = array(
					'contactid' => $cid,
					'firstname' => $contInfo->column_fields['firstname'],
					'lastname' => $contInfo->column_fields['lastname'],
					'email' => $contInfo->column_fields['email'],
					'email_fieldid' => $email_fieldid,
					'phone' => $contInfo->column_fields['phone'],
					'main_contact' => $relInfo['main_contact'],
					'contact_role' => $relInfo['contact_role'],
				);
			}
		}
		$smarty->assign('ACCOUNT_CONTACTS', $contacts);

		$contacts2 = array();
		$contactIds2 = $rm->getRelatedIds('Potentials', $this->id, 'Contacts');
		if (is_array($contactIds)) $contactIds2 = array_diff($contactIds2, $contactIds);
		foreach ($contactIds2 as $cid) {
			$contInfo = CRMEntity::getInstance('Contacts');
			//crmv@117067
			$ret = $contInfo->retrieve_entity_info($cid, 'Contacts',false);
			if(in_array($ret,array('LBL_RECORD_DELETE','LBL_RECORD_NOT_FOUND'))) continue;
			//crmv@117067e
			// get info from relation
			$res = $adb->pquery("select * from {$table_prefix}_contpotentialrel where potentialid = ? and contactid = ?", array($this->id, $cid));
			$relInfo = $adb->FetchByAssoc($res, -1, false);
			$contacts2[] = array(
				'contactid' => $cid,
				'firstname' => $contInfo->column_fields['firstname'],
				'lastname' => $contInfo->column_fields['lastname'],
				'email' => $contInfo->column_fields['email'],
				'email_fieldid' => $email_fieldid,
				'phone' => $contInfo->column_fields['phone'],
				'main_contact' => $relInfo['main_contact'],
				'contact_role' => $relInfo['contact_role'],
			);
		}

		$smarty->assign('OTHER_CONTACTS', $contacts2);
		
		$r = $adb->pquery("select fieldid from {$table_prefix}_field where tabid = ? and fieldname = ?", array(getTabid('Accounts'), 'email1'));
		if ($r) $email_fieldid = $adb->query_result_no_html($r, 0, 'fieldid');

		// now get other accounts and competitors
		$accounts = array();
		$comp = array();
		$relations = $rm->getRelations('Potentials', ModuleRelation::$TYPE_NTON, 'Accounts');
		if (!empty($relations)) {
			$relAccounts = $relations[0]->getRelatedIds($this->id);
			foreach ($relAccounts as $cid) {
				$accInfo = CRMEntity::getInstance('Accounts');
				//crmv@117067
				$ret = $accInfo->retrieve_entity_info($cid, 'Accounts',false);
				if(in_array($ret,array('LBL_RECORD_DELETE','LBL_RECORD_NOT_FOUND'))) continue;
				//crmv@117067e
				// get info from relation
				$res = $adb->pquery("select * from {$table_prefix}_accpotentialrel where potentialid = ? and accountid = ?", array($this->id, $cid));
				$relInfo = $adb->FetchByAssoc($res, -1, false);
				if ($accInfo->column_fields['accounttype'] == 'Competitor') {
					$comp[] = array(
						'accountid' => $cid,
						'accountname' => $accInfo->column_fields['accountname'],
						'email' => $accInfo->column_fields['email1'],
						'email_fieldid' => $email_fieldid,
						'phone' => $accInfo->column_fields['phone'],
						'main_account' => $relInfo['main_account'],
					);
				} else {
					$accounts[] = array(
						'accountid' => $cid,
						'accountname' => $accInfo->column_fields['accountname'],
						'email' => $accInfo->column_fields['email1'],
						'email_fieldid' => $email_fieldid,
						'phone' => $accInfo->column_fields['phone'],
						'partner_role' => $relInfo['partner_role'],
						'main_account' => $relInfo['main_account'],
					);
				}
			}
		}

		$smarty->assign('PARTNERS', $accounts);
		$smarty->assign('COMPETITORS', $comp);
		
		$activeQuotes = $this->getActiveQuotes();
		$smarty->assign('ACTIVEQUOTE_COUNT', count($activeQuotes));
		
		$lines = $this->getProdLinesInfo($activeQuotes[0]);
		$smarty->assign('PRODLINES', $lines);

		// charts
		if (vtlib_isModuleActive('Charts')) {
			$charts = $this->generateCharts();
			$smarty->assign('CHARTS', $charts);
		}
		
		//crmv@176621 removed crmv@149529 generateProcessGraph
		
		return $smarty->fetch('modules/Potentials/Panel.tpl');
	}

	function generateCharts() {
		global $current_user, $currentModule;

		require_once('modules/Potentials/PotentialsCharts.php');
		
		$charts = array();
		
		$oldCurrentModule = $currentModule;
		$currentModule = 'Charts';
		$chartInst = new PotentialsCharts($this);
		
		$chartInst->chart_title = getTranslatedString('AmountHistory', 'Potentials');
		$chartInst->column_fields['record_id'] = 0;
		$chartInst->column_fields['chartname'] = 'AmountHistory';
		$chartInst->column_fields['assigned_user_id'] = $current_user->id;
		$chartInst->column_fields['chart_type'] = 'Line';
		$chartInst->column_fields['chart_legend'] = 0;
		$chartInst->column_fields['chart_labels'] = 0;
		$chartInst->column_fields['chart_exploded'] = 0;
		$chartInst->column_fields['chart_values'] = 1;
		$chartInst->column_fields['chart_order_data'] = 0;
		$chartInst->column_fields['chart_merge_small'] = 0;
		$chartInst->column_fields[$chartInst->cachefield] = $chartInst->generateFileName();
		$charts[] = $chartInst->renderChart(false);
		
		if ($this->prodLineInfo) {
			$chartInst->chart_title = getTranslatedString('ProductLines', 'ProductLines');
			$chartInst->column_fields['record_id'] = 1;
			$chartInst->column_fields['chartname'] = 'ProductLines';
			$chartInst->column_fields['assigned_user_id'] = $current_user->id;
			$chartInst->column_fields['chart_type'] = 'Pie';
			$chartInst->column_fields['chart_legend'] = 0;
			$chartInst->column_fields['chart_labels'] = 1;
			$chartInst->column_fields['chart_exploded'] = 0;
			$chartInst->column_fields['chart_values'] = 1;
			$chartInst->column_fields['chart_order_data'] = 0;
			$chartInst->column_fields['chart_merge_small'] = 0;
			$chartInst->column_fields[$chartInst->cachefield] = $chartInst->generateFileName();
			// remove the fiel always
			$fname = $chartInst->generateFileName();
			@unlink($fname);
			$charts[] = $chartInst->renderChart(false);
		}
		
		$currentModule = $oldCurrentModule;
		return $charts;
	}

	/**
	 * Retrieve the list of active quotes (status = Created or Delivered)
	 * Return false if the Qutoes module is disabled or on error
	 */
	function getActiveQuotes() {
		global $adb, $table_prefix, $current_user;
		
		if (!vtlib_isModuleActive('Quotes')) return false;
		
		$queryGenerator = QueryGenerator::getInstance('Quotes', $current_user);
		$queryGenerator->addField('subject');
		$queryGenerator->appendToWhereClause(" AND {$table_prefix}_quotes.potentialid = '{$this->id}' AND {$table_prefix}_quotes.quotestage in ('Created', 'Delivered')");	//crmv@72942
		
		$query = $queryGenerator->getQuery();
		$query = replaceSelectQuery($query, "{$table_prefix}_crmentity.crmid");
		
		
		$r = $adb->query($query);
		if (!$r) return false;
		
		if ($adb->num_rows($r) == 0) return array();
		
		$result = array();
		while ($row = $adb->FetchByAssoc($r, -1, false)) {
			$result[] = $row['crmid'];
		}
		return $result;
	}
	// crmv@53923e

	function getPopupCreateModules($from_module, $from_crmid, $mode) {
		global $current_user;
		if ($from_module == 'Potentials' && $_REQUEST['show_module'] == 'Contacts' && $mode == 'pot_add_other_contacts' && isPermitted('Contacts', 'EditView')) {
			return array('Contacts');
		} else if ($from_module == 'Potentials' && $_REQUEST['show_module'] == 'Accounts' && ($mode == 'pot_add_partners' || $mode == 'pot_add_competitor') && isPermitted('Accounts', 'EditView')) {
			return array('Accounts');
		}

		return false;
	}

	function getPopupQCreateValues($mod, $relatedIds, $email, $name) {
		if ($mod == 'Accounts' && $_REQUEST['popup_mode'] == 'pot_add_competitor') {
			return array('accounttype'=>'Competitor');
		}
		return false;
	}

	//crmv@44187e

	/**
	 * Move the related records of the specified list of id's to the given record.
	 * @param String This module name
	 * @param Array List of Entity Id's from which related records need to be transfered
	 * @param Integer Id of the the Record to which the related records are to be moved
	 */
	function transferRelatedRecords($module, $transferEntityIds, $entityId) {
		global $adb,$log;
		global $table_prefix;
		$log->debug("Entering function transferRelatedRecords ($module, $transferEntityIds, $entityId)");

		$rel_table_arr = Array("Activities"=>$table_prefix."_seactivityrel","Contacts"=>$table_prefix."_contpotentialrel","Products"=>$table_prefix."_seproductsrel",
						"Attachments"=>$table_prefix."_seattachmentsrel","Quotes"=>$table_prefix."_quotes","SalesOrder"=>$table_prefix."_salesorder",
						"Documents"=>$table_prefix."_senotesrel");

		$tbl_field_arr = Array($table_prefix."_seactivityrel"=>"activityid",$table_prefix."_contpotentialrel"=>"contactid",$table_prefix."_seproductsrel"=>"productid",
						$table_prefix."_seattachmentsrel"=>"attachmentsid",$table_prefix."_quotes"=>"quoteid",$table_prefix."_salesorder"=>"salesorderid",
						$table_prefix."_senotesrel"=>"notesid");

		$entity_tbl_field_arr = Array($table_prefix."_seactivityrel"=>"crmid",$table_prefix."_contpotentialrel"=>"potentialid",$table_prefix."_seproductsrel"=>"crmid",
						$table_prefix."_seattachmentsrel"=>"crmid",$table_prefix."_quotes"=>"potentialid",$table_prefix."_salesorder"=>"potentialid",
						$table_prefix."_senotesrel"=>"crmid");

		foreach($transferEntityIds as $transferId) {
			foreach($rel_table_arr as $rel_module=>$rel_table) {
				$id_field = $tbl_field_arr[$rel_table];
				$entity_id_field = $entity_tbl_field_arr[$rel_table];
				// IN clause to avoid duplicate entries
				$sel_result =  $adb->pquery("select $id_field from $rel_table where $entity_id_field=? " .
						" and $id_field not in (select $id_field from $rel_table where $entity_id_field=?)",
						array($transferId,$entityId));
				$res_cnt = $adb->num_rows($sel_result);
				if($res_cnt > 0) {
					for($i=0;$i<$res_cnt;$i++) {
						$id_field_value = $adb->query_result($sel_result,$i,$id_field);
						$adb->pquery("update $rel_table set $entity_id_field=? where $entity_id_field=? and $id_field=?",
							array($entityId,$transferId,$id_field_value));
					}
				}
			}
		}
		$log->debug("Exiting transferRelatedRecords...");
	}

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	//crmv@38798
	function generateReportsSecQuery($module,$secmodule,$reporttype='',$useProductJoin=true,$joinUitype10=true){ // crmv@146653
		global $table_prefix;
		$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_potential","potentialid");
		$namecntpot = substr($table_prefix.'_contactdetailsPotentials',0,29); //crmv@fix oracle
		$query .= " left join ".$table_prefix."_account ".$table_prefix."_accountPotentials on ".$table_prefix."_potential.related_to = ".$table_prefix."_accountPotentials.accountid
		left join ".$table_prefix."_contactdetails $namecntpot on ".$table_prefix."_potential.related_to = $namecntpot.contactid
		left join ".$table_prefix."_potentialscf on ".$table_prefix."_potentialscf.potentialid = ".$table_prefix."_potential.potentialid
		left join ".$table_prefix."_groups ".$table_prefix."_groupsPotentials on ".$table_prefix."_groupsPotentials.groupid = ".$table_prefix."_crmentityPotentials.smownerid
		left join ".$table_prefix."_users ".$table_prefix."_usersPotentials on ".$table_prefix."_usersPotentials.id = ".$table_prefix."_crmentityPotentials.smownerid
		left join ".$table_prefix."_campaign ".$table_prefix."_campaignPotentials on ".$table_prefix."_potential.campaignid = ".$table_prefix."_campaignPotentials.campaignid";
		return $query;
	}
	//crmv@38798e

	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */
	function setRelationTables($secmodule){
		global $table_prefix;
		$rel_tables = array (
			"Calendar" => array($table_prefix."_seactivityrel"=>array("crmid","activityid"),$table_prefix."_potential"=>"potentialid"),
			"Products" => array($table_prefix."_seproductsrel"=>array("crmid","productid"),$table_prefix."_potential"=>"potentialid"),
			"Quotes" => array($table_prefix."_quotes"=>array("potentialid","quoteid"),$table_prefix."_potential"=>"potentialid"),
			"SalesOrder" => array($table_prefix."_salesorder"=>array("potentialid","salesorderid"),$table_prefix."_potential"=>"potentialid"),
			"Documents" => array($table_prefix."_senotesrel"=>array("crmid","notesid"),$table_prefix."_potential"=>"potentialid"),
			"Accounts" => array($table_prefix."_potential"=>array("potentialid","related_to")),
		);
		return $rel_tables[$secmodule];
	}

	// Function to unlink all the dependent entities of the given Entity by Id
	function unlinkDependencies($module, $id) {
		global $log;
		/*//Backup Activity-Potentials Relation
		$act_q = "select activityid from vte_seactivityrel where crmid = ?";
		$act_res = $this->db->pquery($act_q, array($id));
		if ($this->db->num_rows($act_res) > 0) {
			for($k=0;$k < $this->db->num_rows($act_res);$k++)
			{
				$act_id = $this->db->query_result($act_res,$k,"activityid");
				$params = array($id, RB_RECORD_DELETED, 'vte_seactivityrel', 'crmid', 'activityid', $act_id);
				$this->db->pquery("insert into vte_relatedlists_rb values (?,?,?,?,?,?)", $params);
			}
		}
		$sql = 'delete from vte_seactivityrel where crmid = ?';
		$this->db->pquery($sql, array($id));*/

		parent::unlinkDependencies($module, $id);
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log;
		global $table_prefix;
		if(empty($return_module) || empty($return_id)) return;

		if($return_module == 'Accounts') {
			$this->trash($this->module_name, $id);
		} elseif($return_module == 'Campaigns') {
			$sql = 'UPDATE '.$table_prefix.'_potential SET campaignid = 0 WHERE potentialid = ?';
			$this->db->pquery($sql, array($id));
		} elseif($return_module == 'Products') {
			$sql = 'DELETE FROM '.$table_prefix.'_seproductsrel WHERE crmid=? AND productid=?';
			$this->db->pquery($sql, array($id, $return_id));
		} elseif($return_module == 'Contacts') {
			$sql = 'DELETE FROM '.$table_prefix.'_contpotentialrel WHERE potentialid=? AND contactid=?';
			$this->db->pquery($sql, array($id, $return_id));

			// Potential directly linked with Contact (not through Account - vte_contpotentialrel)
			$directRelCheck = $this->db->pquery('SELECT related_to FROM '.$table_prefix.'_potential WHERE potentialid=? AND related_to=?', array($id, $return_id));
			if($this->db->num_rows($directRelCheck)) {
				$this->trash($this->module_name, $id);
			}

		} else {
			$sql = 'DELETE FROM '.$table_prefix.'_crmentityrel WHERE (crmid=? AND relmodule=? AND relcrmid=?) OR (relcrmid=? AND module=? AND crmid=?)';
			$params = array($id, $return_module, $return_id, $id, $return_module, $return_id);
			$this->db->pquery($sql, $params);
		}
		$this->db->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), $id, $return_id)); // crmv@49398 crmv@69690
	}

	//crmv@37004
	function getMessagePopupFields($module) {
		$namefields = array(
			'potential_no',
			'potentialname',
			'related_to',
			'sales_stage',
			'assigned_user_id',
		);
		return $namefields;
	}

	function getMessagePopupLimitedCond(&$queryGenerator, $module, $relatedIds = array(), $searchstr = '') {
		global $adb, $table_prefix;
		$queryGenerator->addCondition('sales_stage', 'Closed Lost', 'n');
		$queryGenerator->addConditionGlue($queryGenerator::$AND);
		$queryGenerator->addCondition('sales_stage', 'Closed Won', 'n');
	}
	//crmv@37004e
}
?>
