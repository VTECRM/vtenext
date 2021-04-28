{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@97862 *}

<div class="stepTitle" style="width=100%">
	<span class="genHeaderGray">{$MOD.LBL_SHARING}</span><br>
	<span style="font-size:90%"></span><hr>
</div>

<table border="0" width="100%">
	
	<tr>
		<td class="dimport_step_field_cell" align="right" width="35%"><span>{$MOD.LBL_SHARING_TYPE}</span>&nbsp;&nbsp;</td>
		<td align="left" width="350">
			<div class="dvtCellInfo">
				<select class="detailedViewTextBox" name="sharingtype" id="sharingtype" onchange="EditReport.changeSharing()">
					{foreach item=visible from=$VISIBLECRITERIA}
					<option {$visible.selected} value={$visible.value}>{$visible.text}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td align="left">
		</td>
	</tr>
	
	<tr>
		<td colspan="3">
			<br>

			<table id="shareMembersTable" border="0" width="80%" align="center">

				<tr>
					<td></td>
					<td width="40"></td>
					<td width="30%">
						<span><b>{"Entity Type"|getTranslatedString}</b></span><br>
						<select id="shareMemberType" class="detailedViewTextBox" onchange="EditReport.changeMemberType()">
							<option value="groups" selected>{$MOD.LBL_GROUPS}</option>
							<option value="users">{$MOD.LBL_USERS}</option>
						</select>
					</td>
					<td width="140"></td>
					<td width="30%">
						<b>{$MOD.LBL_MEMBERS}</b><br>
					</td>
					<td width="30"></td>
					<td></td>
				</tr>

				<tr>
					<td></td>
					<td></td>
					<td>
						<select id="availmembers" class="detailedViewTextBox" size="8" multiple="" style="min-width:340px">
						</select>
					</td>
					<td align="center" nowrap="" valign="center">
						<button name="add" class="crmbutton edit" type="button" onclick="EditReport.addMembers()">{$APP.LBL_ADD_ITEM} &gt;</button>
					</td>
					<td>
						<select id="sharedmembers" class="detailedViewTextBox" size="8" multiple="" style="min-width:340px">
							{if is_array($SHARING) && count($SHARING) > 0} {* crmv@167234 *}
							{foreach item=share from=$SHARING}
								<option value="{$share.value}">{$share.label}</option>
							{/foreach}
							{/if}
						</select>
					</td>
					<td valign="top">
						<i class="vteicon md-link" onclick="EditReport.removeMembers()">delete</i><br>
					</td>
					<td>
					</td>
				</tr>
				
			</table>

		</td>
	</tr>
</table>

<script type="text/javascript">
var reportUsers = {$SHAREUSERS_JS};
var reportGroups = {$SHAREGROUPS_JS};
</script>