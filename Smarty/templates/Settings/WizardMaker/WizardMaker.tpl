{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@96233 *}


{* javascript for the module maker *}
<script type="text/javascript" src="modules/Settings/WizardMaker/WizardMaker.js"></script>


<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tr>
	<td valign="top"></td>
    <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

	<div align=center>
		{include file='SetMenu.tpl'}
		{include file='Buttons_List.tpl'} {* crmv@30683 *}
		<table class="settingsSelUITopLine" border="0" cellpadding="5" cellspacing="0" width="100%">
			<tr>
				<td rowspan="2" valign="top" width="50"><img src="{'module_maker.png'|resourcever}" alt="{$MOD.LBL_WIZARD_MAKER}" title="{$MOD.LBL_WIZARD_MAKER}" border="0" height="48" width="48"></td>
				<td class="heading2" valign="bottom"><b> {$MOD.LBL_SETTINGS} &gt; {$MOD.LBL_WIZARD_MAKER}</b></td> <!-- crmv@30683 -->
			</tr>
			<tr>
				<td class="small" valign="top">{$MOD.LBL_WIZARD_MAKER_DESC}</td>
			</tr>
		</table>
				
		<table border="0" cellpadding="10" cellspacing="0" width="100%">
			<tr>
				<td>
					
					{if ($MODE eq "create" || $MODE eq "edit") && $STEP > 0}
						{* navigation header *}
						{include file="Settings/WizardMaker/StepHeader.tpl"}
						
						{* navigation footer *}
						{include file="Settings/WizardMaker/StepFooter.tpl"}
						
						{* form for the step *}
						<form id="wizard_maker_form" method="POST" action="index.php?module=Settings&amp;action=WizardMaker&amp;mode={$MODE}&amp;parentTab=Settings">
							
							{* some basic variables *}
							<input type="hidden" name="wizardid" id="wizardid" value="{$WIZARDID}" />
							<input type="hidden" name="wizard_maker_prev_step" id="wizard_maker_prev_step" value="{$STEP}" />
							<input type="hidden" name="wizard_maker_step" id="wizard_maker_step" value="" />
							<input type="hidden" name="wizard_maker_savedata" id="wizard_maker_savedata" value="0" />
							
							{* box to dispaly errors *}
							<div id="wmaker_error_box" class="dvtCellInfo" style="width:98%;color:red;font-weight:700;margin-bottom:30px;padding:10px;{if $STEP_ERROR eq ''}display:none;{/if}">{$STEP_ERROR}</div>
							
							{* include the step template *}
							{include file="Settings/WizardMaker/Step`$STEP`.tpl"}
						</form>
						
					{else}
						
						{* box to dispaly errors *}
						<div id="wmaker_error_box" class="dvtCellInfo" style="width:98%;color:red;font-weight:700;margin-bottom:30px;padding:10px;{if $LIST_ERROR eq ''}display:none;{/if}">{$LIST_ERROR}</div>
					
						{include file="Settings/WizardMaker/List.tpl"}
					{/if}
			
					{include file="Settings/ScrollTop.tpl"}
				</td>
			</tr>
		</table>
		
   </div>

   </td>
   <td valign="top"></td>
</tr>
</table>