{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@96233 *}

<br>
<div style="width:100%" id="wmaker_div_navigation">
	<table border="0" cellspacing="0" cellpadding="0" width="100%">
		<tr>
			{if $USEREDIT}
			<td align="right">
				<input type="button" class="small crmbutton cancel" value="&lt; {$APP.LBL_BACK}" title="{$APP.LBL_BACK}" onclick="WizardMaker.gotoList()" />
			</td>
			{else}
			<td align="left">
				<input type="button" class="small crmbutton cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" title="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="WizardMaker.gotoList()" />
			</td>
			<td align="right">
				{if $STEP > 1}
					<input type="button" class="small crmbutton cancel" value="&lt; {$APP.LBL_BACK}" title="{$APP.LBL_BACK}" onclick="WizardMaker.gotoPrevStep()" />
				{/if}
				{if $STEP < $STEPS}
					<input type="button" class="small crmbutton save" value="{$APP.LBL_FORWARD} &gt;" title="{$APP.LBL_FORWARD}" onclick="WizardMaker.gotoNextStep()" />
				{elseif $STEP eq $STEPS}
					<input type="button" class="small crmbutton save" value="{$APP.LBL_SAVE_LABEL}" title="{$APP.LBL_SAVE_LABEL}" onclick="WizardMaker.saveWizard()" />
				{/if}
			</td>
			{/if}
		</tr>
	</table>
</div>
<br>