<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@203484 removed including file

class HelpDesk extends CRMEntity {
	var $log;
	var $db;
	var $table_name;
	var $table_index= 'ticketid';
	var $tab_name = Array();
	var $tab_name_index = Array();
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();

	var $column_fields = Array();
	//Pavani: Assign value to entity_table
        var $entity_table;

	var $sortby_fields = Array('title','status','priority','crmid','firstname','smownerid','parent_id'); //crmv@7214s

	var $list_fields = Array(
					//Module Sequence Numbering
					//'Ticket ID'=>Array('crmentity'=>'crmid'),
					'Ticket No'=>Array('troubletickets'=>'ticket_no'),
					// END
					'Subject'=>Array('troubletickets'=>'title'),
					'Related to'=>Array('troubletickets'=>'parent_id'),
					'Status'=>Array('troubletickets'=>'status'),
					'Priority'=>Array('troubletickets'=>'priority'),
					'Assigned To'=>Array('crmentity'=>'smownerid')
				);

	var $list_fields_name = Array(
					//'Ticket ID'=>'',
					'Ticket No'=>'ticket_no',
					'Subject'=>'ticket_title',
					'Related to'=>'parent_id',
					'Status'=>'ticketstatus',
					'Priority'=>'ticketpriorities',
					'Assigned To'=>'assigned_user_id'
				     );

	var $list_link_field= 'ticket_title';

	var $range_fields = Array(
				        'ticketid',
					'title',
			        	'firstname',
				        'lastname',
			        	'parent_id',
			        	'productid',
			        	'productname',
			        	'priority',
			        	'severity',
				        'status',
			        	'category',
					'description',
					'solution',
					'modifiedtime',
					'createdtime'
				);
	var $search_fields = Array();
	var $search_fields_name = Array(
		'Ticket No' => 'ticket_no',
		'Title'=>'ticket_title',
		);
	//Specify Required fields
    var $required_fields =  array();

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('assigned_user_id', 'createdtime', 'modifiedtime', 'ticket_title');

    //Added these variables which are used as default order by and sortorder in ListView
    var $default_order_by = 'modifiedtime';
    var $default_sort_order = 'DESC';
	//crmv@10759
	var $search_base_field = 'ticket_title';
	//crmv@10759 e

	//var $groupTable = Array('vte_ticketgrouprelation','ticketid');

	//crmv@2043m crmv@126830
	var $waitForResponseStatus = 'Wait For Response';
	var $answeredByCustomerStatus = 'Answered by customer';
	//crmv@2043me crmv@126830e
	
	public $sendPortalEmails = true; // crmv@142597
	
	public $confidentialInfoRequest = array(); // crmv@160733

	/**	Constructor which will set the column_fields in this object
	 */
	function __construct()
	{
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix."_troubletickets";
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_troubletickets',$table_prefix.'_ticketcf');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_troubletickets'=>'ticketid',$table_prefix.'_ticketcf'=>'ticketid',$table_prefix.'_ticketcomments'=>'ticketid');
		$this->customFieldTable = Array($table_prefix.'_ticketcf', 'ticketid');
	    $this->entity_table = $table_prefix."_crmentity";
        $this->search_fields = Array(
		//'Ticket ID' => Array($table_prefix.'_crmentity'=>'crmid'),
		'Ticket No' =>Array($table_prefix.'_troubletickets'=>'ticket_no'),
		'Title' => Array($table_prefix.'_troubletickets'=>'title')
		);
		$this->log =LoggerManager::getLogger('helpdesk');
		$this->log->debug("Entering HelpDesk() method ...");
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('HelpDesk');
		$this->log->debug("Exiting HelpDesk method ...");
	}
	
	// crmv@137993
	function save($module_name,$longdesc=false,$offline_update=false,$triggerEvent=true) {
		global $adb;
		
		// get the value of some fields before being saved
		if ($this->mode == 'edit') {
			$this->old_status = null;
			$this->old_solution = null;
			$res = $adb->pquery("SELECT status, solution FROM {$this->table_name} WHERE {$this->table_index} = ?", array($this->id));
			if ($res && $adb->num_rows($res) > 0) {
				$this->old_status = $adb->query_result_no_html($res, 0, 'status');
				$this->old_solution = $adb->query_result_no_html($res, 0, 'solution');
			}
		}

		parent::save($module_name,$longdesc,$offline_update,$triggerEvent);
	}
	// crmv@137993e

	function save_module($module)
	{
		//crmv@27146
		//Inserting into Ticket Comment Table
		if(isset($_REQUEST['action']) && $_REQUEST['action'] != 'MassEditSave'){
			$this->insertIntoTicketCommentTable($table_prefix."_ticketcomments",'HelpDesk');
		}
		//Inserting into vte_attachments
		//$this->insertIntoAttachment($this->id,'HelpDesk');
		//crmv@27146e
		
		// crmv@142597
		if ($this->sendPortalEmails) {
			$this->sendPortalReply(); // crmv@137993
		}
		// crmv@142597e

		/* commento altrimenti passa per la save_related_module sia qui che nella CRMEntity
		$return_action = $_REQUEST['return_action'];
		$for_module = $_REQUEST['return_module'];
		$for_crmid  = $_REQUEST['return_id'];
		if ($return_action && $for_module && $for_crmid) {
			if ($for_module != 'Accounts' && $for_module != 'Contacts' && $for_module != 'Products') {
				$on_focus = CRMEntity::getInstance($for_module);
				$on_focus->save_related_module($for_module, $for_crmid, $module, $this->id);
			}
		}
		*/
	}
	
	// crmv@137993
	/**
	 * Check and send the email to alert the portal user
	 */
	function sendPortalReply() {
		global $adb, $table_prefix;
		
		$parent_id = $this->column_fields['parent_id'];
		
		// do nothing if the ticket is not linked to anything
		if (empty($parent_id)) return;
		
		//crmv@93276
		$mail_status = '';
		$emailoptout = 1;
		$isactive = 0;
		
		// crmv@26821
		$parent_module = getSalesEntityType($parent_id);

		if($parent_module == 'Contacts') {
		
			$result = $adb->pquery("select firstname,lastname,email,emailoptout from ".$table_prefix."_contactdetails where contactid=?", array($parent_id));
			$emailoptout = $adb->query_result_no_html($result,0,'emailoptout');
			$parentname = $adb->query_result($result,0,'firstname').' '.$adb->query_result($result,0,'lastname');
			$contact_mailid = $adb->query_result($result,0,'email');
			//Get the status of the vte_portal user. if the customer is active then send the vte_portal link in the mail
			if ($contact_mailid != '' && $emailoptout == 0) {
				$sql = "select isactive from ".$table_prefix."_portalinfo where id=?";
				$isactive = $adb->query_result_no_html($adb->pquery($sql, array($parent_id)),0,'isactive');
			}
			
		} elseif ($parent_module == 'Accounts') {
		
			$result = $adb->pquery("select accountname,emailoptout from ".$table_prefix."_account where accountid=?", array($parent_id));
			$emailoptout = $adb->query_result_no_html($result,0,'emailoptout');
			$parentname = $adb->query_result($result,0,'accountname');
			if($emailoptout == 0){
				// find the first active portal contact (no specific order)
				$result_cnt = $adb->pquery("select contactid from ".$table_prefix."_contactdetails where accountid=?", array($parent_id));
				while($row_cnt = $adb->fetchByAssoc($result_cnt)){
					$result1 = $adb->pquery("select email,emailoptout from ".$table_prefix."_contactdetails where contactid=?", array($row_cnt['contactid']));
					$emailoptout_cnt = $adb->query_result_no_html($result1,0,'emailoptout');
					$contact_mailid_cnt = $adb->query_result($result1,0,'email');
					if ($contact_mailid_cnt != '' && $emailoptout_cnt == 0) {
						$sql = "select isactive from ".$table_prefix."_portalinfo where id=?";
						$isactive = $adb->query_result_no_html($adb->pquery($sql, array($row_cnt['contactid'])),0,'isactive');
						if($isactive) break;
					}
				}
			}
		}
		// Leads can't be used, since there's no link with contacts
		
		//crmv@87556
		if ($this->column_fields['comments'] != '' && !empty($this->column_fields['mailscanner_action'])) {
			$mail_status = $this->sendMailScannerReply();
		//crmv@87556e
		} elseif ($emailoptout == 0 && $isactive && $this->shouldSendPortalReply()) {
			
			require_once('modules/Emails/mail.php');
			
			// get the parent address
			$parent_email = getParentMailId($parent_module,$parent_id);
			
			// prepare the email
			$emailinfo = $this->getPortalEmail($parent_email, $parentname);
				
			// send the email
			$mail_tmp = ''; // crmv@129149
			$mail_status = send_mail('HelpDesk',$emailinfo['recipient'],$emailinfo['from_name'],$emailinfo['from_email'],$emailinfo['subject'],$emailinfo['body'],'','','','','','',$mail_tmp,'','',true); // crmv@129149
			$mail_status_str = $parent_email."=".$mail_status."&&&";
			
		} else {
			$adb->println("'".$parentname."' doesn't want to receive emails about the ticket details as emailoptout is selected or portal not active");
		}
		// crmv@26821e
		
		$this->portal_email_status = $mail_status;
		if ($mail_status != '' && $mail_status != 1) { // crmv@104782
			$this->portal_email_error = getMailErrorString($mail_status_str);;
		}
		//crmv@93276e

		return $mail_status;
	}
	
	/**
	 * Return true if the portal email should be sent (provided the ticket is linked to a valid contact/account)
	 * By default the email is sent:
	 *   in creation mode OR status changed to "Closed" OR comment added OR solution changed
	 */
	function shouldSendPortalReply() {
		$cond = (
			$this->mode != 'edit' ||
			($this->column_fields['ticketstatus'] != $this->old_status && $this->column_fields['ticketstatus'] == 'Closed') ||
			$this->column_fields['comments'] != '' || 
			($this->column_fields['solution'] != $this->old_solution && !empty($this->column_fields['solution']))
		); 
		return $cond;
	}
	
	// crmv@142358
	// TODO: use real email templates or split this function
	/**
	 * Prepare the email template to be sent to the portal user or to the customer
	 * There are several emails sent in different cases:
	 * 1. customer creates ticket from portal -> email to customer & notification to portal user
	 * 2. customer adds a comment from portal -> notifiy ticket owner and followers
	 * 3. customer closes ticket form portal -> notifiy ticket owner and followers
	 * 4. vte user create/edit ticket from vte -> email to customer
	 */
	function getPortalEmail($parent_email, $parentname) {
		global $PORTAL_URL, $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID;

		if ($this->mode == 'edit') {
			if (requestFromPortal()) {
				// the customer added a comment, mail to ticket owner
				$subject = getTranslatedString('LBL_RESPONDTO_TICKETID', 'HelpDesk')."##". $this->id."##". getTranslatedString('LBL_CUSTOMER_PORTAL', 'HelpDesk');
			} else {
				// the user changed the ticket, mail to the customer
				$subject = '[ '.getTranslatedString('LBL_TICKET_ID', 'HelpDesk').' : '.$this->id.' ] '.'Re : '.$this->column_fields['ticket_title'];		
			}
		} else {
			if (requestFromPortal()) {
				$subject = "[From Portal] " .$this->column_fields['ticket_no'].' [ '.getTranslatedString('LBL_TICKET_ID', 'HelpDesk').' : '.$this->id.' ] '.$this->column_fields['ticket_title'];
			} else {
				$subject = '[ '.getTranslatedString('LBL_TICKET_ID', 'HelpDesk').' : '.$this->id.' ] '.$this->column_fields['ticket_title'];		
			}
		}
		
		$bodysubject = 
			getTranslatedString('Ticket No', 'HelpDesk').': '.$this->column_fields['ticket_no']."<br>\n".
			getTranslatedString('LBL_TICKET_ID', 'HelpDesk').': '.$this->id."<br>\n".
			getTranslatedString('LBL_SUBJECT', 'HelpDesk').' '.$this->column_fields['ticket_title'];
		
		if (requestFromPortal()) {
			if ($this->mode == 'edit') {
				$email_body_portal = getTranslatedString('Dear', 'HelpDesk')." ".getTranslatedString('user', 'HelpDesk').","."<br><br>"
					.getTranslatedString('LBL_CUSTOMER_COMMENTS', 'HelpDesk')."<br><br>
					<b>".nl2br($this->column_fields['comments'])."</b><br><br>"
					.getTranslatedString('LBL_RESPOND', 'HelpDesk')."<br><br>"
					.getTranslatedString('LBL_REGARDS', 'HelpDesk')."<br>"
					.getTranslatedString('LBL_SUPPORT_ADMIN', 'HelpDesk');
			} else {
				$email_body_portal = $bodysubject.'<br><br>'.$this->column_fields['description'];
			}
		} else {
			$url = "<a href='".$PORTAL_URL."/index.php?module=HelpDesk&action=index&ticketid=".$this->id."&fun=detail'>".getTranslatedString('LBL_TICKET_DETAILS', 'HelpDesk')."</a>"; //crmv@82517
			$email_body_portal = $bodysubject.'<br><br>'.getPortalInfo_Ticket($this->id,$this->column_fields['ticket_title'],$parentname,$url,$this->mode);
		}
		
		$ret = array(
			'recipient' => $parent_email,
			'from_name' => $HELPDESK_SUPPORT_NAME,
			'from_email' => $HELPDESK_SUPPORT_EMAIL_ID,
			'subject' => $subject,
			'body' => $email_body_portal,
		);

		return $ret;
	}
	// crmv@142358e
	
	function getPortalEmailStatus() {
		return array(
			'status' => $this->portal_email_status,
			'error' => $this->portal_email_error
		);
	}
	// crmv@137993e

	/** Function to insert values in vte_ticketcomments  for the specified tablename and  module
  	  * @param $table_name -- table name:: Type varchar
  	  * @param $module -- module:: Type varchar
 	 */
	function insertIntoTicketCommentTable($table_name, $module, $ownertype=null, $ownerid=null)
	{
		global $log;
		$log->info("in insertIntoTicketCommentTable  ".$table_name."    module is  ".$module);
       	global $adb;
       	global $table_prefix;
		global $current_user;

        $current_time = $adb->formatDate(date('Y-m-d H:i:s'), true);
		
        if (empty($ownertype)) $ownertype = 'user';
        if (empty($ownerid)) $ownerid = $current_user->id;

		if($this->column_fields['comments'] != '')
			$comment = $this->column_fields['comments'];
		else
			$comment = $_REQUEST['comments'];

		// crmv@160733
		if (isset($this->confidentialInfoRequest['mode'])) {
			$cimode = $this->confidentialInfoRequest['mode'];
			if ($cimode == 'request') {
				$comment .= $this->getConfidentialRequestText(($comment == ''));
			} elseif ($cimode == 'provide') {
				$comment = str_replace('@DELETEME@', '', $comment);
				if ($comment != '') $comment .= "\n\n";
				$comment .= $this->getConfidentialReplyText();
			}
		}
		
		if ($comment != '') {
			
			$comid = $adb->getUniqueID($table_prefix.'_ticketcomments');
			$sql = "insert into ".$table_prefix."_ticketcomments (commentid,ticketid,ownerid,ownertype,createdtime,comments) values(?,?,?,?,?,".$adb->getEmptyClob(true).")";
			$params = array($comid, $this->id, $ownerid, $ownertype, $current_time);
			$adb->pquery($sql, $params);
			$adb->updateClob($table_prefix.'_ticketcomments','comments',"commentid=$comid",from_html($comment));
			$this->lastInsertedCommentId = $comid; // crmv@49398
			
			if ($cimode) {
				if ($cimode == 'request') {
					$pwd = $this->confidentialInfoRequest['pwd'];
					$data = $this->confidentialInfoRequest['data'];
					$this->setConfidentialPwd($comid, $pwd, $data);
				} elseif ($cimode == 'provide') {
					$requestComment = intval($this->confidentialInfoRequest['request_commentid']);
					$data = $this->confidentialInfoRequest['data'];
					$r = $this->setConfidentialData($comid, $data, $requestComment);
					if (!$r) throw new Exception('Password not saved correctly');
				}
			}
		}
		// crmv@160733e
	}


	/**
	 *      This function is used to add the vte_at".$table_prefix."nts. This will call the function uploadAndSaveFile which will upload the attachment into the server and save that attachment information in the database.
	 *      @param int $id  - entity id to which the vte_files to be uploaded
	 *      @param string $module  - the current module name
	*/
	function insertIntoAttachment($id,$module)
	{
		global $log, $adb;
		$log->debug("Entering into insertIntoAttachment($id,$module) method.");

		$file_saved = false;

		foreach($_FILES as $fileindex => $files)
		{
			if($files['name'] != '' && $files['size'] > 0)
			{
				$files['original_name'] = vtlib_purify($_REQUEST[$fileindex.'_hidden']);
				$file_saved = $this->uploadAndSaveFile($id,$module,$files);
			}
		}

		$log->debug("Exiting from insertIntoAttachment($id,$module) method.");
	}

	// crmv@49398
	/**	Function to get the ticket comments as a array
	 *	@param  int   $ticketid - ticketid
	 *	@return array $output - array(
						[$i][comments]    => comments
						[$i][owner]       => name of the user or customer who made the comment
						[$i][createdtime] => the comment created time
					     )
				where $i = 0,1,..n which are all made for the ticket
	**/
	function get_ticket_comments_list($ticketid) {
		global $log, $adb, $table_prefix;

		$log->debug("Entering get_ticket_comments_list(".$ticketid.") method ...");

		$output = array();
		$sql = "select * from {$table_prefix}_ticketcomments where ticketid=? order by createdtime DESC";
		$result = $this->db->pquery($sql, array($ticketid));
		$noofrows = $this->db->num_rows($result);

		for ($i=0; $i<$noofrows; ++$i) {
			$row = $this->db->FetchByAssoc($result);
			// crmv@34559
			if ($row['ownertype'] == 'user') {
				$name = getUserName($row['ownerid']);
			} elseif ($row['ownertype'] == 'customer') {
				$name = getContactName($row['ownerid']);
			} else {
				$name = '';
			}
			// crmv@34559e
			$row['owner'] = $name;
			$row['comments'] = nl2br($row['comments']);
			// crmv@160733
			// get the plaintext for the additional info requested
			if ($row['conf_status'] == 1 && !empty($row['conf_data'])) {
				$row['conf_data_plain'] = $this->getConfidentialDataComment($row['commentid']);
			}
			// hide some sensitive info
			unset($row['conf_password'], $row['conf_data']);
			// crmv@160733e
			$output[] = $row;
		}

		$log->debug("Exiting get_ticket_comments_list method ...");
		return $output;
	}
	// crmv@49398e

	/**	Function to form the query which will give the list of tickets based on customername and id ie., contactname and contactid
	 *	@param  string $user_name - name of the customer ie., contact name
	 *	@param  int    $id	 - contact id
	 * 	@return array  - return an array which will be returned from the function process_list_query
	**/
	function get_user_tickets_list($user_name,$id,$where='',$match='')
	{
		global $log;
		global $table_prefix;
		$log->debug("Entering get_user_tickets_list(".$user_name.",".$id.",".$where.",".$match.") method ...");

		$this->db->println("where ==> ".$where);

		// crmv@150773
		$query = "select ".$table_prefix."_crmentity.crmid, ".$table_prefix."_troubletickets.*, ".$table_prefix."_crmentity.smownerid, ".$table_prefix."_crmentity.createdtime, ".$table_prefix."_crmentity.modifiedtime, ".$table_prefix."_contactdetails.firstname, ".$table_prefix."_contactdetails.lastname, ".$table_prefix."_products.productid, ".$table_prefix."_products.productname, ".$table_prefix."_ticketcf.*
			from ".$table_prefix."_troubletickets
			inner join ".$table_prefix."_ticketcf on ".$table_prefix."_ticketcf.ticketid = ".$table_prefix."_troubletickets.ticketid
			inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_troubletickets.ticketid
			left join ".$table_prefix."_contactdetails on ".$table_prefix."_troubletickets.parent_id=".$table_prefix."_contactdetails.contactid
			left join ".$table_prefix."_products on ".$table_prefix."_products.productid = ".$table_prefix."_troubletickets.product_id
			left join ".$table_prefix."_users on ".$table_prefix."_crmentity.smownerid=".$table_prefix."_users.id
			where ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_contactdetails.email='".$user_name."' and ".$table_prefix."_troubletickets.parent_id = '".$id."'";

		if(trim($where) != '')
		{
			if($match == 'all' || $match == '')
			{
				$join = " and ";
			}
			elseif($match == 'any')
			{
				$join = " or ";
			}
			$where = explode("&&&",$where);
			$count = count($where);
			$count --;
			$where_conditions = "";
			foreach($where as $key => $value)
			{
				$this->db->println('key : '.$key.'...........value : '.$value);
				$val = explode(" = ",$value);
				$this->db->println('val0 : '.$val[0].'...........val1 : '.$val[1]);
				if($val[0] == $table_prefix.'_troubletickets.title')
				{
					$where_conditions .= $val[0]."  ".$val[1];
					if($count != $key) 	$where_conditions .= $join;
				}
				elseif($val[1] != '' && $val[1] != 'Any')
				{
					$where_conditions .= $val[0]." = ".$val[1];
					if($count != $key)	$where_conditions .= $join;
				}
			}
			if($where_conditions != '')
				$where_conditions = " and ( ".$where_conditions." ) ";

			$query .= $where_conditions;
			$this->db->println("where condition for customer portal tickets search : ".$where_conditions);
		}

		$query .= " order by ".$table_prefix."_crmentity.crmid desc";
		$log->debug("Exiting get_user_tickets_list method ...");
		return $this->process_list_query($query);
	}

	/**	Function to process the list query and return the result with number of rows
	 *	@param  string $query - query
	 *	@return array  $response - array(	list           => array(
											$i => array(key => val)
									       ),
							row_count      => '',
							next_offset    => '',
							previous_offset	=>''
						)
		where $i=0,1,..n & key = ticketid, title, firstname, ..etc(range_fields) & val = value of the key from db retrieved row
	**/
	function process_list_query($query, $row_offset, $limit= -1, $max_per_page = -1) // crmv@146653
	{
		global $log;
		$log->debug("Entering process_list_query(".$query.") method ...");

   		$result =& $this->db->query($query,true,"Error retrieving $this->object_name list: ");
		$list = Array();
	        $rows_found =  $this->db->getRowCount($result);
        	if($rows_found != 0)
	        {
			$ticket = Array();
			for($index = 0 , $row = $this->db->fetchByAssoc($result, $index); $row && $index <$rows_found;$index++, $row = $this->db->fetchByAssoc($result, $index))
			{
		                foreach($this->range_fields as $columnName)
                		{
		                	if (isset($row[$columnName]))
					{
			                	$ticket[$columnName] = $row[$columnName];
                    			}
		                       	else
				        {
		                        	$ticket[$columnName] = "";
			                }
	     			}
    		                $list[] = $ticket;
                	}
        	}

		$response = Array();
	        $response['list'] = $list;
        	$response['row_count'] = $rows_found;
	        $response['next_offset'] = $next_offset;
        	$response['previous_offset'] = $previous_offset;

		$log->debug("Exiting process_list_query method ...");
	        return $response;
	}

	// crmv@186735 - removed code

	/**     Function to get the list of comments for the given ticket id
	 *      @param  int  $ticketid - Ticket id
	 *      @return list $list - return the list of comments and comment informations as a html output where as these comments and comments informations will be formed in div tag.
	**/
	function getCommentInformation($ticketid)
	{
		global $log;
		global $table_prefix;
		$log->debug("Entering getCommentInformation(".$ticketid.") method ...");
		global $adb;
		global $mod_strings, $default_charset;
		$sql = "select * from ".$table_prefix."_ticketcomments where ticketid=?";
		$result = $adb->pquery($sql, array($ticketid));
		$noofrows = $adb->num_rows($result);

		//In ajax save we should not add this div
		if($_REQUEST['fldName'] != 'comments')
		{
			$list .= '<div id="comments_div" class="wrap-content" style="overflow:auto;max-height:200px;width:100%;">'; // crmv@113422
			$enddiv = '</div>';
		}
		//crmv@3126m
		static $ownerData = Array();
		for($i=0;$i<$noofrows;$i++)
		{
			if($adb->query_result($result,$i,'comments') != '')
			{
				$ownerid = $adb->query_result($result,$i,'ownerid');
				if($adb->query_result($result,$i,'ownertype') == 'user'){
					if(!isset($ownerData['fullname'][$ownerid])) {
						$ownerData['fullname'][$ownerid] = getUserFullName($ownerid);
					}
					$avatar = getUserAvatarImg($ownerid);
					$float ="right";
					$textalign="left";
					$mainstyle = "class=\"dataField\" style=\"float:left;margin-right: auto;margin-bottom: 5px;clear: both;\"";
				}
				else{
					if(!isset($ownerData['avatar'][$ownerid])) {
						$ownerData['avatar'][$ownerid] = resourcever('portal_avatar.png'); //todo image from contact
					}
					if(!isset($ownerData['fullname'][$ownerid])) {
						$ownerData['fullname'][$ownerid] = getContactName($ownerid);
					}					
					$avatar = "<div style=\"float:right;height:100%\">
						<img title=\"{$ownerData['fullname'][$ownerid]}\" alt=\"{$ownerData['fullname'][$ownerid]}\" src=\"{$ownerData['avatar'][$ownerid]}\" class=\"userAvatar\">
					</div>";
					$float ="left";
					$textalign="right";
					$mainstyle = "class=\"dataField\" style=\"float:right;margin-left: auto;margin-bottom: 5px;clear: both;\"";				
				}
				$list.="<div $mainstyle>";
				//this div is to display the comment
				$comment = $adb->query_result($result,$i,'comments');
				// Asha: Fix for ticket #4478 . Need to escape html tags during ajax save.
				if($_REQUEST['action'] == 'HelpDeskAjax') {
					$comment = htmlentities($comment, ENT_QUOTES, $default_charset);
				}
				$list.="$avatar";
				$list.="<div style=\"float:{$float}\">";
				$list .= '<div valign="top" class="dataField" style="text-align:'.$textalign.';padding-top:0px;">';
				$list.="<b>{$ownerData['fullname'][$ownerid]}</b><br>";
				// crmv@160733
				$htmlComment = make_clickable(nl2br($comment));
				$commentid = $adb->query_result_no_html($result,$i,'commentid');
				$confStatus = intval($adb->query_result_no_html($result,$i,'conf_status'));
				if ($confStatus == 1 || $confStatus == 2) {
					$htmlComment = str_replace('{HERELINK}', $this->genConfidentialInfoReplyLink($ticketid, $commentid, $confStatus), $htmlComment);
				} elseif ($confStatus == 3) {
					$htmlComment = str_replace('{HERELINK}', $this->genConfidentialInfoSeeLink($ticketid, $commentid, $confStatus), $htmlComment);
				}
				$list .= $htmlComment;
				// crmv@160733e

				$list .= '</div>';

				//this div is to display the author and time
				$list .= '<div valign="top" style="padding-bottom:5px;text-align:'.$textalign.';" class="dataLabel">';
				$createdtime = $adb->query_result($result,$i,'createdtime');
				$list.=" <a href=\"javascript:;\" title=\"".CRMVUtils::timestamp($createdtime)."\" style=\"color: gray; text-decoration: none;\">".CRMVUtils::timestampAgo($createdtime)."</a>"; // crmv@164654
				$list.= '</div>';
				$list.="</div>";
				$list.="</div>";
			}
		}
		//crmv@3126me
		$list .= $enddiv;

		$log->debug("Exiting getCommentInformation method ...");
		return $list;
	}

	/**     Function to get the Customer Name who has made comment to the ticket from the customer portal
	 *      @param  int    $id   - Ticket id
	 *      @return string $customername - The contact name
	**/
	function getCustomerName($id)
	{
		global $log;
		$log->debug("Entering getCustomerName(".$id.") method ...");
        	global $adb;
        	global $table_prefix;
	        $sql = "select * from ".$table_prefix."_portalinfo inner join ".$table_prefix."_troubletickets on ".$table_prefix."_troubletickets.parent_id = ".$table_prefix."_portalinfo.id where ".$table_prefix."_troubletickets.ticketid=?";
        	$result = $adb->pquery($sql, array($id));
	        $customername = $adb->query_result($result,0,'user_name');
		$log->debug("Exiting getCustomerName method ...");
        	return $customername;
	}
	//Pavani: Function to create, export query for helpdesk module
	/** Function to export the ticket records in CSV Format
	 * @param reference variable - where condition is passed when the query is executed
	 * Returns Export Tickets Query.
	 */
	function create_export_query($where,$oCustomView,$viewId)	//crmv@31775
	{
		global $log;
		global $current_user;
		global $table_prefix;
		$log->debug("Entering create_export_query(".$where.") method ...");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery("HelpDesk", "detail_view");
		$fields_list = getFieldsListFromQuery($sql);
		//Ticket changes--5198
		//crmv@15981
		$fields_list = 	str_replace(','.$table_prefix.'_ticketcomments.comments as "Add Comment"',' ',$fields_list);
		//crmv@15981 end

		//crmv@92596 - leads
		$query = "SELECT $fields_list,case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name
            FROM ".$this->entity_table. "
			INNER JOIN ".$table_prefix."_troubletickets
				ON ".$table_prefix."_troubletickets.ticketid =".$table_prefix."_crmentity.crmid
			LEFT JOIN ".$table_prefix."_crmentity ".$table_prefix."_crmentityRelatedTo
				ON ".$table_prefix."_crmentityRelatedTo.crmid = ".$table_prefix."_troubletickets.parent_id
			LEFT JOIN ".$table_prefix."_account
				ON ".$table_prefix."_account.accountid = ".$table_prefix."_troubletickets.parent_id
			LEFT JOIN ".$table_prefix."_contactdetails
				ON ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_troubletickets.parent_id
			LEFT JOIN ".$table_prefix."_contactdetails ".substr($table_prefix.'_contactdetailsparent_id',0,29)."
				ON ".substr($table_prefix.'_contactdetailsparent_id',0,29).".contactid = ".$table_prefix."_troubletickets.parent_id
			LEFT JOIN ".$table_prefix."_leaddetails
				ON ".$table_prefix."_leaddetails.leadid = ".$table_prefix."_troubletickets.parent_id
			LEFT JOIN ".$table_prefix."_leaddetails ".substr($table_prefix.'_leaddetailsparent_id',0,29)."
				ON ".substr($table_prefix.'_leaddetailsparent_id',0,29).".leadid = ".$table_prefix."_troubletickets.parent_id
			LEFT JOIN ".$table_prefix."_ticketcf
				ON ".$table_prefix."_ticketcf.ticketid=".$table_prefix."_troubletickets.ticketid
			LEFT JOIN ".$table_prefix."_groups
				ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
			LEFT JOIN ".$table_prefix."_users
				ON ".$table_prefix."_users.id=".$table_prefix."_crmentity.smownerid and ".$table_prefix."_users.status='Active'
			LEFT JOIN ".$table_prefix."_seattachmentsrel
				ON ".$table_prefix."_seattachmentsrel.crmid =".$table_prefix."_troubletickets.ticketid
			LEFT JOIN ".$table_prefix."_attachments
				ON ".$table_prefix."_attachments.attachmentsid=".$table_prefix."_seattachmentsrel.attachmentsid
			LEFT JOIN ".$table_prefix."_products
				ON ".$table_prefix."_products.productid=".$table_prefix."_troubletickets.product_id";
		//crmv@92596e

		//crmv@31775
		$reportFilter = $oCustomView->getReportFilter($viewId);
		if ($reportFilter) {
			$tableNameTmp = $oCustomView->getReportFilterTableName($reportFilter,$current_user->id);
			$query .= " INNER JOIN $tableNameTmp ON $tableNameTmp.id = {$table_prefix}_crmentity.crmid";
		}
		//crmv@31775e

		$query .= $this->getNonAdminAccessControlQuery('HelpDesk',$current_user);

		$where_auto = " ".$table_prefix."_crmentity.deleted=0";

		if($where != '') $query .= " WHERE ($where) AND $where_auto";
		else $query .= " WHERE $where_auto";

		$query = $this->listQueryNonAdminChange($query, 'HelpDesk');
		$log->debug("Exiting create_export_query method ...");
		return $query;
	}

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

		$rel_table_arr = Array("Activities"=>$table_prefix."_seactivityrel","Attachments"=>$table_prefix."_seattachmentsrel","Documents"=>$table_prefix."_senotesrel");

		$tbl_field_arr = Array($table_prefix."_seactivityrel"=>"activityid",$table_prefix."_seattachmentsrel"=>"attachmentsid",$table_prefix."_senotesrel"=>"notesid");

		$entity_tbl_field_arr = Array($table_prefix."_seactivityrel"=>"crmid",$table_prefix."_seattachmentsrel"=>"crmid",$table_prefix."_senotesrel"=>"crmid");

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
		//crmv@15526
		parent::transferRelatedRecords($module, $transferEntityIds, $entityId);
		//crmv@15526 end
		$log->debug("Exiting transferRelatedRecords...");
	}

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	function generateReportsSecQuery($module,$secmodule,$reporttype='',$useProductJoin=true,$joinUitype10=true){ // crmv@146653
		global $table_prefix;
		$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_troubletickets","ticketid");
		$query .=" left join ".$table_prefix."_ticketcf on ".$table_prefix."_ticketcf.ticketid = ".$table_prefix."_troubletickets.ticketid
				left join ".$table_prefix."_crmentity ".$table_prefix."_crmentityRelHelpDesk on ".$table_prefix."_crmentityRelHelpDesk.crmid = ".$table_prefix."_troubletickets.parent_id
				left join ".$table_prefix."_account ".$table_prefix."_accountRelHelpDesk on ".$table_prefix."_accountRelHelpDesk.accountid=".$table_prefix."_crmentityRelHelpDesk.crmid
				left join ".$table_prefix."_contactdetails ".substr($table_prefix.'_contactdetailsRelHelpDesk',0,29)." on ".substr($table_prefix.'_contactdetailsRelHelpDesk',0,29).".contactid= ".$table_prefix."_crmentityRelHelpDesk.crmid
				left join ".$table_prefix."_products ".$table_prefix."_productsRel on ".$table_prefix."_productsRel.productid = ".$table_prefix."_troubletickets.product_id
				left join ".$table_prefix."_groups ".$table_prefix."_groupsHelpDesk on ".$table_prefix."_groupsHelpDesk.groupid = ".$table_prefix."_crmentityHelpDesk.smownerid
				left join ".$table_prefix."_users ".$table_prefix."_usersHelpDesk on ".$table_prefix."_usersHelpDesk.id = ".$table_prefix."_crmentityHelpDesk.smownerid";
		return $query;
	}

	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */
	function setRelationTables($secmodule){
		global $table_prefix;
		$rel_tables = array (
			"Calendar" => array($table_prefix."_seactivityrel"=>array("crmid","activityid"),$table_prefix."_troubletickets"=>"ticketid"),
			"Documents" => array($table_prefix."_senotesrel"=>array("crmid","notesid"),$table_prefix."_troubletickets"=>"ticketid"),
			"Products" => array($table_prefix."_troubletickets"=>array("ticketid","product_id")),
			"Services" => array($table_prefix."_crmentityrel"=>array("crmid","relcrmid"),$table_prefix."_troubletickets"=>"ticketid"),
		);
		return $rel_tables[$secmodule];
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log;
		global $table_prefix;
		if(empty($return_module) || empty($return_id)) return;

		if($return_module == 'Contacts' || $return_module == 'Accounts') {
			$sql = 'UPDATE '.$table_prefix.'_troubletickets SET parent_id=0 WHERE ticketid=?';
			$this->db->pquery($sql, array($id));
			$se_sql= 'DELETE FROM '.$table_prefix.'_seticketsrel WHERE ticketid=?';
			$this->db->pquery($se_sql, array($id));
		} elseif($return_module == 'Products') {
			$sql = 'UPDATE '.$table_prefix.'_troubletickets SET product_id=0 WHERE ticketid=?';
			$this->db->pquery($sql, array($id));
		//crmv@112084
		} elseif($return_module == 'ServiceContracts'){
			parent::unlinkRelationship($id, $return_module, $return_id);
			//crmv@130458
			$_tmp_REQUEST = $_REQUEST;
			unset($_REQUEST);
			$servicecontracts = CRMEntity::getInstance('ServiceContracts');
			$servicecontracts->retrieve_entity_info_no_html($return_id,'ServiceContracts');
			$servicecontracts->updateServiceContractState();
			$servicecontracts->mode = 'edit';
			$servicecontracts->formatFieldsForSave = false;
			$servicecontracts->save('ServiceContracts');
			$_REQUEST = $_tmp_REQUEST;
			//crmv@130458e
		//crmv@112084e
		} else {
			return parent::unlinkRelationship($id, $return_module, $return_id); //crmv@177568
		}
		$this->db->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), $id, $return_id)); // crmv@49398 crmv@69690
	}
	function get_timecards($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user;//crmv@203484 removed global singlepane
        global $table_prefix;
        //crmv@203484
        $VTEP = VTEProperties::getInstance();
        $singlepane_view = $VTEP->getProperty('layout.singlepane_view');
        //crmv@203484e
		$log->debug("Entering get_timecards(".$id.") method ...");
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
		if($actions) {
			$button .= $this->get_related_buttons($this_module, $id, $related_module, $actions); // crmv@43864
		}

		$query = "SELECT case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name," .
					" ".$table_prefix."_timecards.*, ".$table_prefix."_troubletickets.ticket_no, ".$table_prefix."_troubletickets.parent_id, ".$table_prefix."_troubletickets.priority," .
					"  ".$table_prefix."_troubletickets.severity, ".$table_prefix."_troubletickets.status, ".$table_prefix."_troubletickets.category, ".$table_prefix."_troubletickets.title," .
					"  ".$table_prefix."_products.*, ".$table_prefix."_crmentity.crmid, ".$table_prefix."_crmentity.smownerid, ".$table_prefix."_crmentity.modifiedtime" .
					" from ".$table_prefix."_timecards" .
					" inner join ".$table_prefix."_timecardscf on ".$table_prefix."_timecardscf.timecardsid = ".$table_prefix."_timecards.timecardsid" .
					" inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_timecards.timecardsid" .
					" inner join ".$table_prefix."_troubletickets on ".$table_prefix."_troubletickets.ticketid = ".$table_prefix."_timecards.ticket_id " .
					" left join ".$table_prefix."_products on ".$table_prefix."_products.productid = ".$table_prefix."_timecards.product_id" .
					" left join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_crmentity.smownerid" .
					" left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid=".$table_prefix."_crmentity.smownerid" .
					" where ".$table_prefix."_timecards.ticket_id=$id and ".$table_prefix."_crmentity.deleted=0 ";

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_timecards method ...");
		return $return_value;
	}
	function save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check=false) { // crmv@146653
	    global $adb,$log;
	    global $table_prefix;
		if(!is_array($with_crmid)) $with_crmid = Array($with_crmid);
		if($with_module == 'Timecards') {
			$with_crmids=implode(',',$with_crmid);
		    $adb->pquery("UPDATE ".$table_prefix."_timecards set ticket_id=? where timecardsid in (?)",Array($crmid, $with_crmids));
			//crmv@29617
			if(!is_array($with_crmid)) $with_crmid = Array($with_crmid);
			foreach($with_crmid as $relcrmid) {
				if ($crmid != $relcrmid) {
					$obj = ModNotifications::getInstance(); // crmv@164122
					$obj->saveRelatedModuleNotification($crmid, $module, $relcrmid, $with_module);
				}
			}
			//crmv@29617e
		//crmv@57850 crmv@99875
		} elseif($with_module == 'ServiceContracts'){
			parent::save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check); // crmv@146653
			$servicecontracts = CRMEntity::getInstance('ServiceContracts');
			foreach($with_crmid as $relcrmid) {
				//crmv@130458
				$_tmp_REQUEST = $_REQUEST;
				unset($_REQUEST);
				$servicecontracts = CRMEntity::getInstance('ServiceContracts');
				$servicecontracts->retrieve_entity_info_no_html($relcrmid,'ServiceContracts');
				$servicecontracts->updateHelpDeskRelatedTo($relcrmid,$crmid);
				$servicecontracts->updateServiceContractState();
				$servicecontracts->mode = 'edit';
				$servicecontracts->formatFieldsForSave = false;
				$servicecontracts->save('ServiceContracts');
				$_REQUEST = $_tmp_REQUEST;
				//crmv@130458e
			}
		//crmv@57850e crmv@99875e
		} else {
		    parent::save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check); // crmv@146653
		}
	}

	//crmv@37004
	function getMessagePopupFields($module) {
		$namefields = array(
			'ticket_no',
			'ticket_title',
			'ticketstatus',
			'ticketseverities',
			'assigned_user_id',
		);
		return $namefields;
	}

	function getMessagePopupLimitedCond(&$queryGenerator, $module, $relatedIds = array(), $searchstr = '') {
		global $adb, $table_prefix;
		$queryGenerator->addCondition('ticketstatus', 'Closed', 'n');
	}

	function getMessagePopupOrderBy(&$queryGenerator, $module, $relatedIds = array(), $searchstr = '') {
		global $table_prefix;
		// TODO: ticketpriority
		return " ORDER BY {$table_prefix}_crmentity.createdtime ASC";
	}
	//crmv@37004e
	
	//crmv@87556
	function sendMailScannerReply() {
		require_once('modules/Emails/mail.php');
		$subject = 'Re: '.$this->column_fields['ticket_title'].' - Ticket Id: '.$this->id;
		$body = nl2br($this->column_fields['comments']);
		$mail_status = send_mail('HelpDesk',$this->column_fields['email_from'],'',$this->column_fields['email_to'],$subject,$body,'','','','','','',$mail_tmp);
		if ($mail_status == 1) {
			global $currentModule;
			$currentModule = 'Messages';
			$_REQUEST['relation'] = $this->id;
			$focusMessages = CRMentity::getInstance($currentModule);
			$focusMessages->internalAppendMessage($mail_tmp,'','',$this->column_fields['email_from'],'',$this->column_fields['email_to'],$subject,$body,'','','');
			$currentModule = 'HelpDesk';
		}
		return $mail_status;
	}
	//crmv@87556e
	
	// crmv@160733
	function setConfidentialRequest($request) {
		$this->confidentialInfoRequest = $request;
	}
	
	function getConfidentialRequestText($emptyComment = false) {
		if ($emptyComment) {
			return getTranslatedString('LBL_CONFIDENTIAL_INFO_REQUEST_ETEXT', 'HelpDesk').".";
		} else {
			return "\n\n".getTranslatedString('LBL_CONFIDENTIAL_INFO_REQUEST_TEXT', 'HelpDesk').".";
		}
	}
	
	function getConfidentialReplyText() {
		return getTranslatedString('LBL_CONFIDENTIAL_INFO_REPLY_TEXT', 'HelpDesk').".";
	}
	
	/**
	 * Set a password to encrypt confidential data, optionally adding an encrypted comment too
	 */
	function setConfidentialPwd($commentid, $pwd, $data = '') {
		global $adb, $table_prefix;
		
		// encrypt pwd
		require_once('include/utils/encryption.php');
		$encryption = new Encryption();
		$cpwd = $encryption->encrypt($pwd);
		
		// encrypt data
		if ($data != '') {
			require_once('modules/SDK/src/208/208Utils.php');
			$UI208 = new EncryptedUitype();
			$data = $UI208->encryptData($data, $pwd);
		} else {
			$data = null;
		}
		
		// statuses: 0/null = none, 1 = requested, 2 = provided, 3 = confidential data
		$adb->pquery("UPDATE {$table_prefix}_ticketcomments SET conf_status = ?, conf_password = ?, conf_data = ? WHERE commentid = ?", array(1, $cpwd, $data, $commentid));
	}
	
	function setConfidentialData($commentid, $data, $parentComment) {
		global $adb, $table_prefix;
		
		require_once('modules/SDK/src/208/208Utils.php');
		
		// get password from parent comment
		$res = $adb->pquery("SELECT conf_password FROM {$table_prefix}_ticketcomments WHERE commentid = ? AND conf_status = ?", array($parentComment, 1));
		$pwd = $adb->query_result_no_html($res, 0, 'conf_password');
		if ($pwd === '' || $pwd === null || $pwd === false) return false; // already entered
		
		// decrypt password
		require_once('include/utils/encryption.php');
		$encryption = new Encryption();
		$pwd = $encryption->decrypt($pwd);
		
		//encrypt the text (same method as encrypted fields)
		$UI208 = new EncryptedUitype();
		$data = $UI208->encryptData($data, $pwd);
		
		// set the data
		$res = $adb->pquery("UPDATE {$table_prefix}_ticketcomments SET conf_status = ?, conf_data = ? WHERE commentid = ?", array(3, $data, $commentid));
		// and clear the password
		$res = $adb->pquery("UPDATE {$table_prefix}_ticketcomments SET conf_status = ?, conf_password = NULL WHERE commentid = ?", array(2, $parentComment));
		
		return true;
	}
	
	function getConfidentialDataComment($commentid, $pwd = null) {
		global $adb, $table_prefix;
		
		require_once('modules/SDK/src/208/208Utils.php');
		
		// get password from parent comment
		$res = $adb->pquery("SELECT conf_password, conf_data FROM {$table_prefix}_ticketcomments WHERE commentid = ? AND conf_status = ?", array($commentid, 1));
		$data = $adb->query_result_no_html($res, 0, 'conf_data');
		
		if (is_null($pwd)) {
			$pwd = $adb->query_result_no_html($res, 0, 'conf_password');
			if ($pwd === '' || $pwd === null || $pwd === false) return false; // password destroyed and nothing provided
		
			// decrypt password
			require_once('include/utils/encryption.php');
			$encryption = new Encryption();
			$pwd = $encryption->decrypt($pwd);
		}
		
		//encrypt the text (same method as encrypted fields)
		if ($data !== null && $data !== '') {
			$UI208 = new EncryptedUitype();
			$data = $UI208->decryptData($data, $pwd);
			if ($data === false) return false; // wrong pwd or corrupted data
		} else {
			$data = '';
		}
		
		return $data;
	}
	
	function getConfidentialData($commentid, $pwd) {
		global $adb, $table_prefix;
		
		require_once('modules/SDK/src/208/208Utils.php');
		
		// get encrypted data from comment
		$res = $adb->pquery("SELECT conf_data FROM {$table_prefix}_ticketcomments WHERE commentid = ? AND conf_status = ?", array($commentid, 3));
		$data = $adb->query_result_no_html($res, 0, 'conf_data');
		if ($data === '' || $data === null || $data === false) return false; // no data
		
		//encrypt the text (same method as encrypted fields)
		$UI208 = new EncryptedUitype();
		$data = $UI208->decryptData($data, $pwd);
		if ($data === false) return false;
		
		return $data;
	}
	
	function genConfidentialInfoReplyLink($ticketid, $commentid, $status) {
		$jsfunc = "VTE.HelpDesk.ConfidentialInfo.askData('HelpDesk', '$ticketid', '$commentid', '$status');";
		return "<b><a href=\"javascript:".htmlspecialchars($jsfunc)."\">".getTranslatedString('LBL_HERE')."</a></b>";
	}
	
	function genConfidentialInfoSeeLink($ticketid, $commentid, $status) {
		$jsfunc = "VTE.HelpDesk.ConfidentialInfo.seeData('HelpDesk', '$ticketid', '$commentid', '$status');";
		return "<b><a href=\"javascript:".htmlspecialchars($jsfunc)."\">".getTranslatedString('LBL_HERE')."</a></b>";
	}
	// crmv@160733e
	
}