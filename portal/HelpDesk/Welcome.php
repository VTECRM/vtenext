<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('Smarty_setup.php');
$smarty = new VTECRM_Smarty();
$block = 'HelpDesk';

(file_exists("$block/header.html")) ? $header = "$block/header.html" : $header = 'VteCore/header.html';
include($header);

$showmodule = array();
// Look if we have the information already
if(isset($_SESSION['__permitted_modules'])) {
	$showmodule = $_SESSION['__permitted_modules'];
} else {
	// Get the information from server
	$params = array();
	$showmodule = $client->call('get_modules',$params,$Server_path,$Server_path);
	// Store for further use.
	$_SESSION['__permitted_modules'] = $showmodule;
}

$smarty->assign("SHOWMODULE", $showmodule);

(file_exists("$block/footer.html")) ? $footer = "$block/footer.html" : $footer = 'VteCore/footer.html';
include($footer);

$smarty->assign("CUSTERMID", $customerid);

$smarty->display('Welcome.tpl');
?>