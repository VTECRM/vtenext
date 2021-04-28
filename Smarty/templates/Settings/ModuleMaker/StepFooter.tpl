{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@64542 crmv@69398 *}

<br>
<div style="width:100%" id="mmaker_div_navigation">
	<table border="0" cellspacing="0" cellpadding="0" width="100%">
		<tr>
			{if $USEREDIT}
			<td align="right">
				<input type="button" class="small crmbutton cancel" value="&lt; {$APP.LBL_BACK}" title="{$APP.LBL_BACK}" onclick="ModuleMaker.gotoList()" />
			</td>
			{else}
			<td align="left">
				<input type="button" class="small crmbutton cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" title="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="ModuleMaker.gotoList()" />
			</td>
			<td align="right">
				{if $STEP > 1}
					<input type="button" class="small crmbutton cancel" value="&lt; {$APP.LBL_BACK}" title="{$APP.LBL_BACK}" onclick="ModuleMaker.gotoPrevStep()" />
				{/if}
				{if $STEP < $STEPS}
					<input type="button" class="small crmbutton save" value="{$APP.LBL_FORWARD} &gt;" title="{$APP.LBL_FORWARD}" onclick="ModuleMaker.gotoNextStep()" />
				{elseif $STEP eq $STEPS}
					<input type="button" class="small crmbutton save" value="{$APP.LBL_SAVE_LABEL}" title="{$APP.LBL_SAVE_LABEL}" onclick="ModuleMaker.saveModule()" />
				{/if}
			</td>
			{/if}
		</tr>
	</table>
</div>
<br>