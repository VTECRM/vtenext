<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@25391

global $theme;
$smarty = new VteSmarty();

?>
<span style="height: 75px;">
<div style="float: right; border-style: solid; border-color: rgb(141, 141, 141); border-width: 1px 3px 3px 1px; overflow: hidden; padding-left: 5px; padding-right: 5px; padding-top: 5px; padding-bottom: 10px; margin-left: 2px; font-weight: normal; height: 75px;">
	<table cellspacing="0" cellpadding="2" border="0">
		<tr>
			<td align="left" colspan="2"><b><?php echo getTranslatedString('LBL_INVITED_TITLE'); ?></b></td>
			<td align="right">
				<a onclick="getObj('CheckAvailableVersionDiv').style.display='none';" href="javascript:;" style="padding-left: 10px;"><img border="0" align="absmiddle" src="<?php echo vtecrm_imageurl('close.gif',$theme); ?>"></a>
			</td>
		</tr>
		<tr>
			<td colspan="3"><hr></td>
		</tr>
		<tr>
			<td align="center"> 
				<a href="index.php?module=Emails&action=EmailsAjax&file=EditView&templatename=<?php echo urlencode('Tell a friend about VTE'); ?>" target="_blank" style="padding: 0 5px 0 5px;"><b><?php echo getTranslatedString('LBL_INVITED_LINK'); ?></b></a> 
			</td>
		</tr>
	</table>
</div>
</span>

<?php VteSession::set('CheckInviteVte', true); ?>