{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{if $EMPTY neq 'true'}
<table width="100%" border="0" cellspacing="0" cellpadding="5">
	<tr>
		<td nowrap class="cellLabel">
			<strong>{$SELMODULE|@getTranslatedString} {$MOD.LBL_MODULE_NUMBERING}</strong>
		</td>
		<td width="100%" class="cellText">
			<b>{$STATUSMSG}</b>
		</td>
		<td width="80%" nowrap class="cellText" align=right>
			<b>{$MOD.LBL_MODULE_NUMBERING_FIX_MISSING}</b>
			<button type="button" class="crmbutton create" onclick="VTE.Settings.CustomModEntityNo.updateModEntityExisting(this, this.form);">{$APP.LBL_APPLY_BUTTON_LABEL}</button>
		</td>
	</tr>
	<tr>
		<td width="20%" nowrap class="cellLabel"><strong>{$MOD.LBL_USE_PREFIX}</strong></td>
		<td width="80%" colspan=2 class="cellText">
			<input type="text" name="recprefix" class="detailedViewTextBox" style="width:30%" value="{$MODNUM_PREFIX}" />
		</td>
	</tr>
	<tr>
		<td width="20%" nowrap class="cellLabel"><strong>{$MOD.LBL_START_SEQ}<font color='red'>*</font></strong></td>
		<td width="80%" colspan=2 class="cellText">
			<input type="text" name="recnumber" class="detailedViewTextBox" style="width:30%" value="{$MODNUM}" />
		</td>
	</tr>
	<tr>
		<td width="20%" nowrap colspan="3" align="center">
			<button type="button" name="Button" class="crmbutton save" onclick="VTE.Settings.CustomModEntityNo.updateModEntityNoSetting(this, this.form);">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
			<button type="button" name="Button" class="crmbutton cancel" onclick="history.back(-1);">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
		</td>
	</tr>
</table>
{else}
<table border="0" cellpadding="5" cellspacing="0" width="98%">
	<tr>
		<td rowspan="2" width="25%"><img src="{'denied.gif'|resourcever}"></td>
		<td style="border-bottom: 1px solid rgb(204, 204, 204);" nowrap="nowrap" width="75%">
			<span class="genHeaderSmall">{$APP.LBL_NO_M} {$APP.LBL_MODULE} {$APP.LBL_FOUND} !</span>
		</td>
	</tr>
</table>
{/if}