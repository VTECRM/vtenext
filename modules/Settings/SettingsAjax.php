<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $table_prefix;
//crmv@30105
$file=strip_tags(vtlib_purify($_REQUEST['file']));//crmv@208173
if(isset($file) && $file !='')
{
	require_once('modules/Settings/'.$file.'.php');
}
//crmv@30105e
if(isset($_REQUEST['orgajax']) && ($_REQUEST['orgajax'] !=''))
{
	require_once('modules/Settings/CreateSharingRule.php');
}
//crmv@7222
if(isset($_REQUEST['orgajaxusr']) && ($_REQUEST['orgajaxusr'] !=''))
{
	require_once('modules/Settings/CreateSharingRuleUsers.php');
}
//crmv@7222e
//crmv@7221
if(isset($_REQUEST['orgajaxadv']) && ($_REQUEST['orgajaxadv'] !=''))
{
	if(isset($_REQUEST['advprivilege']) && ($_REQUEST['advprivilege'] !=''))
		require_once('modules/Settings/CreateAdvSharingRulePerm.php');
	else require_once('modules/Settings/CreateAdvSharingRule.php');
}
//crmv@7221e
?>