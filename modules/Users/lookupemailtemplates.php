<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@80155 */

require('user_privileges/requireUserPrivileges.php'); // crmv@39110

global $adb, $table_prefix, $current_user, $theme;

$small_page_title = getTranslatedString('LBL_EMAIL_TEMPLATES');
include('themes/SmallHeader.php');
?>

<form action="index.php" onsubmit="VteJS_DialogBox.block();">
	<input type="hidden" name="module" value="Users">
	<div style="padding:10px;">
	<table class="vtetable">
	<thead>
		<tr>
			<th width="5%"><?php echo getTranslatedString('LBL_PREVIEW'); ?></th>
			<th width="35%"><?php echo trim($mod_strings['LBL_TEMPLATE_NAME'],':'); ?></th>
			<th width="60%"><?php echo $mod_strings['LBL_DESCRIPTION']; ?></th>
		</tr>
	</thead>
	<tbody>
<?php
$res = $adb->query("select * from ".$table_prefix."_field where fieldname = 'bu_mc'");
$bu_mc_enabled = ($res && $adb->num_rows($res) > 0);

$sql = "select * from ".$table_prefix."_emailtemplates";
if ($bu_mc_enabled) {
	$bu_mc = explode(' |##| ', $current_user->column_fields['bu_mc']);
	if (!empty($bu_mc)) {
		$cond = array();
		foreach($bu_mc as $b) {
			$cond[] = "bu_mc like '%$b%'"; 
		}
		$sql .= " where ".implode(' or ',$cond)." and templatetype = 'Email'"; // crmv@146769
	} else {
		$sql .= " where templatetype = 'Email'";  // crmv@146769
	}
} else {
	$sql .= " where templatetype = 'Email'";  // crmv@146769
}
$sql .= ' and parentid = 0'; // crmv@151466
$sql .= " order by templateid desc";
$result = $adb->query($sql);
if ($result && $adb->num_rows($result) > 0) {
	while($temprow = $adb->fetch_array($result)) {
		$templatename = $temprow["templatename"];
		$folderName = $temprow['foldername'];
		if($is_admin || (!$is_admin && $folderName != 'Personal'))
		{
			echo "<tr>";
			echo "<td><a href='javascript:previewTemplate(".$temprow['templateid'].")'><i class='vteicon'>remove_red_eye</i></a></td>"; // crmv@195115
			echo "<td><a href='javascript:submittemplate(".$temprow['templateid'].");'>".$temprow["templatename"]."</a></td>";
			printf("<td>%s</td>",$temprow["description"]);
		}
	}
} else {
	echo '<tr><td colspan="3" style="background-color:#ffffff;height:340px" align="center">
		<div style="border: 1px solid rgb(246, 249, 252); background-color: rgb(255, 255, 255); width: 45%; position: relative;">
		<table border="0" cellpadding="5" cellspacing="0" width="98%">
		<tr>
		<td rowspan="2" width="25%"><img src="'.resourcever('denied.gif').'"></td>
		<td nowrap="nowrap" width="75%"><span class="genHeaderSmall">
		'.getTranslatedString('LBL_NO_M').' '.getTranslatedString('LBL_RECORDS').' '.getTranslatedString('LBL_FOUND').' !
		</tr>
		</table>
		</div>
	</td></tr>';
}
?>
</tbody>
</table></div>

<?php
include('themes/SmallFooter.php');
?>

<script>
function submittemplate(templateid)
{
	window.document.location.href = 'index.php?module=Users&action=UsersAjax&file=TemplateMerge&templateid='+templateid;
}
function previewTemplate(templateid)
{
	window.document.location.href = 'index.php?module=Users&action=UsersAjax&file=EmailTemplatePreview&templateid='+templateid;
}
// crmv@22038
jQuery(document).ready(function() {
	loadedPopup();
});
// crmv@22038e
</script>
</html>