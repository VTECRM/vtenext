{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@146670 *}

<br>
<div style="width:100%; text-align:right">
	<span id="extws_busy" style="display:none;">{include file="LoadingIndicator.tpl"}</span>
	<input type="button" class="small crmbutton create" value="{$APP.LBL_ADD_BUTTON}" title="{$APP.LBL_ADD_BUTTON}" onclick="ExtWSConfig.createNew()" />
</div>
<br>

{if count($WSLIST) > 0}
	<table width="100%" cellspacing="0" cellpadding="5" border="0" class="listTable">
		<tr>
			<td class="colHeader small">{$APP.LBL_LIST_NAME}</td>
			<td class="colHeader small">{$APP.LBL_DESCRIPTION}</td>
			<td class="colHeader small" width="120">{$APP.LBL_TYPE}</td>
			<td class="colHeader small" width="100">{$APP.Active}</td>
			<td class="colHeader small" width="100">{$APP.LBL_TOOLS}</td>
		</tr>
		
		{foreach item=row from=$WSLIST}
			<tr>
				<td class="listTableRow small">{$row.wsname}</td>
				<td class="listTableRow small">{$row.wsdesc_html}</td>
				<td class="listTableRow small">{$row.wstype}</td>
				<td class="listTableRow small">{if $row.active}{$APP.LBL_YES}{else}{$APP.LBL_NO}{/if}</td>
				<td class="listTableRow small">
					<i class="vteicon md-link" onclick="ExtWSConfig.editWS('{$row.extwsid}')" title="{'LBL_EDIT'|getTranslatedString}" >create</i>&nbsp;
					<i class="vteicon md-link" onclick="ExtWSConfig.deleteWS('{$row.extwsid}')" title="{'LBL_DELETE'|getTranslatedString}" >delete</i>
				</td>
			</tr>
		{/foreach}
	</table>
{else}
	<p>{$MOD.LBL_NO_CUSTOM_EXTWS}</p>
{/if}