{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@92272 *}
{include file="Settings/ProcessMaker/Metadata/Header.tpl"}
<div style="padding:5px;">
	<form class="form-config-shape" shape-id="{$ID}">
		<table border="0" cellpadding="0" cellspacing="5" width="100%">
			{if $SHOW_REQUIRED_CHECK}
				<tr>
					<td align="right">
						<input type="checkbox" id="required2go" name="required2go" {if $METADATA.required2go eq 'on'}checked{/if}/>&nbsp;<label for="required2go">{$MOD.LBL_PROCESS_MAKER_REQUIRED_TO_GO_ALL}</label>
					</td>
				</tr>
			{/if}
			{foreach item=GROUP key=GROUPID from=$CONDITION_GROUPS}
				{if !empty($GROUP.name)}
					<table class="tableHeading" width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td class="dvInnerHeader">
								<strong>{$GROUP.name}</strong>
							</td>
							{*
							{if $SHOW_REQUIRED_CHECK}
								<td class="dvInnerHeader" align="right">
									<input type="checkbox" id="required2go_{$GROUPID}" name="required2go_{$GROUPID}" {if $GROUP.required2go eq 'on'}checked{/if}/>&nbsp;<label for="required2go_{$GROUPID}">{$MOD.LBL_PROCESS_MAKER_REQUIRED_TO_GO}</label>
								</td>
							{/if}
							*}
						</tr>
					</table>
				{/if}
				{foreach item=CONDITION from=$GROUP.conditions}
					<div class="dvtCellLabel">{$CONDITION.label}</div>
					<div class="dvtCellInfo">
						<select name="{$CONDITION.cond}" class="detailedViewTextBox">
							{foreach key=k item=v from=$OUTGOINGS}
								<option value="{$k}" {if $k eq $CONDITION.elementid}selected{/if}>{$v}</option>
							{/foreach}
						</select>
					</div>
				{/foreach}
				<div style="height:5px;"></div>
			{foreachelse}
				{include file="Error.tpl" DESCR=$MOD.LBL_PM_GATEWAY_NO_CONDITIONS}
			{/foreach}
			</td></tr>
		</table>
	</form>
</div>