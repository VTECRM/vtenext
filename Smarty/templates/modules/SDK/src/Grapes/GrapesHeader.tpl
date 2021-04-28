{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@197575 crmv@205899 *}

{if $CAN_EDIT_TEMPLATES}
	{* richiamo solo i css utili *}
	<link rel="stylesheet" type="text/css" href="themes/next/vte_bootstrap.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="themes/next/bootstrap-select.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="themes/next/jquery.dropdown.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="themes/next/style.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="themes/next/select2.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="themes/next/vte_materialize.css" media="screen" />

	<script language="JAVASCRIPT" type="text/javascript" src="include/js/{$CURRENT_LANGUAGE}.lang.js"></script>
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}{"include/js/general.js"|resourcever}"></script>
	<script language="JavaScript" type="text/javascript" src="{$RELPATH}modules/Campaigns/Campaigns.js|resourcever}"></script>

	<div id="nlw_templateEditCont">
		<input type="hidden" id="nlw_templateEditId" value="{$TPL_ID}" />

		<table border="0" cellspacing="0" cellpadding="0" width="100%" style="font-size: 13px;">
			<tr>
				<td align="left">{* <input type="button" class="crmbutton cancel" onclick="nlwCancelEditTemplate()" id="nlw_cancelEditTemplate" value="&lt; {$APP.LBL_CANCEL_BUTTON_LABEL}">*} </td>
				<td align="right"><div id="nlw_templateEditlIndicator" style="width:50px;display:none;">{include file="LoadingIndicator.tpl"}</div></td>
				<td align="right" width="60"><input type="button" class="crmbutton save" onclick="nlwSaveTemplate()" id="nlw_saveTemplate" value="{$APP.LBL_SAVE_LABEL}"></td>
			</tr>
		</table>

		<br>

		<table border="0" width="100%" style="margin-bottom:5px; font-size: 13px;">
			{*crmv@104558*}
			<tr><td style="width: 20%;">{'LBL_NAME'|getTranslatedString:'Settings'}:</td><td><div class="dvtCellInfoM"><input type="text" class="detailedViewTextBox" id="nlw_template_name" name="nlw_template_name" value="{$TPL_NAME}"></div></td></tr>
			<tr><td>{$APP.LBL_DESCRIPTION}:</td><td><div class="dvtCellInfo"><input type="text" class="detailedViewTextBox" id="nlw_template_description" value="{$TPL_DESC}"></div></td></tr>
			<tr><td>{$APP.LBL_SUBJECT}:</td><td><div class="dvtCellInfoM"><input type="text" class="detailedViewTextBox" id="nlw_template_subject" value="{$TPL_SUBJECT}"></div></td></tr> {* crmv@151466 *}
			{*crmv@104558e*}
			{* crmv@168109 *}
			{if $BU_MC_ENABLED}
			<tr>
				<td>Business Unit</td>
				<td>
					<div class="dvtCellInfoM">
						<select id="nlw_template_bu_mc" name="nlw_template_bu_mc[]" class="detailedViewTextBox" multiple>
						{foreach item=arr from=$BU_MC}
							<option value="{$arr.value}" {$arr.selected}>{$arr.label}</option>
						{/foreach}
						</select>
					</div>
				</td>
			</tr>
			{/if}
			{* crmv@168109e *}
		</table>
	</div>
{/if}