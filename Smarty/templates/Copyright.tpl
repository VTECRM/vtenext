{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<style type="text/css">
{literal}
	.level2Bg {
		color: #2b577c;
		background-color: #ebf2f8;
		border-bottom: 1px;
		border-color: gray;
		padding: 0px 20px 0px 20px;
		font-size: 16px;
	}
	
	.licence {
		height: 350px;
		width: 100%; 
	}
{/literal}
</style>

{* crmv@22106 *}
<script language="JavaScript">
{literal}
jQuery(document).ready(function() {
	loadedPopup();
});
{/literal}
</script>
{* crmv@22106e *}

<div id="popupContainer" style="display:none;"></div>

<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr height='40px'>
		{* crmv@182677 *}
		{if $CAN_UPDATE}
			<td class='level2Bg'>
			{if empty($MORPHSUIT)}
				{"LNK_READ_LICENSE"|getTranslatedString}
			{else}
				{"LICENSE_ID"|getTranslatedString:'Morphsuit'}: <b style="font-weight:bolder">{$MORPHSUIT.id}</b>
			</td>
			<td align="center" class='level2Bg'>					
				<font size='2'>
				<span width="50px">&nbsp;</span>
				<i class="vteicon md-sm align-bottom">people</i> {'LBL_ACTIVATED_USERS'|getTranslatedString:'Morphsuit'}: {$MORPHSUIT.activated_users} {'LBL_OF'|getTranslatedString:'Settings'} {$MORPHSUIT.users}
				<br>
				<span width="50px">&nbsp;</span>
				<i class="vteicon md-sm align-bottom" style="width:22px" title="{$MORPHSUIT.expiration}">events</i>{'LBL_EXPIRATION_DATE'|getTranslatedString:'Morphsuit'}: {$MORPHSUIT.expiration_fulldate}
				</font>
			{/if}
			</td>
			<td align='right' class='level2Bg'>
				<input type="button" class="crmbutton save" value="{'LBL_UPDATE_YOUR_LICENSE'|getTranslatedString:'Morphsuit'}" onclick="top.location.href='index.php?module=Morphsuit&amp;action=MorphsuitAjax&amp;file=UpdateMorphsuit'">
			</td>
		{else}
			<td class="level2Bg" colspan="3">{"LNK_READ_LICENSE"|getTranslatedString}</td>
		{/if}
		{* crmv@182677e *}
	</tr>
	<tr>
		<td align="center" colspan="3"><font size='2'>
			{if $FREE_VERSION}
				This software is a collective work consisting of the following major Open Source components:<br> 
				Apache software, MySQL server, PHP, ADOdb, Smarty, PHPMailer, phpSysinfo, MagpieRSS and others, each licensed under a separate Open Source License.
				CrmVillage.biz is not affiliated with nor endorsed by any of the above providers.
				<br>If you are intended to use this software you also must subscribe the <a href='{$LICENSE_FILE}' class='copy' target='_blank'>license</a>
			{else}
				<iframe class="licence" frameborder="0" src="{$LICENSE_FILE}" scrolling="auto"></iframe>
			{/if}
		</font></td>
	</tr>
	</table>
</body>
</html>
