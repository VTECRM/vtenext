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
					<input type="button" class="small crmbutton save" value="{$APP.LBL_SAVE_BUTTON_LABEL}" onclick="encFieldEdit_save()">
					<input type="button" class="small crmbutton cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="location.href='index.php?module=Settings&action=EncryptedFields&parenttab=Settings'">
				</div>

				<br>
				<table border="0" cellspacing="0" cellpadding="5">
					<tr>
						<td><b>{$APP.LBL_MODULE}:</b></td>
						<td>{$FIELDINFO.module|getTranslatedString:'APP_STRINGS'}&nbsp;&nbsp;&nbsp;</td>
						<td><b>{$APP.Field}:</b></td>
						<td>
							{$FIELDINFO.fieldlabel_trans}
							<input type="hidden" id="encFieldEdit_fieldid" name="encFieldEdit_fieldid" value="{$FIELDID}">
						</td>
					</tr>
				</table>


				<br><br>
				<table border="0" cellspacing="0" cellpadding="5" width="80%">
					<tr>
						<td colspan="3"><b>{$MOD.LBL_UT208_CHANGEPWD}</b></td>
					</tr>
					<tr>
						<td class="dvtCellLabel" align="right" width="20%">{$MOD.LBL_UT208_CURRENT_PWD}</td>
						<td width="40%">
							<div class="dvtCellInfo">
								<input type="password" id="encFieldEdit_pwd" name="encFieldAdd_pwd1" class="detailedViewTextBox" value="" onkeyup="encFieldEdit_pwdtype()">
							</div>
						</td>
						<td class="dvtCellLegend" width="40%">{$MOD.LBL_UT208_CHANGEPWD_DESC}</td>
					</tr>
					<tr>
						<td class="dvtCellLabel" align="right">{$MOD.LBL_UT208_NEW_PWD}</td>
						<td>
							<div class="dvtCellInfo">
								<input type="password" id="encFieldEdit_pwd1" name="encFieldAdd_pwd1" class="detailedViewTextBox" value="" disabled="" >
							</div>		
						</td>
						<td class="dvtCellLegend">&nbsp;</td>
					</tr>
					<tr>
						<td class="dvtCellLabel" align="right">{$MOD.LBL_CONFIRM_PASSWORD}</td>
						<td>
							<div class="dvtCellInfo">
								<input type="password" id="encFieldEdit_pwd2" name="encFieldAdd_pwd2" class="detailedViewTextBox" value="" disabled="" >
							</div>
						</td>
						<td class="dvtCellLegend">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="3"><br><b>{$MOD.LBL_ALERT}:</b> {$MOD.LBL_FORGET_PWD_ALERT}</td>
					</tr>
				</table>


				<br><br>
				<table border="0" cellspacing="0" cellpadding="5" width="80%">
					<tr>
						<td colspan="3"><b>{$MOD.LBL_UT208_ADVANCED_OPTIONS}</b></td>
					</tr>
					<tr>
						<td class="dvtCellLabel" align="right" width="20%">{$MOD.LBL_UT208_TIMEOUT}</td>
						<td class="dvtCellInfo" nowrap width="40%"><input type="text" class="detailedViewTextBox" id="encFieldEdit_timeout" name="encFieldEdit_timeout" value="{$FIELDCONFIG.pwd_timeout/60|number_format:0}" style="width:80% !important">&nbsp;{'lbl_minutes'|getTranslatedString:'ModComments'}</td>
						<td width="40%" class="dvtCellLegend">{$MOD.LBL_UT208_TIMEOUT_DESC}</td>
					</tr>
					<tr>
						<td class="dvtCellLabel" align="right">{$MOD.LBL_UT208_FILTER_ROLE}</td>
						<td class="dvtCellInfo">
							<select class="detailedViewTextBox small" multiple="" id="encFieldEdit_roles" size="5">
								{if count($FIELDCONFIG.valid_roles) > 0}
									<option value="" >{$APP.LBL_ALL}</option>
								{else}
									<option value="" selected="" >{$APP.LBL_ALL}</option>
								{/if}
								{foreach item=rolerow from=$ALLROLES}
									{if is_array($FIELDCONFIG.valid_roles) && in_array($rolerow.roleid, $FIELDCONFIG.valid_roles)}
									<option value="{$rolerow.roleid}" selected="">{$rolerow.rolename}</option>
									{else}
									<option value="{$rolerow.roleid}">{$rolerow.rolename}</option>
									{/if}
								{/foreach}
							</select>
						</td>
						<td class="dvtCellLegend">{$MOD.LBL_UT208_FILTER_ROLE_DESC}</td>
					</tr>
					<tr>
						<td class="dvtCellLabel" align="right">{$MOD.LBL_UT208_FILTER_IP}</td>
						<td class="dvtCellInfo"><input type="text" class="detailedViewTextBox" id="encFieldEdit_filterip" name="encFieldEdit_filterip" value="{$FIELDCONFIG.valid_ip|@implode:' '}"></td>
						<td class="dvtCellLegend">{$MOD.LBL_UT208_FILTER_IP_DESC}</td>
					</tr>
				</table>

				<br>

			</div>
		</td>
	</tr>
</table>