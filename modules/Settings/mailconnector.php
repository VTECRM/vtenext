<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

$mode = $_REQUEST['mode'];

if($mode == 'Ajax' && !empty($_REQUEST['xmode'])) {
	$mode = $_REQUEST['xmode'];
}

switch ($mode){
	case 'scannow':
		global $root_directory;
		chdir($root_directory.'/plugins/mailconnector/');
		include('mailconnector.php');
	break;	
	case 'edit':
		include('plugins/mailconnector/interface/edit.php');
	break;
	case 'save_server':
		include('plugins/mailconnector/interface/save_server.php');
	break;
	case 'add_mail':
		include('plugins/mailconnector/interface/add_mail.php');
	break;
	case 'save_account':
		include('plugins/mailconnector/interface/save_account.php');
	break;
	case 'delete_account':
		include('plugins/mailconnector/interface/delete_account.php');
	break;
	default:
		include('plugins/mailconnector/interface/detail.php');
	break;
}
die;
?>