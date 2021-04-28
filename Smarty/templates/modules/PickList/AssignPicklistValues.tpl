{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<div style="position:relative;display: block;" id="orgLay" class="layerPopup">
	<table border="0" cellpadding="5" cellspacing="0" class="layerHeadingULine">
		<tr>
			<td class="layerPopupHeading" align="left" width="40%" nowrap>{$MOD.ASSIGN_PICKLIST_VALUES} - {$FIELDLABEL}</td>
		</tr>
	</table>

	<table border="0" cellspacing="0" cellpadding="5" width="100%" id="assignPicklistTable">
	<tbody>
		<tr>	
			<td>	
				<b>{$MOD.LBL_PICKLIST_VALUES}</b>
				<select multiple id="availList" name="availList" class="small crmFormList notdropdown" style="overflow:auto; height: 150px;min-width:150px;border:1px solid #666666;font-family:Arial, Helvetica, sans-serif;font-size:11px;">
					{foreach key=pick_realval item=pick_val from=$PICKVAL}
						<option value="{$pick_realval|htmlentities:2:"UTF-8"}">{$pick_val}</option>	{* crmv@55994 *} {* crmv@83592 *}
					{/foreach}
				</select>
			</td>
			<td align="center" width="30px">
				<i class="vteicon md-link" title="right" onclick="moveRight();">arrow_forward</i><br>
				<i class="vteicon md-link" title="left" onclick="removeValue();" style="width:24px">arrow_backward</i> {* no idea why I need to specify the width *}
			</td>
			<td>
				<b>{$MOD.LBL_PICKLIST_VALUES_ASSIGNED_TO} {$ROLENAME}</b>
				<select multiple id="selectedColumns" name="selectedColumns" class="small crmFormList notdropdown" style="overflow:auto; height: 150px;min-width:150px;border:1px solid #666666;font-family:Arial, Helvetica, sans-serif;font-size:11px;">
					{foreach key=item_realval item=val from=$ASSIGNED_VALUES}
						<option value="{$item_realval}">{$val}</option>
					{/foreach}
        	    </select>
			</td>
			<td align="center">
				<i class="vteicon md-link" title="up" onclick="moveUp();">arrow_upward</i>
				<i class="vteicon md-link" title="down" onclick="moveDown();">arrow_downward</i>
			</td>
		</tr>
		<tr>
			<td>
				<a href='javascript:;' onclick="showRoleSelectDiv('{$ROLEID}')" id="addRolesLink">
					<b>{$MOD.LBL_ADD_TO_OTHER_ROLES}</b>
				</a>
			</td>
			<td colspan="3" valign="top" align="center" nowrap>
				<input type="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" name="save" class="crmButton small edit" onclick="saveAssignedValues('{$MODULE}','{$FIELDNAME}','{$ROLEID}');">
				<input type="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" name="cancel" class="crmButton small cancel" onclick="fnhide('actiondiv');">
			</td>			
		</tr>
	</tbody>
	</table>
</div>