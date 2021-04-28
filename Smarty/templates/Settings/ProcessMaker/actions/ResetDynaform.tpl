{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@105685 crmv@192951 *}

<table border="0" cellpadding="2" cellspacing="0" width="100%">
	<tr>
		<td align=right width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" mandatory=true label=$APP.DynaForm}
		</td>
		<td align="left">
			<div class="dvtCellInfo">
				<select name="dynaform_involved" class="detailedViewTextBox">
					{foreach key=k item=i from=$DYNAFORMS_INVOLVED}
						<option value="{$k}" {$i.1}>{$i.0}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td align=right width=15% nowrap="nowrap">&nbsp;</td>
	</tr>
	<tr>
		<td align=right width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" mandatory=true label=$MOD.LBL_EMPTY_DYNAFORM_VALUES}
		</td>
		<td align="left">
			{include file="EditViewUI.tpl" NOLABEL=true DIVCLASS="dvtCellInfo" uitype=56 fldname="empty_fields" fldvalue=$EMPTY_FIELDS}
		</td>
		<td align=right width=15% nowrap="nowrap">&nbsp;</td>
	</tr>
</table>