{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@OPER6317 crmv@96233 *}

{include file="WizardHeader.tpl"}

<script type="text/javascript">
{if $WIZARD.jsinfo}
	var WizardInfo = {$WIZARD.jsinfo|replace:"'":"\'"};
{else}
	var WizardInfo = {ldelim}{rdelim};
{/if}
</script>

{assign var="STEPS" value=$WIZARD.steps}

<table id="nlWizMainTab" border="0" height="100%">
	<tr>
		<td id="nlWizLeftPane">
			<div>
				<table id="nlWizStepTable">
					{counter start=0 print=false name="stepcounter" assign="STEP_NO"}
					{foreach item=STEP from=$STEPS}
						{counter print=false name="stepcounter"}
						<tr>
							<td class="nlWizStepCell {if $STEP_NO == 1}nlWizStepCellSelected{/if}">
								<span class="circleIndicator {if $STEP_NO == 1}circleEnabled{/if}">{$STEP_NO}</span>
								{$STEP.label}
							</td>
						</tr>
					{/foreach}
				</table>
			</div>
		</td>
		
		<td id="nlWizRightPane">
		
			<table id="nlwTopButtons" border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr>
					<td align="left"><input type="button" class="crmbutton cancel" onclick="Wizard.gotoPrevStep()" id="nlw_backButton" style="display:none" value="&lt; {$APP.LBL_BACK}"></td>
					<td align="right">
						<input type="button" class="crmbutton save" onclick="Wizard.gotoNextStep()" id="nlw_nextButton" value="{$APP.LBL_FORWARD} &gt;">
						<input type="button" class="crmbutton save" onclick="Wizard.save()" id="nlw_endButton" style="display:none" value="{$APP.LNK_LIST_END}">
					</td>
				</tr>
			</table>
			
			{counter start=0 print=false name="stepcounter" assign="STEP_NO"}
			{foreach item=STEP from=$STEPS}
				{counter print=false name="stepcounter"}
				{if $STEP.type == 'select'}
					{include file="Wizards/WizardSelect.tpl"}
				{elseif $STEP.type == 'create'}
					{include file="Wizards/WizardCreate.tpl"}
				{elseif $STEP.type == 'custom'}
					CUSTOM
				{/if}
			{/foreach}
			
		</td>
	</tr>
</table>

<script type="text/javascript">
	{literal}
	jQuery(document).ready(function() {
		Wizard.initializeStep(1);
	});
	{/literal}
</script>