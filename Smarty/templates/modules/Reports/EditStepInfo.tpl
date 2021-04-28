{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@97862 *}

<div class="stepTitle" style="width=100%">
	<span class="genHeaderGray">{$MOD.LBL_REPORT_DETAILS}</span><br>
	<span style="font-size:90%">{$MOD.LBL_TYPE_THE_NAME} &amp; {$MOD.LBL_DESCRIPTION_FOR_REPORT}</span><hr>
</div>


<table border="0" width="100%">
	
	<tr>
		<td class="dimport_step_field_cell" align="right" width="35%"><span>{$APP.LBL_MODULE}</span>&nbsp;&nbsp;</td>
		<td align="left" width="350">
			{if $REPORTID || $DUPLICATE neq ''} {* crmv@200409 *}
			<div class="dvtCellInfoOff">
				<input class="detailedViewTextBox" name="primarymodule_display" id="primarymodule_display" value="{$PRIMARYMODULE_LABEL}" readonly="" />
				<input type="hidden" name="primarymodule" id="primarymodule" value="{$PRIMARYMODULE}" />
			</div>
			{else}
			<div class="dvtCellInfoM">
				<select class="detailedViewTextBox" name="primarymodule" id="primarymodule" onchange="EditReport.changePrimaryModule()">
					{foreach key=module item=label from=$REPT_MODULES}
					{if $PRIMARYMODULE eq $module}
						<option value="{$module}" selected>{$label}</option>
					{else}
						<option value="{$module}">{$label}</option>
					{/if}
					{/foreach}
				</select>
			</div>
			{/if}
		</td>
		<td align="left">
		</td>
	</tr>
	
	<tr>
		<td class="dimport_step_field_cell" align="right" width="35%"><span>{$MOD.LBL_REPORT_NAME}</span>&nbsp;&nbsp;</td>
		<td align="left" width="350">
			<div class="dvtCellInfoM">
				<input type="text" class="detailedViewTextBox" name="reportname" id="reportname" value="{$REPORTNAME|replace:'"':"&quot;"}"/> {* crmv@117392 *}
			</div>
		</td>
		<td align="left">
		</td>
	</tr>
	
	<tr id="selectFolderRow">
		<td class="dimport_step_field_cell" align="right" width="40%"><span>{$MOD.LBL_REP_FOLDER}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfoM">
				<select class="detailedViewTextBox" name="reportfolder" id="reportfolder">
					{foreach item=folder from=$REP_FOLDERS}
					{if $FOLDERID eq $folder.id}
						<option value="{$folder.id}" selected>{$folder.name}</option>
					{else}
						<option value="{$folder.id}">{$folder.name}</option>
					{/if}
					{/foreach}
				</select>
			</div>
		</td>
		<td align="left">
			<i class="vteicon md-link" title="{$MOD.LBL_ADD_NEW_GROUP}" onclick="EditReport.toggleCreateFolder()">add</i>
		</td>
	</tr>
	
	<tr id="createNewFolderRow" style="display:none">
		<td class="dimport_step_field_cell" align="right" width="40%"><span>{"Create_New_Folder"|getTranslatedString}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfoM">
				<input class="detailedViewTextBox" name="reportnewfolder" id="reportnewfolder" value="" />
			</div>
		</td>
		<td align="left">
			<i class="vteicon md-link" title="" onclick="EditReport.toggleCreateFolder()">highlight_remove</i>
		</td>
	</tr>
	
	<tr>
		<td class="dimport_step_field_cell" align="right" width="40%"><span>{$MOD.LBL_DESCRIPTION}</span>&nbsp;&nbsp;</td>
		<td align="left" width="300">
			<div class="dvtCellInfo">
				<textarea name="reportdes" class="detailedViewTextBox" rows="5">{$REPORTDESC}</textarea>
			</div>
		</td>
		<td align="left">
		</td>
	</tr>

	<tr>
		<td class="dimport_step_field_cell" align="right" width="40%"><span>{$APP.LBL_ASSIGNED_TO}</span>&nbsp;&nbsp;</td>
		<td align="left" width="300">
			<div class="dvtCellInfo">
				<select id="rep_assigned_to" name="rep_assigned_to" class="detailedViewTextBox">
					{foreach key=userid item=username from=$ASSIGNED_TO_USERS}
						<option value="{$userid}" {if $userid eq $REP_ASSIGNED_TO}selected="selected"{/if}>{$username}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td align="left">
		</td>
	</tr>
	
</table>