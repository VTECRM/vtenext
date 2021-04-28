{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@128369 *}

<p><b>{$MOD.LBL_CLUSTERS_LIST}:</b></p>

<table class="listTable" width="100%">
	<tr>
		<td class="colHeader small" width="150">{$APP.LBL_TOOLS}</td>
		<td class="colHeader small">{$MOD.LBL_CLUSTER_NAME}</td>
	</tr>
	{foreach item="CLUSTER" from=$CLUSTERS}
	<tr>
		<td class="listTableRow small">
			<i class="vteicon md-link" title="{$APP.LBL_EDIT}" onclick="EditReport.editCluster('{$REPORTID}', this)">create</i>
			<i class="vteicon md-link" title="{$APP.LBL_DELETE_BUTTON_LABEL}" onclick="EditReport.removeCluster('{$REPORTID}', this);">clear</i>
		</td>
		<td class="listTableRow small">{$CLUSTER.name}</td>
	</tr>
	{/foreach}
</table>

<br>