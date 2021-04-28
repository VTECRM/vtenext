<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Accounts/Accounts.php');

class Accounts2 extends Accounts {
	
	function save_module($module)
	{
		//global $adb;
		//$adb->pquery('update vte_account set fax = ? where accountid = ?',array('0123456789',$this->id));
		
		parent::save_module($module);
	}
}

class Accounts3 extends Accounts2 {
	
	var $list_fields = Array();

	var $list_fields_name = Array(
			'Account Name'=>'accountname',
			'City'=>'bill_city',
			'Facebook'=>'Facebook', 
			'Website'=>'website',
			'Phone'=>'phone',
			'Assigned To'=>'assigned_user_id'
			);
	
	function __construct(){
		global $table_prefix;
		parent::__construct();
		$this->list_fields = Array(
					'Account Name'=>Array($table_prefix.'_account'=>'accountname'),
					'City'=>Array($table_prefix.'_accountbillads'=>'bill_city'), 
					'Facebook'=>Array($table_prefix.'_account'=>'facebook'), 
					'Website'=>Array($table_prefix.'_account'=>'website'),
					'Phone'=>Array($table_prefix.'_account'=> 'phone'),
					'Assigned To'=>Array($table_prefix.'_crmentity'=>'smownerid')
		);
	}
	
}
?>