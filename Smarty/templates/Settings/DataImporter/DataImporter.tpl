{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@65455 crmv@69398 *}

{* some extra translations *}

{*
<script type="text/javascript">
	var ModuleMakerTrans = {ldelim}{rdelim};
{if count($TRANS) > 0}
	{foreach key=lbl item=tr from=$TRANS}
	ModuleMakerTrans['{$lbl}'] = '{"'"|str_replace:"\'":$tr}';
	{/foreach}
{/if}
</script>
*}

{* javascript for the data importer *}
<script type="text/javascript" src="modules/Settings/DataImporter/DataImporter.js"></script>

{* some CSS *}
<style type="text/css">
{literal}
	/*.dimport_step_field_cell {
		min-height: 40px;
		height: 40px;
	}*/
	.floatingDiv {
		display:none;
		position: fixed;
	}
	.floatingHandle {
		padding: 5px;
		cursor: move;
	}
	.dimport_error_badge {
		display: inline-block;
		width: 16px;
		height: 16px;
		color: white;
		background: red;
		font-weight: 700;
		font-size: 12px;
		text-align: center;
		-webkit-border-radius: 8px;
		-moz-border-radius: 8px;
		border-radius: 8px;
		cursor: pointer;
	}
{/literal}
</style>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tr>
	<td valign="top"></td>
    <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

	<div align=center>
		{include file='SetMenu.tpl'}
		{include file='Buttons_List.tpl'} {* crmv@30683 *}
		<table class="settingsSelUITopLine" border="0" cellpadding="5" cellspacing="0" width="100%">
			<tr>
				<td rowspan="2" valign="top" width="50"><img src="{'data_import.png'|resourcever}" alt="{$MOD.LBL_DATA_IMPORTER}" title="{$MOD.LBL_DATA_IMPORTER}" border="0" height="48" width="48"></td>
				<td class="heading2" valign="bottom"><b> {$MOD.LBL_SETTINGS} &gt; {$MOD.LBL_DATA_IMPORTER}</b></td> <!-- crmv@30683 -->
			</tr>
			<tr>
				<td class="small" valign="top">{$MOD.LBL_DATA_IMPORTER_DESC}</td>
			</tr>
		</table>
				
		<table border="0" cellpadding="10" cellspacing="0" width="100%">
			<tr>
				<td>
					{if ($MODE eq "create" || $MODE eq "edit") && $STEP > 0}
						{* navigation header *}
						{include file="Settings/DataImporter/StepHeader.tpl"}
						
						{* navigation buttons, was at the bottom*}
						{include file="Settings/DataImporter/StepFooter.tpl"}
						
						{* form for the step *}
						<form id="data_importer_form" method="POST" action="index.php?module=Settings&amp;action=DataImporter&amp;mode={$MODE}&amp;parentTab=Settings">
							
							{* some basic variables *}
							<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
							<input type="hidden" name="importid" id="importid" value="{$IMPORTID}" />
							<input type="hidden" name="data_importer_prev_step" id="data_importer_prev_step" value="{$STEP}" />
							<input type="hidden" name="data_importer_step" id="data_importer_step" value="" />
							<input type="hidden" name="data_importer_savedata" id="data_importer_savedata" value="0" />
							<input type="hidden" name="skip_vte_header" id="skip_vte_header" value="" />
							
							{* box to dispaly errors *}
							<div id="dimport_error_box" class="dvtCellInfo" style="width:98%;color:red;font-weight:700;margin-bottom:30px;padding:10px;{if $STEP_ERROR eq ''}display:none;{/if}">{$STEP_ERROR}</div>
							
							{* include the step template *}
							{include file="Settings/DataImporter/Step`$STEP`.tpl"}

							<input type="hidden" name="form_var_count" id="form_var_count" value="0" /> {* crmv@111926 *}
						</form>
						<br>
					
					{else}
						
						{* box to dispaly errors *}
						<div id="dimporter_error_box" class="dvtCellInfo" style="width:98%;color:red;font-weight:700;margin-bottom:30px;padding:10px;{if $LIST_ERROR eq ''}display:none;{/if}">{$LIST_ERROR}</div>
					
						{include file="Settings/DataImporter/List.tpl"}
					{/if}
			
					{include file="Settings/ScrollTop.tpl"}
				</td>
			</tr>
		</table>
		<!-- End of Display -->
		
   </div>

   </td>
   <td valign="top"></td>
</tr>
</table>