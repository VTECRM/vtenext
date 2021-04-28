{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@65455 *}

<div>
	<p>{$MOD.LBL_DIMPORT_STEP3_INTRO}</p>
</div>
<br>

<input type="hidden" id="sourcetype" value="{$SOURCETYPE}" />

{if $SOURCETYPE eq "database"}

<table border="0" width="100%">
	
	<tr>
		<td class="dimport_step_field_cell" align="right" width="20%"><span>{$APP.LBL_DATABASE_TYPE}</span>&nbsp;&nbsp;</td>
		<td align="left" width="250">
			<div class="dvtCellInfo">
				<select class="detailedViewTextBox" name="dimport_dbtype" id="dimport_dbtype" onchange="DataImporter.step3_changedbType()">
					<option value="" {if $STEPVARS.dimport_dbtype eq ""}selected=""{/if}>{$APP.LBL_NONE}</option>
					{foreach item=label key=mod from=$DBTYPES}
						<option value="{$mod}" {if $STEPVARS.dimport_dbtype eq $mod}selected=""{/if}>{$label}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td width="50">&nbsp;</td>
		<td>
			{$MOD.LBL_DIMPORT_DBTYPE_DESC}
		</td>
	</tr>
	
	<tr>
		<td class="dimport_step_field_cell" align="right"><span>{$APP.LBL_HOSTNAME}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<input class="detailedViewTextBox" type="text" name="dimport_dbhost" id="dimport_dbhost" value="{$STEPVARS.dimport_dbhost}" maxlength="60" />
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_DIMPORT_DBHOST_DESC}
		</td>
	</tr>
	
	<tr>
		<td class="dimport_step_field_cell" align="right"><span>{$MOD.LBL_PROXY_PORT}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<input class="detailedViewTextBox" type="text" name="dimport_dbport" id="dimport_dbport" value="{$STEPVARS.dimport_dbport}" maxlength="5" />
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_DIMPORT_DBPORT_DESC}
		</td>
	</tr>
	
	<tr>
		<td class="dimport_step_field_cell" align="right"><span>{$APP.LBL_LIST_USER_NAME}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<input class="detailedViewTextBox" type="text" name="dimport_dbuser" id="dimport_dbuser" value="{$STEPVARS.dimport_dbuser}" maxlength="60"/>
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_DIMPORT_DBUSER_DESC}
		</td>
	</tr>
	
	<tr>
		<td class="dimport_step_field_cell" align="right"><span>{$MOD.LBL_PASWRD}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<input class="detailedViewTextBox" type="password" name="dimport_dbpass" id="dimport_dbpass" value="{$STEPVARS.dimport_dbpass}" autocomplete="off" maxlength="60" />
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_DIMPORT_DBPASS_DESC}
		</td>
	</tr>
	
	<tr>
		<td class="dimport_step_field_cell" align="right"><span>{$APP.LBL_DATABASE_NAME}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<input class="detailedViewTextBox" type="text" name="dimport_dbname" id="dimport_dbname" value="{$STEPVARS.dimport_dbname}" maxlength="60" />
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_DIMPORT_DBNAME_DESC}
		</td>
	</tr>
	
</table>

{elseif $SOURCETYPE eq "csv"}
<script>
{literal}

{/literal}
</script>

{* file chooser includes *}
<link href="include/js/jquery_plugins/fileTree/jqueryFileTree.css" rel="stylesheet" type="text/css" media="screen" />
<script src="include/js/jquery_plugins/fileTree/jqueryFileTree.js" type="text/javascript"></script>

{* fields *}
<table border="0" width="100%">
	
	<tr>
		<td class="dimport_step_field_cell" align="right" width="20%"><span>{$MOD.LBL_DIMPORT_CSVPATH}</span>&nbsp;&nbsp;</td>
		<td align="left" width="250">
			{* crmv@105144 *}
			<div class="dvtCellInfo" style="position:relative">
				<input class="detailedViewTextBox" type="text" name="dimport_csvpath" id="dimport_csvpath" value="{$STEPVARS.dimport_csvpath}"/>
				<div class="dvtCellInfoImgRx">
					<i class="vteicon md-link md-sm" title="{$APP.LBL_SELECT}" onclick="DataImporter.openFileChooser()">folder_open</i>
				</div>
			</div>
			{* crmv@105144e *}
		</td>
		<td width="50">&nbsp;</td>
		<td>
			{$MOD.LBL_DIMPORT_CSVPATH_DESC}
		</td>
	</tr>
	
	<tr>
		<td class="dimport_step_field_cell" align="right"><span>{'LBL_CHARACTER_ENCODING'|getTranslatedString:"Import"}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<select class="detailedViewTextBox" name="dimport_csvencoding" id="dimport_csvencoding">
					{foreach item=label key=enc from=$CSVENCODINGS}
						<option value="{$enc}" {if $STEPVARS.dimport_csvencoding eq $enc}selected=""{/if}>{$label}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_DIMPORT_CSVENCODING_DESC}
		</td>
	</tr>
	
	<tr>
		<td class="dimport_step_field_cell" align="right"><span>{'LBL_DELIMITER'|getTranslatedString:"Import"}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<select class="detailedViewTextBox" name="dimport_csvdelimiter" id="dimport_csvdelimiter">
					{foreach item=label key=delim from=$CSVDELIMITERS}
						<option value="{$delim}" {if $STEPVARS.dimport_csvdelimiter eq $delim}selected=""{/if}>{$label}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_DIMPORT_CSVDELIMITER_DESC}
		</td>
	</tr>
	
	<tr>
		<td class="dimport_step_field_cell" align="right"><span>{'LBL_HAS_HEADER'|getTranslatedString:"Import"}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<input type="checkbox" name="dimport_csvhasheader" id="dimport_csvhasheader" {if $STEPVARS.dimport_csvhasheader}checked=""{/if}/>
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_DIMPORT_CSVDELIMITER_DESC}
		</td>
	</tr>
	
</table>

{* file chooser div *}
<div id="dimport_div_filechooser" class="crmvDiv floatingDiv" style="width:500px;height:400px">
	<input type="hidden" id="mmaker_logs_moduleid"  value="" />

	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td class="level3Bg floatingHandle">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="50%"><b>{$MOD.LBL_CHOOSE_CSV_TITLE}</b></td>
					<td width="50%" align="right">&nbsp;</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div class="crmvDivContent" style="padding:10px">
		<p>{$MOD.LBL_CHOOSE_CSV_DESC}</p>
		<div class="" id="fileChooserTree" style="width:480px;height:300px;overflow-y:scroll;overflow-x:hidden"></div>
	</div>
	<div class="closebutton" onclick="DataImporter.hideFloatingDiv('dimport_div_filechooser')"></div>
</div>

{* enable dragging for every floating div *}
<script type="text/javascript">
{literal}
(function() {
	// crmv@192014
	var floats = jQuery('div.floatingDiv');
	floats.each(function(index, f) {
		if (f) {
			var handle = jQuery(f).find('.floatingHandle').get(0);
			if (handle) {
				jQuery(f).draggable({
					handle: handle
				});
			}
		}
	});
	// crmv@192014e
})();
{/literal}
</script>

{/if}