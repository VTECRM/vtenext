{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@96155 crmv@96233 crmv@102379 *}

{if count($WIZARDS) > 0}
<table width="100%" cellspacing="5" cellpadding="2" border="0">
	<tr>
		<td align="center" width="60%">
			<p>{$APP.LBL_CHOOSE_WIZARDS}:</p>
			<select id="select_wizards" multiple="" size="6" style="width:60%">
				{foreach item=wizard from=$WIZARDS}
					<option value="{$wizard.wizardid}">{$wizard.name|getTranslatedString}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td align="right">
			<button type="button" class="crmbutton save" onclick="ModuleHome.addBlock('{$MODHOMEID}', 'Wizards')">{$APP.LBL_SAVE_LABEL}</button>
		</td>
	</tr>
</table>
{else}
<center>
	<p>{$APP.LBL_NO_AVAILABLE_WIZARDS}</p>
</center>
{/if}