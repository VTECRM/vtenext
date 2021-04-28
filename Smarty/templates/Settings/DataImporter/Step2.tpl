{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@65455 *}

<div>
	<p>{$MOD.LBL_DIMPORT_STEP2_INTRO}</p>
</div>
<br>

<table border="0" width="100%">
	
	<tr>
		<td class="dimport_step_field_cell" align="right" width="20%"><span>{$APP.LBL_SOURCE}</span>&nbsp;&nbsp;</td>
		<td align="left" width="250">
			<div class="dvtCellInfo">
				<input type="radio" name="dimport_sourcetype" id="dimport_sourcetype_db" value="database" {if $STEPVARS.dimport_sourcetype eq "database"}checked=""{/if}/> <label for="dimport_sourcetype_db">Database</label><br>
				<br>
				<input type="radio" name="dimport_sourcetype" id="dimport_sourcetype_csv" value="csv" {if $STEPVARS.dimport_sourcetype eq "csv"}checked=""{/if}/> <label for="dimport_sourcetype_csv">File CSV</label><br>
			</div>
		</td>
		<td width="50">&nbsp;</td>
		<td>
			{$MOD.LBL_DIMPORT_SOURCETYPE_DESC}
		</td>
	</tr>
	
</table>