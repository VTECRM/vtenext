<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@168297

require_once('soap/SOAPWebservicesBase.php');

class SOAPWebservices extends SOAPWebservicesBase {

	// standard soap ws, used by vte.
	// these are needed only during install, in order to save them in the db
	static protected $vteWS = array(
		array(
			'name' => 'authenticate_user',
			'params' => array('fieldname'=>'tns:common_array'),
			'return'=>'tns:common_array',
		),
		array(
			'name' => 'change_password',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
			),
		array(
			'name' => 'create_ticket',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
			),
		//for a particular contact ticket list
		array(
			'name' => 'get_tickets_list',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
			),
		array(
			'name' => 'get_ticket_comments',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
			),
		array(
			'name' => 'get_combo_values',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
			),
		array(
			'name' => 'get_KBase_details',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' =>'tns:common_array1',
			),
		array(
			'name' => 'save_faq_comment',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
			),
		array(
			'name' => 'update_ticket_comment',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
			),
		// crmv@160733
		array(
			'name' => 'provide_confidential_info',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
			),
		// crmv@160733e
		array(
			'name' => 'close_current_ticket',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'xsd:string',
			),
		array(
			'name' => 'update_login_details',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'xsd:string',
			),
		array(
			'name' => 'send_mail_for_password',
			'params' => array('email'=>'xsd:string'),
			'return' => 'xsd:string',
			),
		array(
			'name' => 'get_ticket_creator',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'xsd:string',
			),
		array(
			'name' => 'get_picklists',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
			),
		array(
			'name' => 'get_ticket_attachments',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
			),
		array(
			'name' => 'get_filecontent',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
			),
		array(
			'name' => 'add_ticket_attachment',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
			),
		array(
			'name' => 'get_cf_field_details',
			'params' => array('id'=>'xsd:string','contactid'=>'xsd:string','sessionid'=>'xsd:string'),
			'return' =>'tns:field_details_array',
			),
		array(
			'name' => 'get_check_account_id',
			'params' => array('id'=>'xsd:string'),
			'return' => 'xsd:string',
			),
		//to get details of quotes,invoices and documents
		array(
			'name' => 'get_details',
			'params' => array('id'=>'xsd:string','block'=>'xsd:string','contactid'=>'xsd:string','sessionid'=>'xsd:string','language'=>'xsd:string'),
			'return' => 'tns:field_details_array',
			),
		//to get the products list for the entire account of a contact
		array(
			'name' => 'get_product_list_values',
			'params' => array('id'=>'xsd:string','block'=>'xsd:string','sessionid'=>'xsd:string','only_mine'=>'xsd:string'),
			'return' => 'tns:field_details_array',
			),
		array(
			'name' => 'get_list_values',
			'params' => array('id'=>'xsd:string','block'=>'xsd:string','sessionid'=>'xsd:string','only_mine'=>'xsd:string'),
			'return' =>'tns:field_datalist_array',
			),
		array(
			'name' => 'get_product_urllist',
			'params' => array('customerid'=>'xsd:string','productid'=>'xsd:string','block'=>'xsd:string'),
			'return' =>'tns:field_datalist_array',
			),
		array(
			'name' => 'get_filecontent_detail',
			'params' => array('id'=>'xsd:string','folderid'=>'xsd:string','block'=>'xsd:string','contactid'=>'xsd:string','sessionid'=>'xsd:string'),
			'return' =>'tns:get_ticket_attachments_array',
			),
		array(
			'name' => 'get_invoice_detail',
			'params' => array('id'=>'xsd:string','block'=>'xsd:string','contactid'=>'xsd:string','sessionid'=>'xsd:string'),
			'return' => 'tns:field_details_array',
			),
		array(
			'name' => 'get_modules',
			'params' => array(),
			'return' => 'tns:field_details_array',
			),
		array(
			'name' => 'show_all',
			'params' => array('module'=>'xsd:string'),
			'return' => 'xsd:string',
			),
		array(
			'name' => 'get_documents',
			'params' => array('id'=>'xsd:string','module'=>'xsd:string','customerid'=>'xsd:string','sessionid'=> 'xsd:string'),
			'return' => 'tns:field_details_array',
			),
		array(
			'name' => 'updateCount',
			'params' => array('id'=>'xsd:string'),
			'return' => 'xsd:string',
			),
		//to get the Services list for the entire account of a contact
		array(
			'name' => 'get_service_list_values',
			'params' => array('id'=>'xsd:string','module'=>'xsd:string','sessionid'=>'xsd:string','only_mine'=>'xsd:string'),
			'return' => 'tns:field_details_array',
			),
		//to get the Project Tasks for a given Project
		array(
			'name' => 'get_project_components',
			'params' => array('id'=>'xsd:string','module'=>'xsd:string','customerid'=>'xsd:string','sessionid'=>'xsd:string'),
			'return' => 'tns:field_details_array',
			),
		//to get the Project Tickets for a given Project
		array(
			'name' => 'get_project_tickets',
			'params' => array('id'=>'xsd:string','module'=>'xsd:string','customerid'=>'xsd:string','sessionid'=>'xsd:string'),
			'return' => 'tns:field_details_array',
			),
		// crmv@5946 crmv@80441
		array(
			'name' => 'authenticate_user_cookie',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
			),
		array(
			'name' => 'unsubscribe_contact',
			'params' => array('id'=>'xsd:string','block'=>'xsd:string','contactid'=>'xsd:string','sessionid'=>'xsd:string','language'=>'xsd:string'),
			'return' => 'xsd:string',
			),
		array(
			'name' => 'picklist_tickets',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
		),
		array(
			'name' => 'create_potentials',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
		),
		array(
			'name' => 'add_attachment',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
		),
		array(
			'name' => 'get_slo_picklist',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
		),
		array(
			'name' => 'get_potential_attachments',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
		),
		array(
			'name' => 'save_contact_profile',
			'params' => array('contactid'=>'xsd:string','sessionid'=>'xsd:string','fieldnames'=>'tns:common_array','values'=>'tns:common_array'),
			'return' => 'tns:field_details_array',
			),
		array(
			'name' => 'update_ticket',
			'params' => array('fieldname'=>'tns:common_array'),
			'return' => 'tns:common_array',
			),
		// crmv@90004
		array(
			'name' => 'get_folder',
			'params' => array('id'=>'xsd:string','block'=>'xsd:string','sessionid'=>'xsd:string','only_mine'=>'xsd:string','check_folder'=>'xsd:string'),
			'return' =>'tns:field_datalist_array',
		),
		// crmv@90004e
		// crmv@173271
		array(
			'name' => 'get_fields_structure',
			'params' => array('customerid'=>'xsd:string','module'=>'xsd:string','id'=>'xsd:string', 'language'=>'xsd:string'),
			'return' => 'tns:common_array',
		),
		array(
			'name' => 'get_conditionals',
			'params' => array('customerid'=>'xsd:string','module'=>'xsd:string'),
			'return' => 'tns:common_array',
		),
		array(
			'name' => 'get_modules_permissions',
			'params' => array('customerid'=>'xsd:string'),
			'return' => 'tns:common_array',
		),
		array(
			'name' => 'is_edit_permitted',
			'params' => array('customerid'=>'xsd:string','module'=>'xsd:string','id'=>'xsd:string'),
			'return' => 'xsd:string',
		),
		array(
			'name' => 'is_delete_permitted',
			'params' => array('customerid'=>'xsd:string','module'=>'xsd:string','id'=>'xsd:string'),
			'return' => 'xsd:string',
		),
		array(
			'name' => 'update_record',
			'params' => array('customerid'=>'xsd:string','module'=>'xsd:string','id'=>'xsd:string', 'fields' => 'tns:common_array', 'files' => 'tns:common_array'),
			'return' => 'tns:common_array',
		),
		array(
			'name' => 'delete_record',
			'params' => array('customerid'=>'xsd:string','module'=>'xsd:string','id'=>'xsd:string'),
			'return' => 'tns:common_array',
		),
		// crmv@173271e
	);
	
	static public function installWS() {
		$WSMan = SOAPWSManager::getInstance();
		foreach (self::$vteWS as $ws) {
			// convert parameters
			array_walk($ws['params'], function(&$v, $k) {
				$v = array('name'=>$k, 'type'=>$v );
			});
			$WSMan->addWebservice($ws['name'], 'soap/SOAPWebservices.php', 'SOAPWebservices', $ws['return'], $ws['params']);
		}
	}

	function save_contact_profile($contactid, $sessionid, $fieldnames, $values) {
		global $current_user;
		
		$check = $this->checkModuleActive('Contacts');
		if($check == false) {
			return false;
		}

		if(!$this->validateSession($contactid,$sessionid))
			return null;
		
		$focus = CRMEntity::getInstance('Contacts');
		$focus->id = $contactid;
		$focus->retrieve_entity_info($contactid, 'Contacts');
		$focus->column_fields = array_map('decode_html', $focus->column_fields);
		$focus->mode = 'edit';
		foreach ($fieldnames as $i => $fieldname) {
			$focus->column_fields[$fieldname] = $values[$i];
		}
		
		$userid = $this->getPortalUserid();
		$user = CRMEntity::getInstance('Users');
		$current_user = $user->retrieveCurrentUserInfoFromFile($userid);
		
		$focus->save('Contacts');
	}
	//crmv@5946e

	/**	function used to get the list of ticket comments
	* @param array $input_array - array which contains the following parameters
	* int $id - customer id
	* string $sessionid - session id
	* int $ticketid - ticket id
	* @return array $response - ticket comments and details as a array with elements comments, owner and createdtime which will be returned from the function get_ticket_comments_list
	*/
	function get_ticket_comments($input_array)
	{
		global $adb,$log,$current_user;
		$adb->println("Entering customer portal function get_ticket_comments");
		$adb->println($input_array);

		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		$ticketid = (int) $input_array['ticketid'];
		
		if(!$this->validateSession($id,$sessionid))
			return null;
		
		$userid = $this->getPortalUserid();
		$user = CRMEntity::getInstance('Users');
		$current_user = $user->retrieveCurrentUserInfoFromFile($userid);
		if(getFieldVisibilityPermission('HelpDesk', $userid, 'comments') == '1'){
			return null;
		}

		$seed_ticket = CRMEntity::getInstance('HelpDesk');
		$response = $seed_ticket->get_ticket_comments_list($ticketid);
		return $response;
	}

	/**	function used to get the combo values ie., picklist values of the HelpDesk module and also the list of products
	*	@param array $input_array - array which contains the following parameters
	=>	int $id - customer id
		string $sessionid - session id
		*	return array $output - array which contains the product id, product name, ticketpriorities, ticketseverities, ticketcategories and module owners list
		*/
	function get_combo_values($input_array)
	{
		global $log,$adb,$table_prefix,$current_language;	//crmv@55264
		$adb->println("Entering customer portal function get_combo_values");
		$adb->println($input_array);

		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		$current_language = $input_array['language'];	//crmv@55264

		if(!$this->validateSession($id,$sessionid))
			return null;
		//crmv@15507 add security
		$customerid = $id;
		$contactquery = "SELECT contactid, accountid FROM ".$table_prefix."_contactdetails " .
					" INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_contactdetails.contactid" .
					" AND ".$table_prefix."_crmentity.deleted = 0 " .
					" WHERE (accountid = (SELECT accountid FROM ".$table_prefix."_contactdetails WHERE contactid = ?) AND accountid != 0) OR contactid = ?";
		$contactres = $adb->pquery($contactquery, array($customerid,$customerid));
		$no_of_cont = $adb->num_rows($contactres);
		for($i=0;$i<$no_of_cont;$i++){
			$cont_id = $adb->query_result($contactres,$i,'contactid');
			$acc_id = $adb->query_result($contactres,$i,'accountid');
			if(!in_array($cont_id, $allowed_contacts_and_accounts))
			$allowed_contacts_and_accounts[] = $cont_id;
			if(!in_array($acc_id, $allowed_contacts_and_accounts) && $acc_id != '0')
			$allowed_contacts_and_accounts[] = $acc_id;
		}
		//crmv@15507 end
		$output = Array();
		//crmv@15507
		$sql = "select  ".$table_prefix."_products.productid, ".$table_prefix."_products.productname from ".$table_prefix."_products inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_products.productid ";
		$sql.="	INNER JOIN ".$table_prefix."_seproductsrel ON ".$table_prefix."_seproductsrel.productid=".$table_prefix."_products.productid and (".$table_prefix."_seproductsrel.setype='Contacts' or ".$table_prefix."_seproductsrel.setype='Accounts') ";
		$sql.=" and ".$table_prefix."_seproductsrel.crmid in (". generateQuestionMarks($allowed_contacts_and_accounts) .")";
		$sql.="where ".$table_prefix."_crmentity.deleted=0";
		$result = $adb->pquery($sql,$allowed_contacts_and_accounts);
		//crmv@15507 end
		$noofrows = $adb->num_rows($result);
		for($i=0;$i<$noofrows;$i++)
		{
			$check = $this->checkModuleActive('Products');
			if($check == false){
				$output['productid']['productid']="#MODULE INACTIVE#";
				$output['productname']['productname']="#MODULE INACTIVE#";
				break;
			}
			$output['productid']['productid'][$i] = $adb->query_result($result,$i,"productid");
			$output['productname']['productname'][$i] = decode_html($adb->query_result($result,$i,"productname"));
		}

		$userid = $this->getPortalUserid();

		//We are going to display the picklist entries associated with admin user (role is H2)
		$roleres = $adb->pquery("SELECT roleid from ".$table_prefix."_user2role where userid = ?",array($userid));
		$RowCount = $adb->num_rows($roleres);
		if($RowCount > 0){
			$admin_role = $adb->query_result($roleres,0,'roleid');
		}
		
		//crmv@79019 crmv@104022
		if (getFieldVisibilityPermission('HelpDesk', $userid, 'ticketpriorities') == 0) {
			$values_arr = getAssignedPicklistValues('ticketpriorities', $admin_role, $adb,'HelpDesk'); // crmv@166974
			foreach ($values_arr as $pickListValue=>$translated_value){
				$output['ticketpriorities']['ticketpriorities_keys'][] = $pickListValue;
				$output['ticketpriorities']['ticketpriorities'][] = $translated_value;
			}
		}
	
		if (getFieldVisibilityPermission('HelpDesk', $userid, 'ticketseverities') == 0) {
			$values_arr = getAssignedPicklistValues('ticketseverities', $admin_role, $adb,'HelpDesk'); // crmv@166974
			foreach ($values_arr as $pickListValue=>$translated_value){
				$output['ticketseverities']['ticketseverities_keys'][] = $pickListValue;
				$output['ticketseverities']['ticketseverities'][] = $translated_value;
			}
		}
	
		if (getFieldVisibilityPermission('HelpDesk', $userid, 'ticketcategories') == 0) {
			$values_arr = getAssignedPicklistValues('ticketcategories', $admin_role, $adb,'HelpDesk'); // crmv@166974
			foreach ($values_arr as $pickListValue=>$translated_value){
				$output['ticketcategories']['ticketcategories_keys'][] = $pickListValue;
				$output['ticketcategories']['ticketcategories'][] = $translated_value;
			}
		}
		//crmv@79019e crmv@104022e

		// Gather service contract information
		if(!vtlib_isModuleActive('ServiceContracts')) {
			$output['serviceid']['serviceid']="#MODULE INACTIVE#";
			$output['servicename']['servicename']="#MODULE INACTIVE#";
		} else {
			$servicequery = "SELECT ".$table_prefix."_servicecontracts.servicecontractsid,".$table_prefix."_servicecontracts.subject from ".$table_prefix."_servicecontracts inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_servicecontracts.servicecontractsid and ".$table_prefix."_crmentity.deleted = 0";
			//crmv@15507
			$servicequery.=" and ".$table_prefix."_servicecontracts.sc_related_to in (". generateQuestionMarks($allowed_contacts_and_accounts) .")";
			$serviceResult = $adb->pquery($servicequery,$allowed_contacts_and_accounts);
			//crmv@15507 end

			for($i=0;$i < $adb->num_rows($serviceResult);$i++){
				$serviceid = $adb->query_result($serviceResult,$i,'servicecontractsid');
				$output['serviceid']['serviceid'][$i] = $serviceid;
				$output['servicename']['servicename'][$i] = $adb->query_result($serviceResult,$i,'subject');
			}
		}

		return $output;

	}

	/**	function to get the Knowledge base details
	*	@param array $input_array - array which contains the following parameters
	=>	int $id - customer id
		string $sessionid - session id
		*	return array $result - array which contains the faqcategory, all product ids , product names and all faq details
		*/
	function get_KBase_details($input_array)
	{
		global $adb,$log,$table_prefix;
		$adb->println("Entering customer portal function get_KBase_details");
		$adb->println($input_array);

		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];

		if(!$this->validateSession($id,$sessionid))
			return null;

		$userid = $this->getPortalUserid();
		$result['faqcategory'] = array();
		$result['product'] = array();
		$result['faq'] = array();

		//We are going to display the picklist entries associated with admin user (role is H2)
		$roleres = $adb->pquery("SELECT roleid from ".$table_prefix."_user2role where userid = ?",array($userid));
		$RowCount = $adb->num_rows($roleres);
		if($RowCount > 0){
			$admin_role = $adb->query_result($roleres,0,'roleid');
		}
		$category_query = "select ".$table_prefix."_faqcategories.faqcategories from ".$table_prefix."_faqcategories inner join ".$table_prefix."_role2picklist on ".$table_prefix."_role2picklist.picklistvalueid = ".$table_prefix."_faqcategories.picklist_valueid and ".$table_prefix."_role2picklist.roleid='$admin_role'";
		$category_result = $adb->pquery($category_query, array());
		$category_noofrows = $adb->num_rows($category_result);
		for($j=0;$j<$category_noofrows;$j++)
		{
			$faqcategory = $adb->query_result($category_result,$j,'faqcategories');
			$result['faqcategory'][$j] = $faqcategory;
		}

		$check = $this->checkModuleActive('Products');

		if($check == true) {
			$product_query = "select productid, productname from ".$table_prefix."_products inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_products.productid where ".$table_prefix."_crmentity.deleted=0";
			$product_result = $adb->pquery($product_query, array());
			$product_noofrows = $adb->num_rows($product_result);
			for($i=0;$i<$product_noofrows;$i++)
			{
				$productid = $adb->query_result($product_result,$i,'productid');
				$productname = $adb->query_result($product_result,$i,'productname');
				$result['product'][$i]['productid'] = $productid;
				$result['product'][$i]['productname'] = $productname;
			}
		}
		$faq_query = "select ".$table_prefix."_faq.*, ".$table_prefix."_crmentity.createdtime, ".$table_prefix."_crmentity.modifiedtime from ".$table_prefix."_faq " .
			"inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_faq.id " .
			"where ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_faq.status='Published' order by ".$table_prefix."_crmentity.modifiedtime DESC";
		$faq_result = $adb->pquery($faq_query, array());
		$faq_noofrows = $adb->num_rows($faq_result);
		for($k=0;$k<$faq_noofrows;$k++)
		{
			$faqid = $adb->query_result($faq_result,$k,'id');
			$moduleid = $adb->query_result($faq_result,$k,'faq_no');
			$result['faq'][$k]['faqno'] = $moduleid;
			$result['faq'][$k]['id'] = $faqid;
			if($check == true) {
				$result['faq'][$k]['product_id']  = $adb->query_result($faq_result,$k,'product_id');
			}
			$result['faq'][$k]['question'] =  nl2br($adb->query_result($faq_result,$k,'question'));
			$result['faq'][$k]['answer'] = nl2br($adb->query_result($faq_result,$k,'answer'));
			$result['faq'][$k]['category'] = $adb->query_result($faq_result,$k,'category');
			$result['faq'][$k]['faqcreatedtime'] = $adb->query_result($faq_result,$k,'createdtime');
			$result['faq'][$k]['faqmodifiedtime'] = $adb->query_result($faq_result,$k,'modifiedtime');

			$faq_comment_query = "select * from ".$table_prefix."_faqcomments where faqid=? order by createdtime DESC";
			$faq_comment_result = $adb->pquery($faq_comment_query, array($faqid));
			$faq_comment_noofrows = $adb->num_rows($faq_comment_result);
			for($l=0;$l<$faq_comment_noofrows;$l++)
			{
				$faqcomments = nl2br($adb->query_result($faq_comment_result,$l,'comments'));
				$faqcreatedtime = $adb->query_result($faq_comment_result,$l,'createdtime');
				if($faqcomments != '')
				{
					$result['faq'][$k]['comments'][$l] = $faqcomments;
					$result['faq'][$k]['createdtime'][$l] = $faqcreatedtime;
				}
			}
		}
		$adb->println($result);
		return $result;
	}

	/**	function to save the faq comment
	*	@param array $input_array - array which contains the following values
	=> 	int $id - Customer ie., Contact id
		int $sessionid - session id
		int $faqid - faq id
		string $comment - comment to be added with the FAQ
		*	return array $result - This function will call get_KBase_details and return that array
		*/
	function save_faq_comment($input_array)
	{
		global $adb,$table_prefix;
		$adb->println("Entering customer portal function save_faq_comment");
		$adb->println($input_array);

		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		$faqid = (int) $input_array['faqid'];
		$comment = $input_array['comment'];

		if(!$this->validateSession($id,$sessionid))
			return null;

		$createdtime = $adb->formatDate(date('Y-m-d H:i:s'),true); //crmv@69690
		if(trim($comment) != '')
		{
			//crmv@18048
			$commentid = $adb->getUniqueID($table_prefix.'_faqcomments');
			$faq_query = "insert into ".$table_prefix."_faqcomments values(?,?,?,?)";
			$adb->pquery($faq_query, array($commentid, $faqid, $comment, $createdtime));
			//crmv@18048 end
		}

		$params = Array('id'=>"$id", 'sessionid'=>"$sessionid");
		$result = get_KBase_details($input_array);

		return $result;
	}

	/** function to get a list of tickets and to search tickets
	* @param array $input_array - array which contains the following values
	=> 	int $id - Customer ie., Contact id
		int $only_mine - if true it will display only tickets related to contact
		otherwise displays tickets related to account it belongs and all the contacts that are under the same account
		int $where - used for searching tickets
		string $match - used for matching tickets
		*	return array $result - This function will call get_KBase_details and return that array
		*/


	function get_tickets_list($input_array) {

		global $adb,$log;
		global $current_user,$table_prefix;

		$log->debug("Entering customer portal function get_ticket_list");

		$user = CRMEntity::getInstance('Users');
		$userid = $this->getPortalUserid();

		$show_all = $this->show_all('HelpDesk');
		$current_user = $user->retrieveCurrentUserInfoFromFile($userid);

		$id = $input_array['id'];
		$only_mine = $input_array['onlymine'];
		$where = $input_array['where']; //addslashes is already added with where condition fields in portal itself
		$match = $input_array['match'];
		$sessionid = $input_array['sessionid'];

		if(!$this->validateSession($id,$sessionid))
			return null;

		// Prepare where conditions based on search query
		$join_type = '';
		$where_conditions = '';
		if(trim($where) != '') {
			if($match == 'all' || $match == '') {
				$join_type = " AND ";
			} elseif($match == 'any') {
				$join_type = " OR ";
			}
			$where = explode("&&&",$where);
			$where_conditions = implode($join_type, $where);
		}

		$entity_ids_list = $this->get_allowed_ids($id, 'HelpDesk', $only_mine); // crmv@173271

		$focus = CRMEntity::getInstance('HelpDesk');
		$focus->filterInactiveFields('HelpDesk');
		foreach ($focus->list_fields as $fieldlabel => $values){
			foreach($values as $table => $fieldname){
				$fields_list[$fieldlabel] = $fieldname;
			}
		}
		$query = "SELECT ".$table_prefix."_troubletickets.*, ".$table_prefix."_crmentity.smownerid,".$table_prefix."_crmentity.createdtime, ".$table_prefix."_crmentity.modifiedtime, '' AS setype
			FROM ".$table_prefix."_troubletickets
			INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_troubletickets.ticketid AND ".$table_prefix."_crmentity.deleted = 0
			WHERE ".$table_prefix."_troubletickets.parent_id IN (". generateQuestionMarks($entity_ids_list) .") ";
		// Add conditions if there are any search parameters
		if ($join_type != '' && $where_conditions != '') {
			$query .= " AND (".$where_conditions.")";
		}
		$params = array($entity_ids_list);


		$TicketsfieldVisibilityByColumn = array();
		foreach($fields_list as $fieldlabel=> $fieldname) {
			$TicketsfieldVisibilityByColumn[$fieldname] =
				getColumnVisibilityPermission($current_user->id,$fieldname,'HelpDesk');
		}

		$res = $adb->pquery($query,$params);
		$noofdata = $adb->num_rows($res);
		for( $j= 0;$j < $noofdata; $j++)
		{
			$i=0;
			foreach($fields_list as $fieldlabel => $fieldname) {
				$fieldper = $TicketsfieldVisibilityByColumn[$fieldname]; //in troubletickets the list_fields has columns so we call this API
				if($fieldper == '1'){
					continue;
				}
				$output[0]['head'][0][$i]['fielddata'] = $fieldlabel;
				$fieldvalue = $adb->query_result($res,$j,$fieldname);
				$ticketid = $adb->query_result($res,$j,'ticketid');
				if($fieldname == 'title'){
					$fieldvalue = '<a href="index.php?module=HelpDesk&action=index&fun=detail&ticketid='.$ticketid.'">'.$fieldvalue.'</a>';
				}
				if($fieldname == 'parent_id') {
					$crmid = $fieldvalue;
					$module = getSalesEntityType($crmid);
					if ($crmid != '' && $module != '') {
						$fieldvalues = getEntityName($module, array($crmid));
						if($module == 'Contacts')
						$fieldvalue = '<a href="index.php?module=Contacts&action=index&id='.$crmid.'">'.$fieldvalues[$crmid].'</a>';
						elseif($module == 'Accounts')
						$fieldvalue = '<a href="index.php?module=Accounts&action=index&id='.$crmid.'">'.$fieldvalues[$crmid].'</a>';
					} else {
						$fieldvalue = '';
					}
				}
				if($fieldname == 'smownerid'){
					$fieldvalue = getOwnerName($fieldvalue);
				}
				if($fieldname == 'ticketid'){
					$fieldvalue = $ticketid;
				}
				$output[1]['data'][$j][$i]['fielddata'] = $fieldvalue;
				$i++;
			}
		}
		$log->debug("Exiting customer portal function get_ticket_list");
		return $output;
	}

	// crmv@5946 crmv@173271
	/**
	 * @deprecated
	 */
	function picklist_tickets($contactid){
		global $adb,$log,$table_prefix;
		$log->debug("Entering customer portal function get_ticket_list");
		$query = "SELECT {$table_prefix}_potential.*, potentialid as entityid, {$table_prefix}_crmentity.smownerid
					FROM {$table_prefix}_potential
					INNER JOIN {$table_prefix}_crmentity ON crmid = potentialid
					WHERE deleted = 0 AND related_to = (".generateQuestionMarks($contactid).") ORDER BY potentialid DESC";
		$params = array($contactid);
		$res = $adb->pquery($query,$params);
		$noofdata = $adb->num_rows($res);
		for( $j= 0;$j < $noofdata; $j++)
		{
			$potentialid = $adb->query_result($res,$j,'potentialid');
			$potentialname = $adb->query_result($res,$j,'potentialname');
			$fieldvalue[] = '<option value="'.$potentialid.'">'.$potentialname.'</option>';
		}
		return $fieldvalue;
	}
	// crmv@5946e crmv@173271e

	// unsubscribe
	function unsubscribe_contact($id,$module,$customerid,$sessionid,$language){
		global $adb,$log,$current_language,$default_language,$current_user,$table_prefix;

		$log->debug("Entering customer portal function unsubscribe_contact ..");

		$user = CRMEntity::getInstance('Users');
		$userid = $this->getPortalUserid();
		$current_user = $user->retrieveCurrentUserInfoFromFile($userid);

		//(!empty($language)) ? $current_language = $language : $current_language = $default_language;
		
		if(!empty($id)){
			// crmv@5946
			$q= "UPDATE ".$table_prefix."_customerdetails cu INNER JOIN ".$table_prefix."_contactdetails cd ON cu.customerid =  cd.contactid
			SET cu.portal = 0, cu.support_end_date = CURDATE()
			WHERE cd.contactid= ?";
			// crmv@5946e
			$adb->pquery ( $q, array ($id));

	// 		$q2 = "INSERT IGNORE INTO tbl_s_newsletter_g_unsub (SELECT email,NOW() FROM vte_contactdetails WHERE contactid = ?)";
	// 		$adb->pquery ( $q2, array ($id));
			
			return "ok";
		} 
		else{
			return "id empty";	
		}
	}

	function create_potentials($input_array){
		global $adb,$log,$current_user,$table_prefix,$HELPDESK_SUPPORT_NAME,$HELPDESK_SUPPORT_EMAIL_ID;
		$adb->println("Inside customer portal function create_ticket");
		$adb->println($input_array);
		
		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		$potentialname = $input_array['potentialname'];
		$sales_stage = $input_array['sales_stage'];
		$description = $input_array['description'];
		$user_name = $input_array['user_name'];
		$parent_id = (int) $input_array['parent_id'];
		$module = $input_array['module'];
		$servicecontractid = $input_array['serviceid'];
		$projectid = $input_array['projectid'];
		
		if(!$this->validateSession($id,$sessionid))
			return null;
		
		$potentials = CRMEntity::getInstance('Potentials');
		
		$potentials->column_fields['potentialname'] = $potentialname;
		$potentials->column_fields['sales_stage'] = $sales_stage;
		$potentials->column_fields['description']=$description;
		$potentials->column_fields['related_to'] = $id;
			
		$userid = $this->getPortalUserid();
		$potentials->column_fields['assigned_user_id']= $userid; // 'enostra@livecom.coop'; //$userid; // crmv@5946
		
		$user = CRMEntity::getInstance('Users');
		$current_user = $user->retrieveCurrentUserInfoFromFile($userid);
		
		$potentials->save("Potentials");
	/*	
		//crmv@57342
		$templateid = 19;
		
		$query="SELECT *
		FROM {$table_prefix}_emailtemplates
		WHERE templateid = ? ";
		$res = $adb->pquery($query,Array($templateid));
		if ($res && $adb->num_rows($res) > 0 ){
		$subject = $adb->query_result_no_html($res,0,'subject');
		$body = $adb->query_result_no_html($res,0,'body');
		$contents = getMergedDescription($body, $ticket->id,'HelpDesk','', $templateid);
		}else{
				$subject = "[From Portal] " .$ticket->column_fields['ticket_no']." [ Ticket ID : $ticket->id ] ".$title;
				$contents = ' Ticket No : '.$ticket->column_fields['ticket_no']. '<br> Ticket ID : '.$ticket->id.'<br> Ticket Title : '.$title.'<br><br>'.$description;
		}
		//crmv@57342
		
			//get the contact email id who creates the ticket from portal and use this email as from email id in email
			$result = $adb->pquery("select email from ".$table_prefix."_contactdetails where contactid=?", array($parent_id));
		$contact_email = $adb->query_result($result,0,'email');
			$from_email = $contact_email;
			$recordName = getEntityName('Contacts',$parent_id);
		
			//crmv@29617
			$focus = CRMEntity::getInstance('ModNotifications');
			$focus->saveFastNotification(
			array(
					'assigned_user_id' => $userid,
				'related_to' => $ticket->id,
				'mod_not_type' => 'Ticket portal created',
					'createdtime' => $ticket->column_fields['createdtime'],
					'modifiedtime' => $ticket->column_fields['createdtime'],
					'subject' => $subject,
					'description' => $contents,
					'from_email' => $from_email,
				'from_email_name' => $recordName[$parent_id],
			),false
			);
			//crmv@29617e
		
			//send mail to the customer(contact who creates the ticket from portal)
			$adb->println("Send mail to the customer(contact) who creates the portal ticket");
		$mail_status = send_mail('Contacts',$contact_email,$HELPDESK_SUPPORT_NAME,$HELPDESK_SUPPORT_EMAIL_ID,$subject,$contents);
		*/
			$potentialresult = $adb->pquery("SELECT ".$table_prefix."_potential.potentialid FROM ".$table_prefix."_potential 
											INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_potential.potentialid
											WHERE ".$table_prefix."_crmentity.deleted=0 AND ".$table_prefix."_potential.potentialid = ?", array($potentials->id));
			if($adb->num_rows($potentialresult) == 1){
				$record_save = 1;
				$record_array[0]['new_potential']['potentialid'] = $adb->query_result($potentialresult,0,'potentialid');
			}
			if($servicecontractid != ''){
				$res = $adb->pquery("insert into ".$table_prefix."_crmentityrel values(?,?,?,?)",
				array($servicecontractid, 'ServiceContracts', $ticket->id, 'HelpDesk'));
			}
			if($projectid != '') {
				$res = $adb->pquery("insert into ".$table_prefix."_crmentityrel values(?,?,?,?)",
				array($projectid, 'ProjectPlan', $ticket->id, 'HelpDesk'));
			}
			if($record_save == 1){
				$adb->println("Ticket from Portal is saved with id => ".$ticket->id);
				return $record_array;
			}
			else{
				$adb->println("There may be error in saving the ticket.");
				return null;
			}
	}

	// crmv@173271
	/**	function used to create ticket which has been created from customer portal
	*	@param array $input_array - array which contains the following values
	=> 	int $id - customer id
		int $sessionid - session id
		string $title - title of the ticket
		string $description - description of the ticket
		string $priority - priority of the ticket
		string $severity - severity of the ticket
		string $category - category of the ticket
		int $parent_id - parent id ie., customer id as this customer is the parent for this ticket
		int $product_id - product id for the ticket
		*	return array - currently created ticket array, if this is not created then all tickets list will be returned
		*/
	function create_ticket($input_array) {
		global $adb,$log,$current_user,$table_prefix;
		
		$adb->println("Inside customer portal function create_ticket");
		$adb->println($input_array);

		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		
		if (!$this->validateSession($id,$sessionid)) return null;
		
		$title = $input_array['title'];
		$description = $input_array['description'];
		$priority = $input_array['priority'];
		$severity = $input_array['severity'];
		$category = $input_array['category'];
		$parent_id = (int) $input_array['parent_id'];
		$product_id = (int) $input_array['product_id'];

		$servicecontractid = $input_array['serviceid'];
		$projectid = $input_array['projectid'];
		
		$ticket = CRMEntity::getInstance('HelpDesk');

		$ticket->column_fields['ticket_title'] = $title;
		$ticket->column_fields['description']=$description;
		$ticket->column_fields['ticketpriorities']=$priority;
		if(!empty($severity)){ // crmv@81291
			$ticket->column_fields['ticketseverities']=$severity;
		}
		$ticket->column_fields['ticketcategories']=$category;
		$ticket->column_fields['ticketstatus']='Open';

		$ticket->column_fields['parent_id']=$parent_id;
		$ticket->column_fields['product_id']=$product_id;

		$userid = $this->getPortalUserid();

		$ticket->column_fields['assigned_user_id']=$userid;
		
		// crmv@152221 - patch for BU
		if (function_exists('isModuleWidthBUMC') && $parent_id > 0) {
			if (isModuleWidthBUMC('Contacts') && isModuleWidthBUMC('HelpDesk')) {
				// get the BU from the contact
				$bumc = getModuleBUMCField('Contacts', $parent_id);
				$ticket->column_fields['bu_mc'] = $bumc;
			}
		}
		// crmv@152221e
		
		$user = CRMEntity::getInstance('Users');
		$current_user = $user->retrieveCurrentUserInfoFromFile($userid);

		$ticket->save("HelpDesk");
		
		// crmv@142358
		// retrieve email template for portal user
		$mailinfo = $ticket->getPortalEmail('', '');
		$subject = $mailinfo['subject'];
		$contents = $mailinfo['body'];
		// crmv@142358e
		
		//get the contact email id who creates the ticket from portal and use this email as from email id in email
		//crmv@171574
		$focusContacts = CRMEntity::getInstance('Contacts');
		$from_email = getSingleFieldValue($focusContacts->table_name, 'email', $focusContacts->table_index, $parent_id);
		//crmv@171574e
		$recordName = getEntityName('Contacts',$parent_id);

		//crmv@29617
		$focus = ModNotifications::getInstance(); // crmv@164122
		$focus->saveFastNotification(
			array(
				'assigned_user_id' => $userid,
				'related_to' => $ticket->id,
				'mod_not_type' => 'Ticket portal created',
				'createdtime' => $ticket->column_fields['createdtime'],
				'modifiedtime' => $ticket->column_fields['createdtime'],
				'subject' => $subject,
				'description' => $contents,
				'from_email' => $from_email,
				'from_email_name' => $recordName[$parent_id],
			),false
		);
		//crmv@29617e

		// crmv@142358 - removed email

		$ticketresult = $adb->pquery("select ".$table_prefix."_troubletickets.ticketid from ".$table_prefix."_troubletickets
			inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_troubletickets.ticketid inner join ".$table_prefix."_ticketcf on ".$table_prefix."_ticketcf.ticketid = ".$table_prefix."_troubletickets.ticketid
			where ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_troubletickets.ticketid = ?", array($ticket->id));
		if($adb->num_rows($ticketresult) == 1)
		{
			$record_save = 1;
			$record_array[0]['new_ticket']['ticketid'] = $adb->query_result($ticketresult,0,'ticketid');
		}
		if($servicecontractid != ''){
			$res = $adb->pquery("insert into ".$table_prefix."_crmentityrel values(?,?,?,?)",
			array($servicecontractid, 'ServiceContracts', $ticket->id, 'HelpDesk'));
		}
		if($projectid != '') {
			$res = $adb->pquery("insert into ".$table_prefix."_crmentityrel values(?,?,?,?)",
			array($projectid, 'ProjectPlan', $ticket->id, 'HelpDesk'));
		}
		if($record_save == 1)
		{
			$adb->println("Ticket from Portal is saved with id => ".$ticket->id);
			return $record_array;
		}
		else
		{
			$adb->println("There may be error in saving the ticket.");
			return null;
		}
	}
	 // crmv@173271e

	/**	function used to update the ticket comment which is added from the customer portal
	*	@param array $input_array - array which contains the following values
	=> 	int $id - customer id
		int $sessionid - session id
		int $ticketid - ticket id
		int $ownerid - customer ie., contact id who has added this ticket comment
		string $comments - comment which is added from the customer portal
		*	return void
		*/
	function update_ticket_comment($input_array)
	{
		global $adb,$mod_strings,$table_prefix, $current_user;
		$adb->println("Inside customer portal function update_ticket_comment");
		$adb->println($input_array);

		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		$ticketid = (int) $input_array['ticketid'];
		$ownerid = (int) $input_array['ownerid'];
		$comments = $input_array['comments'];
		
		if(!$this->validateSession($id,$sessionid))
			return null;
			
		$user = CRMEntity::getInstance('Users');
		$userid = $this->getPortalUserid();
		$current_user = $user->retrieveCurrentUserInfoFromFile($userid);
		
		$focusHelpDesk = CRMEntity::getInstance('HelpDesk');	//crmv@142955
		$focusHelpDesk->retrieve_entity_info_no_html($ticketid,'HelpDesk'); //crmv@171574

		// crmv@142358
		// crmv@171574 removed code
		$focusHelpDesk->mode = 'edit';
		$focusHelpDesk->column_fields['comments'] = $comments;
		// crmv@142358e
		
		//crmv@171574
		if (trim($comments) != '') {
			// disable standard notifications
			global $global_skip_notifications;
			$tmp_global_skip_notifications = $global_skip_notifications;
			$global_skip_notifications = true;
			
			$focusHelpDesk->column_fields['ticketstatus'] = $focusHelpDesk->answeredByCustomerStatus;
			$focusHelpDesk->sendPortalEmails = false;
			$focusHelpDesk->save('HelpDesk');
			
			$global_skip_notifications = $tmp_global_skip_notifications;
			
			if (empty($focusHelpDesk->lastInsertedCommentId)) $focusHelpDesk->insertIntoTicketCommentTable($table_prefix."_ticketcomments",'HelpDesk','customer',intval($input_array['ownerid']));
			$commentid = $focusHelpDesk->lastInsertedCommentId;
			
			//To get the contact name
			$customername = getEntityName('Contacts', $ownerid, true);
			$focusContacts = CRMEntity::getInstance('Contacts');
			$from_email = getSingleFieldValue($focusContacts->table_name, 'email', $focusContacts->table_index, $ownerid);
			
			//send mail to the assigned to user when customer add comment
			// crmv@142358
			$mailinfo = $focusHelpDesk->getPortalEmail('', '');
			$subject = $mailinfo['subject'];
			$contents = $mailinfo['body'];
			// crmv@142358
			
			//crmv@29617 crmv@57851
			$focus = ModNotifications::getInstance(); // crmv@164122
			$users = $focus->getFollowingUsers($ticketid);
			$users[] = $focusHelpDesk->column_fields['assigned_user_id'];
			if (!empty($users)) {
				$already_notified_users = array();
				foreach($users as $user) {
					if (in_array($user,$already_notified_users)) {
						continue;
					}
					$notified_users = $focus->saveFastNotification(
						array(
							'assigned_user_id' => $user,
							'related_to' => $ticketid,
							'mod_not_type' => 'Ticket portal replied',
							'createdtime' => $servercreatedtime,
							'modifiedtime' => $servercreatedtime,
							'subject' => $subject,
							'description' => $contents,
							'from_email' => $from_email,
							'from_email_name' => $customername,
						)
					);
					if(!empty($notified_users)) {
						foreach($notified_users as $notified_user) {
							$already_notified_users[] = $notified_user;
						}
					}
				}
			}
			//crmv@29617e crmv@57851e
		}
		//crmv@171574e
		return $commentid; // crmv@160733
	}

	// crmv@160733
	function provide_confidential_info($input_array) {

		$focusHelpDesk = CRMEntity::getInstance('HelpDesk');
		
		$comment = $input_array['comment'];
		if ($comment != '') $comment .= "\n\n";
		$comment .= $focusHelpDesk->getConfidentialReplyText();
		
		$input_array['comments'] = $comment;
		
		$commentid = $this->update_ticket_comment($input_array); // crmv@174228
		if ($commentid > 0) {
			$focusHelpDesk->setConfidentialData($commentid, $input_array['data'], $input_array['request_commentid']);
		}
	}
	// crmv@160733e

	/**	function used to close the ticket
	*	@param array $input_array - array which contains the following values
	=> 	int $id - customer id
		int $sessionid - session id
		int $ticketid - ticket id
		*	return string - success or failure message will be returned based on the ticket close update query
		*/
	function close_current_ticket($input_array)
	{
		global $adb,$mod_strings,$log,$current_user;
		$adb->println("Inside customer portal function close_current_ticket");
		$adb->println($input_array);

		//foreach($input_array as $fieldname => $fieldvalue)$input_array[$fieldname] = mysql_real_escape_string($fieldvalue);
		$userid = $this->getPortalUserid();

		//crmv@174812
		if (!$current_user) {
			$user = CRMEntity::getInstance('Users');
			$current_user = $user->retrieveCurrentUserInfoFromFile($userid);
		}
		//crmv@174812e
		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		$ticketid = (int) $input_array['ticketid'];

		if(!$this->validateSession($id,$sessionid))
			return null;

		$focus = CRMEntity::getInstance('HelpDesk');
		$focus->id = $ticketid;
		$focus->retrieve_entity_info($focus->id,'HelpDesk');
		$focus->mode = 'edit';
		$focus->column_fields = array_map('decode_html', $focus->column_fields);
		$focus->column_fields['ticketstatus'] ='Closed';
		// Blank out the comments information to avoid un-necessary duplication
		$focus->column_fields['comments'] = '';
		// END
		$focus->sendPortalEmails = false; // crmv@142358
		$focus->save("HelpDesk");
		
		return $focus;
	}

	/**	function used to authenticate whether the customer has access or not
	*	@param string $username - customer name for the customer portal
	*	@param string $password - password for the customer portal
	*	@param string $login - true or false. If true means function has been called for login process and we have to clear the session if any, false means not called during login and we should not unset the previous sessions
	*	return array $list - returns array with all the customer details
	*/
	function authenticate_user($username,$password,$version,$login = 'true')
	{
		global $adb,$log,$table_prefix;
		$adb->println("Inside customer portal function authenticate_user($username, $password, $login).");
		include('vteversion.php'); // crmv@181168
		if(version_compare($version,'5.1.0','>=') == 0){
			$list[0] = "NOT COMPATIBLE";
			return $list;
		}
		//crmv@157490
		require_once('include/utils/encryption.php');
		$encryption = new Encryption();
		//crmv@157490e
		
		$salt = '';
		if(is_numeric($username)){
			$q = "SELECT user_name, user_password FROM {$table_prefix}_portalinfo WHERE id = ?";
			$ress = $adb->pquery($q, array($username));
			
			if($adb->num_rows($ress)>0){
				$token = $password;
				
				$username = $adb->query_result($ress,0,'user_name');
				$password = $encryption->decrypt($adb->query_result($ress,0,'user_password')); //crmv@157490
				$salt = 'QO(:Q!u@=Y>(MoX=Q1Jx%w:NZV-Ljcnsw>3-qIv@|u_~uDA+|52x<-1Mn{ywdyor';
				if(!crypt($username.$password,$salt) == $token){
					return array('',array('err1'=>'INVALID_USERNAME_OR_PASSWORD'));
				}
			}else{
				//forzo l'errore
				$username = '';
				$password = '';
			}
		}
		
		$username = $adb->sql_escape_string($username);
		$password = $adb->sql_escape_string($encryption->encrypt($password)); //crmv@157490

		$current_date = date("Y-m-d");
		$sql = "select id, user_name, user_password,last_login_time, support_start_date, support_end_date from ".$table_prefix."_portalinfo inner join ".$table_prefix."_customerdetails on ".$table_prefix."_portalinfo.id=".$table_prefix."_customerdetails.customerid inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_portalinfo.id where ".$table_prefix."_crmentity.deleted=0 and user_name=? and user_password = ? and isactive=1 and ".$table_prefix."_customerdetails.portal=1 and ".$table_prefix."_customerdetails.support_end_date >= ?";
		$result = $adb->pquery($sql, array($username, $password, $current_date));
		$err[0]['err1'] = "MORE_THAN_ONE_USER";
		$err[1]['err1'] = "INVALID_USERNAME_OR_PASSWORD";

		$num_rows = $adb->num_rows($result);

		if($num_rows > 1)		return $err[0];//More than one user
		elseif($num_rows <= 0)		return $err[1];//No user

		$customerid = $adb->query_result($result,0,'id');

		$list[0]['id'] = $customerid;
		$list[0]['user_name'] = $adb->query_result($result,0,'user_name');
		$list[0]['user_password'] = $encryption->decrypt($adb->query_result($result,0,'user_password'));	//crmv@157490
		$list[0]['last_login_time'] = $adb->query_result($result,0,'last_login_time');
		$list[0]['support_start_date'] = $adb->query_result($result,0,'support_start_date');
		$list[0]['support_end_date'] = $adb->query_result($result,0,'support_end_date');

		//During login process we will pass the value true. Other times (change password) we will pass false
		if($login != 'false')
		{
			$sessionid = makeRandomPassword();

			$this->unsetServerSessionId($customerid);

			$sql="insert into ".$table_prefix."_soapservice values(?,?,?)";
			$result = $adb->pquery($sql, array($customerid,'customer' ,$sessionid));

			$list[0]['sessionid'] = $sessionid;
		}

		return $list;
	}

	// crmv@111615
	function authenticate_user_cookie($id,$user_hash,$step){
		global $adb,$table_prefix;

		if (!empty($id) && $step == 'save') {
			$user_hash = makeRandomPassword() . makeRandomPassword();
			$ress = $adb->pquery("UPDATE {$table_prefix}_portalinfo SET user_hash = ? WHERE id = ?", array($user_hash, $id));
			return array($user_hash);
			
		} elseif (!empty($id) && !empty($user_hash) && $step == 'check') {
			$query = "SELECT user_name, user_password FROM {$table_prefix}_portalinfo WHERE isactive = 1 AND id = ? AND user_hash = ?";
			$ress = $adb->pquery($query, array($id,$user_hash));
			if ($ress && $adb->num_rows($ress) > 0) {
				$row = $adb->fetchByAssoc($ress, -1, false);
				//crmv@157490
				require_once('include/utils/encryption.php');
				$encryption = new Encryption();
				$row['user_password'] = $encryption->decrypt($row['user_password']);
				//crmv@157490e
				return $row;
			}
		}
		return false;

	}
	// crmv@111615e

	/**	function used to change the password for the customer portal
	*	@param array $input_array - array which contains the following values
	=> 	int $id - customer id
		int $sessionid - session id
		string $username - customer name
		string $password - new password to change
		*	return array $list - returns array with all the customer details
		*/
	function change_password($input_array)
	{
		global $adb,$log,$table_prefix;
		$log->debug("Entering customer portal function change_password");
		$adb->println($input_array);

		$id = (int) $input_array['id'];
		$sessionid = $input_array['sessionid'];
		$username = $input_array['username'];
		$password = $input_array['password'];
		$version = $input_array['version'];

		if(!$this->validateSession($id,$sessionid))
			return null;

		$list = $this->authenticate_user($username,$password,$version ,'false'); // crmv@168297
		if(!empty($list[0]['id'])){
			return array('MORE_THAN_ONE_USER');
		}
		
		//crmv@157490
		require_once('include/utils/encryption.php');
		$encryption = new Encryption();
		$password = $encryption->encrypt($password);
		//crmv@157490e
		
		$sql = "update ".$table_prefix."_portalinfo set user_password=? where id=? and user_name=?";
		$result = $adb->pquery($sql, array($password, $id, $username));
		//crmv@62750
		$sql = "UPDATE {$table_prefix}_contactdetails SET websites_pwd = ? WHERE contactid = ? ";
		$result = $adb->pquery($sql, array($password, $id));
		//crmv@62750e
		//crmv@57342
		$templateid = 21;
		
		$query="SELECT *
				FROM {$table_prefix}_emailtemplates
				WHERE templateid = ? ";
		$res = $adb->pquery($query,Array($templateid));
		if ($res && $adb->num_rows($res) > 0 ){
			$subject = $adb->query_result_no_html($res,0,'subject');
			$body = $adb->query_result_no_html($res,0,'body');
			$contents = getMergedDescription($body, $id,'Contacts','', $templateid);
		}else{
			return;
		}
		//crmv@57342
		
		//get the contact email id who creates the ticket from portal and use this email as from email id in email
		$result = $adb->pquery("select email from ".$table_prefix."_contactdetails where contactid=?", array($id));
		$contact_email = $adb->query_result($result,0,'email');
		$from_email = $contact_email;
		$recordName = getEntityName('Contacts',$id);
		
		$mail_status = send_mail('Contacts',$contact_email,$HELPDESK_SUPPORT_NAME,$HELPDESK_SUPPORT_EMAIL_ID,$subject,$contents);
		
		$log->debug("Exiting customer portal function change_password");
		return $list;
	}

	/**	function used to update the login details for the customer
	*	@param array $input_array - array which contains the following values
	=> 	int $id - customer id
		int $sessionid - session id
		string $flag - login/logout, based on this flag, login or logout time will be updated for the customer
		*	return string $list - empty value
		*/
	function update_login_details($input_array)
	{
		global $adb,$log,$table_prefix;
		$log->debug("Entering customer portal function update_login_details");
		$adb->println("INPUT ARRAY for the function update_login_details");
		$adb->println($input_array);

		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		$flag = $input_array['flag'];

		if(!$this->validateSession($id,$sessionid))
			return null;

		$current_time = $adb->formatDate(date('YmdHis'), true);

		if($flag == 'login')
		{
			$sql = "update ".$table_prefix."_portalinfo set login_time=? where id=?";
			$result = $adb->pquery($sql, array($current_time, $id));
		}
		elseif($flag == 'logout')
		{
			$sql = "update ".$table_prefix."_portalinfo set logout_time=?, last_login_time=login_time where id=?";
			$result = $adb->pquery($sql, array($current_time, $id));
		}
		$log->debug("Exiting customer portal function update_login_details");
	}

	/**	function used to send mail to the customer when he forgot the password and want to retrieve the password
	*	@param string $mailid - email address of the customer
	*	return message about the mail sending whether entered mail id is correct or not or is there any problem in mail sending
	*/
	function send_mail_for_password($mailid)
	{
		global $adb,$mod_strings,$log,$table_prefix;
		$log->debug("Entering customer portal function send_mail_for_password");
		$adb->println("Inside the function send_mail_for_password($mailid).");
		
		// crmv@170747
		if (empty($mod_strings)) {
			global $default_language;
			$mod_strings = return_module_language($default_language, 'HelpDesk'); // TODO: pass the language from portal
		}
		// crmv@170747e
		
		//crmv@157490
		require_once('include/utils/encryption.php');
		$encryption = new Encryption();
		//crmv@157490e

		$sql = "SELECT user_name, user_password, isactive, firstname, lastname 
				FROM ".$table_prefix."_portalinfo 
				INNER JOIN ".$table_prefix."_crmentity ON crmid = id 
				INNER JOIN ".$table_prefix."_contactdetails ON id = contactid 
				WHERE deleted = 0 AND user_name = ?";
		$res = $adb->pquery($sql, array($mailid));
		$user_name = $adb->query_result($res,0,'user_name');
		$password = $encryption->decrypt($adb->query_result($res,0,'user_password')); //crmv@157490
		$isactive = $adb->query_result($res,0,'isactive');

		$fromquery = "select ".$table_prefix."_users.user_name, ".$table_prefix."_users.email1 from ".$table_prefix."_users inner join ".$table_prefix."_crmentity on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid inner join ".$table_prefix."_contactdetails on ".$table_prefix."_contactdetails.contactid=".$table_prefix."_crmentity.crmid where ".$table_prefix."_contactdetails.email =?";
		$from_res = $adb->pquery($fromquery, array($mailid));
		$initialfrom = $adb->query_result($from_res,0,'user_name');
		$from = $adb->query_result($from_res,0,'email1');

		// crmv@99393
		if (empty($initialfrom) || empty($from)) {
			global $HELPDESK_SUPPORT_EMAIL_ID, $HELPDESK_SUPPORT_NAME;
			$initialfrom = $HELPDESK_SUPPORT_NAME;
			$from = $HELPDESK_SUPPORT_EMAIL_ID;
		}
		// crmv@99393e

		$contents = $mod_strings['LBL_LOGIN_DETAILS'];
		$contents .= "<br><br>".$mod_strings['LBL_USERNAME']." ".$user_name;
		$contents .= "<br>".$mod_strings['LBL_PASSWORD']." ".$password;
		
		// crmv@78744 - removed direct email creation
		if ($mailid == '') {
			$ret_msg = "false@@@<b>".$mod_strings['LBL_GIVE_MAILID']."</b>";
		} elseif ($user_name == '' && $password == '') {
			$ret_msg = "false@@@<b>".$mod_strings['LBL_CHECK_MAILID']."</b>";
		} elseif ($isactive == 0) {
			$ret_msg = "false@@@<b>".$mod_strings['LBL_LOGIN_REVOKED']."</b>";
		} elseif (!send_mail('Contacts',$mailid,$initialfrom,$from,$mod_strings['LBL_SUBJECT_PORTAL_LOGIN_DETAILS'],$contents)) {
			$ret_msg = "false@@@<b>".$mod_strings['LBL_MAIL_COULDNOT_SENT']."</b>";
		} else {
			$ret_msg = "true@@@<b>".$mod_strings['LBL_MAIL_SENT']."</b>";
		}
		// crmv@78744e

		$adb->println("Exit from send_mail_for_password. $ret_msg");
		$log->debug("Exiting customer portal function send_mail_for_password");
		return $ret_msg;
	}

	// crmv@173271
	/**	
	 * @deprecated
	 * This function is not used anymore, please don't use it!
	 *
	 * function used to get the ticket creater
	 *	@param array $input_array - array which contains the following values
	 * int $id - customer ie., contact id
	 *	int $sessionid - session id
	 *	int $ticketid - ticket id
	 *	return int $creator - ticket created user id will be returned ie., smcreatorid from crmentity table
	 */
	function get_ticket_creator($input_array)
	{
		global $adb,$table_prefix;

		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		$ticketid = (int) $input_array['ticketid'];

		if(!$this->validateSession($id,$sessionid))
			return null;

		$res = $adb->pquery("select smcreatorid from ".$table_prefix."_crmentity where crmid=?", array($ticketid));
		$creator = $adb->query_result_no_html($res,0,'smcreatorid');

		return $creator;
	}
	// crmv@173271e

	/**	function used to get the picklist values
	*	@param array $input_array - array which contains the following values
	=>	int $id - customer ie., contact id
		int $sessionid - session id
		string $picklist_name - picklist name you want to retrieve from database
		*	return array $picklist_array - all values of the corresponding picklist will be returned as a array
		*/
	function get_picklists($input_array)
	{
		global $adb, $log,$table_prefix;
		$log->debug("Entering customer portal function get_picklists");
		$adb->println("INPUT ARRAY for the function get_picklists");
		$adb->println($input_array);

		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		$picklist_name = $adb->sql_escape_string($input_array['picklist_name']);

		if(!$this->validateSession($id,$sessionid))
		return null;

		$picklist_array = Array();

		$admin_role = 'H2';
		$userid = $this->getPortalUserid();
		$roleres = $adb->pquery("SELECT roleid from ".$table_prefix."_user2role where userid = ?", array($userid));
		$RowCount = $adb->num_rows($roleres);
		if($RowCount > 0){
			$admin_role = $adb->query_result($roleres,0,'roleid');
		}

		$res = $adb->pquery("select ".$table_prefix."_". $picklist_name.".* from ".$table_prefix."_". $picklist_name." inner join ".$table_prefix."_role2picklist on ".$table_prefix."_role2picklist.picklistvalueid = ".$table_prefix."_". $picklist_name.".picklist_valueid and ".$table_prefix."_role2picklist.roleid='$admin_role' ORDER BY sortid,{$picklist_name}", array()); // crmv@135834
		for($i=0;$i<$adb->num_rows($res);$i++)
		{
			$picklist_val = $adb->query_result($res,$i,$picklist_name);
			$picklist_array[$i] = $picklist_val;
		}

		$adb->println($picklist_array);
		$log->debug("Exiting customer portal function get_picklists($picklist_name)");
		return $picklist_array;
	}
	/**	function to get the attachments of a ticket
	*	@param array $input_array - array which contains the following values
	=>	int $id - customer ie., contact id
		int $sessionid - session id
		int $ticketid - ticket id
		*	return array $output - This will return all the file details related to the ticket
		*/
	function get_ticket_attachments($input_array)
	{
		global $adb,$log,$table_prefix;
		$log->debug("Entering customer portal function get_ticket_attachments");
		$adb->println("INPUT ARRAY for the function get_ticket_attachments");
		$adb->println($input_array);

		$check = $this->checkModuleActive('Documents');
		if($check == false){
			return array("#MODULE INACTIVE#");
		}
		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		$ticketid = $input_array['ticketid'];

		$isPermitted = $this->check_permission($id,'HelpDesk',$ticketid);
		if($isPermitted == false) {
			return array("#NOT AUTHORIZED#");
		}

		if(!$this->validateSession($id,$sessionid))
		return null;

		$query = "select ".$table_prefix."_troubletickets.ticketid, ".$table_prefix."_attachments.*,".$table_prefix."_notes.filename,".$table_prefix."_notes.filelocationtype from ".$table_prefix."_troubletickets " .
			"left join ".$table_prefix."_senotesrel on ".$table_prefix."_senotesrel.crmid=".$table_prefix."_troubletickets.ticketid " .
			"left join ".$table_prefix."_notes on ".$table_prefix."_notes.notesid=".$table_prefix."_senotesrel.notesid " .
			"inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_notes.notesid " .
			"left join ".$table_prefix."_seattachmentsrel on ".$table_prefix."_seattachmentsrel.crmid=".$table_prefix."_notes.notesid " .
			"left join ".$table_prefix."_attachments on ".$table_prefix."_attachments.attachmentsid = ".$table_prefix."_seattachmentsrel.attachmentsid " .
			"and ".$table_prefix."_crmentity.deleted = 0 where ".$table_prefix."_troubletickets.ticketid =?";

		$res = $adb->pquery($query, array($ticketid));
		$noofrows = $adb->num_rows($res);
		for($i=0;$i<$noofrows;$i++)
		{
			$filename = $adb->query_result($res,$i,'filename');
			$filepath = $adb->query_result($res,$i,'path');

			$fileid = $adb->query_result($res,$i,'attachmentsid');
			$filesize = filesize($filepath.$fileid."_".$filename);
			$filetype = $adb->query_result($res,$i,'type');
			$filelocationtype = $adb->query_result($res,$i,'filelocationtype');
			//Now we will not pass the file content to CP, when the customer click on the link we will retrieve
			//$filecontents = base64_encode(file_get_contents($filepath.$fileid."_".$filename));//fread(fopen($filepath.$filename, "r"), $filesize));

			$output[$i]['fileid'] = $fileid;
			$output[$i]['filename'] = $filename;
			$output[$i]['filetype'] = $filetype;
			$output[$i]['filesize'] = $filesize;
			$output[$i]['filelocationtype'] = $filelocationtype;
		}
		$log->debug("Exiting customer portal function get_ticket_attachments");
		return $output;
	}

	// crmv@205309
	/**	function used to get the contents of a file
	*	@param array $input_array - array which contains the following values
	=>	int $id - customer ie., contact id
		int $sessionid - session id
		int $fileid - id of the file to which we want contents
		string $filename - name of the file to which we want contents
		*	return $filecontents array with single file contents like [fileid] => filecontent
		*/
	function get_filecontent($input_array) {
		global $log;
		
		$log->debug("Entering customer portal function get_filecontent");
		
		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		$fileid = $input_array['fileid'];
		//$filename = $input_array['filename'];
		//$ticketid = $input_array['ticketid'];
		
		if(!$this->validateSession($id,$sessionid))	return null;
		
		$FS = FileStorage::getInstance();
		$filecontents = $FS->downloadFile($fileid, ['return_content' => true]);
		$filecontents = [$fileid => base64_encode($filecontents)];

		$log->debug("Exiting customer portal function get_filecontent ");
		return $filecontents;
	}

	/**	function to add attachment for a ticket ie., the passed contents will be write in a file and the details will be stored in database
	*	@param array $input_array - array which contains the following values
	=>	int $id - customer ie., contact id
		int $sessionid - session id
		int $ticketid - ticket id
		string $filename - file name to be attached with the ticket
		string $filetype - file type
		int $filesize - file size
		string $filecontents - file contents as base64 encoded format
		*	return void
		*/
	function add_ticket_attachment($input_array) {
		global $log, $current_user;
		
		$log->debug("Entering customer portal function add_ticket_attachment");
		
		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		
		$ticketid = $input_array['ticketid'];
		$filename = $input_array['filename'];
		$filecontents = $input_array['filecontents'];

		if(!$this->validateSession($id,$sessionid)) return null;
		
		// save data to a temp file (possible race condition, but should be limited)
		$tempdir = tempnam(sys_get_temp_dir(), '') . 'dir';
		if (file_exists($tempdir)) unlink($tempdir);
		$r = mkdir($tempdir);
		if ($r === false) return false;
		
		// clean the file name
		$FS = FileStorage::getInstance();
		$filename = $FS->sanitizeFilename($filename);
		
		// save the content to a file
		$path = $tempdir.'/'.$filename;
		$data = base64_decode($filecontents);
		$r = file_put_contents($path, $data);
		if ($r === false) return false;
		
		// init current user
		$user_id = $this->getPortalUserid();
		$user = CRMEntity::getInstance('Users');
		$current_user = $user->retrieveCurrentUserInfoFromFile($user_id);
		
		// save the document
		$focus = CRMEntity::getInstance('Documents');
		$focus->createDocumentFromPathFile($path, 1, $ticketid, $user_id);
		
		// clean up temporary files
		if (file_exists($path)) unlink($path);
		rmdir($tempdir);
	}
	// crmv@205309e

	/**	function used to get the Account name
	*	@param int $id - Account id
	*	return string $message - Account name returned
	*/
	function get_account_name($accountid)
	{
		global $adb,$log,$table_prefix;
		$log->debug("Entering customer portal function get_account_name");
		$res = $adb->pquery("select accountname from ".$table_prefix."_account where accountid=?", array($accountid));
		$accountname=$adb->query_result($res,0,'accountname');
		$log->debug("Exiting customer portal function get_account_name");
		return $accountname;
	}

	/** function used to get the Contact name
	*  @param int $id -Contact id
	* return string $message -Contact name returned
	*/
	function get_contact_name($contactid)
	{
		global $adb,$log,$table_prefix;
		$log->debug("Entering customer portal function get_contact_name");
		$contact_name = '';
		if($contactid != '')
		{
			$sql = "select firstname,lastname from ".$table_prefix."_contactdetails where contactid=?";
			$result = $adb->pquery($sql, array($contactid));
			$firstname = $adb->query_result($result,0,"firstname");
			$lastname = $adb->query_result($result,0,"lastname");
			$contact_name = $firstname." ".$lastname;
			return $contact_name;
		}
		$log->debug("Exiting customer portal function get_contact_name");
		return false;
	}

	// crmv@173271
	/**     function used to get the Account id
	 *      @param int $id - Contact id
	 *      return string $message - Account id returned
	 */
	public function get_check_account_id($customerid) {
		global $adb, $table_prefix;

		$res = $adb->pquery(
			"SELECT co.accountid 
			FROM {$table_prefix}_contactdetails co
			INNER JOIN {$table_prefix}_crmentity c ON co.contactid = c.crmid AND c.deleted = 0
			INNER JOIN {$table_prefix}_account a ON a.accountid = co.accountid
			INNER JOIN {$table_prefix}_crmentity c2 ON a.accountid = c2.crmid AND c2.deleted = 0
			WHERE co.contactid = ?",
			array($customerid)
		);
		if ($res && $adb->num_rows($res)) {
			$accountid = $adb->query_result_no_html($res, 0, 'accountid');
		}

		return $accountid;
	}
	// crmv@173271e


	/**	function used to get the vendor name
	*	@param int $id - vendor id
	*	return string $name - Vendor name returned
	*/

	function get_vendor_name($vendorid)
	{
		global $adb,$log,$table_prefix;
		$log->debug("Entering customer portal function get_vendor_name");
		$res = $adb->pquery("select vendorname from ".$table_prefix."_vendor where vendorid=?", array($vendorid));
		$name=$adb->query_result($res,0,'vendorname');
		$log->debug("Exiting customer portal function get_vendor_name");
		return $name;
	}

	// crmv@90004
	function get_folder($id,$module,$sessionid,$only_mine='true',$check_folder='') {
		
		global $adb,$log,$table_prefix;

		$folders = array();
		$folderid_contacts = array();
		$i = 0;

		if ($module == 'Documents')	{
					
			// CARTELLE DEI DOCUMENTI DI TUTTI I DOCUMENTI ASSEGNATI A AME E ALLA AZIENDA A CUI APPARTENGO
			
			$sql_Accounts = "SELECT accountid FROM {$table_prefix}_contactdetails WHERE contactid = ? ";
			$ris_Accounts = $adb->pquery($sql_Accounts,array($id));
			
			$accountid = $adb->query_result($ris_Accounts,'accountid');
			
			$groupby = " GROUP BY folderid ";
			
			$sql = "SELECT {$table_prefix}_crmentityfolder.folderid,
							{$table_prefix}_crmentityfolder.foldername
							FROM
							{$table_prefix}_notes
							INNER JOIN {$table_prefix}_crmentity
								ON {$table_prefix}_crmentity.crmid = {$table_prefix}_notes.notesid
								INNER JOIN {$table_prefix}_senotesrel
								on  {$table_prefix}_senotesrel.notesid = {$table_prefix}_notes.notesid
							LEFT JOIN {$table_prefix}_crmentityfolder
								ON {$table_prefix}_crmentityfolder.folderid = {$table_prefix}_notes.folderid
								WHERE {$table_prefix}_crmentity.deleted = 0 AND active_portal = 1 ";
			if(!empty($accountid)){
				$sql .= " and {$table_prefix}_senotesrel.crmid IN (?,?) ".$groupby;
				$params = array($id,$accountid);
			}else{
				$sql .= " and {$table_prefix}_senotesrel.crmid = ? ".$groupby;
				$params = array($id);
			}
			
			$ris = $adb->pquery($sql,$params);

			while($row = $adb->fetchByAssoc($ris)){
					$folderid = $row['folderid'];
					$foldername = $row['foldername'];
					$folders[$i]['foldername'] = $foldername;
					$folders[$i]['folderid'] = $folderid;

					if(!empty($check_folder)){
						$folderid_contacts[] = $folderid;
					}

					$i ++;
			}

			if(!empty($check_folder)){
				if(in_array($check_folder,$folderid_contacts)){
					return true;
				}else{
					return false;
				}
			}else{
				return $folders;
			}
		}
	}
	//crmv@90004e


	/**	function used to get the Quotes/Invoice List
	*	@param int $id - id -Contactid
	*	return string $output - Quotes/Invoice list Array
	*/

	function get_list_values($id,$module,$sessionid,$only_mine='true',$folderid='')	
	{
		global $adb,$log,$current_user,$table_prefix;
		$log->debug("Entering customer portal function get_list_values");
		$check = $this->checkModuleActive($module);
		if($check == false){
			return array("#MODULE INACTIVE#");
		}
		
		// crmv@173271
		// specialized function for tickets
		if ($module == 'HelpDesk') {
			$params = array('sessionid' => $sessionid, 'id' => $id, 'onlymine' => $only_mine);
			return $this->get_tickets_list($params);
		}
		// crmv@173271e

		$user = CRMEntity::getInstance('Users');
		$userid = $this->getPortalUserid();
		$current_user = $user->retrieveCurrentUserInfoFromFile($userid);
		$focus = CRMEntity::getInstance($module);
		$focus->filterInactiveFields($module);
		foreach ($focus->list_fields as $fieldlabel => $values){
			foreach($values as $table => $fieldname){
				$fields_list[$fieldlabel] = $fieldname;
			}
		}

		if(!$this->validateSession($id,$sessionid)) return null;

		$entity_ids_list = $this->get_allowed_ids($id, $module, $only_mine); // crmv@173271
		
		if($module == 'Quotes')
		{
			$query = "select distinct ".$table_prefix."_quotes.*,".$table_prefix."_crmentity.smownerid,
			case when ".$table_prefix."_quotes.contactid is not null then ".$table_prefix."_quotes.contactid else ".$table_prefix."_quotes.accountid end as entityid,
			case when ".$table_prefix."_quotes.contactid is not null then 'Contacts' else 'Accounts' end as setype,
			".$table_prefix."_potential.potentialname,".$table_prefix."_account.accountid
			from ".$table_prefix."_quotes left join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_quotes.quoteid
			LEFT OUTER JOIN ".$table_prefix."_account
			ON ".$table_prefix."_account.accountid = ".$table_prefix."_quotes.accountid
			LEFT OUTER JOIN ".$table_prefix."_potential
			ON ".$table_prefix."_potential.potentialid = ".$table_prefix."_quotes.potentialid
			where ".$table_prefix."_crmentity.deleted=0 and (".$table_prefix."_quotes.accountid in  (". generateQuestionMarks($entity_ids_list) .") or contactid in (". generateQuestionMarks($entity_ids_list) ."))";
			$params = array($entity_ids_list,$entity_ids_list);
			$fields_list['Related To'] = 'entityid';

		}
		else if($module == 'Invoice')
		{
			$query ="select distinct ".$table_prefix."_invoice.*,".$table_prefix."_crmentity.smownerid,
			case when ".$table_prefix."_invoice.contactid !=0 then ".$table_prefix."_invoice.contactid else ".$table_prefix."_invoice.accountid end as entityid,
			case when ".$table_prefix."_invoice.contactid !=0 then 'Contacts' else 'Accounts' end as setype
			from ".$table_prefix."_invoice
			left join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_invoice.invoiceid
			where ".$table_prefix."_crmentity.deleted=0 and (accountid in (". generateQuestionMarks($entity_ids_list) .") or contactid in  (". generateQuestionMarks($entity_ids_list) ."))";
			$params = array($entity_ids_list,$entity_ids_list);
			$fields_list['Related To'] = 'entityid';
		}
		else if ($module == 'Documents')
		{
			// crmv@90004
			$check_autorized = $this->get_folder($id,'Documents','','',$folderid);
			if($check_autorized == false){
				return array("#NOT AUTHORIZED#");
			}
			//crmv@30967
			$query ="select ".$table_prefix."_notes.*, ".$table_prefix."_crmentity.*, ".$table_prefix."_senotesrel.crmid as entityid, '' as setype,".$table_prefix."_crmentityfolder.foldername from ".$table_prefix."_notes " .
				"inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_notes.notesid " .
				"left join ".$table_prefix."_senotesrel on ".$table_prefix."_senotesrel.notesid=".$table_prefix."_notes.notesid " .
				"LEFT JOIN ".$table_prefix."_crmentityfolder ON ".$table_prefix."_crmentityfolder.folderid = ".$table_prefix."_notes.folderid " .
				"where ".$table_prefix."_crmentity.deleted = 0 and ".$table_prefix."_notes.active_portal = 1 and ".$table_prefix."_senotesrel.crmid in (".generateQuestionMarks($entity_ids_list).")"; // crmv@136411
			
			//crmv@30967e
			$params = array($entity_ids_list);
			//crmv@123482
			if($folderid !=''){
				$query .= " AND ".$table_prefix."_notes.folderid=? ";
				array_push($params,$folderid);
			}
			//crmv@123482e
			$fields_list['Related To'] = 'entityid';
		}else if ($module == 'Contacts'){
			$query = "select ".$table_prefix."_contactdetails.*,".$table_prefix."_crmentity.smownerid from ".$table_prefix."_contactdetails
			inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_contactdetails.contactid
			where ".$table_prefix."_crmentity.deleted = 0 and contactid IN (".generateQuestionMarks($entity_ids_list).")";
			$params = array($entity_ids_list);			
		}else if ($module == 'Assets') {
			$accountRes = $adb->pquery("SELECT accountid FROM ".$table_prefix."_contactdetails
							INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_crmentity.crmid
							WHERE contactid = ? AND deleted = 0", array($id));
			$accountRow = $adb->num_rows($accountRes);
			if($accountRow) {
			$accountid = $adb->query_result($accountRes, 0, 'accountid');
			$query = "select ".$table_prefix."_assets.*, ".$table_prefix."_assets.account as entityid , ".$table_prefix."_crmentity.smownerid from ".$table_prefix."_assets
							inner join ".$table_prefix."_crmentity on ".$table_prefix."_assets.assetsid = ".$table_prefix."_crmentity.crmid
							left join ".$table_prefix."_account on ".$table_prefix."_account.accountid = ".$table_prefix."_assets.account
							left join ".$table_prefix."_products on ".$table_prefix."_products.productid = ".$table_prefix."_assets.product
							where ".$table_prefix."_crmentity.deleted = 0 and account = ?";
			$params = array($accountid);
			$fields_list['Related To'] = 'entityid';
			}
		}else if ($module == 'ProjectPlan') {
			$query = "SELECT ".$table_prefix."_project.*, ".$table_prefix."_crmentity.smownerid
						FROM ".$table_prefix."_project
						INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_project.projectid
						WHERE ".$table_prefix."_crmentity.deleted = 0 AND ".$table_prefix."_project.linktoaccountscontacts IN (".generateQuestionMarks($entity_ids_list).")";
			$params = array($entity_ids_list);
			$fields_list['Related To'] = 'linktoaccountscontacts';
		//crmv@128933
		}else if ($module == 'ProjectTask') {
			$query = "SELECT ".$table_prefix."_projecttask.*, ".$table_prefix."_crmentity.smownerid, ".$table_prefix."_project.linktoaccountscontacts
						FROM ".$table_prefix."_projecttask
						INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_projecttask.projecttaskid
						INNER JOIN ".$table_prefix."_project ON ".$table_prefix."_project.projectid = ".$table_prefix."_projecttask.projectid
						WHERE ".$table_prefix."_crmentity.deleted = 0 AND ".$table_prefix."_project.linktoaccountscontacts IN (".generateQuestionMarks($entity_ids_list).")";
			$params = array($entity_ids_list);
			$fields_list['Related To'] = 'linktoaccountscontacts';
		}else if ($module == 'ProjectMilestone') {
			$query = "SELECT ".$table_prefix."_projectmilestone.*, ".$table_prefix."_crmentity.smownerid, ".$table_prefix."_project.linktoaccountscontacts
						FROM ".$table_prefix."_projectmilestone
						INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_projectmilestone.projectmilestoneid
						INNER JOIN ".$table_prefix."_project ON ".$table_prefix."_project.projectid = ".$table_prefix."_projectmilestone.projectid
						WHERE ".$table_prefix."_crmentity.deleted = 0 AND ".$table_prefix."_project.linktoaccountscontacts IN (".generateQuestionMarks($entity_ids_list).")";
			$params = array($entity_ids_list);
			$fields_list['Related To'] = 'linktoaccountscontacts';
		//crmv@128933e
		}else if($module == 'Potentials') 
		{ // crmv@5946
			//$type = getSalesEntityType($linktoid);
			//$fied = '';
			
			//if($type == 'Contacts'){
				$fied = 'related_to';
		//	}else if($type == 'Accounts'){
		//		$fied = 'accountid';
		//	}

			if(!empty($fied)){
				// potentialid,potentialname,potential_no
				$query = "SELECT {$table_prefix}_potential.*, potentialid as entityid, {$table_prefix}_crmentity.smownerid 
							FROM {$table_prefix}_potential 
							INNER JOIN {$table_prefix}_crmentity ON crmid = potentialid
							WHERE deleted = 0 AND ".$fied." IN (".generateQuestionMarks($entity_ids_list).")";
				$params = array($entity_ids_list);
			}
			// crmv@5946e
		} else {
			$queryGen = QueryGenerator::getInstance($module, $current_user);
			$queryGen->initForAllCustomView();
			$queryGen->addFieldAlias('id', 'entityid');			
			$fields_list['Related To'] = 'entityid';
			$query = $queryGen->getQuery();
		}

		$res = $adb->pquery($query,$params);
		$noofdata = $adb->num_rows($res);

		$columnVisibilityByFieldnameInfo = array();
		if($noofdata) {
			foreach($fields_list as $fieldlabel =>$fieldname ) {
				$columnVisibilityByFieldnameInfo[$fieldname] = getColumnVisibilityPermission($current_user->id,$fieldname,$module);
			}
		}

		for( $j= 0;$j < $noofdata; $j++)
		{
			$i=0;
			foreach($fields_list as $fieldlabel =>$fieldname ) {
				$fieldper = $columnVisibilityByFieldnameInfo[$fieldname];
				if($fieldper == '1' && $fieldname != 'entityid'){
					continue;
				}
				$fieldlabel = getTranslatedString($fieldlabel,$module);

				$output[0][$module]['head'][0][$i]['fielddata'] = $fieldlabel;
				$fieldvalue = $adb->query_result($res,$j,$fieldname);
				
				// crmv@5946
				if($module == 'Potentials'){
					if($fieldname =='potentialname'){		
						$fieldid = $adb->query_result($res,$j,'potentialid');
						$fieldvalue = '<a href="index.php?&module=Potentials&action=index&status=true&id='.$fieldid.'">'.$fieldvalue.'</a>';
					}	
				}
				// crmv@5946e
				
				if($module == 'Quotes')
				{
					if($fieldname =='subject'){
						$fieldid = $adb->query_result($res,$j,'quoteid');
						$filename = $fieldid.'_Quotes.pdf';
						$fieldvalue = '<a href="index.php?&module=Quotes&action=index&id='.$fieldid.'">'.$fieldvalue.'</a>';
					}
					if($fieldname == 'total'){
						$sym = $this->getCurrencySymbol($res,$j,'currency_id');
						$fieldvalue = $sym.$fieldvalue;
					}
				}
				if($module == 'Invoice')
				{
					if($fieldname =='subject'){
						$fieldid = $adb->query_result($res,$j,'invoiceid');
						$filename = $fieldid.'_Invoice.pdf';
						$fieldvalue = '<a href="index.php?&module=Invoice&action=index&status=true&id='.$fieldid.'">'.$fieldvalue.'</a>';
					}
					if($fieldname == 'total'){
						$sym = $this->getCurrencySymbol($res,$j,'currency_id');
						$fieldvalue = $sym.$fieldvalue;
					}
				}
				if($module == 'Documents')
				{
					if($fieldname == 'title'){
						$fieldid = $adb->query_result($res,$j,'notesid');
						$fieldvalue = '<a href="index.php?&module=Documents&action=index&id='.$fieldid.'">'.$fieldvalue.'</a>';
					}
					if( $fieldname == 'filename'){
						$fieldid = $adb->query_result($res,$j,'notesid');
						$filename = $fieldvalue;
						$folderid = $adb->query_result($res,$j,'folderid');
						$filename = $adb->query_result($res,$j,'filename');
						$fileactive = $adb->query_result($res,$j,'filestatus');
						$filetype = $adb->query_result($res,$j,'filelocationtype');

						if($fileactive == 1){
							if($filetype == 'I'){
								$fieldvalue = '<a href="index.php?&downloadfile=true&folderid='.$folderid.'&filename='.$filename.'&module=Documents&action=index&id='.$fieldid.'">'.$fieldvalue.'</a>';
							}
							elseif($filetype == 'E'){
								$fieldvalue = '<a target="_blank" href="'.$filename.'" onclick = "updateCount('.$fieldid.');">'.$filename.'</a>';
							}
						}else{
							$fieldvalue = $filename;
						}
					}
					if($fieldname == 'folderid'){
						$fieldvalue = $adb->query_result($res,$j,'foldername');
					}
				}
				if($module == 'Invoice' && $fieldname == 'salesorderid')
				{
					if($fieldvalue != '')
					$fieldvalue = $this->get_salesorder_name($fieldvalue);
				}

				if($module == 'Services'){
					if($fieldname == 'servicename'){
						$fieldid = $adb->query_result($res,$j,'serviceid');
						$fieldvalue = '<a href="index.php?module=Services&action=index&id='.$fieldid.'">'.$fieldvalue.'</a>';
					}
					if($fieldname == 'discontinued'){
						if($fieldvalue == 1){
							$fieldvalue = 'Yes';
						}else{
							$fieldvalue = 'No';
						}
					}
					if($fieldname == 'unit_price'){
						$sym = $this->getCurrencySymbol($res,$j,'currency_id');
						$fieldvalue = $sym.$fieldvalue;
					}

				}
				if($module == 'Contacts'){
					if($fieldname == 'lastname' || $fieldname == 'firstname'){
						$fieldid = $adb->query_result($res,$j,'contactid');
						$fieldvalue ='<a href="index.php?module=Contacts&action=index&id='.$fieldid.'">'.$fieldvalue.'</a>';
					}
				}
				if($module == 'ProjectPlan'){
					if($fieldname == 'projectname'){
						$fieldid = $adb->query_result($res,$j,'projectid');
						$fieldvalue = '<a href="index.php?module=ProjectPlan&action=index&id='.$fieldid.'">'.$fieldvalue.'</a>';
					}
				}
				//crmv@128933
				if($module == 'ProjectTask'){
					if($fieldname == 'projecttaskname'){
						$fieldid = $adb->query_result($res,$j,'projecttaskid');
						$fieldvalue = '<a href="index.php?module=ProjectTask&action=index&id='.$fieldid.'">'.$fieldvalue.'</a>';
					}
				}
				if($module == 'ProjectMilestone'){
					if($fieldname == 'projectmilestonename'){
						$fieldid = $adb->query_result($res,$j,'projectmilestoneid');
						$fieldvalue = '<a href="index.php?module=ProjectMilestone&action=index&id='.$fieldid.'">'.$fieldvalue.'</a>';
					}
				}
				
				//crmv@128933e
				if($fieldname == 'entityid' || $fieldname == 'contactid' || $fieldname == 'accountid' || $fieldname == 'potentialid' || $fieldname == 'account' || $fieldname == 'linktoaccountscontacts') {
					$crmid = $fieldvalue;
					$modulename = getSalesEntityType($crmid);
					if ($crmid != '' && $modulename != '') {
						$fieldvalues = getEntityName($modulename, array($crmid));
						
						// crmv@167855
						if($modulename == 'Potentials'){
							$fieldvalue = $adb->query_result($res,$j,'potentialname');
						} else {
							$fieldvalue = '<a href="index.php?module='.$modulename.'&action=index&id='.$crmid.'">'.$fieldvalues[$crmid].'</a>';
						}
						// crmv@167855e
					} else {
						$fieldvalue = '';
					}
				}
				if($module == 'Assets' && $fieldname == 'assetname') {
						$assetname = $fieldvalue;
						$assetid = $adb->query_result($res, $j, 'assetsid');
						$fieldvalue = '<a href="index.php?module=Assets&action=index&id='.$assetid.'">'.$assetname.'</a>';
				}
				if($fieldname == 'product' && $module == 'Assets'){
					$crmid= $adb->query_result($res,$j,'product');
					$fres = $adb->pquery('select '.$table_prefix.'_products.productname from '.$table_prefix.'_products where productid=?',array($crmid));
					$productname = $adb->query_result($fres,0,'productname');
					$fieldvalue = '<a href="index.php?module=Products&action=index&id='.$crmid.'">'.$productname.'</a>';
				}
				if($fieldname == 'smownerid'){
					$fieldvalue = getOwnerName($fieldvalue);
				}
				$output[1][$module]['data'][$j][$i]['fielddata'] = $fieldvalue;
				$i++;
			}
		}
		$log->debug("Exiting customer portal function get_list_values");
		return $output;

	}


	/**	function used to get the contents of a file
	*	@param int $id - customer ie., id
	*	return $filecontents array with single file contents like [fileid] => filecontent
	*/
	function get_filecontent_detail($id,$folderid,$module,$customerid,$sessionid)
	{
		global $adb,$log,$table_prefix;
		global $site_URL;
		$log->debug("Entering customer portal function get_filecontent_detail ");
		$isPermitted = $this->check_permission($customerid,$module,$id);
		if($isPermitted == false) {
			return array("#NOT AUTHORIZED#");
		}

		if(!$this->validateSession($customerid,$sessionid))
		return null;

		if($module == 'Documents')
		{
			$query="SELECT filetype FROM ".$table_prefix."_notes WHERE notesid =?";
			$res = $adb->pquery($query, array($id));
			$filetype = $adb->query_result($res, 0, "filetype");
			$this->updateDownloadCount($id);

			$fileidQuery = 'select attachmentsid from '.$table_prefix.'_seattachmentsrel where crmid = ?';
			$fileres = $adb->pquery($fileidQuery,array($id));
			$fileid = $adb->query_result($fileres,0,'attachmentsid');

			$filepathQuery = 'select path,name from '.$table_prefix.'_attachments where attachmentsid = ?';
			$fileres = $adb->pquery($filepathQuery,array($fileid));
			$filepath = $adb->query_result($fileres,0,'path');
			$filename = $adb->query_result($fileres,0,'name');
			$filename= decode_html($filename);

			$saved_filename =  $fileid."_".$filename;
			$filenamewithpath = $filepath.$saved_filename;
			$filesize = filesize($filenamewithpath );
		}
		else
		{
			$query ='select '.$table_prefix.'_attachments.*,'.$table_prefix.'_seattachmentsrel.* from '.$table_prefix.'_attachments inner join '.$table_prefix.'_seattachmentsrel on '.$table_prefix.'_seattachmentsrel.attachmentsid='.$table_prefix.'_attachments.attachmentsid where '.$table_prefix.'_seattachmentsrel.crmid =?';

			$res = $adb->pquery($query, array($id));

			$filename = $adb->query_result($res,0,'name');
			$filename = decode_html($filename);
			$filepath = $adb->query_result($res,0,'path');
			$fileid = $adb->query_result($res,0,'attachmentsid');
			$filesize = filesize($filepath.$fileid."_".$filename);
			$filetype = $adb->query_result($res,0,'type');
			$filenamewithpath=$filepath.$fileid.'_'.$filename;

		}
		$output[0]['fileid'] = $fileid;
		$output[0]['filename'] = $filename;
		$output[0]['filetype'] = $filetype;
		$output[0]['filesize'] = $filesize;
		$output[0]['filecontents']=base64_encode(file_get_contents($filenamewithpath));
		$log->debug("Exiting customer portal function get_filecontent_detail ");
		return $output;
	}

	/** Function that the client actually calls when a file is downloaded
	*
	*/
	function updateCount($id){
		global $adb,$log;
		$log->debug("Entering customer portal function updateCount");
		$result = $this->updateDownloadCount($id);
		$log->debug("Entering customer portal function updateCount");
		return $result;

	}

	/**
	* Function to update the download count of a file
	*/
	function updateDownloadCount($id){
		global $adb,$log,$table_prefix;
		$log->debug("Entering customer portal function updateDownloadCount");
		$updateDownloadCount = "UPDATE ".$table_prefix."_notes SET filedownloadcount = filedownloadcount+1 WHERE notesid = ?";
		$countres = $adb->pquery($updateDownloadCount,array($id));
		$log->debug("Entering customer portal function updateDownloadCount");
		return true;
	}

	/**	function used to get the salesorder name
	*	@param int $id -  id
	*	return string $name - Salesorder name returned
	*/

	function get_salesorder_name($id)
	{
		global $adb,$log,$table_prefix;
		$log->debug("Entering customer portal function get_salesorder_name");
		$res = $adb->pquery(" select subject from ".$table_prefix."_salesorder where salesorderid=?", array($id));
		$name=$adb->query_result($res,0,'subject');
		$log->debug("Exiting customer portal function get_salesorder_name");
		return $name;
	}

	function get_invoice_detail($id,$module,$customerid,$sessionid)
	{

		global $adb,$site_URL,$log,$current_user,$table_prefix;
		$log->debug("Entering customer portal function get_invoice_details $id - $module - $customerid - $sessionid");
		$user = CRMEntity::getInstance('Users');
		$userid = $this->getPortalUserid();
		$current_user = $user->retrieveCurrentUserInfoFromFile($userid);

		$isPermitted = $this->check_permission($customerid,$module,$id);
		if($isPermitted == false) {
			return array("#NOT AUTHORIZED#");
		}

		if(!$this->validateSession($customerid,$sessionid))
		return null;

		$fieldquery = "SELECT fieldname, columnname, fieldlabel,block,uitype FROM ".$table_prefix."_field WHERE tabid = ? AND displaytype in (1,2,4) ORDER BY block,sequence";
		$fieldres = $adb->pquery($fieldquery,array(getTabid($module)));
		$nooffields = $adb->num_rows($fieldres);
		$query = "select ".$table_prefix."_invoice.*,".$table_prefix."_crmentity.* ,".$table_prefix."_invoicebillads.*,".$table_prefix."_invoiceshipads.*,
			".$table_prefix."_invoicecf.* from ".$table_prefix."_invoice
			inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_invoice.invoiceid
			LEFT JOIN ".$table_prefix."_invoicebillads ON ".$table_prefix."_invoice.invoiceid = ".$table_prefix."_invoicebillads.invoicebilladdressid
			LEFT JOIN ".$table_prefix."_invoiceshipads ON ".$table_prefix."_invoice.invoiceid = ".$table_prefix."_invoiceshipads.invoiceshipaddressid
			INNER JOIN ".$table_prefix."_invoicecf ON ".$table_prefix."_invoice.invoiceid = ".$table_prefix."_invoicecf.invoiceid
			where ".$table_prefix."_invoice.invoiceid=?";
		$res = $adb->pquery($query, array($id));

		for($i=0;$i<$nooffields;$i++)
		{
			$fieldname = $adb->query_result($fieldres,$i,'columnname');
			$fieldlabel = getTranslatedString($adb->query_result($fieldres,$i,'fieldlabel'));

			$blockid = $adb->query_result($fieldres,$i,'block');
			$blocknameQuery = "select blocklabel from ".$table_prefix."_blocks where blockid = ?";
			$blockPquery = $adb->pquery($blocknameQuery,array($blockid));
			$blocklabel = $adb->query_result($blockPquery,0,'blocklabel');

			$fieldper = getFieldVisibilityPermission($module,$current_user->id,$fieldname);
			if($fieldper == '1'){
				continue;
			}

			$fieldvalue = $adb->query_result($res,0,$fieldname);
			/* crmv@40055
			if($fieldname == 'subject' && $fieldvalue !='')
			{
				$fieldid = $adb->query_result($res,0,'invoiceid');
				//$fieldlabel = "(Download PDF)  ".$fieldlabel;
				$fieldvalue = '<a href="index.php?downloadfile=true&module=Invoice&action=index&id='.$fieldid.'">'.$fieldvalue.'</a>';
			}
			*/
			if( $fieldname == 'salesorderid' || $fieldname == 'contactid' || $fieldname == 'accountid' || $fieldname == 'potentialid')
			{
				$crmid = $fieldvalue;
				$Entitymodule = getSalesEntityType($crmid);
				if ($crmid != '' && $Entitymodule != '') {
					$fieldvalues = getEntityName($Entitymodule, array($crmid));
					if($Entitymodule == 'Contacts')
					$fieldvalue = '<a href="index.php?module=Contacts&action=index&id='.$crmid.'">'.$fieldvalues[$crmid].'</a>';
					elseif($Entitymodule == 'Accounts')
					$fieldvalue = '<a href="index.php?module=Accounts&action=index&id='.$crmid.'">'.$fieldvalues[$crmid].'</a>';
					else
					$fieldvalue = $fieldvalues[$crmid];
				} else {
					$fieldvalue = '';
				}
			}
			if($fieldname == 'total'){
				$sym = $this->getCurrencySymbol($res,0,'currency_id');
				$fieldvalue = $sym.$fieldvalue;
			}
			if($fieldname == 'smownerid'){
				$fieldvalue = getOwnerName($fieldvalue);
			}
			$output[0][$module][$i]['fieldlabel'] = $fieldlabel;
			$output[0][$module][$i]['fieldvalue'] = $fieldvalue;
			$output[0][$module][$i]['blockname'] = getTranslatedString($blocklabel,$module);
		}
		$log->debug("Entering customer portal function get_invoice_detail ..");
		return $output;
	}

	/* Function to get contactid's and account's product details'
	*
	*/
	function get_product_list_values($id,$modulename,$sessionid,$only_mine='true')
	{

		global $current_user,$adb,$log,$table_prefix;
		$log->debug("Entering customer portal function get_product_list_values ..");
		$check = $this->checkModuleActive($modulename);
		if($check == false){
			return array("#MODULE INACTIVE#");
		}
		$user = CRMEntity::getInstance('Users');
		$userid = $this->getPortalUserid();
		$current_user = $user->retrieveCurrentUserInfoFromFile($userid);

		if(!$this->validateSession($id,$sessionid))
		return null;

		$entity_ids_list = $this->get_allowed_ids($id, $modulename, $only_mine); // crmv@173271
		
		$focus = CRMEntity::getInstance('Products');
		$focus->filterInactiveFields('Products');
		foreach ($focus->list_fields as $fieldlabel => $values){
			foreach($values as $table => $fieldname){
				$fields_list[$fieldlabel] = $fieldname;
			}
		}
		$fields_list['Related To'] = 'entityid';
		$query = array();
		$params = array();

		$query[] = "SELECT ".$table_prefix."_products.*,".$table_prefix."_seproductsrel.crmid as entityid, ".$table_prefix."_seproductsrel.setype FROM ".$table_prefix."_products
			INNER JOIN ".$table_prefix."_crmentity on ".$table_prefix."_products.productid = ".$table_prefix."_crmentity.crmid
			LEFT JOIN ".$table_prefix."_seproductsrel on ".$table_prefix."_seproductsrel.productid = ".$table_prefix."_products.productid
			WHERE ".$table_prefix."_seproductsrel.crmid in (". generateQuestionMarks($entity_ids_list).") and ".$table_prefix."_crmentity.deleted = 0 ";
		$params[] = array($entity_ids_list);

		$checkQuotes = $this->checkModuleActive('Quotes');
		if($checkQuotes == true){
			$query[] = "select distinct ".$table_prefix."_products.*,
				case when ".$table_prefix."_quotes.contactid is not null then ".$table_prefix."_quotes.contactid else ".$table_prefix."_quotes.accountid end as entityid,
				case when ".$table_prefix."_quotes.contactid is not null then 'Contacts' else 'Accounts' end as setype
				from ".$table_prefix."_quotes INNER join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_quotes.quoteid
				left join ".$table_prefix."_inventoryproductrel on ".$table_prefix."_inventoryproductrel.id=".$table_prefix."_quotes.quoteid
				left join ".$table_prefix."_products on ".$table_prefix."_products.productid = ".$table_prefix."_inventoryproductrel.productid
				where ".$table_prefix."_inventoryproductrel.productid = ".$table_prefix."_products.productid AND ".$table_prefix."_crmentity.deleted=0 and (accountid in  (". generateQuestionMarks($entity_ids_list) .") or contactid in (". generateQuestionMarks($entity_ids_list) ."))";
			$params[] = array($entity_ids_list,$entity_ids_list);
		}
		$checkInvoices = $this->checkModuleActive('Invoice');
		if($checkInvoices == true){
			$query[] = "select distinct ".$table_prefix."_products.*,
				case when ".$table_prefix."_invoice.contactid !=0 then ".$table_prefix."_invoice.contactid else ".$table_prefix."_invoice.accountid end as entityid,
				case when ".$table_prefix."_invoice.contactid !=0 then 'Contacts' else 'Accounts' end as setype
				from ".$table_prefix."_invoice
				INNER join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_invoice.invoiceid
				left join ".$table_prefix."_inventoryproductrel on ".$table_prefix."_inventoryproductrel.id=".$table_prefix."_invoice.invoiceid
				left join ".$table_prefix."_products on ".$table_prefix."_products.productid = ".$table_prefix."_inventoryproductrel.productid
				where ".$table_prefix."_inventoryproductrel.productid = ".$table_prefix."_products.productid AND ".$table_prefix."_crmentity.deleted=0 and (accountid in (". generateQuestionMarks($entity_ids_list) .") or contactid in  (". generateQuestionMarks($entity_ids_list) ."))";
			$params[] = array($entity_ids_list,$entity_ids_list);
		}
		for($k=0;$k<count($query);$k++)
		{
			$res[$k] = $adb->pquery($query[$k],$params[$k]);

			$noofdata[$k] = $adb->num_rows($res[$k]);
			if($noofdata[$k] == 0)
			$output[$k][$modulename]['data'] = '';
			for( $j= 0;$j < $noofdata[$k]; $j++)
			{
				$i=0;
				foreach($fields_list as $fieldlabel=> $fieldname) {
					$fieldper = getFieldVisibilityPermission('Products',$current_user->id,$fieldname);
					if($fieldper == '1' && $fieldname != 'entityid'){
						continue;
					}
					$output[$k][$modulename]['head'][0][$i]['fielddata'] = $fieldlabel;
					$fieldvalue = $adb->query_result($res[$k],$j,$fieldname);
					$fieldid = $adb->query_result($res[$k],$j,'productid');

					if($fieldname == 'entityid') {
						$crmid = $fieldvalue;
						$module = $adb->query_result($res[$k],$j,'setype');
						if ($crmid != '' && $module != '') {
							$fieldvalues = getEntityName($module, array($crmid));
							if($module == 'Contacts')
							$fieldvalue = '<a href="index.php?module=Contacts&action=index&id='.$crmid.'">'.$fieldvalues[$crmid].'</a>';
							elseif($module == 'Accounts')
							$fieldvalue = '<a href="index.php?module=Accounts&action=index&id='.$crmid.'">'.$fieldvalues[$crmid].'</a>';
						} else {
							$fieldvalue = '';
						}
					}

					if($fieldname == 'productname')
					$fieldvalue = '<a href="index.php?module=Products&action=index&productid='.$fieldid.'&id='.$fieldid.'">'.$fieldvalue.'</a>'; // crmv@173271

					if($fieldname == 'unit_price'){
						$sym = $this->getCurrencySymbol($res[$k],$j,'currency_id');
						$fieldvalue = $sym.$fieldvalue;
					}
					$output[$k][$modulename]['data'][$j][$i]['fielddata'] = $fieldvalue;
					$i++;
				}
			}
		}
		$log->debug("Exiting function get_product_list_values.....");
		return $output;
	}

	/*function used to get details of tickets,quotes,documents,Products,Contacts,Accounts
	*	@param int $id - id of quotes or invoice or notes
	*	return string $message - Account informations will be returned from :Accountdetails table
	*/
	function get_details($id,$module,$customerid,$sessionid,$language='')
	{
		global $adb,$log,$current_language,$default_language,$current_user,$table_prefix;

		$log->debug("Entering customer portal function get_details ..");

		$user = CRMEntity::getInstance('Users');
		$userid = $this->getPortalUserid();
		$current_user = $user->retrieveCurrentUserInfoFromFile($userid);

		(!empty($language)) ? $current_language = $language : $current_language = $default_language;

		$isPermitted = $this->check_permission($customerid,$module,$id);
		if($isPermitted == false && $module != 'Accounts') { // crmv@5946
			return array("#NOT AUTHORIZED#");
		}

		if($module != 'Accounts'){ // crmv@5946
			if(!$this->validateSession($customerid,$sessionid))
			return null;
		}	
			
		if($module == 'Quotes'){
			$query =  "SELECT
				".$table_prefix."_quotes.*,".$table_prefix."_crmentity.*,".$table_prefix."_quotesbillads.*,".$table_prefix."_quotesshipads.*,
				".$table_prefix."_quotescf.* FROM ".$table_prefix."_quotes
				INNER JOIN ".$table_prefix."_crmentity " .
					"ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_quotes.quoteid
				INNER JOIN ".$table_prefix."_quotesbillads
					ON ".$table_prefix."_quotes.quoteid = ".$table_prefix."_quotesbillads.quotebilladdressid
				INNER JOIN ".$table_prefix."_quotesshipads
					ON ".$table_prefix."_quotes.quoteid = ".$table_prefix."_quotesshipads.quoteshipaddressid
				LEFT JOIN ".$table_prefix."_quotescf
					ON ".$table_prefix."_quotes.quoteid = ".$table_prefix."_quotescf.quoteid
				WHERE ".$table_prefix."_quotes.quoteid=(". generateQuestionMarks($id) .") AND ".$table_prefix."_crmentity.deleted = 0";

		}
		else if($module == 'Documents'){
			//crmv@30967
			$query =  "SELECT
				".$table_prefix."_notes.*,".$table_prefix."_crmentity.*,".$table_prefix."_crmentityfolder.foldername
				FROM ".$table_prefix."_notes
				INNER JOIN ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_notes.notesid
				LEFT JOIN ".$table_prefix."_crmentityfolder
					ON ".$table_prefix."_notes.folderid = ".$table_prefix."_crmentityfolder.folderid
				where ".$table_prefix."_notes.notesid=(". generateQuestionMarks($id) .") AND ".$table_prefix."_crmentity.deleted=0";
			//crmv@30967e
		}
		else if($module == 'HelpDesk'){
			// crmv@150773
			$query ="SELECT
				".$table_prefix."_troubletickets.*,".$table_prefix."_crmentity.smownerid,".$table_prefix."_crmentity.createdtime,".$table_prefix."_crmentity.modifiedtime,
				".$table_prefix."_ticketcf.*  FROM ".$table_prefix."_troubletickets
				INNER JOIN ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_troubletickets.ticketid
				INNER JOIN ".$table_prefix."_ticketcf
					ON ".$table_prefix."_ticketcf.ticketid = ".$table_prefix."_troubletickets.ticketid
				WHERE (".$table_prefix."_troubletickets.ticketid=(". generateQuestionMarks($id) .") AND ".$table_prefix."_crmentity.deleted = 0)";
		}
		else if($module == 'Services'){
			$query ="SELECT ".$table_prefix."_service.*,".$table_prefix."_crmentity.*,".$table_prefix."_servicecf.*  FROM ".$table_prefix."_service
				INNER JOIN ".$table_prefix."_crmentity
					ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_service.serviceid AND ".$table_prefix."_crmentity.deleted = 0
				LEFT JOIN ".$table_prefix."_servicecf
					ON ".$table_prefix."_service.serviceid = ".$table_prefix."_servicecf.serviceid
				WHERE ".$table_prefix."_service.serviceid= (". generateQuestionMarks($id) .")";
		}
		else if($module == 'Contacts'){
			$query = "SELECT ".$table_prefix."_contactdetails.*,".$table_prefix."_contactaddress.*,".$table_prefix."_contactsubdetails.*,".$table_prefix."_contactscf.*" .
				" ,".$table_prefix."_crmentity.*,".$table_prefix."_customerdetails.*
				FROM ".$table_prefix."_contactdetails
				INNER JOIN ".$table_prefix."_crmentity
					ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_contactdetails.contactid
				INNER JOIN ".$table_prefix."_contactaddress
					ON ".$table_prefix."_contactaddress.contactaddressid = ".$table_prefix."_contactdetails.contactid
				INNER JOIN ".$table_prefix."_contactsubdetails
					ON ".$table_prefix."_contactsubdetails.contactsubscriptionid = ".$table_prefix."_contactdetails.contactid
				INNER JOIN ".$table_prefix."_contactscf
					ON ".$table_prefix."_contactscf.contactid = ".$table_prefix."_contactdetails.contactid
				LEFT JOIN ".$table_prefix."_customerdetails
					ON ".$table_prefix."_customerdetails.customerid = ".$table_prefix."_contactdetails.contactid
				WHERE ".$table_prefix."_contactdetails.contactid = (". generateQuestionMarks($id) .") AND ".$table_prefix."_crmentity.deleted = 0";
		}
		else if($module == 'Accounts'){
			$query = "SELECT ".$table_prefix."_account.*,".$table_prefix."_accountbillads.*,".$table_prefix."_accountshipads.*,".$table_prefix."_accountscf.*,
				".$table_prefix."_crmentity.* FROM ".$table_prefix."_account
				INNER JOIN ".$table_prefix."_crmentity
					ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_account.accountid
				INNER JOIN ".$table_prefix."_accountbillads
					ON ".$table_prefix."_account.accountid = ".$table_prefix."_accountbillads.accountaddressid
				INNER JOIN ".$table_prefix."_accountshipads
					ON ".$table_prefix."_account.accountid = ".$table_prefix."_accountshipads.accountaddressid
				INNER JOIN ".$table_prefix."_accountscf
					ON ".$table_prefix."_account.accountid = ".$table_prefix."_accountscf.accountid" .
			" WHERE ".$table_prefix."_account.accountid = (". generateQuestionMarks($id) .") AND ".$table_prefix."_crmentity.deleted = 0";
		}
		else if ($module == 'Products'){
			$query = "SELECT ".$table_prefix."_products.*,".$table_prefix."_productcf.*,".$table_prefix."_crmentity.* " .
			"FROM ".$table_prefix."_products " .
			"INNER JOIN ".$table_prefix."_crmentity " .
				"ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_products.productid " .
			"LEFT JOIN ".$table_prefix."_productcf " .
				"ON ".$table_prefix."_productcf.productid = ".$table_prefix."_products.productid " .
			"LEFT JOIN ".$table_prefix."_vendor
				ON ".$table_prefix."_vendor.vendorid = ".$table_prefix."_products.vendor_id
			LEFT JOIN ".$table_prefix."_users
				ON ".$table_prefix."_users.id = ".$table_prefix."_products.handler " .
			"WHERE ".$table_prefix."_products.productid = (". generateQuestionMarks($id) .") AND ".$table_prefix."_crmentity.deleted = 0";
		} else if($module == 'Assets') {
			$query = "SELECT ".$table_prefix."_assets.*, ".$table_prefix."_assetscf.*, ".$table_prefix."_crmentity.*
			FROM ".$table_prefix."_assets
			INNER JOIN ".$table_prefix."_crmentity
			ON ".$table_prefix."_assets.assetsid = ".$table_prefix."_crmentity.crmid
			INNER JOIN ".$table_prefix."_assetscf
			ON ".$table_prefix."_assets.assetsid = ".$table_prefix."_assetscf.assetsid
			WHERE ".$table_prefix."_crmentity.deleted = 0 AND ".$table_prefix."_assets.assetsid = (". generateQuestionMarks($id) .")";
		} else if ($module == 'ProjectPlan') {
			$query = "SELECT ".$table_prefix."_project.*, ".$table_prefix."_projectcf.*, ".$table_prefix."_crmentity.*
						FROM ".$table_prefix."_project
						INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_project.projectid
						LEFT JOIN ".$table_prefix."_projectcf ON ".$table_prefix."_projectcf.projectid = ".$table_prefix."_project.projectid
						WHERE ".$table_prefix."_project.projectid = ? AND ".$table_prefix."_crmentity.deleted = 0";
		//crmv@128933
		} else if ($module == 'ProjectTask') {
			$query = "SELECT ".$table_prefix."_projecttask.*, ".$table_prefix."_projecttaskcf.*, ".$table_prefix."_crmentity.*
						FROM ".$table_prefix."_projecttask
						INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_projecttask.projecttaskid
						LEFT JOIN ".$table_prefix."_projecttaskcf ON ".$table_prefix."_projecttaskcf.projecttaskid = ".$table_prefix."_projecttask.projecttaskid
						WHERE ".$table_prefix."_projecttask.projecttaskid = ? AND ".$table_prefix."_crmentity.deleted = 0";
		} else if ($module == 'ProjectMilestone') {
			$query = "SELECT ".$table_prefix."_projectmilestone.*, ".$table_prefix."_projectmilestonecf.*, ".$table_prefix."_crmentity.*
						FROM ".$table_prefix."_projectmilestone
						INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_projectmilestone.projectmilestoneid
						LEFT JOIN ".$table_prefix."_projectmilestonecf ON ".$table_prefix."_projectmilestonecf.projectmilestoneid = ".$table_prefix."_projectmilestone.projectmilestoneid
						WHERE ".$table_prefix."_projectmilestone.projectmilestoneid = ? AND ".$table_prefix."_crmentity.deleted = 0";
		//crmv@128933e
		} else if($module == 'Potentials') {
			// crmv@5946
			$query = "SELECT * 
						FROM {$table_prefix}_potential 
						INNER JOIN {$table_prefix}_crmentity ON crmid = potentialid
						WHERE deleted = 0 AND potentialid = ?";
			// crmv@5946e
		// crmv@167855
		} else {
			$queryGen = QueryGenerator::getInstance($module, $current_user);
			$queryGen->initForAllCustomView();
			$queryGen->appendToWhereClause("AND {$table_prefix}_crmentity.crmid = ?");
			$query = $queryGen->getQuery();
			$query = preg_replace('/^SELECT.*?FROM/i', "SELECT * FROM", trim($query));
		}
		// crmv@167855e

		$params = array($id);
		$res = $adb->pquery($query,$params);

		// crmv@167855
		$fieldquery = "SELECT fieldid, fieldname,columnname,fieldlabel,blocklabel,uitype FROM ".$table_prefix."_field
			INNER JOIN  ".$table_prefix."_blocks on ".$table_prefix."_blocks.blockid=".$table_prefix."_field.block
			WHERE ".$table_prefix."_field.presence IN (0,2) AND ".$table_prefix."_field.tabid = ? AND displaytype in (1,2,4)
			ORDER BY ".$table_prefix."_field.block,".$table_prefix."_field.sequence";
		// crmv@167855e

		$fieldres = $adb->pquery($fieldquery,array(getTabid($module)));
		$nooffields = $adb->num_rows($fieldres);
		
		// Dummy instance to make sure column fields are initialized for futher processing
		$focus = CRMEntity::getInstance($module);
		
		// crmv@167855
		if (vtlib_isModuleActive('Conditionals')){
			$focus->retrieve_entity_info($id, $module);
			$col_fields = $focus->column_fields;
			
			$conditionals_obj = CRMEntity::getInstance('Conditionals');
			$conditionals_obj->Initialize($module,getTabid($module),$col_fields);
		}
		// crmv@167855e

		for($i=0;$i<$nooffields;$i++)
		{
			$columnname = $adb->query_result($fieldres,$i,'columnname');
			$fieldname = $adb->query_result($fieldres,$i,'fieldname');
			$fieldid = $adb->query_result($fieldres,$i,'fieldid');
			$blockid = $adb->query_result($fieldres,$i,'block');
			$uitype = $adb->query_result($fieldres,$i,'uitype');
			
			// crmv@167855
			if ($conditionals_obj){
				if (is_array($conditionals_obj->permissions[$fieldid])){
					if ($conditionals_obj->permissions[$fieldid]["f2fp_visible"] == 0) {
						continue;
					} elseif ($conditionals_obj->permissions[$fieldid]["f2fp_editable"] == 0) {
						// do nothing, in portal everything is readonly
					}
					if ($conditionals_obj->permissions[$fieldid]["f2fp_mandatory"] == 1) {
						// do nothing, in portal everything is readonly
					}
				}
			}
			// crmv@167855e

			$blocklabel = $adb->query_result($fieldres,$i,'blocklabel');
			$blockname = getTranslatedString($blocklabel,$module);
			if($blocklabel == 'LBL_COMMENTS' || $blocklabel == 'LBL_IMAGE_INFORMATION'){ // the comments block of tickets is hardcoded in customer portal,get_ticket_comments is used for it
				continue;
			}
			if($uitype == 83){ //for taxclass in products and services
				continue;
			}
			$fieldper = getFieldVisibilityPermission($module,$current_user->id,$fieldname);
			if($fieldper == '1'){
				continue;
			}

			$fieldlabel = getTranslatedString($adb->query_result($fieldres,$i,'fieldlabel'),$module);
			$fieldvalue = $adb->query_result($res,0,$columnname);

			$output[0][$module][$fieldname]['fieldlabel'] = $fieldlabel ;
			$output[0][$module][$fieldname]['fieldname'] = $fieldname ; //crmv@57342
			$output[0][$module][$fieldname]['blockname'] = $blockname;

			if($columnname == 'parent_id' || $columnname == 'contactid' || $columnname == 'accountid' || $columnname == 'potentialid'
				|| $fieldname == 'account_id' || $fieldname == 'contact_id' || $columnname == 'linktoaccountscontacts' || $uitype == 10)	//crmv@128933
			{
				$crmid = $fieldvalue;
				$modulename = getSalesEntityType($crmid);
				if ($crmid != '' && $modulename != '') {
					$fieldvalues = getEntityName($modulename, array($crmid));
					if($modulename == 'Contacts')
					$fieldvalue = '<a href="index.php?module=Contacts&action=index&id='.$crmid.'">'.$fieldvalues[$crmid].'</a>';
					elseif($modulename == 'Accounts')
					$fieldvalue = '<a href="index.php?module=Accounts&action=index&id='.$crmid.'">'.$fieldvalues[$crmid].'</a>';
					else
					$fieldvalue = $fieldvalues[$crmid];
				} else {
					$fieldvalue = '';
				}
			}

			if($module=='Quotes')
			{
				/* crmv@40055
				if($fieldname == 'subject' && $fieldvalue !=''){
					$fieldid = $adb->query_result($res,0,'quoteid');
					$fieldvalue = '<a href="index.php?downloadfile=true&module=Quotes&action=index&id='.$fieldid.'">'.$fieldvalue.'</a>';
				}
				*/
				if($fieldname == 'total'){
					$sym = $this->getCurrencySymbol($res,0,'currency_id');
					$fieldvalue = $sym.$fieldvalue;
				}
			}
			if($module == 'Documents')
			{
				$fieldid = $adb->query_result($res,0,'notesid');
				$filename = $fieldvalue;
				$folderid = $adb->query_result($res,0,'folderid');
				$filestatus = $adb->query_result($res,0,'filestatus');
				$filetype = $adb->query_result($res,0,'filelocationtype');
				if($fieldname == 'filename'){
					if($filestatus == 1){
						if($filetype == 'I' || $filetype == 'B'){
							$fieldvalue = '<a href="index.php?downloadfile=true&folderid='.$folderid.'&filename='.$filename.'&module=Documents&action=index&id='.$fieldid.'" >'.$fieldvalue.'</a>';
						}
						elseif($filetype == 'E'){
							$fieldvalue = '<a target="_blank" href="'.$filename.'" onclick = "updateCount('.$fieldid.');">'.$filename.'</a>';
						}
					}
				}
				if($fieldname == 'folderid'){
					$fieldvalue = $adb->query_result($res,0,'foldername');
				}
				if($fieldname == 'filesize'){
					if($filetype == 'I' || $filetype == 'B'){
						$fieldvalue = $fieldvalue .' B';
					}
					elseif($filetype == 'E'){
						$fieldvalue = '--';
					}
				}
				if($fieldname == 'filelocationtype'){
					if($fieldvalue == 'I' || $fieldvalue == 'B'){
						$fieldvalue = getTranslatedString('LBL_INTERNAL',$module);
					}elseif($fieldvalue == 'E'){
						$fieldvalue = getTranslatedString('LBL_EXTERNAL',$module);
					}else{
						$fieldvalue = '---';
					}
				}
			}
			if($columnname == 'product_id' && $fieldvalue != '0' && !empty($fieldvalue)) {	//crmv@26277 crmv@169840
				$fieldvalues = getEntityName('Products', array($fieldvalue));
				$fieldvalue = '<a href="index.php?module=Products&action=index&productid='.$fieldvalue.'">'.$fieldvalues[$fieldvalue].'</a>';
			}
			if($module == 'Products'){
				if($fieldname == 'vendor_id'){
					$fieldvalue = $this->get_vendor_name($fieldvalue);
				}
			}
			if($module == 'Assets' ){
				if($fieldname == 'account'){
					$accountid = $adb->query_result($res,0,'account');
					$accountres = $adb->pquery("select ".$table_prefix."_account.accountname from ".$table_prefix."_account where accountid=?",array($accountid));
					$accountname = $adb->query_result($accountres,0,'accountname');
					$fieldvalue = $accountname;
				}
				if($fieldname == 'product'){
					$productid = $adb->query_result($res,0,'product');
					$productres = $adb->pquery("select ".$table_prefix."_products.productname from ".$table_prefix."_products where productid=?",array($productid));
					$productname = $adb->query_result($productres,0,'productname');
					$fieldvalue = $productname;
				}
				if($fieldname == 'invoiceid'){
					$invoiceid = $adb->query_result($res,0,'invoiceid');
					$invoiceres = $adb->pquery("select ".$table_prefix."_invoice.subject from ".$table_prefix."_invoice where invoiceid=?",array($invoiceid));
					$invoicename = $adb->query_result($invoiceres,0,'subject');
					$fieldvalue = $invoicename;
				}
			}
			if($module == 'Potentials' ){ // crmv@5946
				if($fieldname == 'related_to'){
					$related_to = $adb->query_result($res,0,'related_to');
					$fieldvalue = getContactName($related_to);
				}
				
			} // crmv@5946e
			if($fieldname == 'assigned_user_id' || $fieldname == 'assigned_user_id1' || $fieldname == 'creator'){	//crmv@128933
				$fieldvalue = getOwnerName($fieldvalue);
			}
			if($uitype == 56){
				if($fieldvalue == 1){
					$fieldvalue = 'Yes';
				}else{
					$fieldvalue = 'No';
				}
			}
			if($module == 'HelpDesk' && $fieldname == 'ticketstatus'){
				$parentid = $adb->query_result($res,0,'parent_id');
				$status = $adb->query_result($res,0,'status');
				//crmv@91545
				if($customerid != $parentid ){ //allow only the owner to delete the ticket
					$closebutton = false;
				}else{
					$closebutton = true;
				}
				$fieldvalue = $status; 
				$output[0][$module][$fieldname]['closebutton'] = $closebutton;
				//crmv@91545e		
			}
			if($fieldname == 'unit_price'){
				$sym = $this->getCurrencySymbol($res,0,'currency_id');
				$fieldvalue = $sym.$fieldvalue;
			}
			if($uitype == 19 || $uitype == 21){ // description fields
				$fieldvalue = nl2br($fieldvalue);
			}
			$output[0][$module][$fieldname]['fieldvalue'] = $fieldvalue;
		}
		
		if($module == 'HelpDesk'){
			$ticketid = $adb->query_result($res,0,'ticketid');
			$sc_info = $this->getRelatedServiceContracts($ticketid);
			if (!empty($sc_info)) {
				$modulename = 'ServiceContracts';
				$blocklable = getTranslatedString('LBL_SERVICE_CONTRACT_INFORMATION',$modulename);
				$j=$i;
				for($k=0;$k<count($sc_info);$k++){
					foreach ($sc_info[$k] as $label => $value) {
						$output[0][$module][$j]['fieldlabel']= getTranslatedString($label,$modulename);
						$output[0][$module][$j]['fieldvalue']= $value;
						$output[0][$module][$j]['blockname'] = $blocklable;
						$j++;
					}
				}
			}
		}
		$log->debug("Existing customer portal function get_details ..");
		return $output;
	}
	
	// crmv@173271
	
	/**
	 * Generic function to retrieve a record to be displayed in detail or edit
	 */
	public function get_record($customerid, $sessionid, $module, $id, $language = '') {
		global $current_user, $current_language, $default_language;
		
		$return = array('success' => false);
		
		if (!$current_user) {
			$userid = $this->getPortalUserid();
			$user = CRMEntity::getInstance('Users');
			$current_user = $user->retrieveCurrentUserInfoFromFile($userid);
		}
		
		(!empty($language)) ? $current_language = $language : $current_language = $default_language;
		
		if (!$this->validateSession($customerid,$sessionid)) {
			$return['message'] = 'Invalid session ID';
			$return['code'] = 'INVALID_SESSION';
			return $return;
		}
		
		if (!$this->check_permission($customerid,$module,$id)) {
			$return['message'] = 'Not authorized';
			$return['code'] = 'NOT_AUTHORIZED';
			return $return;
		}
		
		$focus = CRMEntity::getInstance($module);
		$r = $focus->retrieve_entity_info_no_html($id, $module, false);
		
		if ($r || !$focus->id) {
			$return['message'] = 'Unable to retrieve the chosen record';
			$return['code'] = 'BAD_RECORD';
			return $return;
		}
		
		$values = $this->process_retrieved_record($module, $id, $focus->column_fields);
		
		$return['success'] = true;
		$return['values'] = $values;
		
		return $return;
	}
	
	protected function process_retrieved_record($module, $crmid, $values) {
		global $adb, $table_prefix;
		
		$tabid = getTabid($module);
		
		// crmv@167855
		if (vtlib_isModuleActive('Conditionals')){
			$conditionals_obj = CRMEntity::getInstance('Conditionals');
			$conditionals_obj->Initialize($module,$tabid,$values);
		}
		// crmv@167855e
		
		// get all fields
		// crmv@167855
		$fieldquery = 
			"SELECT fieldid, fieldname,columnname,fieldlabel,blocklabel,uitype 
			FROM {$table_prefix}_field f
			INNER JOIN  {$table_prefix}_blocks b on b.blockid = f.block
			WHERE f.presence IN (0,2) AND f.tabid = ? AND displaytype in (1,2,4)
			ORDER BY b.sequence, f.sequence";
		// crmv@167855e
		$fieldres = $adb->pquery($fieldquery,array($tabid));
		while ($row = $adb->fetchByAssoc($fieldres, -1, false)) {
			$fieldid = $row['fieldid'];
			$fieldname = $row['fieldname'];
			$uitype = $row['uitype'];
			
			// crmv@167855
			if ($conditionals_obj){
				if (is_array($conditionals_obj->permissions[$fieldid])){
					if ($conditionals_obj->permissions[$fieldid]["f2fp_visible"] == 0) {
						continue;
					} elseif ($conditionals_obj->permissions[$fieldid]["f2fp_editable"] == 0) {
						// do nothing, in portal everything is readonly
					}
					if ($conditionals_obj->permissions[$fieldid]["f2fp_mandatory"] == 1) {
						// do nothing, in portal everything is readonly
					}
				}
			}
			// crmv@167855e
			
			$rawvalue = $values[$fieldname];
			// TODO: finire
			$value = array(
				'value' => $rawvalue
			);
			$values[$fieldname] = $value;
		}
		
		return $values;
	}
	
	/**
	 * Return the fields' structure (ws format) for the specified module
	 */
	public function get_fields_structure($customerid, $module, $id = 0, $language = '') {
		global $adb, $table_prefix, $current_user, $current_language, $default_language;
		
		if (!$current_user) {
			$userid = $this->getPortalUserid();
			$user = CRMEntity::getInstance('Users');
			$current_user = $user->retrieveCurrentUserInfoFromFile($userid);
		}
		
		(!empty($language)) ? $current_language = $language : $current_language = $default_language;
		
		$struct = array();
		
		$modinfo = vtws_describe($module, $current_user);
		
		if (vtlib_isModuleActive('Conditionals') && $id > 0) {
			$cond = CRMEntity::getInstance('Conditionals');
			$focusCond = CRMEntity::getInstance($module);
			$focusCond->retrieve_entity_info($id, $module);
			$cond->Initialize($module, getTabid($module), $focusCond->column_fields);
		}
		
		$RM = RelationManager::getInstance();
		
		if (is_array($modinfo['fields'])) {
			foreach ($modinfo['fields'] as $finfo) {
				$fieldname = $finfo['name'];
				
				// apply conditionals
				if ($cond) {
					$perm = $cond->permissions[$finfo['fieldid']];
					if ($perm) {
						if ($perm['f2fp_visible'] == 0) {
							continue;
						} elseif ($perm['f2fp_editable'] == 0) {
							$finfo['editable'] = false;
						} else {
							$finfo['editable'] = true;
							if ($perm['f2fp_mandatory']) {
								$finfo['mandatory'] = true;
							}
						}
					}
				}
				
				// manually remove some other fields
				if (in_array($fieldname, array('id', 'assigned_user_id'))) continue;
				
				// crmv@182817
				if ($finfo['type']['name'] == 'picklist' && $finfo['uitype'] == 300) {
					$finfo['uitype'] = 15;
					unset($finfo['type']['linkedPicklistDetails']);
				}
				// crmv@182817e
				
				$struct[] = $finfo;
			}
		}
		
		// sort by block,sequence (not perfect, but better than nothing)
		usort($struct, function($a, $b) {
			return ($a['sequence'] < $b['sequence'] ? -1 : ($a['sequence'] > $b['sequence'] ? +1 :  0));
		});
		usort($struct, function($a, $b) {
			return ($a['blockid'] < $b['blockid'] ? -1 : ($a['blockid'] > $b['blockid'] ? +1 :  0));
		});
		
		return $struct;
	}
	
	public function getAttachmentsFolder() {
		$folderInfo = getEntityFoldersByName('Portal attachments', 'Documents');
		if (empty($folderInfo)) {
			$folderid = addEntityFolder('Documents', 'Portal attachments');
		} else {
			$folderid = $folderInfo[0]['folderid'];
		}
		
		return $folderid;
	}
	
	/**
	 * Update a generic record
	 */
	public function update_record($customerid, $module, $id, $fields, $files = array(), $usePermissions = true) {
		global $adb, $table_prefix;
		global $current_user;
		
		if (!$current_user) {
			$userid = $this->getPortalUserid();
			$user = CRMEntity::getInstance('Users');
			$current_user = $user->retrieveCurrentUserInfoFromFile($userid);
		}
		
		$return = array('success' => false);
		
		if ($usePermissions) {
		
			// check if permitted (for the specific customer)
			/*if (!$this->is_edit_permitted($customerid, $module, $id)) {
				$return['message'] = 'User is not authorized to edit this record';
				$return['code'] = 'NOT_AUTHORIZED';
				return $return;
			}*/
			
		} else {
			// become admin if permissions aren't checked
			$oldCurrentUser = $current_user;
			$user = CRMEntity::getInstance('Users');
			$current_user = Users::getActiveAdminUser();
		}
		
		// check empty fields
		$struct = $this->get_fields_structure($customerid, $module, $id);
		foreach ($struct as $finfo) {
			$fieldname = $finfo['name'];
			if (isset($fields[$fieldname])) {
				$emptyval = ($fields[$fieldname] === '' || $fields[$fieldname] === null);
				if ($finfo['mandatory'] && $emptyval) {
					$return['message'] = 'Missing mandatory field: '.$finfo['label'];
					$return['code'] = 'MISSING_FIELDS';
					return $return;
				}
			}
		}
		
		$focus = CRMEntity::getInstance($module);
		$r = $focus->retrieve_entity_info_no_html($id, $module, false);
		
		if ($r || !$focus->id) {
			$return['message'] = 'Unable to retrieve the chosen record';
			$return['code'] = 'BAD_RECORD';
			return $return;
		}
		
		// sanitize fields
		foreach ($struct as $finfo) {
			if (array_key_exists($finfo['name'], $fields)) {
				$value = $fields[$finfo['name']];
				if ($finfo['type']['name'] == 'date') {
					if ($value != '' && !preg_match('/^[0-9]{4}-[01][0-9]-[0-3][0-9]$/', $value)) {
						$fields[$finfo['name']] = '0000-00-00';
					}
				}
				// TODO: other types
			}
		}
		
		// TODO: more data transformation

		// check for file upload
		if (count($files) > 0 && is_array($struct)) {
			
			$folderid = $this->getAttachmentsFolder();
		
			foreach ($struct as $finfo) {
				$filefield = $finfo['name'];
				if (array_key_exists($filefield, $files)) {
					
					$fileinfo = $files[$filefield];
					if ($finfo['uitype'] == 209) {
						
						$name = $fileinfo['original_name'];
						
						// it used to be serialized in soap, but it requires too much memory
						// so we assume the portal is in the same host as the vte and pass
						// the file on the local FS.
						/*$tmpdir = $this->tempdir();
						$tmpfile = $tmpdir.'/'.$name;
						$r = file_put_contents($tmpfile, base64_decode($fileinfo['data']));
						*/
						$tmpfile = $fileinfo['path'];
						/*if ($r === false) {
							$result['message'] = 'Unable to upload image '.$fileinfo['original_name'];
							//return $result;
							continue;
						}*/
						$focusDoc = CRMEntity::getInstance('Documents');
						$docid = $focusDoc->createDocumentFromPathFile($tmpfile, $folderid, $id);
						//@unlink($tmpfile);
						//rmdir($tmpdir);
						// now get the attachment
						$SBU = StorageBackendUtils::getInstance();
						$atts = $SBU->getAttachments('Documents', $docid);
						$bkey = $atts[0]['backend_key'];
						list($xx, $path) = explode(':', $bkey, 2);
						// set the field
						$fields[$filefield] = $path;
					} elseif ($module == 'Documents' && $finfo['type']['name'] == 'file' && $finfo['name'] == 'filename') {
						// standard upload, document, simulate upload
						
						$name = $fileinfo['original_name'];
						
						require_once('modules/Documents/storage/StorageBackendUtils.php');
						require_once('modules/Settings/MailScanner/core/MailAttachmentMIME.php');
						
						$SBU = StorageBackendUtils::getInstance();
						
						$_FILES = array();
						$_FILES[$fieldname] = array(
							'name' => $name,
							'size' => filesize($fileinfo['path']),
							'type' => MailAttachmentMIME::detect($fileinfo['path']),
							'tmp_name' => $fileinfo['path'],
						);
						$_POST['copy_not_move'] = true;
						
						$fields['filename']         = $name;
						$fields['filestatus']       = 1;
						$fields['filelocationtype'] = 'B';
						$fields['backend_name'] = $SBU->defaultBackend;
					}
				}
			}
		}
		
		if (count($fields) > 0) {
			$_REQUEST['ajxaction'] = 'DETAILVIEW'; // avoid destroying products
			$focus->mode = 'edit';
			$focus->column_fields = array_replace($focus->column_fields, $fields);
			try {
				$focus->save($module);
				$return['success'] = true;
			} catch (Exception $e) {
				$return['message'] = 'Unable to save record';
				$return['code'] = 'SAVE_FAILED';
				
			}
		}
		
		// restore user
		if (!$usePermissions && $oldCurrentUser) {
			$current_user = $oldCurrentUser;
		}
		
		return $return;
	}
	
	/**
	 * Delete a record. Since this is never allowed, this function always fail
	 */
	public function delete_record($customerid, $module, $id) {
		global $current_user;
		
		if (!$current_user) {
			$userid = $this->getPortalUserid();
			$user = CRMEntity::getInstance('Users');
			$current_user = $user->retrieveCurrentUserInfoFromFile($userid);
		}
		
		$return = array('success' => false);
		
		// check permission!
		if (!$this->is_delete_permitted($customerid, $module, $id)) {
			$return['message'] = 'Not authorized';
			$return['code'] = 'NOT_AUTHORIZED';
			return $return;
		}
		
		$focus = CRMEntity::getInstance($module);
		$focus->trash($module, $id);
		
		$return['success'] = true;
		
		return $return;
	}

	/**
	 * Get read/write permissions for every accessible module
	 */
	public function get_modules_permissions($customerid) {
		global $current_user;
		
		$perm = array();

		if (!$current_user) {
			$userid = $this->getPortalUserid();
			$user = CRMEntity::getInstance('Users');
			$current_user = $user->retrieveCurrentUserInfoFromFile($userid);
		}
	
		$modules = $this->get_modules();
		foreach ($modules as $mod) {
			$perm[$mod] = array(
				'perm_read' => (isPermitted($mod, 'DetailView') === 'yes'),
				'perm_write' => ($mod == 'HelpDesk'), // should use isPermitted, but for compatibility, we need to allow generic write
				'perm_delete' => false,
			);
		}
		
		// but you can change the logic
		
		return $perm;
	}
	
	/**
	 * Return true if the user is allowed to edit the record.
	 * In general this is never permitted, except for the user's contact and visible tickets
	 */
	public function is_edit_permitted($customerid, $module, $id) {
		global $adb, $table_prefix, $current_user;
		
		if (!$current_user) {
			$userid = $this->getPortalUserid();
			$user = CRMEntity::getInstance('Users');
			$current_user = $user->retrieveCurrentUserInfoFromFile($userid);
		}
		
		if ($module == 'Contacts') {
			$perm = ($id == $customerid);
		} elseif ($module == 'HelpDesk') {
			$perm = $this->check_permission($customerid, $module, $id);
		} else {
			$perm = false;
		}
		
		// but here you can add more logic!
		
		return $perm;
	}
	
	/**
	 * Return if a specific record can be deleted. Always false
	 */
	public function is_delete_permitted($customerid, $module, $id) {
		global $adb, $table_prefix, $current_user;
		
		if (!$current_user) {
			$userid = $this->getPortalUserid();
			$user = CRMEntity::getInstance('Users');
			$current_user = $user->retrieveCurrentUserInfoFromFile($userid);
		}
		
		$perm = false;
		
		// but here you can add more logic!
		
		return $perm;
	}
	
	/**
	 * Get all the accessible contacts or accounts for the user
	 */
	protected function get_allowed_ids($customerid, $module, $only_mine = 'true') {
		global $adb, $table_prefix;
		
		$show_all= $this->show_all($module);
		
		$allowed_ids = array();
		$allowed_ids[] = $customerid; // I am always visible
		
		if ($show_all != 'false' && $only_mine != 'true') {
		
			$accountid = $this->get_check_account_id($customerid);
			if ($accountid > 0) {
				$allowed_ids[] = $accountid; // add my account
			
				// and add contacts from my account
				$contactquery = "SELECT co.contactid 
					FROM {$table_prefix}_contactdetails co
					INNER JOIN {$table_prefix}_crmentity c ON c.crmid = co.contactid AND c.deleted = 0 
					WHERE accountid = ? AND co.contactid != ?";
				$res = $adb->pquery($contactquery, array($accountid, $customerid));
				while ($row = $adb->fetchByAssoc($res, -1, false)) {
					$allowed_ids[] = $row['contactid'];
				}
			}
		}
		
		return $allowed_ids;
	}
	
	/* Function to check the permission if the customer can see the recorde details
	* @params $customerid :: INT contact's Id
	* 			$module :: String modulename
	* 			$entityid :: INT Records Id
	*/
	public function check_permission($customerid, $module, $entityid) {
		global $adb,$log,$table_prefix;
		
		$log->debug("Entering customer portal function check_permission ..");

		// contacts module is always permitted, so it can be hidden from the list
		if ($module == 'Contacts') return true; // crmv@114615

		$check = $this->checkModuleActive($module);
		if (!$check) return false;

		$allowed_contacts_and_accounts = $this->get_allowed_ids($customerid, $module, 'false');

		// for contacts/accounts, if they are present in the allowed list then send true
		if (in_array($entityid, $allowed_contacts_and_accounts)) {
			return true;
		}
		
		$checkModule = $module;
		$checkId = $entityid;
		$checkRelated = $allowed_contacts_and_accounts;
		$checkRelModules = array('Contacts', 'Accounts');
				
		$RM = RelationManager::getInstance();
		switch ($module) {
			case 'Accounts':
				// allow only my account
				$accountid = $this->get_check_account_id($customerid);
				return ($accountid == $entityid);
				break;
			case 'Potentials':
				// always permitted... why ??
				return true;
			case 'Documents':
				// linked to ANY faq (why??)
				$query = 
					"SELECT snr.notesid 
					FROM {$table_prefix}_notes n
					INNER JOIN {$table_prefix}_senotesrel snr ON snr.notesid = n.notesid
					INNER JOIN {$table_prefix}_crmentity c ON c.crmid = n.notesid AND c.deleted = 0
					WHERE snr.crmid IN (SELECT id FROM {$table_prefix}_faq)
					AND n.notesid = ? AND n.active_portal = 1";
				$res = $adb->limitpQuery($query, 0, 1, array($entityid));
				if ($adb->num_rows($res) > 0) return true;
				
				// or directly linked or linked to a visible projectplan
				$linkedMods = array();
				if ($this->checkModuleActive('ProjectPlan')) $linkedMods[] = 'ProjectPlan';
				if (count($linkedMods) > 0) {
					$moreids = array();
					foreach ($allowed_contacts_and_accounts as $id) {
						$mod = getSalesEntityType($id);
						if ($mod) {
							$ids = $RM->getRelatedIds($mod, $id, $linkedMods);
							$moreids = array_merge($moreids, $ids);
						}
					}
					if (count($moreids) > 0) {
						$checkRelated = array_merge($checkRelated, $moreids);
						$checkRelModules = array_merge($checkRelModules, $linkedMods);
					}
				}
				break;
			case 'Products':
			case 'Services':
				// directly linked or linked to a visible invoice or quote
				$linkedMods = array();
				if ($this->checkModuleActive('Quotes')) $linkedMods[] = 'Quotes';
				if ($this->checkModuleActive('Invoice')) $linkedMods[] = 'Invoice';
				if (count($linkedMods) > 0) {
					$moreids = array();
					foreach ($allowed_contacts_and_accounts as $id) {
						$mod = getSalesEntityType($id);
						if ($mod) {
							$ids = $RM->getRelatedIds($mod, $id, $linkedMods);
							$moreids = array_merge($moreids, $ids);
						}
					}
					if (count($moreids) > 0) {
						$checkRelated = array_merge($checkRelated, $moreids);
						$checkRelModules = array_merge($checkRelModules, $linkedMods);
					}
				}
				break;
			case 'ProjectMilestone':
			case 'ProjectTask':
				// fallback on projectplan
				$ids = $RM->getRelatedIds($module, $entityid, array('ProjectPlan'));
				$checkModule = 'ProjectPlan';
				$checkId = $ids[0];
				break;
			default:
				break;
		}
		
		// in general, I can access the record if it's directly linked to the contact or the account
		if ($checkId > 0) {
			$relids = $RM->getRelatedIds($checkModule, $checkId, $checkRelModules);
			$int = array_intersect($relids, $checkRelated);
			if (count($int) > 0) return true;
		}

		$log->debug("Exiting customerportal function check_permission ..");
		return false;
	}
	// crmv@173271e

	/* Function to get related Documents for faq
	*  @params $id :: INT parent's Id
	* 			$module :: String modulename
	* 			$customerid :: INT contact's Id'
	*/
	function get_documents($id,$module,$customerid,$sessionid)
	{
		global $adb,$log,$table_prefix;
		$log->debug("Entering customer portal function get_documents ..");
		$check = $this->checkModuleActive($module);
		if($check == false){
			return array("#MODULE INACTIVE#");
		}
		$fields_list = array(
		'title' => 'Title',
		'filename' => 'FileName',
		'createdtime' => 'Created Time');

		if(!$this->validateSession($customerid,$sessionid))
		return null;

		$query ="select ".$table_prefix."_notes.title,'Documents' ActivityType, ".$table_prefix."_notes.filename,
			crm2.createdtime,".$table_prefix."_notes.notesid,".$table_prefix."_notes.folderid,
			".$table_prefix."_notes.notecontent description, ".$table_prefix."_users.user_name, ".$table_prefix."_notes.filelocationtype
			from ".$table_prefix."_notes
			LEFT join ".$table_prefix."_senotesrel on ".$table_prefix."_senotesrel.notesid= ".$table_prefix."_notes.notesid
			INNER join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid= ".$table_prefix."_senotesrel.crmid
			LEFT join ".$table_prefix."_crmentity crm2 on crm2.crmid=".$table_prefix."_notes.notesid and crm2.deleted=0
			LEFT JOIN ".$table_prefix."_groups
			ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
			LEFT join ".$table_prefix."_users on crm2.smownerid= ".$table_prefix."_users.id
			WHERE ".$table_prefix."_crmentity.crmid=? AND ".$table_prefix."_notes.active_portal=1"; // crmv@200139
		$res = $adb->pquery($query,array($id));
		$noofdata = $adb->num_rows($res);
		for( $j= 0;$j < $noofdata; $j++)
		{
			$i=0;
			foreach($fields_list as $fieldname => $fieldlabel) {
				$output[0][$module]['head'][0][$i]['fielddata'] = $fieldlabel; //$adb->query_result($fieldres,$i,'fieldlabel');
				$fieldvalue = $adb->query_result($res,$j,$fieldname);
				if($fieldname =='title') {
					$fieldid = $adb->query_result($res,$j,'notesid');
					$filename = $fieldvalue;
					$fieldvalue = '<a href="index.php?&module=Documents&action=index&id='.$fieldid.'">'.$fieldvalue.'</a>';
				}
				if($fieldname == 'filename'){
					$fieldid = $adb->query_result($res,$j,'notesid');
					$filename = $fieldvalue;
					$folderid = $adb->query_result($res,$j,'folderid');
					$filetype = $adb->query_result($res,$j,'filelocationtype');
					if ($filetype == 'I' || $filetype == 'B') { // crmv@200139
						$fieldvalue = '<a href="index.php?&downloadfile=true&folderid='.$folderid.'&filename='.$filename.'&module=Documents&action=index&id='.$fieldid.'">'.$fieldvalue.'</a>';
					} else {
						$fieldvalue = '<a target="_blank" href="'.$filename.'">'.$filename.'</a>';
					}
				}
				$output[1][$module]['data'][$j][$i]['fielddata'] = $fieldvalue;
				$i++;
			}
		}
		$log->debug("Exiting customerportal function  get_faq_document ..");
		return $output;
	}

	/* Function to get related projecttasks/projectmilestones for a Project
	*  @params $id :: INT Project's Id
	* 			$module :: String modulename
	* 			$customerid :: INT contact's Id'
	*/
	function get_project_components($id,$module,$customerid,$sessionid) {
		global $adb,$log,$table_prefix;
		
		$log->debug("Entering customer portal function get_project_components ..");
		$check = $this->checkModuleActive($module);

		if($check == false) {
			return array("#MODULE INACTIVE#");
		}

		if(!$this->validateSession($customerid,$sessionid))
			return null;

		$user = CRMEntity::getInstance('Users');
		$userid = $this->getPortalUserid();
		$current_user = $user->retrieveCurrentUserInfoFromFile($userid);

		$focus = CRMEntity::getInstance($module);
		$focus->filterInactiveFields($module);
		$componentfieldVisibilityByColumn = array();
		$fields_list = array();

		foreach ($focus->list_fields as $fieldlabel => $values){
			foreach($values as $table => $fieldname){
				$fields_list[$fieldlabel] = $fieldname;
				$componentfieldVisibilityByColumn[$fieldname] = getColumnVisibilityPermission($current_user->id,$fieldname,$module);
			}
		}

		if ($module == 'ProjectTask') {
			$query ="SELECT ".$table_prefix."_projecttask.*, ".$table_prefix."_crmentity.smownerid
					FROM ".$table_prefix."_projecttask
					INNER JOIN ".$table_prefix."_project ON ".$table_prefix."_project.projectid = ".$table_prefix."_projecttask.projectid AND ".$table_prefix."_project.projectid = ?
					INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_projecttask.projecttaskid AND ".$table_prefix."_crmentity.deleted = 0";
		} elseif ($module == 'ProjectMilestone') {
			$query ="SELECT ".$table_prefix."_projectmilestone.*, ".$table_prefix."_crmentity.smownerid
					FROM ".$table_prefix."_projectmilestone
					INNER JOIN ".$table_prefix."_project ON ".$table_prefix."_project.projectid = ".$table_prefix."_projectmilestone.projectid AND ".$table_prefix."_project.projectid = ?
					INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_projectmilestone.projectmilestoneid AND ".$table_prefix."_crmentity.deleted = 0";
		}
		$res = $adb->pquery($query,array($id));
		$noofdata = $adb->num_rows($res);

		for( $j= 0;$j < $noofdata; ++$j) {
			$i=0;
			foreach($fields_list as $fieldlabel => $fieldname) {
				$fieldper = $componentfieldVisibilityByColumn[$fieldname];
				if($fieldper == '1'){
					continue;
				}
				$output[0][$module]['head'][0][$i]['fielddata'] = $fieldlabel;
				$fieldvalue = $adb->query_result($res,$j,$fieldname);
				if($fieldname == 'smownerid'){
					$fieldvalue = getOwnerName($fieldvalue);
				}
				$output[1][$module]['data'][$j][$i]['fielddata'] = $fieldvalue;
				$i++;
			}
		}
		$log->debug("Exiting customerportal function  get_project_components ..");
		return $output;
	}

	/* Function to get related tickets for a Project
	*  @params $id :: INT Project's Id
	* 			$module :: String modulename
	* 			$customerid :: INT contact's Id'
	*/
	function get_project_tickets($id,$module,$customerid,$sessionid) {
		global $adb,$log,$table_prefix;
		
		$log->debug("Entering customer portal function get_project_tickets ..");
		$check = $this->checkModuleActive($module);
		if($check == false) {
			return array("#MODULE INACTIVE#");
		}
		
		if(!$this->validateSession($customerid,$sessionid))
			return null;

		$user = CRMEntity::getInstance('Users');
		$userid = $this->getPortalUserid();
		$current_user = $user->retrieveCurrentUserInfoFromFile($userid);

		$focus = CRMEntity::getInstance('HelpDesk');
		$focus->filterInactiveFields('HelpDesk');
		$TicketsfieldVisibilityByColumn = array();
		$fields_list = array();
		foreach ($focus->list_fields as $fieldlabel => $values){
			foreach($values as $table => $fieldname){
				$fields_list[$fieldlabel] = $fieldname;
				$TicketsfieldVisibilityByColumn[$fieldname] = getColumnVisibilityPermission($current_user->id,$fieldname,'HelpDesk');
			}
		}

		$query = "SELECT ".$table_prefix."_troubletickets.*, ".$table_prefix."_crmentity.smownerid FROM ".$table_prefix."_troubletickets
			INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_troubletickets.ticketid
			INNER JOIN ".$table_prefix."_crmentityrel ON (".$table_prefix."_crmentityrel.relcrmid = ".$table_prefix."_crmentity.crmid OR ".$table_prefix."_crmentityrel.crmid = ".$table_prefix."_crmentity.crmid)
			WHERE ".$table_prefix."_crmentity.deleted = 0 AND (".$table_prefix."_crmentityrel.crmid = ? OR ".$table_prefix."_crmentityrel.relcrmid = ?)";

		$params = array($id, $id);
		$res = $adb->pquery($query,$params);
		$noofdata = $adb->num_rows($res);

		for( $j= 0;$j < $noofdata; $j++) {
			$i=0;
			foreach($fields_list as $fieldlabel => $fieldname) {
				$fieldper = $TicketsfieldVisibilityByColumn[$fieldname]; //in troubletickets the list_fields has columns so we call this API
				if($fieldper == '1'){
					continue;
				}
				$output[0][$module]['head'][0][$i]['fielddata'] = $fieldlabel;
				$fieldvalue = $adb->query_result($res,$j,$fieldname);
				$ticketid = $adb->query_result($res,$j,'ticketid');
				if($fieldname == 'title'){
					$fieldvalue = '<a href="index.php?module=HelpDesk&action=index&fun=detail&ticketid='.$ticketid.'">'.$fieldvalue.'</a>';
				}
				if($fieldname == 'parent_id') {
					$crmid = $fieldvalue;
					$entitymodule = getSalesEntityType($crmid);
					if ($crmid != '' && $entitymodule != '') {
						$fieldvalues = getEntityName($entitymodule, array($crmid));
						if($entitymodule == 'Contacts')
						$fieldvalue = '<a href="index.php?module=Contacts&action=index&id='.$crmid.'">'.$fieldvalues[$crmid].'</a>';
						elseif($entitymodule == 'Accounts')
						$fieldvalue = '<a href="index.php?module=Accounts&action=index&id='.$crmid.'">'.$fieldvalues[$crmid].'</a>';
					} else {
						$fieldvalue = '';
					}
				}
				if($fieldname == 'smownerid'){
					$fieldvalue = getOwnerName($fieldvalue);
				}
				$output[1][$module]['data'][$j][$i]['fielddata'] = $fieldvalue;
				$i++;
			}
		}
		$log->debug("Exiting customerportal function  get_project_tickets ..");
		return $output;
	}

	/* Function to get contactid's and account's product details'
	*
	*/
	function get_service_list_values($id,$modulename,$sessionid,$only_mine='true') {
		global $current_user,$adb,$log,$table_prefix;
		
		$log->debug("Entering customer portal Function get_service_list_values");
		$check = $this->checkModuleActive($modulename);
		if($check == false){
			return array("#MODULE INACTIVE#");
		}
		$user = CRMEntity::getInstance('Users');
		$userid = $this->getPortalUserid();
		$current_user = $user->retrieveCurrentUserInfoFromFile($userid);
		$entity_ids_list = array();
		$show_all=$this->show_all($modulename);

		if(!$this->validateSession($id,$sessionid))
		return null;

		$entity_ids_list = $this->get_allowed_ids($id, $modulename, $only_mine); // crmv@173271

		$focus = CRMEntity::getInstance('Services');
		$focus->filterInactiveFields('Services');
		foreach ($focus->list_fields as $fieldlabel => $values){
			foreach($values as $table => $fieldname){
				$fields_list[$fieldlabel] = $fieldname;
			}
		}
		$fields_list['Related To'] = 'entityid';
		$query = array();
		$params = array();

		$query[] = "select ".$table_prefix."_service.*," .
			"case when ".$table_prefix."_crmentityrel.crmid != ".$table_prefix."_service.serviceid then ".$table_prefix."_crmentityrel.crmid else ".$table_prefix."_crmentityrel.relcrmid end as entityid, " .
			"'' as setype from ".$table_prefix."_service " .
			"inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_service.serviceid " .
			"left join ".$table_prefix."_crmentityrel on (".$table_prefix."_crmentityrel.relcrmid=".$table_prefix."_service.serviceid or ".$table_prefix."_crmentityrel.crmid=".$table_prefix."_service.serviceid) " .
			"where ".$table_prefix."_crmentity.deleted = 0 and " .
			"( ".$table_prefix."_crmentityrel.crmid in (".generateQuestionMarks($entity_ids_list).") OR " .
			"(".$table_prefix."_crmentityrel.relcrmid in (".generateQuestionMarks($entity_ids_list).") AND ".$table_prefix."_crmentityrel.module = 'Services')" .
			")";

		$params[] = array($entity_ids_list, $entity_ids_list);

		$checkQuotes = $this->checkModuleActive('Quotes');
		if($checkQuotes == true){
			$query[] = "select distinct ".$table_prefix."_service.*,
				case when ".$table_prefix."_quotes.contactid is not null then ".$table_prefix."_quotes.contactid else ".$table_prefix."_quotes.accountid end as entityid,
				case when ".$table_prefix."_quotes.contactid is not null then 'Contacts' else 'Accounts' end as setype
				from ".$table_prefix."_quotes INNER join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_quotes.quoteid
				left join ".$table_prefix."_inventoryproductrel on ".$table_prefix."_inventoryproductrel.id=".$table_prefix."_quotes.quoteid
				left join ".$table_prefix."_service on ".$table_prefix."_service.serviceid = ".$table_prefix."_inventoryproductrel.productid
				where ".$table_prefix."_inventoryproductrel.productid = ".$table_prefix."_service.serviceid AND ".$table_prefix."_crmentity.deleted=0 and (accountid in  (". generateQuestionMarks($entity_ids_list) .") or contactid in (". generateQuestionMarks($entity_ids_list) ."))";
			$params[] = array($entity_ids_list,$entity_ids_list);
		}
		$checkInvoices = $this->checkModuleActive('Invoice');
		if($checkInvoices == true){
			$query[] = "select distinct ".$table_prefix."_service.*,
				case when ".$table_prefix."_invoice.contactid !=0 then ".$table_prefix."_invoice.contactid else ".$table_prefix."_invoice.accountid end as entityid,
				case when ".$table_prefix."_invoice.contactid !=0 then 'Contacts' else 'Accounts' end as setype
				from ".$table_prefix."_invoice
				INNER join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_invoice.invoiceid
				left join ".$table_prefix."_inventoryproductrel on ".$table_prefix."_inventoryproductrel.id=".$table_prefix."_invoice.invoiceid
				left join ".$table_prefix."_service on ".$table_prefix."_service.serviceid = ".$table_prefix."_inventoryproductrel.productid
				where ".$table_prefix."_inventoryproductrel.productid = ".$table_prefix."_service.serviceid AND ".$table_prefix."_crmentity.deleted=0 and (accountid in (". generateQuestionMarks($entity_ids_list) .") or contactid in  (". generateQuestionMarks($entity_ids_list) ."))";
			$params[] = array($entity_ids_list,$entity_ids_list);
		}

		$ServicesfieldVisibilityPermissions = array();
		foreach($fields_list as $fieldlabel=> $fieldname) {
			$ServicesfieldVisibilityPermissions[$fieldname] =
				getFieldVisibilityPermission('Services',$current_user->id,$fieldname);
		}

		for($k=0;$k<count($query);$k++)
		{
			$res[$k] = $adb->pquery($query[$k],$params[$k]);
			$noofdata[$k] = $adb->num_rows($res[$k]);
			if($noofdata[$k] == 0) {
				$output[$k][$modulename]['data'] = '';
			}
			for( $j= 0;$j < $noofdata[$k]; $j++)
			{
				$i=0;
				foreach($fields_list as $fieldlabel=> $fieldname) {
					$fieldper = $ServicesfieldVisibilityPermissions[$fieldname];
					if($fieldper == '1' && $fieldname != 'entityid'){
						continue;
					}
					$output[$k][$modulename]['head'][0][$i]['fielddata'] = $fieldlabel;
					$fieldvalue = $adb->query_result($res[$k],$j,$fieldname);
					$fieldid = $adb->query_result($res[$k],$j,'serviceid');

					if($fieldname == 'entityid') {
						$crmid = $fieldvalue;
						$module = $adb->query_result($res[$k],$j,'setype');
						if($module == ''){
							$module = $adb->query_result($adb->pquery("SELECT setype FROM ".$table_prefix."_crmentity WHERE crmid = ?", array($crmid)),0,'setype');
						}
						if ($crmid != '' && $module != '') {
							$fieldvalues = getEntityName($module, array($crmid));
							if($module == 'Contacts')
							$fieldvalue = '<a href="index.php?module=Contacts&action=index&id='.$crmid.'">'.$fieldvalues[$crmid].'</a>';
							elseif($module == 'Accounts')
							$fieldvalue = '<a href="index.php?module=Accounts&action=index&id='.$crmid.'">'.$fieldvalues[$crmid].'</a>';
						} else {
							$fieldvalue = '';
						}
					}

					if($fieldname == 'servicename')
					$fieldvalue = '<a href="index.php?module=Services&action=index&id='.$fieldid.'">'.$fieldvalue.'</a>';

					if($fieldname == 'unit_price'){
						$sym = $this->getCurrencySymbol($res[$k],$j,'currency_id');
						$fieldvalue = $sym.$fieldvalue;
					}
					$output[$k][$modulename]['data'][$j][$i]['fielddata'] = $fieldvalue;
					$i++;
				}
			}
		}
		$log->debug("Exiting customerportal function get_product_list_values.....");
		return $output;
	}
	
	/**
	*  Function that gives the Currency Symbol
	* @params $result $adb object - resultset
	* $column String column name
	* Return $value - Currency Symbol
	*/
	function getCurrencySymbol($result,$i,$column){
		global $adb;
		$currencyid = $adb->query_result($result,$i,$column);
		$curr = getCurrencySymbolandCRate($currencyid);
		$value = "(".$curr['symbol'].")";
		return $value;

	}

	// crmv@173271
	// crmv@172565
	public function checkModuleActive($module){
		$modules = $this->get_modules();
		return in_array($module, $modules);
	}
	// crmv@172565e

	/**
	 *Function to get the list of modules allowed for customer portal
	 */
	public function get_modules() {
		global $adb,$log,$table_prefix;
		$log->debug("Entering customer portal Function get_modules");

		static $modules = null;
		if (is_null($modules)) {
			$modules = array();

			$query = $adb->query(
				"SELECT ct.*, t.name
				FROM {$table_prefix}_customerportal_tabs ct
				INNER JOIN {$table_prefix}_tab t ON t.tabid = ct.tabid
				WHERE t.presence = 0 AND ct.visible = 1
				ORDER BY ct.sequence ASC"
			);
			if ($adb->num_rows($query) > 0) {
				while($resultrow = $adb->fetch_array($query)) {
					$modules[] = $resultrow['name'];
				}
			}
		}
		$log->debug("Exiting customerportal function get_modules");
		return $modules;
	}
	// crmv@173271e

	/* Function to check if the module has the permission to show the related contact's and Account's information
	*/
	function show_all($module){

		global $adb,$log,$table_prefix;
		$log->debug("Entering customer portal Function show_all");
		$tabid = getTabid($module);
		if($module=='Tickets'){
			$tabid = getTabid('HelpDesk');
		}
		$query = $adb->pquery("SELECT prefvalue from ".$table_prefix."_customerportal_prefs where tabid = ?", array($tabid));
		$norows = $adb->num_rows($query);
		if($norows > 0){
			if($adb->query_result($query,0,'prefvalue') == 1){
				return 'true';
			}else {
				return 'false';
			}
		}else {
			return 'false';
		}
		$log->debug("Exiting customerportal function show_all");
	}

	/* Function to get ServiceContracts information in the tickets module if the ticket is related to ServiceContracts
	*/
	function getRelatedServiceContracts($crmid){
		global $adb,$log,$table_prefix;
		$log->debug("Entering customer portal function getRelatedServiceContracts");
		$module = 'ServiceContracts';
		$sc_info = array();
		if(vtlib_isModuleActive($module) !== true){
			return $sc_info;
		}
		$query = "SELECT * FROM ".$table_prefix."_servicecontracts " .
		"INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_servicecontracts.servicecontractsid AND ".$table_prefix."_crmentity.deleted = 0 " .
		"LEFT JOIN ".$table_prefix."_crmentityrel ON ".$table_prefix."_crmentityrel.crmid = ".$table_prefix."_servicecontracts.servicecontractsid " .
		"WHERE (".$table_prefix."_crmentityrel.relcrmid = ? and ".$table_prefix."_crmentityrel.module= 'ServiceContracts')";

		$res = $adb->pquery($query,array($crmid));
		$rows = $adb->num_rows($res);
		for($i=0;$i<$rows;$i++){
			$sc_info[$i]['Subject'] = $adb->query_result($res,$i,'subject');
			$sc_info[$i]['Used Units'] = $adb->query_result($res,$i,'used_units');
			$sc_info[$i]['Total Units'] = $adb->query_result($res,$i,'total_units');
			$sc_info[$i]['Available Units'] = $adb->query_result($res,$i,'total_units')- $adb->query_result($res,$i,'used_units');
		}
		return $sc_info;
		$log->debug("Exiting customerportal function getRelatedServiceContracts");
	}

	// crmv@5946

	/**	function to get the attachments of a potential
	*	@param array $input_array - array which contains the following values
	=>	int $id - customer ie., contact id
	int $sessionid - session id
	int $potentialid - potential id
	*	return array $output - This will return all the file details related to the ticket
	*/
	function get_potential_attachments($input_array)
	{
		global $adb,$log,$table_prefix;
		$log->debug("Entering customer portal function get_ticket_attachments");
		$adb->println("INPUT ARRAY for the function get_ticket_attachments");
		$adb->println($input_array);

		$check = $this->checkModuleActive('Documents');
		if($check == false){
			return array("#MODULE INACTIVE#");
		}
		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		$potentialid = $input_array['potentialid'];

		$isPermitted = $this->check_permission($id,'Potentials',$potentialid);
		if($isPermitted == false) {
			return array("#NOT AUTHORIZED#");
		}

		if(!$this->validateSession($id,$sessionid))
			return null;

		$query = "select ".$table_prefix."_potential.potentialid, ".$table_prefix."_attachments.*,".$table_prefix."_notes.filename,".$table_prefix."_notes.filelocationtype " .
				"from ".$table_prefix."_potential " .
				"left join ".$table_prefix."_senotesrel on ".$table_prefix."_senotesrel.crmid=".$table_prefix."_potential.potentialid " .
				"left join ".$table_prefix."_notes on ".$table_prefix."_notes.notesid=".$table_prefix."_senotesrel.notesid " .
				"inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_notes.notesid " .
				"left join ".$table_prefix."_seattachmentsrel on ".$table_prefix."_seattachmentsrel.crmid=".$table_prefix."_notes.notesid " .
				"left join ".$table_prefix."_attachments on ".$table_prefix."_attachments.attachmentsid = ".$table_prefix."_seattachmentsrel.attachmentsid " .
				"and ".$table_prefix."_crmentity.deleted = 0 where ".$table_prefix."_potential.potentialid =?";
		$res = $adb->pquery($query, array($potentialid));

		$noofrows = $adb->num_rows($res);
		for($i=0;$i<$noofrows;$i++)
		{
			$filename = $adb->query_result($res,$i,'filename');
			$filepath = $adb->query_result($res,$i,'path');

			$fileid = $adb->query_result($res,$i,'attachmentsid');
			$filesize = filesize($filepath.$fileid."_".$filename);
			$filetype = $adb->query_result($res,$i,'type');
			$filelocationtype = $adb->query_result($res,$i,'filelocationtype');
			//Now we will not pass the file content to CP, when the customer click on the link we will retrieve
			//$filecontents = base64_encode(file_get_contents($filepath.$fileid."_".$filename));//fread(fopen($filepath.$filename, "r"), $filesize));

			$output[$i]['fileid'] = $fileid;
			$output[$i]['filename'] = $filename;
			$output[$i]['filetype'] = $filetype;
			$output[$i]['filesize'] = $filesize;
			$output[$i]['filelocationtype'] = $filelocationtype;
		}
		$log->debug("Exiting customer portal function get_ticket_attachments");
		return $output;
	}

	/**	function to add attachment for a all module ie., the passed contents will be write in a file and the details will be stored in database
	*	@param array $input_array - array which contains the following values
	=>	int $id - customer ie., contact id
	int $sessionid - session id
	int $ticketid - ticket id
	string $filename - file name to be attached with the ticket
	string $filetype - file type
	int $filesize - file size
	string $filecontents - file contents as base64 encoded format
	PASSARE ALLA FUNZIONE IL MODULO E LA CHIAVE PRIMARIA DEL MODULO
	*	return void
	*/
	function add_attachment($input_array)
	{	
		global $adb, $log,$root_directory, $upload_badext, $current_user,$table_prefix;
		$log->debug("Entering customer portal function add_ticket_attachment");
		$adb->println("INPUT ARRAY for the function add_ticket_attachment");
		$adb->println($input_array);
		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		
		$primarykey = $input_array['key'];
		$module = $input_array['module'];
		$valueprimarykey = $input_array[$primarykey];
		
		$filename = $input_array['filename'];
		$filetype = $input_array['filetype'];
		$filesize = $input_array['filesize'];
		$filecontents = $input_array['filecontents'];

		if(!$this->validateSession($id,$sessionid))
			return null;

		//decide the file path where we should upload the file in the server
		$upload_filepath = decideFilePath();

		$attachmentid = $adb->getUniqueID($table_prefix."_crmentity");

		//fix for space in file name
		$filename = preg_replace('/\s+/', '_', $filename);
		$ext_pos = strrpos($filename, ".");
		$ext = substr($filename, $ext_pos + 1);

		if (in_array(strtolower($ext), $upload_badext)){
			$filename .= ".txt";
		}
		$new_filename = $attachmentid.'_'.$filename;

		$data = base64_decode($filecontents);
		$description = 'CustomerPortal Attachment';

		//write a file with the passed content
		$handle = @fopen($upload_filepath.$new_filename,'w');
		fputs($handle, $data);
		fclose($handle);

		//Now store this file information in db and relate with the ticket
		$date_var = $adb->formatDate(date('Y-m-d H:i:s'), true);

		//crmv@20945
		$setype = $module." Attachment";
		$crmquery = "insert into ".$table_prefix."_crmentity (crmid,setype,createdtime,modifiedtime) values(?,?,?,?)"; // crmv@150773
		$crmresult = $adb->pquery($crmquery, array($attachmentid, $setype, $date_var, $date_var)); // crmv@150773
		//crmv@20945e

		$attachmentquery = "insert into ".$table_prefix."_attachments(attachmentsid,name,description,type,path) values(?,?,?,?,?)";
		$attachmentreulst = $adb->pquery($attachmentquery, array($attachmentid, $filename, $description, $filetype, $upload_filepath));

		$relatedquery = "insert into ".$table_prefix."_seattachmentsrel values(?,?)";
		$relatedresult = $adb->pquery($relatedquery, array($valueprimarykey, $attachmentid));

		$user_id = $this->getPortalUserid();

		$user = CRMEntity::getInstance('Users');
		$current_user = $user->retrieveCurrentUserInfoFromFile($user_id);

		$focus = CRMEntity::getInstance('Documents');
		$focus->column_fields['notes_title'] = $filename;
		$focus->column_fields['filename'] = $filename;
		$focus->column_fields['filetype'] = $filetype;
		$focus->column_fields['filesize'] = $filesize;
		$focus->column_fields['filelocationtype'] = 'I';
		$focus->column_fields['filedownloadcount']= 0;
		$focus->column_fields['filestatus'] = 1;
		$focus->column_fields['assigned_user_id'] = $user_id;
		$focus->column_fields['folderid'] = 1;
		$focus->parentid = $valueprimarykey;
		$focus->save('Documents');

		$related_doc = 'insert into '.$table_prefix.'_seattachmentsrel values (?,?)';
		$res = $adb->pquery($related_doc,array($focus->id,$attachmentid));

		// crmv@38798
		$tic_doc = 'insert into '.$table_prefix.'_senotesrel (crmid, notesid, relmodule) values(?,?,?)';
		$res = $adb->pquery($tic_doc,array($valueprimarykey,$focus->id,$module));
		// crmv@38798e
		$log->debug("Exiting customer portal function add_ticket_attachment");
	}
	// crmv@5946e

	function get_slo_picklist($input_array)
	{
		global $log,$adb,$table_prefix,$current_language;	
		$adb->println("Entering customer portal function get_slo_picklist");
		$adb->println($input_array);
		

		$id = $input_array['id'];
		$sessionid = $input_array['sessionid'];
		$current_language = $input_array['language'];
		$field = $input_array['field'];
		$module = $input_array['module'];	
		

		if(!$this->validateSession($id,$sessionid))
			return null;

		if(!$field){
			return null;
		}

		$output = Array();

		$userid = $this->getPortalUserid();

		//We are going to display the picklist entries associated with admin user (role is H2)
		$roleres = $adb->pquery("SELECT roleid from ".$table_prefix."_user2role where userid = ?",array($userid));
		$RowCount = $adb->num_rows($roleres);
		if($RowCount > 0){
			$admin_role = $adb->query_result($roleres,0,'roleid');
		}
		
		$result1 = $adb->pquery("select {$table_prefix}_$field.$field 
									from {$table_prefix}_$field 
									inner join {$table_prefix}_role2picklist on {$table_prefix}_role2picklist.picklistvalueid = {$table_prefix}_$field.picklist_valueid and {$table_prefix}_role2picklist.roleid='$admin_role'", array());
		for($i=0;$i<$adb->num_rows($result1);$i++)
		{
			if ($adb->query_result($result1,$i,$field) == " "){
				$value = 'N/A';
			}else{
				$value = getTranslatedString($adb->query_result($result1,$i,$field), $module);
			}
			$output[$i] = $value;
		}

		
		return $output;

	}

	/*crmv@80441 starts
	* 'id'=>"$customerid",
	'sessionid'=>"$sessionid",
	'ticketid'=>"$ticketid",
	'field'=>"$field",
	'value'=>"$value"
	*
	*/
	function update_ticket($input_array)
	{
		global $adb,$log,$current_user,$table_prefix;
		$mode = $input_array['mode']; // saveTicketStars

		$ticketid = $input_array['ticketid'];
		$fieldname = $input_array['field'];
		$value = $input_array['value'];

		if(!empty($ticketid) && !empty($fieldname) && !empty($value)){

			// Prendo la tabella del campo
			$query = "SELECT * FROM ".$table_prefix."_field WHERE fieldname = ?";
			$res = $adb->pquery($query,array($fieldname));
			$tablename = $adb->query_result($res,0,'tablename');

			if(!empty($tablename)){
				$query_update = "UPDATE ".$tablename." SET ".$fieldname." = ? WHERE ticketid = ?";
				$ris = $adb->pquery($query_update,array($value,$ticketid));

				return array('OK');
			}else{
				return array('tablename non trovata');
			}

		}else{
			return array('Campi vuoti');
		}

		return array($query_update);
	}
	//crmv@80441e starts
	
	// crmv@173271
	public function get_conditionals($customerid, $module) {
		
		$user = CRMEntity::getInstance('Users');
		$userid = $this->getPortalUserid();
		$current_user = $user->retrieveCurrentUserInfoFromFile($userid);
		
		$conditionals = CRMEntity::getInstance('Conditionals');
		$ids = $conditionals->getIdsForModuleAndUser($module, $userid);
		
		$list = array();
		foreach ($ids as $ruleid) {
			$list[] = $conditionals->getConditional($ruleid);
		}
		
		return $list;
	}
	// crmv@173271e

}