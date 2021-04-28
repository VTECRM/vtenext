{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@96233 *}

<br>
<div style="position:relative" class="progressbar-circles">
	<div class="steps-border"></div>
	<table class="step-table" cellspacing="0" cellpadding="0" width="100%">
		<tr class="step-row">
			{math assign=tw equation="round(100 / $STEPS)"}
			{section name=curstep step=1 loop=$STEPS}
				{assign var=cstep value="`$smarty.section.curstep.index+1`"}
				{assign var=label value="LBL_WIZARD_MAKER_STEP`$cstep`"}
				<td width="{$tw}%" class="step-cell {if $STEP == $cstep}active-step{/if} {if $STEP > $cstep}previous-step{/if} {if $STEP < $cstep}next-step{/if}" nowrap="">
					<span class="step-border">
						<span class="step-num">{$cstep}</span>
					</span>
					<span class="step-text">{$MOD.$label}</span></td>
			{/section}
		</tr>
	</table>
</div>