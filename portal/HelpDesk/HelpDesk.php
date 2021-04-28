<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@173271 */


class HelpDeskModule extends PortalModule {

	public $hasComments = true;
	public $hasAttachments = true;

	public $hasListStatusFilter = true;
	public $hasListSearch = true;
	
	public $display_columns = 1;
	
	public $list_function = 'get_tickets_list';
	
	protected $statusCache = array();
	
	public function __construct($module) {
		parent::__construct($module);
	}
	
	public function hasListOwnerFilter() {
		return true; // always visible
	}
	
	public function canCreateRecord() {
		return true;
	}
	
	public function getStatusFilterValues() {
		return array(
			"" => "",
			"Open" => getTranslatedString('LBL_STATUS_TICKET_OPEN'),
			"Closed" => getTranslatedString('LBL_STATUS_TICKET_CLOSE'),
		);
	}
	
	public function getCreationFields() {
		$struct = parent::getCreationFields();
		
		// only 2 fields are shown
		$show_only = array('ticket_title', 'description', /* 'ticketpriorities', 'ticketseverities', 'ticketcategories' */);
		$changeLabels = array(
			'title' => 'TICKET_TITLE',
			'description' => 'LBL_DESCRIPTION',
			'ticketpriorities' => 'LBL_TICKET_PRIORITY',
			'ticketseverities' => 'LBL_TICKET_SEVERITY',
			'ticketcategories' => 'LBL_TICKET_CATEGORY',
		);

		// show only chosen fields
		$struct = array_values(array_filter($struct, function($field) use ($show_only) {
			return in_array($field['name'], $show_only);
		}));
		
		// kept for compatibility, remove it if you can!
		$combos = $this->getComboValues() ?: array();

		// alter labels and combo values
		foreach ($struct as &$field) {
			$name = $field['name'];
			
			// change the title uitype to display as single line
			if ($name == 'ticket_title') {
				$field['uitype'] = 1;
				$field['type']['name'] = 'string';
			}
			
			if (array_key_exists($name, $changeLabels)) {
				$field['label'] = getTranslatedString($changeLabels[$name]);
			}
			// alter values
			if (array_key_exists($name, $combos) && is_array($combos[$name][$name])) {
				$values = array();
				foreach ($combos[$name][$name] as $i => $label) {
					$values[] = array(
						'value' => $combos[$name][$name.'_keys'][$i],
						'label' => $label,
					);
				}
				$field['type']['picklistValues'] = $values;
			}
		}
		
		return $struct;
	}

	// crmv@191009
	protected function prepareList() {
		$smarty = parent::prepareList();

		$status_array = getPicklist('ticketstatus');
		$smarty->assign('SEARCH_TICKETSTATUS', getComboList('search_ticketstatus', $status_array, ' '));
	
		$priority_array = getPicklist('ticketpriorities');
		$smarty->assign('SEARCH_TICKETPRIORITY', getComboList('search_ticketpriority', $priority_array, ' '));
	
		$category_array = getPicklist('ticketcategories');
		$smarty->assign('SEARCH_TICKETCATEGORY', getComboList('search_ticketcategory', $category_array, ' '));

		return $smarty;
	}
	// crmv@191009e
	
	protected function callListFunction() {
		global $client, $Server_Path;
		
		$username = $_SESSION['customer_name'];
		$customerid = $_SESSION['customer_id'];
		$sessionid = $_SESSION['customer_sessionid'];
		
		$onlymine = $_REQUEST['onlymine'];
		
		if ($this->hasListSearch()) {
			// This is an archaic parameter list
			$match_condition = (isset($_REQUEST['search_match']))?$_REQUEST['search_match']:'';
			$where = getTicketSearchQuery();
		} else {
			$match_condition = '';
			$where = '';
		}
		
		$params = Array('id'=>$customerid,'block'=>$this->moduleName,'sessionid'=>$sessionid,'onlymine'=>$onlymine);
		$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'user_name' => "$username", 'onlymine' => $onlymine, 'where' => "$where", 'match' => "$match_condition"));	
		$result = $client->call($this->list_function, $params, $Server_Path, $Server_Path);
		
		return $result;
	}
	
	protected function processListResult($result) {
		// TODO merge this shit with the standard function getblock_fieldlistview
		
		$showstatus = $_REQUEST['showstatus'];
		
		$listData = null;
		
		if (!empty($result)) {

			$header = $result[0]['head'][0];
			$nooffields = count($header);
			$data = $result[1]['data'];
			
			$header_arr = array();
			for($i=0; $i<$nooffields; $i++)
			{
				$header_value = $header[$i]['fielddata'];
				$header_arr[] = $header_value;
			}

			$entries_arr = array();
			$links_arr = array();
			for($i=0;$i<count($data);$i++)
			{
				$row = array();
				$ticket_link = '';
				$ticket_status = '';
				
				for($j=0; $j<$nooffields; $j++) {
					$fielddata = $data[$i][$j]['fielddata'];
					if ($header[$j]['fielddata'] == 'Status') {
						$ticket_status = $fielddata;
					}
					if ($header[$j]['fielddata'] == 'Subject') {
						preg_match('/<a href="(.+)">/', $fielddata, $match);
						$ticket_link = $match[1];
					}
					$fielddata = strip_tags($fielddata);
					$row[] = $fielddata;
				}
				
				// TODO: filter server side!
				if ($showstatus != '') {
					if ($ticket_status == $showstatus) {
						$entries_arr[] = $row;
						$links_arr[] = $ticket_link;
					}
				} else {
					$entries_arr[] = $row;
					$links_arr[] = $ticket_link;
				}
			}
			$listData = array('HEADER' => $header_arr, 'ENTRIES'=>$entries_arr, 'LINKS'=>$links_arr);
		}
		return $listData;
	}
	
	protected function preprocessDetailFields($id, $fields) {
		// save the status
		$ticket_status = $fields['ticketstatus']['fieldvalue'];
		$this->statusCache[$id] = $ticket_status;
		
		return $fields;
	}
	
	protected function prepareDetail($id) {
		$smarty = parent::prepareDetail($id);
		
		$smarty->assign('TICKETID', $id);
		
		//$fields = $smarty->get_template_vars('FIELDLIST');
		
		$smarty->assign('PERMISSION', array('perm_read' => 'true', 'perm_write' => false, 'perm_delete'=> false));
		
		$ticket_status = $this->statusCache[$id];
		$isClosed = ($ticket_status == 'Closed');
		
		$smarty->assign('SHOW_SOLVED_BUTTON', !$isClosed);
		$smarty->assign('SHOW_SOLVED_BANNER', $isClosed);
		
		// comments
		if ($this->hasComments()) {
			$comments = $this->getComments($id);
			if (is_array($comments) && count($comments) > 0) {
				$smarty->assign('BADGE',count($comments));
				$smarty->assign('COMMENTS', $comments);
			}
			
			$smarty->assign('CAN_ADD_COMMENTS',!$isClosed);
			$smarty->assign('CAN_SEE_COMMENTS',true);
		}
		
		// attachments
		if ($this->hasAttachments()) {
			global $upload_status; // TODO: get rid of this
			
			$files_array = getTicketAttachmentsList($id);
			$smarty->assign('FILES',$files_array);
			
			$canSee = ($files_array[0] != "#MODULE INACTIVE#");
			$smarty->assign('VIEW_ATTACHMENTS',$canSee);
			
			$canUpload = (!$isClosed && $files_array[0] != "#MODULE INACTIVE#");
			$smarty->assign('UPLOAD_ATTACHMENTS',$canUpload);
			
			//To display the file upload error
			$smarty->assign('UPLOADSTATUS',$upload_status);
		}
		
		return $smarty;
	}
	
	protected function prepareCreate() {
		
		$smarty = parent::prepareCreate();
		
		// add products (not used anyway...)
		$result = $this->getComboValues() ?: array();
		for ($i=0; $i<count($result); $i++) {
			if($result[$i]['productid'] != '') {
				$productslist[0] = $result[$i]['productid'];
			}
			if($result[$i]['productname'] != '') {
				$productslist[1] = $result[$i]['productname'];
			}
		}

		$productarray = '';
		if($productslist[0] != '#MODULE INACTIVE#'){
			if(is_array($productslist[0])){ // crmv@167234
				$noofrows = count($productslist[0]);	
				for($i=0;$i<$noofrows;$i++) {
					if($i > 0)
						$productarray .= ',';
					$productarray .= "'".$productslist[1][$i]."'";
				}
			} // crmv@167234
		}

		$smarty->assign('PRODUCTARRAY',$productarray);
		$smarty->assign('PROJECTID',$_REQUEST['projectid']); // TODO: BAD BAD BAD!!
		
		$smarty->assign('CREATE_TITLE', getTranslatedString('LBL_NEW_TICKET'));
		
		return $smarty;
	}
	
	public function getComments($id) {
		global $client, $Server_Path;
		
		$customerid = $_SESSION['customer_id'];
		$sessionid = $_SESSION['customer_sessionid'];
		
		$return = null;
		
		// crmv@160733
		$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'ticketid' => "$id"));
		$commentresult = $client->call('get_ticket_comments', $params, $Server_Path, $Server_Path);
		$commentscount = is_array($commentresult) ? count($commentresult) : 0;
		
		if(is_array($commentresult) && $commentscount > 0) {
			// add number to comments
			array_walk($commentresult, function(&$comm, $idx) use ($commentscount) {
				$comm['num'] = $commentscount-$idx;
				// and check for the confidential info string
				if (in_array($comm['conf_status'], array(1,2))) {
					// request
					$morecomment = str_replace(array("\n", "'"), array("\\n", "\\'"), htmlentities($comm['conf_data_plain'], ENT_COMPAT, 'UTF-8'));
					$link = "<b><a href=\"javascript:VTEPortal.HelpDesk.ConfidentialInfo.askData('HelpDesk', '$id', '{$comm['commentid']}', '{$comm['conf_status']}', '$morecomment');\">".getTranslatedString('LBL_HERE')."</a></b>";
					$comm['comments'] = str_replace('{HERELINK}', $link, $comm['comments']);
				} elseif ($comm['conf_status'] == 3) {
					// answer, remove the last line (supposedly, the link line)
					if (strchr($comm['comments'], "\n") !== false) {
						$comm['comments'] = preg_replace("/\n.+?$/", getTranslatedString('LBL_CONFIDENTIAL_INFO_REPLY_TEXT'), $comm['comments']);
					} else {
						$comm['comments'] = getTranslatedString('LBL_CONFIDENTIAL_INFO_REPLY_TEXT');
					}
				}
			});
			$return = $commentresult;
		}
		// crmv@160733e
		
		return $return;
	}
	
	public function createRecord($request) {
		global $client;
		
		// TODO: check permission
		
		$customerid = $_SESSION['customer_id'];
		$sessionid = $_SESSION['customer_sessionid'];

		// param name => request name
		$ticket = Array(
			'title' => 'ticket_title',
			'description' => 'description',
			'priority' => 'ticketpriorities',
			'severity' => 'ticketseverities',
			'category' => 'ticketcategories',
			'serviceid' => 'serviceid', // not used
			'projectid' => 'projectid', // not used
		);
		
		// prepare input object
		foreach($ticket as $key => $reqkey) {
			$ticket[$key] = $request[$reqkey];
		}
		
		$ticket['parent_id'] = $customerid;
		
		// add special fields (not needed)
		$combos = $this->getComboValues();
		$ticket['product_id'] = $combos[0]['productid'][$request['productid']];
		
		
		$params = Array(array_replace($ticket, Array(
			'id'=>"$customerid",
			'sessionid'=>"$sessionid",
		)));

		$record_result = $client->call('create_ticket', $params);

		/*crmv@57342*/
		if(isset($record_result[0]['new_ticket']) && $record_result[0]['new_ticket']['ticketid'] != '') {
			$ticketid = $record_result[0]['new_ticket']['ticketid'];
			if ($this->hasAttachments()) {
				$upload_status = AddAttachment($ticketid); // TODO: in the class!
			}
			return $ticketid;
		}
		/*crmv@57342e*/
		
		return false;
	}
	
	protected function getComboValues() {
		global $client, $Server_Path;
		
		if (empty($_SESSION['combolist'])) {
			$customerid = $_SESSION['customer_id'];
			$sessionid = $_SESSION['customer_sessionid'];
		
			$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'language'=>getPortalCurrentLanguage()));	//crmv@55264
			$result = $client->call('get_combo_values', $params, $Server_Path, $Server_Path) ?: array();
		
			$_SESSION['combolist'] = $result;
		}
		
		return $_SESSION['combolist'];
	}
	
}