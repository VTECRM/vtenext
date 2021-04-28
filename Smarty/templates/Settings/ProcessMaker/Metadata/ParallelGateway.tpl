{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@103534 *}
{include file="Settings/ProcessMaker/Metadata/Header.tpl"}
<div style="padding:5px;">
	<form class="form-config-shape" shape-id="{$ID}">
		<table border="0" cellpadding="0" cellspacing="5" width="100%">
			<tr><td>
				<div class="dvtCellLabel">{$MOD.LBL_PM_GATEWAY_END_PARALLEL}</div>
				<div class="dvtCellInfo">
					<select name="closing_gateway" class="detailedViewTextBox">
						<option value="" {if $METADATA.closing_gateway eq ''}selected{/if}>{$APP.LBL_NONE}</option>
						{foreach key=k item=v from=$GATEWAY_LIST}
							<option value="{$k}" {if $k eq $METADATA.closing_gateway}selected{/if}>{$v}</option>
						{/foreach}
					</select>
				</div>
			</td></tr>
		</table>
	</form>
</div>