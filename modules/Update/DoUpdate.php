<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@181161 crmv@183486 */

global $current_user;
if (!is_admin($current_user)) die('Unauthorized'); // crmv@37463

$doStart = ($_REQUEST['do_update'] == '1');

if ($doStart) {
	// ----------- iframe content -----------
?>
<html>
<head>
<style type="text/css">
	body {
		font-size: 10pt;
		font-family: monospace, sans-serif;
	}
</style>
</head>
<body>
<?php

	require_once("modules/$currentModule/$currentModule.php");
	
	$startUpdateTime = microtime(true);
	
	$focus = new Update('','','',$_REQUEST['current_version'], $_REQUEST['specificied_version']);
	$focus->update_changes();

	$endUpdateTime = microtime(true);
	$deltaTime = ($endUpdateTime - $startUpdateTime)/60;
	$deltaTime = round($deltaTime,2);
	
?>
<script type="text/javascript">
	if (parent.updateCompleted) parent.updateCompleted("<?php echo $deltaTime; ?>");
</script>
<?php
	exit;
}

// ----------- main page -----------

global $theme, $mod_strings, $app_strings;

$smarty = new VteSmarty();

$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign("THEME", $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");

$iframeUrl = "index.php?module=Update&action=UpdateAjax&file=DoUpdate&do_update=1&current_version={$_REQUEST['current_version']}&specificied_version={$_REQUEST['specificied_version']}&parenttab=Settings";
$smarty->assign('IFRAME_URL', $iframeUrl);

$smarty->display('modules/Update/DoUpdate.tpl');