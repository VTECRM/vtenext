<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('config.php');

/** Creates a file with all the user, user-role,user-profile, user-groups informations
  * @param $userid -- user id:: Type integer
  * @returns user_privileges_userid file under the user_privileges directory
 */

// crmv@39110
function createUserPrivilegesfile($userid, $mobile = 0) {
	global $root_directory;

	// check if there's a profile for the mobile
	$profilesList = getUserProfile($userid, $mobile);
	if ($mobile && count($profilesList) == 0) {
		// no profile -> delete the file -> use default file
		@unlink($root_directory.'user_privileges/user_privileges_m_'.$userid.'.php');
		return;
	}

	if ($mobile) {
		$handle=@fopen($root_directory.'user_privileges/user_privileges_m_'.$userid.'.php',"w+");
	} else {
		$handle=@fopen($root_directory.'user_privileges/user_privileges_'.$userid.'.php',"w+");
	}
	// crmv@39110e

	if($handle)	{

		$newbuf='';
		$newbuf .="<?php\n\n";
		$newbuf .="\n";
		$newbuf .= "//This is the access privilege file\n";
		$user_focus= CRMEntity::getInstance('Users');
		$user_focus->retrieve_entity_info_no_html($userid,"Users");	// crmv@137835
		$userInfo=Array();
		$user_focus->column_fields["id"] = '';
		$user_focus->id = $userid;
		foreach($user_focus->column_fields as $field=>$value_iter) {
			$userInfo[$field]= $user_focus->$field;
		}

		if ($user_focus->is_admin == 'on') {
			$newbuf .= "\$is_admin=true;\n";
			$newbuf .="\n";
			$newbuf .= "\$user_info=".constructSingleStringKeyValueArray($userInfo).";\n";
			$newbuf .= "\n";
			$newbuf .= "?>";
			fputs($handle, $newbuf);
			fclose($handle);
			return;

		} else {
			$newbuf .= "\$is_admin=false;\n";
			$newbuf .= "\n";
			// crmv@39110
			$globalPermissionArr=getCombinedUserGlobalPermissions($userid, $mobile);
			$tabsPermissionArr=getCombinedUserTabsPermissions($userid, $mobile);
			$actionPermissionArr=getCombinedUserActionPermissions($userid, $mobile);
			// crmv@39110e
			$user_role=fetchUserRole($userid);
			$user_role_info=getRoleInformation($user_role);
			$user_role_parent=$user_role_info[$user_role][1];
			$userGroupFocus=new GetUserGroups();
			$userGroupFocus->getAllUserGroups($userid);
			$subRoles=getRoleSubordinates($user_role);
			$subRoleAndUsers=getSubordinateRoleAndUsers($user_role);
			$def_org_share=getDefaultSharingAction();
			$parentRoles=getParentRole($user_role);

			$newbuf .= "\$current_user_roles='".$user_role."';\n\n";
			$newbuf .= "\n";
			$newbuf .= "\$current_user_parent_role_seq='".$user_role_parent."';\n";
			$newbuf .= "\n";
			$newbuf .= "\$current_user_profiles=".constructSingleArray($profilesList).";\n"; // crmv@39110
			$newbuf .= "\n";
			$newbuf .= "\$profileGlobalPermission=".constructArray($globalPermissionArr).";\n";
			$newbuf .="\n";
			$newbuf .= "\$profileTabsPermission=".constructArray($tabsPermissionArr).";\n";
			$newbuf .="\n";
			$newbuf .= "\$profileActionPermission=".constructTwoDimensionalArray($actionPermissionArr).";\n";
			$newbuf .="\n";
			$newbuf .= "\$current_user_groups=".constructSingleArray($userGroupFocus->user_groups).";\n";
			$newbuf .="\n";
			$newbuf .= "\$subordinate_roles=".constructSingleCharArray($subRoles).";\n";
			$newbuf .="\n";
			$newbuf .= "\$parent_roles=".constructSingleCharArray($parentRoles).";\n";
			$newbuf .="\n";
			$newbuf .= "\$subordinate_roles_users=".constructTwoDimensionalCharIntSingleArray($subRoleAndUsers).";\n";
			$newbuf .="\n";
			$newbuf .= "\$user_info=".constructSingleStringKeyValueArray($userInfo).";\n";

			$newbuf .= "?>";
			fputs($handle, $newbuf);
			fclose($handle);

			// crmv@63349 - populate user table
			$tutables = TmpUserTables::getInstance();
			$tutables->cleanTmpForUser($userid);
			$tutables->generateTmpForUser($userid);

			$tmodreltables = TmpUserModRelTables::getInstance();
			$tmodreltables->cleanTmpForUser($userid);
			// crmv@63349e
		}
	}
}

/** Creates a file with all the organization default sharing permissions and custom sharing permissins specific for the specified user. In this file the information of the other users whose data is shared with the specified user is stored.
  * @param $userid -- user id:: Type integer
  * @returns sharing_privileges_userid file under the user_privileges directory
 */
function createUserSharingPrivilegesfile($userid)
{
	global $adb, $root_directory,$table_prefix;
	require('user_privileges/user_privileges_'.$userid.'.php');
	$handle=@fopen($root_directory.'user_privileges/sharing_privileges_'.$userid.'.php',"w+");

if($handle)
	{
		$newbuf='';
		$newbuf .="<?php\n\n";
		$newbuf .="\n";
		$newbuf .= "//This is the sharing access privilege file\n";
		$user_focus= CRMEntity::getInstance('Users');
		$user_focus->retrieve_entity_info_no_html($userid,"Users");	// crmv@137835
		if($user_focus->is_admin == 'on')
		{
			$newbuf .= "\n";
			$newbuf .= "?>";
			fputs($handle, $newbuf);
			fclose($handle);
			return;
		}
		else
		{
			//Constructig the Default Org Share Array
			$def_org_share=getAllDefaultSharingAction();
			$newbuf .= "\$defaultOrgSharingPermission=".constructArray($def_org_share).";\n";
			$newbuf .= "\n";

			//Constructing the Related Module Sharing Array
			$relModSharArr= GetDSRelModList(); // crmv@193648

			$newbuf .= "\$related_module_share=".constructTwoDimensionalValueArray($relModSharArr).";\n\n";

			//Constructing Lead Sharing Rules
			$lead_share_per_array=getUserModuleSharingObjects("Leads",$userid,$def_org_share,$current_user_roles,$parent_roles,$current_user_groups);
			$lead_share_read_per=$lead_share_per_array['read'];
			$lead_share_write_per=$lead_share_per_array['write'];
			$lead_sharingrule_members=$lead_share_per_array['sharingrules'];

			$newbuf .= "\$Leads_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($lead_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($lead_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($lead_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Leads_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($lead_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($lead_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($lead_share_write_per['USR']).");\n\n";

			//Constructing the Lead Email Related Module Sharing Array
			$lead_related_email=getRelatedModuleSharingArray("Leads","Emails",$lead_sharingrule_members,$lead_share_read_per,$lead_share_write_per,$def_org_share);

			$lead_email_share_read_per=$lead_related_email['read'];
			$lead_email_share_write_per=$lead_related_email['write'];

			$newbuf .= "\$Leads_Emails_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($lead_email_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($lead_email_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($account_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Leads_Emails_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($lead_email_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($lead_email_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($account_share_read_per['USR']).");\n\n";



			//Constructing Account Sharing Rules
			$account_share_per_array=getUserModuleSharingObjects("Accounts",$userid,$def_org_share,$current_user_roles,$parent_roles,$current_user_groups);
			$account_share_read_per=$account_share_per_array['read'];
			$account_share_write_per=$account_share_per_array['write'];
			$account_sharingrule_members=$account_share_per_array['sharingrules'];
//crmv@7222
			$newbuf .= "\$Accounts_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($account_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($account_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($account_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Accounts_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($account_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($account_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($account_share_write_per['USR']).");\n\n";
			//Constructing Contact Sharing Rules
			$newbuf .= "\$Contacts_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($account_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($account_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($account_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Contacts_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($account_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($account_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($account_share_write_per['USR']).");\n\n";





			//Constructing the Account Potential Related Module Sharing Array

			$acct_related_pot=getRelatedModuleSharingArray("Accounts","Potentials",$account_sharingrule_members,$account_share_read_per,$account_share_write_per,$def_org_share);

			$acc_pot_share_read_per=$acct_related_pot['read'];
			$acc_pot_share_write_per=$acct_related_pot['write'];
			$newbuf .= "\$Accounts_Potentials_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($acc_pot_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($acc_pot_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($acc_pot_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Accounts_Potentials_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($acc_pot_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($acc_pot_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($acc_pot_share_write_per['USR']).");\n\n";

			//Constructing the Account Ticket Related Module Sharing Array
			$acct_related_tkt=getRelatedModuleSharingArray("Accounts","HelpDesk",$account_sharingrule_members,$account_share_read_per,$account_share_write_per,$def_org_share);

			$acc_tkt_share_read_per=$acct_related_tkt['read'];
			$acc_tkt_share_write_per=$acct_related_tkt['write'];

			$newbuf .= "\$Accounts_HelpDesk_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($acc_tkt_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($acc_tkt_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($acc_tkt_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Accounts_HelpDesk_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($acc_tkt_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($acc_tkt_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($acc_tkt_share_write_per['USR']).");\n\n";

			//Constructing the Account Email Related Module Sharing Array
			$acct_related_email=getRelatedModuleSharingArray("Accounts","Emails",$account_sharingrule_members,$account_share_read_per,$account_share_write_per,$def_org_share);

			$acc_email_share_read_per=$acct_related_email['read'];
			$acc_email_share_write_per=$acct_related_email['write'];

			$newbuf .= "\$Accounts_Emails_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($acc_email_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($acc_email_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($acc_email_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Accounts_Emails_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($acc_email_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($acc_email_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($acc_email_share_write_per['USR']).");\n\n";

			//Constructing the Account Quote Related Module Sharing Array
			$acct_related_qt=getRelatedModuleSharingArray("Accounts","Quotes",$account_sharingrule_members,$account_share_read_per,$account_share_write_per,$def_org_share);

			$acc_qt_share_read_per=$acct_related_qt['read'];
			$acc_qt_share_write_per=$acct_related_qt['write'];

			$newbuf .= "\$Accounts_Quotes_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($acc_qt_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($acc_qt_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($acc_qt_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Accounts_Quotes_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($acc_qt_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($acc_qt_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($acc_qt_share_write_per['USR']).");\n\n";

			//Constructing the Account SalesOrder Related Module Sharing Array
			$acct_related_so=getRelatedModuleSharingArray("Accounts","SalesOrder",$account_sharingrule_members,$account_share_read_per,$account_share_write_per,$def_org_share);

			$acc_so_share_read_per=$acct_related_so['read'];
			$acc_so_share_write_per=$acct_related_so['write'];

			$newbuf .= "\$Accounts_SalesOrder_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($acc_so_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($acc_so_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($acc_so_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Accounts_SalesOrder_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($acc_so_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($acc_so_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($acc_so_share_write_per['USR']).");\n\n";


			//Constructing the Account Invoice Related Module Sharing Array
			$acct_related_inv=getRelatedModuleSharingArray("Accounts","Invoice",$account_sharingrule_members,$account_share_read_per,$account_share_write_per,$def_org_share);

			$acc_inv_share_read_per=$acct_related_inv['read'];
			$acc_inv_share_write_per=$acct_related_inv['write'];

			$newbuf .= "\$Accounts_Invoice_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($acc_inv_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($acc_inv_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($acc_inv_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Accounts_Invoice_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($acc_inv_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($acc_inv_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($acc_inv_share_write_per['USR']).");\n\n";


			//Constructing Potential Sharing Rules
			$pot_share_per_array=getUserModuleSharingObjects("Potentials",$userid,$def_org_share,$current_user_roles,$parent_roles,$current_user_groups);
			$pot_share_read_per=$pot_share_per_array['read'];
			$pot_share_write_per=$pot_share_per_array['write'];
			$pot_sharingrule_members=$pot_share_per_array['sharingrules'];
			$newbuf .= "\$Potentials_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($pot_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalArray($pot_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($pot_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Potentials_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($pot_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalArray($pot_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($pot_share_write_per['USR']).");\n\n";

			//Constructing the Potential Quotes Related Module Sharing Array
			$pot_related_qt=getRelatedModuleSharingArray("Potentials","Quotes",$pot_sharingrule_members,$pot_share_read_per,$pot_share_write_per,$def_org_share);

			$pot_qt_share_read_per=$pot_related_qt['read'];
			$pot_qt_share_write_per=$pot_related_qt['write'];

			$newbuf .= "\$Potentials_Quotes_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($pot_qt_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($pot_qt_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($pot_qt_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Potentials_Quotes_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($pot_qt_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($pot_qt_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($pot_qt_share_write_per['USR']).");\n\n";

			//Constructing the Potential SalesOrder Related Module Sharing Array
			$pot_related_inv=getRelatedModuleSharingArray("Potentials","SalesOrder",$pot_sharingrule_members,$pot_share_read_per,$pot_share_write_per,$def_org_share);



			$pot_inv_share_read_per=$pot_related_inv['read'];
			$pot_inv_share_write_per=$pot_related_inv['write'];

			$newbuf .= "\$Potentials_SalesOrder_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($pot_inv_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($pot_inv_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($pot_inv_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Potentials_SalesOrder_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($pot_inv_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($pot_inv_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($pot_inv_share_write_per['USR']).");\n\n";




			//Constructing HelpDesk Sharing Rules
			$hd_share_per_array=getUserModuleSharingObjects("HelpDesk",$userid,$def_org_share,$current_user_roles,$parent_roles,$current_user_groups);
			$hd_share_read_per=$hd_share_per_array['read'];
			$hd_share_write_per=$hd_share_per_array['write'];
			$newbuf .= "\$HelpDesk_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($hd_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalArray($hd_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($hd_share_read_per['USR']).");\n\n";
			$newbuf .= "\$HelpDesk_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($hd_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalArray($hd_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($hd_share_write_per['USR']).");\n\n";


			//Constructing Emails Sharing Rules
			$email_share_per_array=getUserModuleSharingObjects("Emails",$userid,$def_org_share,$current_user_roles,$parent_roles,$current_user_groups);
			$email_share_read_per=$email_share_per_array['read'];
			$email_share_write_per=$email_share_per_array['write'];
			$newbuf .= "\$Emails_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($email_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($email_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($email_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Emails_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($email_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($email_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($email_share_write_per['USR']).");\n\n";

			//Constructing Campaigns Sharing Rules
			$campaign_share_per_array=getUserModuleSharingObjects("Campaigns",$userid,$def_org_share,$current_user_roles,$parent_roles,$current_user_groups);
			$campaign_share_read_per=$campaign_share_per_array['read'];
			$campaign_share_write_per=$campaign_share_per_array['write'];
			$newbuf .= "\$Campaigns_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($campaign_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($campaign_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($campaign_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Campaigns_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($campaign_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($campaign_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($campaign_share_write_per['USR']).");\n\n";


			//Constructing Quotes Sharing Rules
			$quotes_share_per_array=getUserModuleSharingObjects("Quotes",$userid,$def_org_share,$current_user_roles,$parent_roles,$current_user_groups);
			$quotes_share_read_per=$quotes_share_per_array['read'];
			$quotes_share_write_per=$quotes_share_per_array['write'];
			$quotes_sharingrule_members=$quotes_share_per_array['sharingrules'];
			$newbuf .= "\$Quotes_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($quotes_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($quotes_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($quotes_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Quotes_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($quotes_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($quotes_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($quotes_share_write_per['USR']).");\n\n";

			//Constructing the Quote SalesOrder Related Module Sharing Array
			$qt_related_so=getRelatedModuleSharingArray("Quotes","SalesOrder",$quotes_sharingrule_members,$quotes_share_read_per,$quotes_share_write_per,$def_org_share);

			$qt_so_share_read_per=$qt_related_so['read'];
			$qt_so_share_write_per=$qt_related_so['write'];

			$newbuf .= "\$Quotes_SalesOrder_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($qt_so_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($qt_so_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($qt_so_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Quotes_SalesOrder_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($qt_so_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($qt_so_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($qt_so_share_write_per['USR']).");\n\n";




			//Constructing Orders Sharing Rules
			$po_share_per_array=getUserModuleSharingObjects("PurchaseOrder",$userid,$def_org_share,$current_user_roles,$parent_roles,$current_user_groups);
			$po_share_read_per=$po_share_per_array['read'];
			$po_share_write_per=$po_share_per_array['write'];
			$newbuf .= "\$PurchaseOrder_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($po_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalArray($po_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($po_share_read_per['USR']).");\n\n";
			$newbuf .= "\$PurchaseOrder_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($po_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalArray($po_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($po_share_write_per['USR']).");\n\n";

			//Constructing Sales Order Sharing Rules
			$so_share_per_array=getUserModuleSharingObjects("SalesOrder",$userid,$def_org_share,$current_user_roles,$parent_roles,$current_user_groups);
			$so_share_read_per=$so_share_per_array['read'];
			$so_share_write_per=$so_share_per_array['write'];
			$so_sharingrule_members=$so_share_per_array['sharingrules'];
			$newbuf .= "\$SalesOrder_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($so_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($so_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($so_share_read_per['USR']).");\n\n";
			$newbuf .= "\$SalesOrder_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($so_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($so_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($so_share_write_per['USR']).");\n\n";

			//Constructing the SalesOrder Invoice Related Module Sharing Array
			$so_related_inv=getRelatedModuleSharingArray("SalesOrder","Invoice",$so_sharingrule_members,$so_share_read_per,$so_share_write_per,$def_org_share);

			$so_inv_share_read_per=$so_related_inv['read'];
			$so_inv_share_write_per=$so_related_inv['write'];

			$newbuf .= "\$SalesOrder_Invoice_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($so_inv_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($so_inv_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($so_inv_share_read_per['USR']).");\n\n";
			$newbuf .= "\$SalesOrder_Invoice_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($so_inv_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalValueArray($so_inv_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($so_inv_share_write_per['USR']).");\n\n";



			//Constructing Invoice Sharing Rules
			$inv_share_per_array=getUserModuleSharingObjects("Invoice",$userid,$def_org_share,$current_user_roles,$parent_roles,$current_user_groups);
			$inv_share_read_per=$inv_share_per_array['read'];
			$inv_share_write_per=$inv_share_per_array['write'];
			$newbuf .= "\$Invoice_share_read_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($inv_share_read_per['ROLE']).",'GROUP'=>".constructTwoDimensionalArray($inv_share_read_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($inv_share_read_per['USR']).");\n\n";
			$newbuf .= "\$Invoice_share_write_permission=array('ROLE'=>".constructTwoDimensionalCharIntSingleValueArray($inv_share_write_per['ROLE']).",'GROUP'=>".constructTwoDimensionalArray($inv_share_write_per['GROUP']).",'USR'=>".constructSingleArray_emptycheck($inv_share_write_per['USR']).");\n\n";
//crmv@7222e

			// Writing Sharing Rules For Custom Modules.
			// TODO: We are ignoring rules that has already been calculated above, it is good to add GENERIC logic here.
			$custom_modules = getSharingModuleList(
				Array('Leads', 'Accounts', 'Contacts', 'Potentials', 'HelpDesk',
				'Emails', 'Campaigns','Quotes', 'PurchaseOrder', 'SalesOrder', 'Invoice'));

			for($idx = 0; $idx < count($custom_modules); ++$idx) {
				$module_name = $custom_modules[$idx];
				$mod_share_perm_array = getUserModuleSharingObjects($module_name,$userid,
					$def_org_share,$current_user_roles,$parent_roles,$current_user_groups);

				$mod_share_read_perm = $mod_share_perm_array['read'];
				$mod_share_write_perm= $mod_share_perm_array['write'];
				$newbuf .= '$'.$module_name."_share_read_permission=array('ROLE'=>".
					constructTwoDimensionalCharIntSingleValueArray($mod_share_read_perm['ROLE']).",'GROUP'=>".
					constructTwoDimensionalArray($mod_share_read_perm['GROUP']).",'USR'=>".constructSingleArray_emptycheck($mod_share_read_perm['USR']).");\n\n"; //crmv@42329
				$newbuf .= '$'.$module_name."_share_write_permission=array('ROLE'=>".
					constructTwoDimensionalCharIntSingleValueArray($mod_share_write_perm['ROLE']).",'GROUP'=>".
					constructTwoDimensionalArray($mod_share_write_perm['GROUP']).",'USR'=>".constructSingleArray_emptycheck($mod_share_write_perm['USR']).");\n\n"; //crmv@42329
			}
			// END

			//crmv@7221
			//Constructing the Related Module Advanced Sharing Array
			$relModSharArr = GetAdvRelModList(); // crmv@193648

			$newbuf .= "\$related_module_adv_share=".constructTwoDimensionalValueArray($relModSharArr).";\n\n";

			include_once('modules/CustomView/CustomView.php');
			$cv = CRMEntity::getInstance('CustomView'); // crmv@115329
			$res=get_advanced_query(getAdvSharingRules("Accounts",$userid),$cv,$user_focus);
			$newbuf .="\$Accounts_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			//crmv@13979
			$res=get_advanced_query(getAdvSharingRules("Contacts",$userid),$cv,$user_focus);
			$newbuf .="\$Accounts_Contacts_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";
			//crmv@13979 end
			$res=get_advanced_query(getAdvRelatedSharingRules("Accounts","HelpDesk",$userid),$cv,$user_focus);
			$newbuf .="\$Accounts_HelpDesk_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvRelatedSharingRules("Accounts","Potentials",$userid),$cv,$user_focus);
			$newbuf .="\$Accounts_Potentials_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvRelatedSharingRules("Accounts","Emails",$userid),$cv,$user_focus);
			$newbuf .="\$Accounts_Emails_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvRelatedSharingRules("Accounts","Quotes",$userid),$cv,$user_focus);
			$newbuf .="\$Accounts_Quotes_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvRelatedSharingRules("Accounts","SalesOrder",$userid),$cv,$user_focus);
			$newbuf .="\$Accounts_SalesOrder_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvRelatedSharingRules("Accounts","Invoice",$userid),$cv,$user_focus);
			$newbuf .="\$Accounts_Invoice_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvSharingRules("Leads",$userid),$cv,$user_focus);
			$newbuf .="\$Leads_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvSharingRules("Potentials",$userid),$cv,$user_focus);
			$newbuf .="\$Potentials_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvRelatedSharingRules("Potentials","Quotes",$userid),$cv,$user_focus);
			$newbuf .="\$Potentials_Quotes_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvRelatedSharingRules("Potentials","SalesOrder",$userid),$cv,$user_focus);
			$newbuf .="\$Potentials_SalesOrder_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvSharingRules("HelpDesk",$userid),$cv,$user_focus);
			$newbuf .="\$HelpDesk_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvSharingRules("Campaigns",$userid),$cv,$user_focus);
			$newbuf .="\$Campaigns_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvSharingRules("Quotes",$userid),$cv,$user_focus);
			$newbuf .="\$Quotes_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvRelatedSharingRules("Quotes","SalesOrder",$userid),$cv,$user_focus);
			$newbuf .="\$Quotes_SalesOrder_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvSharingRules("PurchaseOrder",$userid),$cv,$user_focus);
			$newbuf .="\$PurchaseOrder_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvSharingRules("SalesOrder",$userid),$cv,$user_focus);
			$newbuf .="\$SalesOrder_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvRelatedSharingRules("SalesOrder","Invoice",$userid),$cv,$user_focus);
			$newbuf .="\$SalesOrder_Invoice_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			$res=get_advanced_query(getAdvSharingRules("Invoice",$userid),$cv,$user_focus);
			$newbuf .="\$Invoice_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";

			// Writing Sharing Rules For Custom Modules.
			// TODO: We are ignoring rules that has already been calculated above, it is good to add GENERIC logic here.
			$custom_modules = getSharingModuleList(
				Array('Leads', 'Accounts', 'Contacts', 'Potentials', 'HelpDesk',
				'Emails', 'Campaigns','Quotes', 'PurchaseOrder', 'SalesOrder', 'Invoice'));

			for($idx = 0; $idx < count($custom_modules); ++$idx) {
				$module_name = $custom_modules[$idx];
				$res=get_advanced_query(getAdvSharingRules($module_name,$userid),$cv,$user_focus);
				$newbuf .="\$".$module_name."_adv_role=array('listview'=>".$res['listview'].",'read'=>".$res['read'].",'write'=>".$res['write'].",'columns'=>'".$res['columns']."');\n\n";
			}
			// END
			//crmv@7221e
			$newbuf .= "?>";
			fputs($handle, $newbuf);
			fclose($handle);

			//Populating Temp Tables
			populateSharingtmptables($userid);

			// crmv@63349 - populate user table
			$tutables = TmpUserTables::getInstance();
			$tutables->cleanTmpForUser($userid);
			$tutables->generateTmpForUser($userid);
			
			$tumtables = TmpUserModTables::getInstance();
			$tumtables->cleanTmpForUser($userid);
			$tumtables->generateTmpForUser($userid);

			$tmodreltables = TmpUserModRelTables::getInstance();
			$tmodreltables->cleanTmpForUser($userid);
			// crmv@63349e
		}
	}
}

// crmv@193648
function GetDSRelModList() {
	global $adb, $table_prefix;
	
	static $relModSharArr = null;
		
	if (is_null($relModSharArr)) {
		$result = $adb->query("SELECT * FROM {$table_prefix}_datashare_relmod");
		$num_rows = $adb->num_rows($result);
		for($i=0;$i<$num_rows;$i++) {
			$parTabId=$adb->query_result_no_html($result,$i,'tabid');
			$relTabId=$adb->query_result_no_html($result,$i,'relatedto_tabid');
			if (is_array($relModSharArr[$relTabId])) {
				$temArr=$relModSharArr[$relTabId];
				$temArr[]=$parTabId;
			} else {
				$temArr=Array();
				$temArr[]=$parTabId;
			}
			$relModSharArr[$relTabId]=$temArr;
		}
	}
	
	return $relModSharArr;
}

function GetAdvRelModList() {
	global $adb;

	static $relModSharArr = null;
	
	if (is_null($relModSharArr)) {
		$result = $adb->query("SELECT * FROM tbl_s_advrule_relmodlist");
		$num_rows = $adb->num_rows($result);
		for($i=0;$i<$num_rows;$i++) {
			$parTabId = $adb->query_result_no_html($result,$i,'tabid');
			$relTabId = $adb->query_result_no_html($result,$i,'relatedto_tabid');
			if (is_array($relModSharArr[$relTabId])) {
				$temArr=$relModSharArr[$relTabId];
				$temArr[]=$parTabId;
			} else {
				$temArr=Array();
				$temArr[]=$parTabId;
			}
			$relModSharArr[$relTabId]=$temArr;
		}
	}
	
	return $relModSharArr;
}
// crmv@193648e


/** Gives an array which contains the information for what all roles, groups and user data is to be shared with the spcified user for the specified module

  * @param $module -- module name:: Type varchar
  * @param $userid -- user id:: Type integer
  * @param $def_org_share -- default organization sharing permission array:: Type array
  * @param $current_user_roles -- roleid:: Type varchar
  * @param $parent_roles -- parent roles:: Type varchar
  * @param $current_user_groups -- user id:: Type integer
  * @returns $mod_share_permission -- array which contains the id of roles,group and users data shared with specifed user for the specified module
 */
//crmv@160797
function getUserModuleSharingObjects($module,$userid,$def_org_share,$current_user_roles,$parent_roles,$current_user_groups)
{
	global $adb,$table_prefix;
	
	$mod_tabid=getTabid($module);
	
	$mod_share_permission;
	$mod_share_read_permission=Array();
	$mod_share_write_permission=Array();
	$mod_share_read_permission['ROLE']=Array();
	$mod_share_write_permission['ROLE']=Array();
	$mod_share_read_permission['GROUP']=Array();
	$mod_share_write_permission['GROUP']=Array();
	//crmv@7222
	$mod_share_read_permission['USR']=Array();
	$mod_share_write_permission['USR']=Array();
	//crmv@7222e
	
	$share_id_members=Array();
	$share_id_groupmembers=Array();
	//If Sharing of leads is Private
	if($def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 0 || $def_org_share[$mod_tabid] == 8)
	{
		$role_read_per=Array();
		$role_write_per=Array();
		$rs_read_per=Array();
		$rs_write_per=Array();
		$grp_read_per=Array();
		$grp_write_per=Array();
		//crmv@7222
		$usr_read_per=Array();
		$usr_write_per=Array();
		//crmv@7222e
		
		//Retreiving from vte_role to vte_role
		$query="select ".$table_prefix."_datashare_role2role.* from ".$table_prefix."_datashare_role2role inner join ".$table_prefix."_datashare_mod_rel on ".$table_prefix."_datashare_mod_rel.shareid=".$table_prefix."_datashare_role2role.shareid where ".$table_prefix."_datashare_mod_rel.tabid=? and ".$table_prefix."_datashare_role2role.to_roleid=?";
		$result=$adb->pquery($query, array($mod_tabid, $current_user_roles));
		$num_rows=$adb->num_rows($result);
		for($i=0;$i<$num_rows;$i++)
		{
			$share_roleid=$adb->query_result($result,$i,'share_roleid');
			
			$shareid=$adb->query_result($result,$i,'shareid');
			$share_id_role_members=Array();
			$share_id_roles=Array();
			$share_id_roles[]=$share_roleid;
			$share_id_role_members['ROLE']=$share_id_roles;
			$share_id_members[$shareid]=$share_id_role_members;
			
			$share_permission=$adb->query_result($result,$i,'permission');
			if($share_permission == 1)
			{
				if($def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
				{
					if(! array_key_exists($share_roleid,$role_read_per))
					{
						
						$share_role_users=getRoleUserIds($share_roleid);
						$role_read_per[$share_roleid]=$share_role_users;
					}
				}
				if(! array_key_exists($share_roleid,$role_write_per))
				{
					
					$share_role_users=getRoleUserIds($share_roleid);
					$role_write_per[$share_roleid]=$share_role_users;
				}
			}
			elseif($share_permission == 0 && $def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
			{
				if(! array_key_exists($share_roleid,$role_read_per))
				{
					
					$share_role_users=getRoleUserIds($share_roleid);
					$role_read_per[$share_roleid]=$share_role_users;
				}
				
			}
		}
		
		
		//Retreiving from role to rs
		$parRoleList = array();
		foreach($parent_roles as $par_role_id)
		{
			array_push($parRoleList, $par_role_id);
		}
		array_push($parRoleList, $current_user_roles);
		$query="select ".$table_prefix."_datashare_role2rs.* from ".$table_prefix."_datashare_role2rs inner join ".$table_prefix."_datashare_mod_rel on ".$table_prefix."_datashare_mod_rel.shareid=".$table_prefix."_datashare_role2rs.shareid where ".$table_prefix."_datashare_mod_rel.tabid=? and ".$table_prefix."_datashare_role2rs.to_roleandsubid in (". generateQuestionMarks($parRoleList) .")";
		$result=$adb->pquery($query, array($mod_tabid, $parRoleList));
		$num_rows=$adb->num_rows($result);
		for($i=0;$i<$num_rows;$i++)
		{
			$share_roleid=$adb->query_result($result,$i,'share_roleid');
			
			$shareid=$adb->query_result($result,$i,'shareid');
			$share_id_role_members=Array();
			$share_id_roles=Array();
			$share_id_roles[]=$share_roleid;
			$share_id_role_members['ROLE']=$share_id_roles;
			$share_id_members[$shareid]=$share_id_role_members;
			
			$share_permission=$adb->query_result($result,$i,'permission');
			if($share_permission == 1)
			{
				if($def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
				{
					if(! array_key_exists($share_roleid,$role_read_per))
					{
						
						$share_role_users=getRoleUserIds($share_roleid);
						$role_read_per[$share_roleid]=$share_role_users;
					}
				}
				if(! array_key_exists($share_roleid,$role_write_per))
				{
					
					$share_role_users=getRoleUserIds($share_roleid);
					$role_write_per[$share_roleid]=$share_role_users;
				}
			}
			elseif($share_permission == 0 && $def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
			{
				if(! array_key_exists($share_roleid,$role_read_per))
				{
					
					$share_role_users=getRoleUserIds($share_roleid);
					$role_read_per[$share_roleid]=$share_role_users;
				}
				
			}
			
		}
		
		//Get roles from Role2Grp
		$grpIterator=false;
		$groupList = $current_user_groups;
		if (empty($groupList)) $groupList = array(0); //crmv@27785
		
		if (!empty($groupList)) {	//crmv@27785
			$query="select ".$table_prefix."_datashare_role2group.* from ".$table_prefix."_datashare_role2group inner join ".$table_prefix."_datashare_mod_rel on ".$table_prefix."_datashare_mod_rel.shareid=".$table_prefix."_datashare_role2group.shareid where ".$table_prefix."_datashare_mod_rel.tabid=?";
			$qparams = array($mod_tabid);
			if (count($groupList) > 0) {
				$query .= " and ".$table_prefix."_datashare_role2group.to_groupid in (". generateQuestionMarks($groupList) .")";
				array_push($qparams, $groupList);
			}
			$result=$adb->pquery($query, $qparams);
			$num_rows=$adb->num_rows($result);
			for($i=0;$i<$num_rows;$i++)
			{
				$share_roleid=$adb->query_result($result,$i,'share_roleid');
				$shareid=$adb->query_result($result,$i,'shareid');
				$share_id_role_members=Array();
				$share_id_roles=Array();
				$share_id_roles[]=$share_roleid;
				$share_id_role_members['ROLE']=$share_id_roles;
				$share_id_members[$shareid]=$share_id_role_members;
				
				$share_permission=$adb->query_result($result,$i,'permission');
				if($share_permission == 1)
				{
					if($def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
					{
						if(! array_key_exists($share_roleid,$role_read_per))
						{
							
							$share_role_users=getRoleUserIds($share_roleid);
							$role_read_per[$share_roleid]=$share_role_users;
						}
					}
					if(! array_key_exists($share_roleid,$role_write_per))
					{
						
						$share_role_users=getRoleUserIds($share_roleid);
						$role_write_per[$share_roleid]=$share_role_users;
					}
				}
				elseif($share_permission == 0 && $def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
				{
					if(! array_key_exists($share_roleid,$role_read_per))
					{
						
						$share_role_users=getRoleUserIds($share_roleid);
						$role_read_per[$share_roleid]=$share_role_users;
					}
					
				}
			}
		}	//crmv@27785
		
		
		//Retreiving from rs to vte_role
		$query="select ".$table_prefix."_datashare_rs2role.* from ".$table_prefix."_datashare_rs2role inner join ".$table_prefix."_datashare_mod_rel on ".$table_prefix."_datashare_mod_rel.shareid=".$table_prefix."_datashare_rs2role.shareid where ".$table_prefix."_datashare_mod_rel.tabid=? and ".$table_prefix."_datashare_rs2role.to_roleid=?";
		$result=$adb->pquery($query, array($mod_tabid, $current_user_roles));
		$num_rows=$adb->num_rows($result);
		for($i=0;$i<$num_rows;$i++)
		{
			$share_rsid=$adb->query_result($result,$i,'share_roleandsubid');
			$share_roleids=getRoleAndSubordinatesRoleIds($share_rsid);
			$share_permission=$adb->query_result($result,$i,'permission');
			
			$shareid=$adb->query_result($result,$i,'shareid');
			$share_id_role_members=Array();
			$share_id_roles=Array();
			foreach($share_roleids as $share_roleid)
			{
				$share_id_roles[]=$share_roleid;
				
				
				if($share_permission == 1)
				{
					if($def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
					{
						if(! array_key_exists($share_roleid,$role_read_per))
						{
							
							$share_role_users=getRoleUserIds($share_roleid);
							$role_read_per[$share_roleid]=$share_role_users;
						}
					}
					if(! array_key_exists($share_roleid,$role_write_per))
					{
						
						$share_role_users=getRoleUserIds($share_roleid);
						$role_write_per[$share_roleid]=$share_role_users;
					}
				}
				elseif($share_permission == 0 && $def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
				{
					if(! array_key_exists($share_roleid,$role_read_per))
					{
						
						$share_role_users=getRoleUserIds($share_roleid);
						$role_read_per[$share_roleid]=$share_role_users;
					}
					
				}
			}
			$share_id_role_members['ROLE']=$share_id_roles;
			$share_id_members[$shareid]=$share_id_role_members;
			
		}
		
		
		//Retreiving from rs to rs
		$parRoleList = array();
		foreach($parent_roles as $par_role_id)
		{
			array_push($parRoleList, $par_role_id);
		}
		array_push($parRoleList, $current_user_roles);
		$query="select ".$table_prefix."_datashare_rs2rs.* from ".$table_prefix."_datashare_rs2rs inner join ".$table_prefix."_datashare_mod_rel on ".$table_prefix."_datashare_mod_rel.shareid=".$table_prefix."_datashare_rs2rs.shareid where ".$table_prefix."_datashare_mod_rel.tabid=? and ".$table_prefix."_datashare_rs2rs.to_roleandsubid in (". generateQuestionMarks($parRoleList) .")";
		$result=$adb->pquery($query, array($mod_tabid, $parRoleList));
		$num_rows=$adb->num_rows($result);
		for($i=0;$i<$num_rows;$i++)
		{
			$share_rsid=$adb->query_result($result,$i,'share_roleandsubid');
			$share_roleids=getRoleAndSubordinatesRoleIds($share_rsid);
			$share_permission=$adb->query_result($result,$i,'permission');
			
			$shareid=$adb->query_result($result,$i,'shareid');
			$share_id_role_members=Array();
			$share_id_roles=Array();
			foreach($share_roleids as $share_roleid)
			{
				
				$share_id_roles[]=$share_roleid;
				
				if($share_permission == 1)
				{
					if($def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
					{
						if(! array_key_exists($share_roleid,$role_read_per))
						{
							
							$share_role_users=getRoleUserIds($share_roleid);
							$role_read_per[$share_roleid]=$share_role_users;
						}
					}
					if(! array_key_exists($share_roleid,$role_write_per))
					{
						
						$share_role_users=getRoleUserIds($share_roleid);
						$role_write_per[$share_roleid]=$share_role_users;
					}
				}
				elseif($share_permission == 0 && $def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
				{
					if(! array_key_exists($share_roleid,$role_read_per))
					{
						
						$share_role_users=getRoleUserIds($share_roleid);
						$role_read_per[$share_roleid]=$share_role_users;
					}
					
				}
			}
			$share_id_role_members['ROLE']=$share_id_roles;
			$share_id_members[$shareid]=$share_id_role_members;
			
		}
		
		//Get roles from Rs2Grp
		
		$query="select ".$table_prefix."_datashare_rs2grp.* from ".$table_prefix."_datashare_rs2grp inner join ".$table_prefix."_datashare_mod_rel on ".$table_prefix."_datashare_mod_rel.shareid=".$table_prefix."_datashare_rs2grp.shareid where ".$table_prefix."_datashare_mod_rel.tabid=?";
		$qparams = array($mod_tabid);
		if (count($groupList) > 0) {
			$query .= " and ".$table_prefix."_datashare_rs2grp.to_groupid in (". generateQuestionMarks($groupList) .")";
			array_push($qparams, $groupList);
		}
		$result=$adb->pquery($query, $qparams);
		$num_rows=$adb->num_rows($result);
		for($i=0;$i<$num_rows;$i++)
		{
			$share_rsid=$adb->query_result($result,$i,'share_roleandsubid');
			$share_roleids=getRoleAndSubordinatesRoleIds($share_rsid);
			$share_permission=$adb->query_result($result,$i,'permission');
			
			$shareid=$adb->query_result($result,$i,'shareid');
			$share_id_role_members=Array();
			$share_id_roles=Array();
			
			foreach($share_roleids as $share_roleid)
			{
				
				$share_id_roles[]=$share_roleid;
				
				if($share_permission == 1)
				{
					if($def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
					{
						if(! array_key_exists($share_roleid,$role_read_per))
						{
							
							$share_role_users=getRoleUserIds($share_roleid);
							$role_read_per[$share_roleid]=$share_role_users;
						}
					}
					if(! array_key_exists($share_roleid,$role_write_per))
					{
						
						$share_role_users=getRoleUserIds($share_roleid);
						$role_write_per[$share_roleid]=$share_role_users;
					}
				}
				elseif($share_permission == 0 && $def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
				{
					if(! array_key_exists($share_roleid,$role_read_per))
					{
						
						$share_role_users=getRoleUserIds($share_roleid);
						$role_read_per[$share_roleid]=$share_role_users;
					}
					
				}
			}
			$share_id_role_members['ROLE']=$share_id_roles;
			$share_id_members[$shareid]=$share_id_role_members;
			
			
			
		}
		$mod_share_read_permission['ROLE']=$role_read_per;
		$mod_share_write_permission['ROLE']=$role_write_per;
		
		
		//Retreiving from the grp2role sharing
		$query="select ".$table_prefix."_datashare_grp2role.* from ".$table_prefix."_datashare_grp2role inner join ".$table_prefix."_datashare_mod_rel on ".$table_prefix."_datashare_mod_rel.shareid=".$table_prefix."_datashare_grp2role.shareid where ".$table_prefix."_datashare_mod_rel.tabid=? and ".$table_prefix."_datashare_grp2role.to_roleid=?";
		$result=$adb->pquery($query, array($mod_tabid, $current_user_roles));
		$num_rows=$adb->num_rows($result);
		for($i=0;$i<$num_rows;$i++)
		{
			$share_grpid=$adb->query_result($result,$i,'share_groupid');
			$share_permission=$adb->query_result($result,$i,'permission');
			
			$shareid=$adb->query_result($result,$i,'shareid');
			$share_id_grp_members=Array();
			$share_id_grps=Array();
			$share_id_grps[]=$share_grpid;
			
			
			if($share_permission == 1)
			{
				if($def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
				{
					if(! array_key_exists($share_grpid,$grp_read_per))
					{
						$focusGrpUsers = new GetGroupUsers();
						$focusGrpUsers->getAllUsersInGroup($share_grpid);
						$share_grp_users=$focusGrpUsers->group_users;
						$share_grp_subgroups=$focusGrpUsers->group_subgroups;
						$grp_read_per[$share_grpid]=$share_grp_users;
						foreach($focusGrpUsers->group_subgroups as $subgrpid=>$subgrpusers)
						{
							if(! array_key_exists($subgrpid,$grp_read_per))
							{
								$grp_read_per[$subgrpid]=$subgrpusers;
							}
							if(! in_array($subgrpid,$share_id_grps))
							{
								$share_id_grps[]=$subgrpid;
							}
							
						}
					}
				}
				if(! array_key_exists($share_grpid,$grp_write_per))
				{
					$focusGrpUsers = new GetGroupUsers();
					$focusGrpUsers->getAllUsersInGroup($share_grpid);
					$share_grp_users=$focusGrpUsers->group_users;
					$grp_write_per[$share_grpid]=$share_grp_users;
					foreach($focusGrpUsers->group_subgroups as $subgrpid=>$subgrpusers)
					{
						if(! array_key_exists($subgrpid,$grp_write_per))
						{
							$grp_write_per[$subgrpid]=$subgrpusers;
						}
						if(! in_array($subgrpid,$share_id_grps))
						{
							$share_id_grps[]=$subgrpid;
						}
						
					}
					
				}
			}
			elseif($share_permission == 0 && $def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
			{
				if(! array_key_exists($share_grpid,$grp_read_per))
				{
					$focusGrpUsers = new GetGroupUsers();
					$focusGrpUsers->getAllUsersInGroup($share_grpid);
					$share_grp_users=$focusGrpUsers->group_users;
					$grp_read_per[$share_grpid]=$share_grp_users;
					foreach($focusGrpUsers->group_subgroups as $subgrpid=>$subgrpusers)
					{
						if(! array_key_exists($subgrpid,$grp_read_per))
						{
							$grp_read_per[$subgrpid]=$subgrpusers;
						}
						if(! in_array($subgrpid,$share_id_grps))
						{
							$share_id_grps[]=$subgrpid;
						}
						
					}
				}
				
			}
			$share_id_grp_members['GROUP']=$share_id_grps;
			$share_id_members[$shareid]=$share_id_grp_members;
			
		}
		
		//Retreiving from the grp2rs sharing
		
		$query="select ".$table_prefix."_datashare_grp2rs.* from ".$table_prefix."_datashare_grp2rs inner join ".$table_prefix."_datashare_mod_rel on ".$table_prefix."_datashare_mod_rel.shareid=".$table_prefix."_datashare_grp2rs.shareid where ".$table_prefix."_datashare_mod_rel.tabid=? and ".$table_prefix."_datashare_grp2rs.to_roleandsubid in (". generateQuestionMarks($parRoleList) .")";
		$result=$adb->pquery($query, array($mod_tabid, $parRoleList));
		$num_rows=$adb->num_rows($result);
		for($i=0;$i<$num_rows;$i++)
		{
			$share_grpid=$adb->query_result($result,$i,'share_groupid');
			$share_permission=$adb->query_result($result,$i,'permission');
			
			$shareid=$adb->query_result($result,$i,'shareid');
			$share_id_grp_members=Array();
			$share_id_grps=Array();
			$share_id_grps[]=$share_grpid;
			
			
			if($share_permission == 1)
			{
				if($def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
				{
					if(! array_key_exists($share_grpid,$grp_read_per))
					{
						$focusGrpUsers = new GetGroupUsers();
						$focusGrpUsers->getAllUsersInGroup($share_grpid);
						$share_grp_users=$focusGrpUsers->group_users;
						$grp_read_per[$share_grpid]=$share_grp_users;
						
						foreach($focusGrpUsers->group_subgroups as $subgrpid=>$subgrpusers)
						{
							if(! array_key_exists($subgrpid,$grp_read_per))
							{
								$grp_read_per[$subgrpid]=$subgrpusers;
							}
							if(! in_array($subgrpid,$share_id_grps))
							{
								$share_id_grps[]=$subgrpid;
							}
							
						}
					}
				}
				if(! array_key_exists($share_grpid,$grp_write_per))
				{
					$focusGrpUsers = new GetGroupUsers();
					$focusGrpUsers->getAllUsersInGroup($share_grpid);
					$share_grp_users=$focusGrpUsers->group_users;
					$grp_write_per[$share_grpid]=$share_grp_users;
					foreach($focusGrpUsers->group_subgroups as $subgrpid=>$subgrpusers)
					{
						if(! array_key_exists($subgrpid,$grp_write_per))
						{
							$grp_write_per[$subgrpid]=$subgrpusers;
						}
						if(! in_array($subgrpid,$share_id_grps))
						{
							$share_id_grps[]=$subgrpid;
						}
						
					}
					
				}
			}
			elseif($share_permission == 0 && $def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
			{
				if(! array_key_exists($share_grpid,$grp_read_per))
				{
					$focusGrpUsers = new GetGroupUsers();
					$focusGrpUsers->getAllUsersInGroup($share_grpid);
					$share_grp_users=$focusGrpUsers->group_users;
					$grp_read_per[$share_grpid]=$share_grp_users;
					foreach($focusGrpUsers->group_subgroups as $subgrpid=>$subgrpusers)
					{
						if(! array_key_exists($subgrpid,$grp_read_per))
						{
							$grp_read_per[$subgrpid]=$subgrpusers;
						}
						if(! in_array($subgrpid,$share_id_grps))
						{
							$share_id_grps[]=$subgrpid;
						}
						
					}
				}
				
			}
			$share_id_grp_members['GROUP']=$share_id_grps;
			$share_id_members[$shareid]=$share_id_grp_members;
			
		}
		
		//Retreiving from the grp2grp sharing
		
		$query="select ".$table_prefix."_datashare_grp2grp.* from ".$table_prefix."_datashare_grp2grp inner join ".$table_prefix."_datashare_mod_rel on ".$table_prefix."_datashare_mod_rel.shareid=".$table_prefix."_datashare_grp2grp.shareid where ".$table_prefix."_datashare_mod_rel.tabid=?";
		$qparams = array($mod_tabid);
		if (count($groupList) > 0) {
			$query .= " and ".$table_prefix."_datashare_grp2grp.to_groupid in (". generateQuestionMarks($groupList) .")";
			array_push($qparams, $groupList);
		}
		$result=$adb->pquery($query, $qparams);
		$num_rows=$adb->num_rows($result);
		for($i=0;$i<$num_rows;$i++)
		{
			$share_grpid=$adb->query_result($result,$i,'share_groupid');
			$share_permission=$adb->query_result($result,$i,'permission');
			
			$shareid=$adb->query_result($result,$i,'shareid');
			$share_id_grp_members=Array();
			$share_id_grps=Array();
			$share_id_grps[]=$share_grpid;
			
			if($share_permission == 1)
			{
				if($def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
				{
					if(! array_key_exists($share_grpid,$grp_read_per))
					{
						$focusGrpUsers = new GetGroupUsers();
						$focusGrpUsers->getAllUsersInGroup($share_grpid);
						$share_grp_users=$focusGrpUsers->group_users;
						$grp_read_per[$share_grpid]=$share_grp_users;
						foreach($focusGrpUsers->group_subgroups as $subgrpid=>$subgrpusers)
						{
							if(! array_key_exists($subgrpid,$grp_read_per))
							{
								$grp_read_per[$subgrpid]=$subgrpusers;
							}
							if(! in_array($subgrpid,$share_id_grps))
							{
								$share_id_grps[]=$subgrpid;
							}
							
							
						}
					}
				}
				if(! array_key_exists($share_grpid,$grp_write_per))
				{
					$focusGrpUsers = new GetGroupUsers();
					$focusGrpUsers->getAllUsersInGroup($share_grpid);
					$share_grp_users=$focusGrpUsers->group_users;
					$grp_write_per[$share_grpid]=$share_grp_users;
					foreach($focusGrpUsers->group_subgroups as $subgrpid=>$subgrpusers)
					{
						if(! array_key_exists($subgrpid,$grp_write_per))
						{
							$grp_write_per[$subgrpid]=$subgrpusers;
						}
						if(! in_array($subgrpid,$share_id_grps))
						{
							$share_id_grps[]=$subgrpid;
						}
						
					}
					
				}
			}
			elseif($share_permission == 0 && $def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
			{
				if(! array_key_exists($share_grpid,$grp_read_per))
				{
					$focusGrpUsers = new GetGroupUsers();
					$focusGrpUsers->getAllUsersInGroup($share_grpid);
					$share_grp_users=$focusGrpUsers->group_users;
					$grp_read_per[$share_grpid]=$share_grp_users;
					foreach($focusGrpUsers->group_subgroups as $subgrpid=>$subgrpusers)
					{
						if(! array_key_exists($subgrpid,$grp_read_per))
						{
							$grp_read_per[$subgrpid]=$subgrpusers;
						}
						if(! in_array($subgrpid,$share_id_grps))
						{
							$share_id_grps[]=$subgrpid;
						}
						
					}
				}
				
			}
			$share_id_grp_members['GROUP']=$share_id_grps;
			$share_id_members[$shareid]=$share_id_grp_members;
			
		}
		$mod_share_read_permission['GROUP']=$grp_read_per;
		$mod_share_write_permission['GROUP']=$grp_write_per;
		
		//crmv@7222
		//Retreiving from the usr2usr sharing
		
		$query="select ".$table_prefix."_datashare_usr2usr.* from ".$table_prefix."_datashare_usr2usr inner join ".$table_prefix."_datashare_mod_rel on ".$table_prefix."_datashare_mod_rel.shareid=".$table_prefix."_datashare_usr2usr.shareid where ".$table_prefix."_datashare_mod_rel.tabid=? and to_userid =?";
		$qparams = array($mod_tabid,$userid);
		$result=$adb->pquery($query, $qparams);
		$num_rows=$adb->num_rows($result);
		$user_sharing=Array();
		$share_id_usr=Array();
		for($i=0;$i<$num_rows;$i++)
		{
			$shareid=$adb->query_result($result,$i,'shareid');
			$share_to_usrid=$adb->query_result($result,$i,'share_userid');
			$share_permission=$adb->query_result($result,$i,'permission');
			
			if($share_permission == 1)
			{
				if($def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
				{
					$usr_write_per[$userid][]=$share_to_usrid;
					$usr_read_per[$userid][]=$share_to_usrid;
				}
			}
			elseif($share_permission == 0 && $def_org_share[$mod_tabid] == 3 || $def_org_share[$mod_tabid] == 8)
			{
				$usr_read_per[$userid][]=$share_to_usrid;
			}
			$share_id_usr_members['USR'][]=$share_to_usrid;
			$share_id_members[$shareid]=$share_id_usr_members;
		}
		$mod_share_read_permission['USR']=$usr_read_per[$userid];
		$mod_share_write_permission['USR']=$usr_write_per[$userid];
		//crmv@7222e
	}
	$mod_share_permission['read']=$mod_share_read_permission;
	$mod_share_permission['write']=$mod_share_write_permission;
	$mod_share_permission['sharingrules']=$share_id_members;
	return $mod_share_permission;
}
//crmv@160797e

/** Gives an array which contains the information for what all roles, groups and user's related module data that is to be shared  for the specified parent module and shared module

  * @param $par_mod -- parent module name:: Type varchar
  * @param $share_mod -- shared module name:: Type varchar
  * @param $userid -- user id:: Type integer
  * @param $def_org_share -- default organization sharing permission array:: Type array
  * @param $mod_sharingrule_members -- Sharing Rule Members array:: Type array
  * @param $$mod_share_read_per -- Sharing Module Read Permission array:: Type array
  * @param $$mod_share_write_per -- Sharing Module Write Permission array:: Type array
  * @returns $related_mod_sharing_permission; -- array which contains the id of roles,group and users related module data to be shared
 */
function getRelatedModuleSharingArray($par_mod,$share_mod,$mod_sharingrule_members,$mod_share_read_per,$mod_share_write_per,$def_org_share)
{
	global $adb, $table_prefix;
	$related_mod_sharing_permission=Array();
	$mod_share_read_permission=Array();
	$mod_share_write_permission=Array();

	$mod_share_read_permission['ROLE']=Array();
        $mod_share_write_permission['ROLE']=Array();
        $mod_share_read_permission['GROUP']=Array();
        $mod_share_write_permission['GROUP']=Array();
//crmv@7222
   $mod_share_read_permission['USR']=Array();
   $mod_share_write_permission['USR']=Array();
//crmv@7222e
	$par_mod_id=getTabid($par_mod);
	$share_mod_id=getTabid($share_mod);

	if($def_org_share[$share_mod_id] == 3 || $def_org_share[$share_mod_id] == 0)
	{

		$role_read_per=Array();
		$role_write_per=Array();
		$grp_read_per=Array();
		$grp_write_per=Array();
//crmv@7222
		$usr_read_per=Array();
		$usr_write_per=Array();
//crmv@7222e

		foreach($mod_sharingrule_members as $sharingid => $sharingInfoArr)
		{
			$query = "select ".$table_prefix."_datashare_relmod_perm.* from ".$table_prefix."_datashare_relmod_perm inner join ".$table_prefix."_datashare_relmod on ".$table_prefix."_datashare_relmod.datashare_relatedmodule_id=".$table_prefix."_datashare_relmod_perm.datashare_relatedmodule_id where ".$table_prefix."_datashare_relmod_perm.shareid=? and ".$table_prefix."_datashare_relmod.tabid=? and ".$table_prefix."_datashare_relmod.relatedto_tabid=?";
			$result=$adb->pquery($query, array($sharingid, $par_mod_id, $share_mod_id));
			$share_permission=$adb->query_result($result,0,'permission');

			foreach($sharingInfoArr as $shareType => $shareEntArr)
			{

					foreach($shareEntArr as $key=>$shareEntId)
					{
						if($shareType == 'ROLE')
						{
							if($share_permission == 1)
							{
								if($def_org_share[$share_mod_id] == 3)
								{
									if(! array_key_exists($shareEntId,$role_read_per))
									{
										if(array_key_exists($shareEntId,$mod_share_read_per['ROLE']))
										{
											$share_role_users=$mod_share_read_per['ROLE'][$shareEntId];
										}
										elseif(array_key_exists($shareEntId,$mod_share_write_per['ROLE']))
										{
											$share_role_users=$mod_share_write_per['ROLE'][$shareEntId];
										}
										else
										{

											$share_role_users=getRoleUserIds($shareEntId);
										}

										$role_read_per[$shareEntId]=$share_role_users;

									}
								}
								if(! array_key_exists($shareEntId,$role_write_per))
								{
									if(array_key_exists($shareEntId,$mod_share_read_per['ROLE']))
									{
										$share_role_users=$mod_share_read_per['ROLE'][$shareEntId];
									}
									elseif(array_key_exists($shareEntId,$mod_share_write_per['ROLE']))
									{
										$share_role_users=$mod_share_write_per['ROLE'][$shareEntId];
									}
									else
									{

										$share_role_users=getRoleUserIds($shareEntId);

									}

									$role_write_per[$shareEntId]=$share_role_users;
								}
							}
							elseif($share_permission == 0 && $def_org_share[$share_mod_id] == 3)
							{
								if(! array_key_exists($shareEntId,$role_read_per))
								{
									if(array_key_exists($shareEntId,$mod_share_read_per['ROLE']))
									{
										$share_role_users=$mod_share_read_per['ROLE'][$shareEntId];
									}
									elseif(array_key_exists($shareEntId,$mod_share_write_per['ROLE']))
									{
										$share_role_users=$mod_share_write_per['ROLE'][$shareEntId];
									}
									else
									{

										$share_role_users=getRoleUserIds($shareEntId);
									}

									$role_read_per[$shareEntId]=$share_role_users;

								}


							}

						}
						elseif($shareType == 'GROUP')
						{
							if($share_permission == 1)
							{
								if($def_org_share[$share_mod_id] == 3)
								{

									if(! array_key_exists($shareEntId,$grp_read_per))
									{
										if(array_key_exists($shareEntId,$mod_share_read_per['GROUP']))
										{
											$share_grp_users=$mod_share_read_per['GROUP'][$shareEntId];
										}
										elseif(array_key_exists($shareEntId,$mod_share_write_per['GROUP']))
										{
											$share_grp_users=$mod_share_write_per['GROUP'][$shareEntId];
										}
										else
										{
											$focusGrpUsers = new GetGroupUsers();
											$focusGrpUsers->getAllUsersInGroup($shareEntId);
											$share_grp_users=$focusGrpUsers->group_users;

											foreach($focusGrpUsers->group_subgroups as $subgrpid=>$subgrpusers)
											{
												if(! array_key_exists($subgrpid,$grp_read_per))
												{
													$grp_read_per[$subgrpid]=$subgrpusers;
												}

											}

										}

										$grp_read_per[$shareEntId]=$share_grp_users;

									}
								}
								if(! array_key_exists($shareEntId,$grp_write_per))
								{
									if(! array_key_exists($shareEntId,$grp_write_per))
									{
										if(array_key_exists($shareEntId,$mod_share_read_per['GROUP']))
										{
											$share_grp_users=$mod_share_read_per['GROUP'][$shareEntId];
										}
										elseif(array_key_exists($shareEntId,$mod_share_write_per['GROUP']))
										{
											$share_grp_users=$mod_share_write_per['GROUP'][$shareEntId];
										}
										else
										{
											$focusGrpUsers = new GetGroupUsers();
											$focusGrpUsers->getAllUsersInGroup($shareEntId);
											$share_grp_users=$focusGrpUsers->group_users;
											foreach($focusGrpUsers->group_subgroups as $subgrpid=>$subgrpusers)
											{
												if(! array_key_exists($subgrpid,$grp_write_per))
												{
													$grp_write_per[$subgrpid]=$subgrpusers;
												}

											}

										}

										$grp_write_per[$shareEntId]=$share_grp_users;

									}
								}
							}
							elseif($share_permission == 0 && $def_org_share[$share_mod_id] == 3)
							{
								if(! array_key_exists($shareEntId,$grp_read_per))
								{
									if(array_key_exists($shareEntId,$mod_share_read_per['GROUP']))
									{
										$share_grp_users=$mod_share_read_per['GROUP'][$shareEntId];
									}
									elseif(array_key_exists($shareEntId,$mod_share_write_per['GROUP']))
									{
										$share_grp_users=$mod_share_write_per['GROUP'][$shareEntId];
									}
									else
									{
										$focusGrpUsers = new GetGroupUsers();
										$focusGrpUsers->getAllUsersInGroup($shareEntId);
										$share_grp_users=$focusGrpUsers->group_users;
										foreach($focusGrpUsers->group_subgroups as $subgrpid=>$subgrpusers)
										{
											if(! array_key_exists($subgrpid,$grp_read_per))
											{
												$grp_read_per[$subgrpid]=$subgrpusers;
											}

										}

									}

									$grp_read_per[$shareEntId]=$share_grp_users;

								}


							}
						}
//crmv@7222
						if($shareType == 'USR')
						{
							if($share_permission == 1)
							{
								if($def_org_share[$mod_tabid] == 3)
								{
									if(! in_array($shareEntId,$usr_write_per))
									{
										$usr_write_per[]=$shareEntId;
									}
									if(! in_array($shareEntId,$usr_read_per))
									{
										$usr_read_per[]=$shareEntId;
									}
								}
								else{
									if(! in_array($shareEntId,$usr_write_per))
									{
										$usr_write_per[]=$shareEntId;
									}
									if(! in_array($shareEntId,$usr_read_per))
									{
										$usr_read_per[]=$shareEntId;
									}
								}
							}
							elseif($share_permission == 0 && $def_org_share[$share_mod_id] == 3)
							{
									if(! in_array($shareEntId,$usr_read_per))
									{
										$usr_read_per[]=$shareEntId;
									}
							}
						}
				}
			}
		}
		$mod_share_read_permission['ROLE']=$role_read_per;
		$mod_share_write_permission['ROLE']=$role_write_per;
		$mod_share_read_permission['GROUP']=$grp_read_per;
		$mod_share_write_permission['GROUP']=$grp_write_per;
		$mod_share_read_permission['USR']=$usr_read_per;
		$mod_share_write_permission['USR']=$usr_write_per;
//crmv@7222e
	}

	$related_mod_sharing_permission['read']=$mod_share_read_permission;
	$related_mod_sharing_permission['write']=$mod_share_write_permission;
	return $related_mod_sharing_permission;


}


/** Converts the input array  to a single string to facilitate the writing of the input array in a flat file

  * @param $var -- input array:: Type array
  * @returns $code -- contains the whole array in a single string:: Type array
 */
function constructArray($var)
{
	if (is_array($var))
	{
		$code = 'array(';
		foreach ($var as $key => $value)
		{
			if ($value == null || $value == '') $value = "''";
			$code .= "'$key'=>$value,";
		}
		$code .= ')';
		return $code;
	} else {
		return 'null';
	}
}

/** Converts the input array  to a single string to facilitate the writing of the input array in a flat file

  * @param $var -- input array:: Type array
  * @returns $code -- contains the whole array in a single string:: Type array
 */
function constructSingleStringValueArray($var)
{

        $size = sizeof($var);
        $i=1;
        if (is_array($var))
        {
                $code = 'array(';
                foreach ($var as $key => $value)
                {
                        if($i<$size)
                        {
                                $code .= $key."=>'".$value."',";
                        }
                        else
                        {
                                $code .= $key."=>'".$value."'";
                        }
                        $i++;
                }
                $code .= ')';
                return $code;
        } else {
			return 'null';
		}
}

/** Converts the input array  to a single string to facilitate the writing of the input array in a flat file

  * @param $var -- input array:: Type array
  * @returns $code -- contains the whole array in a single string:: Type array
 */
function constructSingleStringKeyAndValueArray($var)
{

        $size = sizeof($var);
        $i=1;
        if (is_array($var))
        {
                $code = 'array(';
                foreach ($var as $key => $value)
                {
                        if($i<$size)
                        {
                                $code .= "'".$key."'=>".$value.",";
                        }
                        else
                        {
                                $code .= "'".$key."'=>".$value;
                        }
                        $i++;
                }
                $code .= ')';
                return $code;
        } else {
			return 'null';
		}
}



/** Converts the input array  to a single string to facilitate the writing of the input array in a flat file

  * @param $var -- input array:: Type array
  * @returns $code -- contains the whole array in a single string:: Type array
 */
function constructSingleStringKeyValueArray($var) {
	global $adb;
    $size = sizeof($var);
    $i=1;
    if (is_array($var)) {
		$code = 'array(';
		foreach ($var as $key => $value) {
		    //fix for signatue quote(') issue
		    $value=$adb->sql_escape_string($value);
			if($i<$size) {
				$code .= "'".$key."'=>'".$value."',";
			} else {
				$code .= "'".$key."'=>'".$value."'";
			}
			$i++;
		}
	    $code .= ')';
	    return $code;
    } else {
		return 'null';
	}
}


/** Converts the input array  to a single string to facilitate the writing of the input array in a flat file

  * @param $var -- input array:: Type array
  * @returns $code -- contains the whole array in a single string:: Type array
 */
function constructSingleArray($var)
{
	if (is_array($var))
	{
       		$code = 'array(';
       		foreach ($var as $value)
		{
           		$code .= $value.',';
       		}
       		$code .= ')';
       		return $code;
   	} else {
		return 'null';
	}
}

//crmv@7222
/** Converts the input array  to a single string to facilitate the writing of the input array in a flat file

  * @param $var -- input array:: Type array
  * @returns $code -- contains the whole array in a single string:: Type array
 */
function constructSingleArray_emptycheck($var)
{
	if (is_array($var))
	{
       		$code = 'array(';
       		foreach ($var as $value)
		{
           		$code .= $value.',';
       		}
       		$code .= ')';
       		return $code;
   }
   else return 'array()';
}
//crmv@7222e

/** Converts the input array  to a single string to facilitate the writing of the input array in a flat file

  * @param $var -- input array:: Type array
  * @returns $code -- contains the whole array in a single string:: Type array
 */
function constructSingleCharArray($var)
{
	if (is_array($var))
	{
       		$code = "array(";
       		foreach ($var as $value)
		{
           		$code .="'".$value."',";
       		}
       		$code .= ")";
       		return $code;
   	} else {
		return 'null';
	}
}


/** Converts the input array  to a single string to facilitate the writing of the input array in a flat file

  * @param $var -- input array:: Type array
  * @returns $code -- contains the whole array in a single string:: Type array
 */
function constructTwoDimensionalArray($var)
{
	if (is_array($var))
	{
       		$code = 'array(';
       		foreach ($var as $key => $secarr)
		{
           		$code .= $key.'=>array(';
			foreach($secarr as $seckey => $secvalue)
			{
				$code .= $seckey.'=>'.$secvalue.',';
			}
			$code .= '),';
       		}
       		$code .= ')';
       		return $code;
   	} else {
		return 'null';
	}
}

/** Converts the input array  to a single string to facilitate the writing of the input array in a flat file

  * @param $var -- input array:: Type array
  * @returns $code -- contains the whole array in a single string:: Type array
 */
function constructTwoDimensionalValueArray($var)
{
	if (is_array($var))
	{
       		$code = 'array(';
       		foreach ($var as $key => $secarr)
		{
           		$code .= $key.'=>array(';
			foreach($secarr as $seckey => $secvalue)
			{
				$code .= $secvalue.',';
			}
			$code .= '),';
       		}
       		$code .= ')';
       		return $code;
   	} else {
		return 'null';
	}
}

/** Converts the input array  to a single string to facilitate the writing of the input array in a flat file

  * @param $var -- input array:: Type array
  * @returns $code -- contains the whole array in a single string:: Type array
 */
function constructTwoDimensionalCharIntSingleArray($var)
{
	if (is_array($var))
	{
       		$code = "array(";
       		foreach ($var as $key => $secarr)
		{
           		$code .= "'".$key."'=>array(";
			foreach($secarr as $seckey => $secvalue)
			{
				$code .= $seckey.",";
			}
			$code .= "),";
       		}
       		$code .= ")";
       		return $code;
   	} else {
		return 'null';
	}
}

/** Converts the input array  to a single string to facilitate the writing of the input array in a flat file

  * @param $var -- input array:: Type array
  * @returns $code -- contains the whole array in a single string:: Type array
 */
function constructTwoDimensionalCharIntSingleValueArray($var)
{
	if (is_array($var))
	{
       		$code = "array(";
       		foreach ($var as $key => $secarr)
		{
           		$code .= "'".$key."'=>array(";
			foreach($secarr as $seckey => $secvalue)
			{
				$code .= $secvalue.",";
			}
			$code .= "),";
       		}
       		$code .= ")";
       		return $code;
   	} else {
		return 'null';
	}
}


/** Function to populate the read/wirte Sharing permissions data of user/groups for the specified user into the database
  * @param $userid -- user id:: Type integer
 */

function populateSharingtmptables($userid)
{
	global $adb,$table_prefix;

	require('user_privileges/sharing_privileges_'.$userid.'.php');
	//Deleting from the existing vte_tables
	$table_arr=Array($table_prefix.'_tmp_read_u_per', $table_prefix.'_tmp_write_u_per',$table_prefix.'_tmp_read_g_per',$table_prefix.'_tmp_write_g_per',$table_prefix.'_tmp_read_u_rel_per',$table_prefix.'_tmp_write_u_rel_per',$table_prefix.'_tmp_read_g_rel_per',$table_prefix.'_tmp_write_g_rel_per');
	foreach($table_arr as $tabname)
	{
		$query = "delete from ".$tabname." where userid=?";
		$adb->pquery($query, array($userid));
	}

	// Look up for modules for which sharing access is enabled.
	$sharingArray = Array('Emails');
	$otherModules = getSharingModuleList();
	$sharingArray = array_merge($sharingArray, $otherModules);
	foreach($sharingArray as $module)
	{
		$module_sharing_read_permvar    = $module.'_share_read_permission';
		$module_sharing_write_permvar   = $module.'_share_write_permission';
		//crmv@23973
		populateSharingPrivileges('USER',$userid,$module,'read',   ${$module_sharing_read_permvar} );
		populateSharingPrivileges('USER',$userid,$module,'write',  ${$module_sharing_write_permvar} );
		populateSharingPrivileges('GROUP',$userid,$module,'read',  ${$module_sharing_read_permvar} );
		populateSharingPrivileges('GROUP',$userid,$module,'write', ${$module_sharing_write_permvar} );
		//crmv@23973e
	}
	//Populating Values into the temp related sharing tables
	foreach($related_module_share as $rel_tab_id => $tabid_arr)
	{
		$rel_tab_name=getTabname($rel_tab_id);
		foreach($tabid_arr as $taid)
		{
			$tab_name=getTabname($taid);

			$relmodule_sharing_read_permvar    = $tab_name.'_'.$rel_tab_name.'_share_read_permission';
			$relmodule_sharing_write_permvar   = $tab_name.'_'.$rel_tab_name.'_share_write_permission';
			//crmv@23973
			populateRelatedSharingPrivileges('USER',$userid,$tab_name,$rel_tab_name,'read', ${$relmodule_sharing_read_permvar});
           	populateRelatedSharingPrivileges('USER',$userid,$tab_name,$rel_tab_name,'write', ${$relmodule_sharing_write_permvar});
           	populateRelatedSharingPrivileges('GROUP',$userid,$tab_name,$rel_tab_name,'read', ${$relmodule_sharing_read_permvar});
           	populateRelatedSharingPrivileges('GROUP',$userid,$tab_name,$rel_tab_name,'write', ${$relmodule_sharing_write_permvar});
           	//crmv@23973e
		}
	}
}

/** Function to populate the read/wirte Sharing permissions data for the specified user into the database
  * @param $userid -- user id:: Type integer
  * @param $enttype -- can have the value of User or Group:: Type varchar
  * @param $module -- module name:: Type varchar
  * @param $pertype -- can have the value of read or write:: Type varchar
 */
//crmv@23973 crmv@74560
function populateSharingPrivileges($enttype,$userid,$module,$pertype,$var_name_arr=Array()) {
	global $adb, $table_prefix;
	$tabid = getTabid($module);
	
	if($enttype=='USER') {
		if($pertype =='read') {
			$table_name=$table_prefix.'_tmp_read_u_per';
		} elseif($pertype == 'write') {
			$table_name=$table_prefix.'_tmp_write_u_per';
		}
		$user_arr=Array();
		$inserts = array();
		
		if(is_array($var_name_arr['ROLE']) && sizeof($var_name_arr['ROLE']) > 0) { // crmv@189598
			foreach($var_name_arr['ROLE'] as $roleid=>$roleusers) {
				foreach($roleusers as $user_id) {
					if(! in_array($user_id,$user_arr)) {
						$inserts[] = array($userid, $tabid, $user_id);
						$user_arr[]=$user_id;
					}
				}
			}
		}
		if(is_array($var_name_arr['GROUP']) && sizeof($var_name_arr['GROUP']) > 0) { // crmv@189598
			foreach($var_name_arr['GROUP'] as $grpid=>$grpusers) {
				foreach($grpusers as $user_id) {
					if(! in_array($user_id,$user_arr)) {
						$inserts[] = array($userid, $tabid, $user_id);
						$user_arr[]=$user_id;
					}
				}
			}
		}
		//crmv@7222
		if(is_array($var_name_arr['USR']) && sizeof($var_name_arr['USR']) > 0) { // crmv@189598
			foreach($var_name_arr['USR'] as $user_id) {
				if(! in_array($user_id,$user_arr)) {
					$inserts[] = array($userid, $tabid, $user_id);
					$user_arr[]=$userid;
				}
			}
		}
		//crmv@7222e
		$adb->bulkInsert($table_name, null, $inserts);
	
	} elseif($enttype=='GROUP') {
		if($pertype =='read') {
			$table_name=$table_prefix.'_tmp_read_g_per';
		} elseif($pertype == 'write') {
			$table_name=$table_prefix.'_tmp_write_g_per';
		}
		$grp_arr=Array();
		$inserts = array();
		if(is_array($var_name_arr['GROUP']) &&  sizeof($var_name_arr['GROUP']) > 0) { // crmv@189598
			foreach($var_name_arr['GROUP'] as $grpid=>$grpusers) {
				if(! in_array($grpid,$grp_arr)) {
					$inserts[] = array($userid, $tabid, $grpid);
					$grp_arr[]=$grpid;
				}
			}
		}
		$adb->bulkInsert($table_name, null, $inserts);
	}
}

function populateRelatedSharingPrivileges($enttype,$userid,$module,$relmodule,$pertype,$var_name_arr=Array()) {
	global $adb,$table_prefix;
	$tabid=getTabid($module);
	$reltabid=getTabid($relmodule);
	
	if ($enttype=='USER') {
		if ($pertype =='read') {
			$table_name=$table_prefix.'_tmp_read_u_rel_per';
		} elseif($pertype == 'write') {
			$table_name=$table_prefix.'_tmp_write_u_rel_per';
		}
		$user_arr=Array();
		$inserts = array();
		if(is_array($var_name_arr['ROLE']) && sizeof($var_name_arr['ROLE']) > 0) { // crmv@189598
			foreach($var_name_arr['ROLE'] as $roleid=>$roleusers) {

				foreach($roleusers as $user_id) {
					if(! in_array($user_id,$user_arr)) {
						$inserts[] = array($userid, $tabid, $reltabid, $user_id);
						$user_arr[]=$user_id;
					}
				}
			}
		}
		if(is_array($var_name_arr['GROUP']) && sizeof($var_name_arr['GROUP']) > 0) { // crmv@189598
			foreach($var_name_arr['GROUP'] as $grpid=>$grpusers) {
				foreach($grpusers as $user_id) {
					if(! in_array($user_id,$user_arr)) {
						$inserts[] = array($userid, $tabid, $reltabid, $user_id);
						$user_arr[]=$user_id;
					}
				}
			}
		}
		//crmv@7222
		if(is_array($var_name_arr['USR']) && sizeof($var_name_arr['USR']) > 0) { // crmv@189598
			foreach($var_name_arr['USR'] as $user_id) {
				if(! in_array($user_id,$user_arr)) {
					$inserts[] = array($userid, $tabid, $reltabid, $user_id);
					$user_arr[]=$user_id;
				}
			}
		}
		//crmv@7222e
		$adb->bulkInsert($table_name, null, $inserts);
	
	} elseif($enttype=='GROUP') {
		if ($pertype =='read') {
			$table_name=$table_prefix.'_tmp_read_g_rel_per';
		} elseif($pertype == 'write') {
			$table_name=$table_prefix.'_tmp_write_g_rel_per';
		}
		// Lookup for the variable if not set through function argument
		$grp_arr=Array();
		$inserts = array();
		if(is_array($var_name_arr['GROUP']) && sizeof($var_name_arr['GROUP']) > 0) { // crmv@189598
			foreach($var_name_arr['GROUP'] as $grpid=>$grpusers) {
				if(! in_array($grpid,$grp_arr)) {
					$inserts[] = array($userid, $tabid, $reltabid, $grpid);
					$grp_arr[]=$grpid;
				}
			}
		}
		
		$adb->bulkInsert($table_name, null, $inserts);
	}
}

// class to handle the recalc procedure
class SharingPrivileges {

	public $table = '';
	public $enableLog = false;		// if true, some debug logs will be written
	public $useCache = true;		// if true, the rows are cached for cacheValidity time
	public $cacheValidity = 120;	// validity of each row in the cache, in seconds
	
	public $pollInterval = 0.50;	// check every these seconds if the cron has finished
	public $pollTimeout = 4;		// if the cron is not terminated after this amount of time (s), give up
	
	public $orphanTimeout = 3600;	// if a process is found in running state after this time (s), it is cleaned
	
	protected $rowCache = array();
	
	public function __construct() {
		global $table_prefix;
		$this->table = $table_prefix."_tmp_recalc";
	}
	
	// this is for debug only
	protected function log($text) {
		if ($this->enableLog) {
			@file_put_contents('logs/share_recalc.log', date('[Y-m-d H:i:s] ').$text."\n", FILE_APPEND);
		}
	}
	
	public function clearCache() {
		$this->rowCache = array();
	}
	
	protected function setCache($row) {
		$op = $row['operation'];
		$this->rowCache[$op] = array(
			'expire' => time() + $this->cacheValidity,
			'data' => $row,
		);
	}
	
	protected function getCache($operation) {
		$cr = $this->rowCache[$operation];
		if (!$cr) return false;
		if ($cr['expire'] <= time()) {
			// cache expired
			unset($this->rowCache[$operation]);
			return false;
		}
		return $cr['data'];
	}
	
	public function getInfo($operation = 'RecalcPrivileges', $skipCache = false) {
		global $adb;
		
		if ($this->useCache && !$skipCache) {
			$cinfo = $this->getCache($operation);
			if ($cinfo) return $cinfo;
		}
		
		$res = $adb->pquery("SELECT * FROM {$this->table} WHERE operation = ?", array($operation));
		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
			if ($this->useCache) $this->setCache($row);
			return $row;
		}
		
		return null;
	}
	
	public function isRunning($operation = 'RecalcPrivileges', $skipCache = false) {
		global $adb;
		if ($skipCache) {
			$res = $adb->pquery("SELECT running FROM {$this->table} WHERE operation = ?", array($operation));
			if ($res && $adb->num_rows($res) > 0) {
				$running = $adb->query_result_no_html($res, 0, 'running');
				return ($running == '1');
			}
			return false;
		} else {
			$info = $this->getInfo($operation);
		}
		return ($info['running'] == 1);
	}
	
	public function getStatus($operation = 'RecalcPrivileges', $skipCache = false) {
		global $adb;
		if ($skipCache) {
			$res = $adb->pquery("SELECT status FROM {$this->table} WHERE operation = ?", array($operation));
			if ($res && $adb->num_rows($res) > 0) {
				$status = $adb->query_result_no_html($res, 0, 'status');
				return $status;
			}
			return false;
		} else {
			$info = $this->getInfo($operation);
		}
		return $info['status'];
	}
	
	// crmv@81802
	public function setValues($values, $operation = 'RecalcPrivileges') {
		global $adb;
		
		$res = $adb->pquery("SELECT id FROM {$this->table} WHERE operation = ?", array($operation));
		if ($res && $adb->num_rows($res) == 0) {
			// insert
			return $this->insertValues($values, $operation);
		} else {
			// update
			return $this->updateValues($values, $operation);
		}
	}
	
	protected function insertValues($values, $operation = 'RecalcPrivileges') {
		global $adb;
		
		$values['operation'] = $operation;
		if (!array_key_exists('id', $values)) {
			$values['id'] = 1;
		}
		
		$cols = array_keys($values);
		$adb->format_columns($cols);
		$sql = "INSERT INTO {$this->table} (".implode(', ', $cols).") VALUES (".generateQuestionMarks($values).")";
		$res = $adb->pquery($sql, $values);
	}
		
	protected function updateValues($values, $operation = 'RecalcPrivileges') {
		global $adb;
		
		$sql = "UPDATE {$this->table} SET ";
		$params = array();
		$i = 0;
		foreach ($values as $k => $v) {
			if ($i > 0) $sql .= ", ";
			$sql .= "$k = ?";
			$params[] = $v;
			++$i;
		}
		$sql .= " WHERE operation = ?";
		$params[] = $operation;
		
		$res = $adb->pquery($sql, $params);
	}
	// crmv@81802e
	
	public function setRecalcStatus($status, $running = 0) {
		$values = array(
			'status' => $status,
			'running' => intval($running),
		);
		if ($status == 'Running' && $running) {
			$values['starttime'] = date('Y-m-d H:i:s');
		} elseif ($status == '' && !$running) {
			$values['endtime'] = date('Y-m-d H:i:s');
		}
		return $this->setValues($values, 'RecalcPrivileges');
	}
	
	public function scheduleRecalc() {
		$status = $this->getStatus();
		$running = $this->isRunning();
		$tempTab = PerformancePrefs::getBoolean('USE_TEMP_TABLES', true, true);
		
		$this->log("Scheduling a recalculation. Current status is '$status', running flag: ".intval($running));
		
		// enable temporary tables if slave is disabled
		if (!$tempTab && !PerformancePrefs::getBoolean('SLAVE_HANDLER')) { // crmv@185894
			PerformancePrefs::setTemp('USE_TEMP_TABLES', true);
			$this->log("Temporary tables enabled.");
		}
		
		if ($status == 'Scheduled')  {
			// already scheduled, check if running, otherwise exit
			if ($running) $this->abortCronAndWait();
			$this->setValues(array('status' => 'Scheduled'));
		} elseif ($status == 'Running') {
			// already running, abort and re-schedule
			$ok = $this->abortCronAndWait();
			if ($ok) {
				$this->setValues(array('status' => 'Scheduled'));
			} else {
				$this->log("The cron job did'n finish in time.");
				return false;
			}
		} elseif ($status == 'Abort') {
			$ok = $this->waitCron();
			if ($ok) {
				$this->setValues(array('status' => 'Scheduled'));
			} else {
				$this->log("The cron job did'n finish in time.");
				return false;
			}
		} else {
			// not running, schedule
			$this->setValues(array('status' => 'Scheduled'));
		}
		
		$this->log("Scheduled correctly.");
		return true;
	}

	// check if a previous cron process has died
	public function checkDeadCron() {
		$info = $this->getInfo();
		$status = $this->getStatus();
		if ($this->isRunning() || $status == 'Running') {
			$lastStart = $info['starttime'];
			$late = date('Y-m-d H:i:s', time()-$this->orphanTimeout);
			if (!empty($lastStart) && substr($lastStart, 0, 10) != '0000-00-00' && $lastStart < $late) {
				$this->log("Found an orphaned recalculation, clearing it.");
				$this->setValues(array('status' => '', 'running' => 0));
				$this->clearCache();
			}
		}
	}
	
	// Start the calculaction, checking for interruptions after every user
	public function recalcFromCron() {
	
		$this->checkDeadCron();
	
		$status = $this->getStatus();
		// already running or not scheduled
		if ($this->isRunning() || $status != 'Scheduled') return true;
		
		$this->log("Starting cron recalculation");
		
		// otherwise, do it!
		$this->setRecalcStatus('Running', 1);
		try {
			$r = RecalculateSharingRules('', array($this, 'checkInterruptFn'));
		} catch (Exception $e) {
			$r = false;
			$this->log("Recalculation raised an exception: ".$e->getMessage());
		}
		$this->setRecalcStatus('', 0);
		
		// now reactivate temp tables if the operation was not aborted
		if ($r) {
			$this->log("Recalculation terminated");
			$tempTab = PerformancePrefs::getBoolean('USE_TEMP_TABLES', true, true);
			$oTab = PerformancePrefs::getTemp('USE_TEMP_TABLES');
			if (!$tempTab && $oTab) {
				PerformancePrefs::unsetTemp('USE_TEMP_TABLES');
				$this->log("Temporary tables deactivated.");
			}
		} else {
			$this->log("The cron has been interrupted");
		}
		
		return $r;
	}
	
	// if any cron is running, stop it, and start the calculation immediately, this recalc is not interruptible
	public function recalcNow($users = '') {
		$this->setRecalcTimeLimit(); // crmv@199834
		$this->checkDeadCron();
		
		$r = $this->abortCronAndWait();
		if (!$r) {
			$this->log("The cron job did'n finish in time, but the recalculation will be executed anyway");
		}
		
		$this->log("Starting immediate recalculation");
		
		// warning, the only bad thing can happen if a recalc is scheduled during this manual recalc
		// which shouldn't happen, since it's done from UI
		$this->setValues(array('status' => '', 'running' => 0, 'starttime' => date('Y-m-d H:i:s')));
		$r = RecalculateSharingRules('');
		$this->setValues(array('endtime' => date('Y-m-d H:i:s')));
		
		// deactivate temporary tables if needed
		if ($r) {
			$this->log("Immediate recalculation terminated correctly");
			$tempTab = PerformancePrefs::getBoolean('USE_TEMP_TABLES', true, true);
			$oTab = PerformancePrefs::getTemp('USE_TEMP_TABLES');
			if (!$tempTab && $oTab) {
				PerformancePrefs::unsetTemp('USE_TEMP_TABLES');
				$this->log("Temporary tables deactivated.");
			}
		} else {
			$this->log("Immediate recalculation failed for unknown reason");
		}
		return $r;
	}
	
	protected function waitCron() {
		$running = $this->isRunning();
		if (!$running) return true;
		
		$t0 = microtime(true);
		$delta = 0;
		while ($running && $delta <= $this->pollTimeout) {
			usleep($this->pollInterval * 1000000);
			$running = $this->isRunning('RecalcPrivileges', true);
			$delta = microtime(true) - $t0;
		}
		return (!$running && $delta <= $this->pollTimeout);
	}
	
	public function abortCronAndWait() {
		$running = $this->isRunning();
		if (!$running) return true;
		
		$this->setValues(array('status' => 'Abort'));
		$r = $this->waitCron();
		if ($r) {
			// ok, reset the values, to be sure
			$this->setRecalcStatus('', 0);
		}
		return $r;
	}
	
	public function checkInterruptFn($userid = null) {
		$status = $this->getStatus('RecalcPrivileges',true);
		if ($status == 'Abort') return false;
		return true;
	}

	// crmv@199834
	public function recalcNowOrSchedule() {
		$VTEP = VTEProperties::getInstance();
		$limit = intval($VTEP->getProperty('performance.recalc_privileges_limit'));

		$recalcNow = false;

		if ($limit === 0) {
			$recalcNow = true;
		} else {
			$totalUsers = $this->getTotalUsers();
			if ($totalUsers <= $limit) {
				$recalcNow = true;
			}
		}

		if ($recalcNow) {
			return $this->recalcNow();
		} else {
			// try to schedule the recalculation otherwise run it directly
			$r = $this->scheduleRecalc();
			if (!$r) {
				return $this->recalcNow();
			}
		}

		return false;
	}

	public function setRecalcTimeLimit() {
		global $php_max_execution_time;
		set_time_limit($php_max_execution_time);
	}

	public function getTotalUsers() {
		global $adb, $table_prefix;

		$totalUsers = 0;

		$totalUsersRes = $adb->query("SELECT COUNT(*) AS c FROM {$table_prefix}_users WHERE deleted = 0");
		if (!!$totalUsersRes) {
			$totalUsers = intval($adb->query_result_no_html($totalUsersRes, 0, 'c'));
		}

		return $totalUsers;
	}

	public function getSharingStatusMessage() {
		$sharingInfo = $this->getInfo();

		if (empty($sharingInfo)) return "";

		$message = "";
		$sharingStatus = $sharingInfo['status'];
		$sharingStartDate = getValidDisplayDate($sharingInfo['starttime']);

		if ($sharingStatus == 'Scheduled')  {
			$message = getTranslatedString('LBL_SHARING_RECALC_SCHEDULED', 'APP_STRINGS');
		} elseif ($sharingStatus == 'Running') {
			$message = getTranslatedString('LBL_SHARING_RECALC_RUNNING', 'APP_STRINGS');
		} elseif ($sharingStatus == 'Abort') {
			$message = getTranslatedString('LBL_SHARING_RECALC_ABORTED', 'APP_STRINGS');
		} else {
			$message = getTranslatedString('LBL_SHARING_RECALC_LASTDATE', 'APP_STRINGS');
			$message = str_replace(array('{start_date}'), array($sharingStartDate), $message);
		}

		return $message;
	}

	public function getSharingStatusIcon() {
		$sharingInfo = $this->getInfo();

		if (empty($sharingInfo)) return "";

		$icon = "";
		$sharingStatus = $sharingInfo['status'];

		if ($sharingStatus == 'Scheduled')  {
			$icon = "schedule";
		} elseif ($sharingStatus == 'Running') {
			$icon = "autorenew";
		} elseif ($sharingStatus == 'Abort') {
			$icon = "clear";
		} else {
			// nothing
		}

		return $icon;
	}
	// crmv@199834e
	
}
//crmv@23973e crmv@74560e