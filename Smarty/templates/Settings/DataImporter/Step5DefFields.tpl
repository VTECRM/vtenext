{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@65455 *}
{* tables for field defaults *}
{* TODO: labels *}

{assign var=DEFAULT_CREATE value=$STEPVARS.dimport_deffields.create}
{assign var=DEFAULT_UPDATE value=$STEPVARS.dimport_deffields.update}

<br><br>
{* title *}
<table border="0" width="100%" class="small">
	<tr>
		<td><b>{$MOD.LBL_DEFAULT_FIELDS_CREATE}:</b></td>
		<td align="right">
			<input type="button" class="small crmbutton create" title="{$MOD.LBL_ADD_NEW_FIELD}" value="{$MOD.LBL_ADD_NEW_FIELD}" onclick="DataImporterFields.addDefaultField('create')"/>
		</td>
	</tr>
</table>
<br>

{if count($DEFAULT_CREATE) > 0}
	<table border="0" width="100%" class="small listTable" id="dimport_table_dfield_c" cellspacing="0" cellpadding="5">
		<tr>
			<td class="small colHeader" width="40%"><b>{'LBL_CRM_FIELDS'|@getTranslatedString:'Import'}</b></td>
			<td class="small colHeader" width=""><b>{'LBL_DEFAULT_VALUE'|@getTranslatedString:'Import'}</b></td>
			<td class="small colHeader" width="100" align="right"><b>{$APP.LBL_ACTIONS}</b></td>
		</tr>
		{foreach key=fieldno item=drow from=$DEFAULT_CREATE}
			<tr>
				<td class="listTableRow" id="dimport_cell_c_{$fieldno}_deffield">
					<select id="dimport_dfield_c_{$fieldno}_field" name="dimport_dfield_c_{$fieldno}_field" onchange="DataImporterFields.onDefFieldChange('{$fieldno}', 'create')">
					<option value="">{$APP.LBL_NONE}</option>
					{foreach item=field from=$ALLFIELDS}
						<option value="{$field.fieldname}" {if $drow.field eq $field.fieldname}selected=""{/if}>{$field.label} {if $field.mandatory}(*){/if}</option>
					{/foreach}
					</select>
				</td>
				<td class="listTableRow" id="dimport_cell_c_{$fieldno}_default"></td>
				<td class="listTableRow" align="right">
					<img style="cursor:pointer;" onClick="DataImporterFields.removeDefaultField('create', '{$fieldno}')" src="{'delete.gif'|resourcever}" border="0"  alt="{$APP.LBL_DELETE_BUTTON}" title="{$APP.LBL_DELETE_BUTTON}"/>&nbsp;&nbsp;
				</td>
			</tr>
		{/foreach}
	</table>
{/if}


{* default fields for update *}
<br><br>
{* title *}
<table border="0" width="100%" class="small">
	<tr>
		<td><b>{$MOD.LBL_DEFAULT_FIELDS_UPDATE}:</b></td>
		<td align="right">
			<input type="button" class="small crmbutton create" title="{$MOD.LBL_ADD_NEW_FIELD}" value="{$MOD.LBL_ADD_NEW_FIELD}" onclick="DataImporterFields.addDefaultField('update')"/>
		</td>
	</tr>
</table>
<br>

{if count($DEFAULT_UPDATE) > 0}
	<table border="0" width="100%" class="small listTable" id="dimport_table_dfield_u" cellspacing="0" cellpadding="5">
		<tr>
			<td class="small colHeader" width="40%"><b>{'LBL_CRM_FIELDS'|@getTranslatedString:'Import'}</b></td>
			<td class="small colHeader" width=""><b>{'LBL_DEFAULT_VALUE'|@getTranslatedString:'Import'}</b></td>
			<td class="small colHeader" width="100" align="right"><b>{$APP.LBL_ACTIONS}</b></td>
		</tr>
		{foreach key=fieldno item=drow from=$DEFAULT_UPDATE}
			<tr>
				<td class="listTableRow" id="dimport_cell_u_{$fieldno}_deffield">
					<select id="dimport_dfield_u_{$fieldno}_field" name="dimport_dfield_u_{$fieldno}_field" onchange="DataImporterFields.onDefFieldChange('{$fieldno}', 'update')">
					<option value="">{$APP.LBL_NONE}</option>
					{foreach item=field from=$ALLFIELDS}
						<option value="{$field.fieldname}" {if $drow.field eq $field.fieldname}selected=""{/if}>{$field.label} {if $field.mandatory}(*){/if}</option>
					{/foreach}
					</select>
				</td>
				<td class="listTableRow" id="dimport_cell_u_{$fieldno}_default"></td>
				<td class="listTableRow" align="right">
					<img style="cursor:pointer;" onClick="DataImporterFields.removeDefaultField('update', '{$fieldno}')" src="{'delete.gif'|resourcever}" border="0"  alt="{$APP.LBL_DELETE_BUTTON}" title="{$APP.LBL_DELETE_BUTTON}"/>&nbsp;&nbsp;
				</td>
			</tr>
		{/foreach}
	</table>
{/if}



{* set the defaults *}
<script type="text/javascript">
	{foreach key=srccol item=row from=$DEFAULT_CREATE}
		{if $row.field neq ''}
		DataImporterFields.alignDefDefault('{$srccol}', 'create', '{$row.default}');
		{/if}
	{/foreach}
	{foreach key=srccol item=row from=$DEFAULT_UPDATE}
		{if $row.field neq ''}
		DataImporterFields.alignDefDefault('{$srccol}', 'update', '{$row.default}');
		{/if}
	{/foreach}
</script>