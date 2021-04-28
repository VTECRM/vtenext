{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@65455 *}

<div>
	<p>{$MOD.LBL_DIMPORT_STEP4_INTRO}</p>
</div>
<br>



<table border="0" width="100%">
	
	<tr>
		<td class="dimport_step_field_cell" align="right" width="20%"><span>{$MOD.LBL_IMPORT_TABLE}</span>&nbsp;&nbsp;</td>
		<td align="left" width="250">
			<div class="dvtCellInfo">
				<select class="detailedViewTextBox" name="dimport_dbtable" id="dimport_dbtable">
					<option value="" {if $STEPVARS.dimport_dbtable eq ""}selected=""{/if}>{$APP.LBL_NONE}</option>
					{foreach item=tab from=$DBTABLES}
						<option value="{$tab}" {if $STEPVARS.dimport_dbtable eq $tab}selected=""{/if}>{$tab}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td width="50">&nbsp;</td>
		<td>
			{$MOD.LBL_IMPORT_TABLE_DESC}
		</td>
	</tr>

</table>

{if $CAN_EDIT_QUERY}
<br>
<p>{$MOD.LBL_OR_WRITE_IMPORT_QUERY}</p>

	{* scripts and styles *}
	<link rel="stylesheet" type="text/css" media="screen" href="include/js/codemirror/lib/codemirror.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="include/js/codemirror/theme/ambiance.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="include/js/codemirror/theme/eclipse.css" />
	{* Custom styles for the editor *}
	<style type="text/css">
		{literal}
		.CodeMirror {
			border: 1px solid #909090;
			border-radius: 5px;
			-webkit-border-radius: 5px;
			-moz-border-radius: 5px;
			height: 150px;
		}
		{/literal}
	</style>
	<script type="text/javascript" src="include/js/codemirror/lib/codemirror.js"></script>
	<script type="text/javascript" src="include/js/codemirror/mode/sql/sql.js"></script>

<table border="0" width="100%">
	
	<tr>
		<td align="left" class="">
			<textarea class="detailedViewTextBox" style="" placeholder="SELECT field1, ... FROM table WHERE ..." id="dimport_dbquery" name="dimport_dbquery">{$STEPVARS.dimport_dbquery}</textarea>
		</td>
	</tr>
	<tr>
		<td align="left" class="">
			{if $DESTMODULE eq 'ProductRows'}
			<p>{$MOD.LBL_DIMPORT_INV_ROWS_SQL_NOTE}</p>
			{/if}
		</td>
	</tr>

</table>

{* init the editor *}
<script type="text/javascript">
	(function() {ldelim}
		{if $DBTYPE eq 'mysql'}
		var mode = "text/x-mysql";
		{elseif $DBTYPE eq 'mssql'}
		var mode = "text/x-mssql";
		{elseif $DBTYPE eq 'oci8po'}
		var mode = "text/x-plsql";
		{else}
		var mode = "sql";
		{/if}
		DataImporter.initEditor('{"'"|str_replace:"\'":$STEPVARS.dimport_dbquery}', mode);
	{rdelim})();
</script>

{/if}