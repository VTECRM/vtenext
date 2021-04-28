{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@161554 *}

<script type="text/javascript" src="{"modules/Settings/GDPRConfig/GDPRConfig.js"|resourcever}"></script>
<script type="text/javascript" src="include/ckeditor/ckeditor.js"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td valign="top"></td>
    <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">

	<div align="center">
		{include file='SetMenu.tpl'}
		{include file='Buttons_List.tpl'} {* crmv@30683 *}
		<table class="settingsSelUITopLine" border="0" cellpadding="5" cellspacing="0" width="100%">
			<tr>
				<td rowspan="2" valign="top" width="50"><img src="{'PrivacySettings.png'|resourcever}" alt="{$MOD.LBL_EXTWS_CONFIG}" title="{$MOD.LBL_EXTWS_CONFIG}" border="0" height="48" width="48"></td>
				<td class="heading2" valign="bottom"><b> {$MOD.LBL_SETTINGS} &gt; {$MOD.LBL_GDPR}</b></td>
			</tr>
			<tr>
				<td class="small" valign="top">{$MOD.LBL_GDPR_DESCRIPTION}</td>
			</tr>
		</table>
				
		<table border="0" cellpadding="10" cellspacing="0" width="100%">
			<tr>
				<td>
					{if $MODE eq 'edit'} 
						{include file="Settings/GDPRConfig/Edit.tpl"}
					{else}
						{include file="Settings/GDPRConfig/Detail.tpl"}
					{/if}
			
					<br><br>
					
					{include file='Settings/ScrollTop.tpl'}
				</td>
			</tr>
		</table>
   </div>

   </td>
   <td valign="top"></td>
</tr>
</table>