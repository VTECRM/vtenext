<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@42752 crmv@43050 crmv@43448 crmv@43864 crmv@58208 */

require_once('modules/Popup/Popup.php');
require_once('modules/SDK/src/Favorites/Utils.php');

global $adb, $table_prefix;
global $mod_strings, $app_strings, $theme;
global $currentModule, $current_user;

$from_module = vtlib_purify($_REQUEST['from_module']);
$from_crmid = intval($_REQUEST['from_crmid']);

$mod = str_replace('.', '', vtlib_purify($_REQUEST['mod']));
$recordid = intval($_REQUEST['record']);

// todo: get them from class
$callback_link = vtlib_purify($_REQUEST['callback_link']);
$callback_create = vtlib_purify($_REQUEST['callback_create']);

if ($from_module == 'Messages') {
	$messageid = $from_crmid;
}

if (isPermitted($mod, 'DetailView', $recordid) != 'yes') die('<p>Not authorized</p>');

$entityName = getEntityName($mod, $recordid);
//crmv@77738 - fix quotes problem
global $default_charset;
$entityName = popup_from_html($entityName[$recordid]);
$entityName = htmlspecialchars($entityName,ENT_QUOTES,$default_charset);
//crmv@77738e



$_REQUEST['module'] = $currentModule = $mod;
$_REQUEST['action'] = 'DetailView';
$_REQUEST['hide_button_list'] = 1;
$_REQUEST['hide_custom_links'] = '1';

?>
<table border="0" cellspacing="0" cellpadding="0" width=100%"><tr>
	<td align="right">
		<input class="crmbutton small save" onclick="<?php echo "{$callback_link}('$currentModule', $recordid, '$entityName')"; ?>" type="button" title="<?php echo getTranslatedString('LBL_LINK_ACTION', 'Messages'); ?>" value="<?php echo getTranslatedString('LBL_LINK_ACTION', 'Messages'); ?>">
		<input class="crmbutton small cancel" onclick="LPOP.create_cancel()" type="button" title="<?php echo getTranslatedString('LBL_BACK'); ?>" value="<?php echo getTranslatedString('LBL_BACK'); ?>">
	</td>
</tr></table>
<?php

require("modules/VteCore/PreView.php");
?>
<script type="text/javascript">
jQuery(document).ready(function() {
	// open links in new window
	jQuery('.dvtCellInfoOff a').each(function(inedx, item) {
		if (jQuery(item).attr('href')) {
			jQuery(item).attr('target', '_blank');
		}
	});
});
</script>