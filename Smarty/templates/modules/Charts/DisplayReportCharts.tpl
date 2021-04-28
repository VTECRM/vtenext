{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{assign var="chartColumns" value=2} {* numero di colonne *}

{assign var="chartCounter" value=0}
<table align="center" cellspacing="0" cellpadding="0" border="0"><tr>
{foreach item=chartInst from=$CHART_LIST}
	<td>
		{$chartInst->invalidateCache()} {* always up-to-date when displaying reports *}
		{$chartInst->renderChart(false)}
	</td>

	{assign var="chartCounter" value=$chartCounter+1}

	{if ($chartCounter % $chartColumns) eq 0}
		</tr><tr>
	{/if}
{/foreach}
</tr></table>