<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $result;
global $client;
global $Server_Path;

$customerid = $_SESSION['customer_id'];
$sessionid = $_SESSION['customer_sessionid'];
if($id != '')
{
	//Get the Basic Information
	$params = array('id' => "$id", 'block'=>"$block", 'contactid'=>"$customerid",'sessionid'=>"$sessionid");
 	if (empty($detailview_function)) $detailview_function = 'get_details';
	$result = $client->call($detailview_function, $params, $Server_Path, $Server_Path);

	$smarty = new VTECRM_Smarty();
	
	// Check for Authorization
	if (count($result) == 1 && $result[0] == "#NOT AUTHORIZED#") {
		$smarty->display('NotAuthorized.tpl');
		die();
	} else {
		$info = $result[0][$block];
 		
 		include('Potentials/config.php');
 		foreach ($permittedFieldsDetail as $fieldname) {
 			$data['LBL_POTENTIALS_INFO'][]=$info[$fieldname];
 		}
 		
 		foreach($fieldPotentials as $fieldPotentialVieBlockDatiCliente){
 			$campi[] = $info[$fieldPotentialVieBlockDatiCliente];
 		}
 		
 		// Blocco Dati bancari
 		foreach($fieldBlockDatiBancari as $fieldnameblock){
 			$data['LBL_DATE_POTENTIALS_BANCARI'][]=$info[$fieldnameblock];
 		}	
 		
// 		$smarty->assign('FIELDLIST',$data);
		
		// Blocco Dati del Cliente
		$accountid = $_SESSION['customer_account_id'];
		$params2 = array('id' => "$accountid", 'block'=>"Accounts", 'accountid'=>"$accountid",'sessionid'=>"$sessionid");

		$account_info = $client->call('get_details', $params2, $Server_Path, $Server_Path);
		
		foreach ($permittedFieldsDetailInfoAccounts as $fieldnameInfoAccounts) {
			$campi1[] = $account_info[0]['Accounts'][$fieldnameInfoAccounts];
		}
		$data['LBL_INFO_POTENTIALS_ACCOUNTS'] = array_merge($campi1,$campi);
			
		$smarty->assign('FIELDLIST',$data);
		
		// Allegati
		$files_array = getPotentialAttachmentsList($id);
		
		$smarty->assign('FILES',$files_array);
		
		$smarty->display('PotentialsDetail.tpl');
	}
}
?>