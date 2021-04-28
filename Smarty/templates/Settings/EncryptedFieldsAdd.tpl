{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@37679 *}
<script language="javascript" type="text/javascript" src="modules/SDK/src/208/Settings/208Settings.js"></script>
<link rel="stylesheet" href="modules/SDK/src/208/Settings/208Settings.css">

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td valign="top"></td>
		<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">
			<div align="center">
				{include file='SetMenu.tpl'}
				{include file='Buttons_List.tpl'}
				<table border="0" cellspacing="0" cellpadding="5" width="100%" class="settingsSelUITopLine">
					<tr>
						<td width=50 rowspan=2 valign=top>
							<img src="{'uitype208.png'|resourcever}" alt="LBL_EDIT_UITYPE208" width="48" height="48" border=0 title="{$MOD.LBL_PROFILES}">
						</td>
						<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_EDIT_UITYPE208}</b></td>
					</tr>
					<tr>
						<td valign=top class="small">{$MOD.LBL_EDIT_UITYPE208_DESC}</td>
					</tr>
				</table>


				<div style="text-align:right;width:100%">
					<input type="button" class="small crmbutton create" value="{$APP.LBL_CREATE}" onclick="encFieldAdd_save()">
					<input type="button" class="small crmbutton cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="location.href='index.php?module=Settings&action=EncryptedFields&parenttab=Settings'">
				</div>

				<br>
				<table border="0" cellspacing="0" cellpadding="5">
					<tr>
						<td colspan="2">{$MOD.LBL_UT208_CHOOSEFIELD}:</td>
					</tr>

					<tr>
					<td>
					<b>{$APP.LBL_MODULE}:</b>
					<select id="encFieldAdd_selModule" onchange="encFieldAdd_changeModule()">
						{foreach key=rowmodule item=label from=$AVAIL_MODULES}
							{if empty($curmodule)}
								{assign var=curmodule value=$rowmodule}
							{/if}
							<option value="{$rowmodule}">{$label}</option>
						{/foreach}
					</select>&nbsp;&nbsp;&nbsp;
					</td>

					<td>
					<b>{$APP.Field}:</b>

					{foreach key=rowmodule item=modfields from=$AVAIL_FIELDS}
						<select id="encFieldAdd_sel_{$rowmodule}" style="{if $curmodule neq $rowmodule}display:none{/if}">
						{foreach item=efield from=$modfields}
							<option value="{$efield.fieldid}">{$efield.fieldlabel_trans}</option>
						{/foreach}
						</select>
					{/foreach}
					</td>

				</table>
				<br><br>

				<table border="0" cellspacing="0" cellpadding="5">
					<tr>
						<td colspan="2">{$MOD.LBL_UT208_CHOOSEPWD}:</td>
					</tr>

					<tr>
						<td class="dvtCellLabel" align="right">{$MOD.LBL_PASSWORD}</td>
						<td>
							<div class="dvtCellInfo">
								<input type="password" id="encFieldAdd_pwd1" name="encFieldAdd_pwd1" class="detailedViewTextBox" value="" autocomplete="off">
							</div>
						</td>
					</tr>
					<tr>
						<td class="dvtCellLabel" align="right">{$MOD.LBL_CONFIRM_PASSWORD}</td>
						<td>
							<div class="dvtCellInfo">
								<input type="password" id="encFieldAdd_pwd2" name="encFieldAdd_pwd2" class="detailedViewTextBox" value="" autocomplete="off">
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2"><br><br><b>{$MOD.LBL_ALERT}:</b> {$MOD.LBL_FORGET_PWD_ALERT}</td>
					</tr>
				</table>

			</div>
		</td>
	</tr>
</table>