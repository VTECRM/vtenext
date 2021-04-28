{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@96233 *}

<br>
<div style="width:100%; text-align:right">
	<span id="wmaker_busy" style="display:none;">{include file="LoadingIndicator.tpl"}</span>
	<input type="button" class="small crmbutton create" value="{$APP.LBL_ADD_BUTTON}" title="{$APP.LBL_ADD_BUTTON}" onclick="WizardMaker.createNew()" />
</div>
<br>

{if count($WIZLIST) > 0}
	<table width="100%" cellspacing="0" cellpadding="5" border="0" class="listTable">
		<tr>
			<td class="colHeader small">{$APP.Wizard}</td>
			<td class="colHeader small">{$APP.LBL_MODULE}</td>
			<td class="colHeader small">{$APP.Active}</td>
			<td class="colHeader small" width="140">{$APP.LBL_TOOLS}</td>
		</tr>
		
		{foreach item=row from=$WIZLIST}
			<tr>
				<td class="listTableRow small">{$row.name|getTranslatedString:$row.module}</td>
				<td class="listTableRow small">{$row.module|getTranslatedString:$row.module}</td>
				<td class="listTableRow small">
					{if $row.enabled}
						<i class="vteicon md-link checkok" onclick="WizardMaker.disableWizard('{$row.wizardid}')">check</i>
					{else}
						<i class="vteicon md-link checkko" onclick="WizardMaker.enableWizard('{$row.wizardid}')">clear</i>
					{/if}
				</td>
				<td class="listTableRow small">
					<i class="vteicon md-link" onclick="WizardMaker.editWizard('{$row.wizardid}')" title="{'LBL_EDIT'|getTranslatedString}" >create</i>&nbsp;
					<i class="vteicon md-link" onclick="WizardMaker.deleteWizard('{$row.wizardid}')" title="{'LBL_DELETE'|getTranslatedString}" >delete</i>
				</td>
			</tr>
		{/foreach}
	</table>
{else}
	<p>{$APP.LBL_NO_AVAILABLE_WIZARDS}</p>
{/if}