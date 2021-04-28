{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@128369 *}

<div class="stepTitle" style="width=100%">
	<span class="genHeaderGray">{$MOD.LBL_CLUSTERS}</span><br>
	<span style="font-size:90%">{$MOD.LBL_SELECT_CLUSTERS}</span><hr>
</div>


<div id="filterListReview" style="margin-left:10px">
	<p><b>{$MOD.ACTIVE_ADV_FILTERS}:</b></p> {* crmv@171125 *}
	<div id="filterListReviewContent" style="font-size:90%;margin-left:10px">
	</div>
	
	<br>
</div>


{* clusters list *}
<div id="clustersList" style="padding:10px">
{if is_array($CLUSTERS) && count($CLUSTERS) > 0} {* crmv@167234 *}
{include file="modules/Reports/ClustersList.tpl"}
{/if}
</div>

<table border="0" width="100%" align="center">
	<tr>
		<td class="" align="center"><button class="crmbutton edit" onclick="EditReport.createCluster('{$REPORTID}')">{$MOD.LBL_ADD_CLUSTER}</button></td>
	</tr>
</table>


<input type="hidden" name="clusters" id="clusters" value="{$CLUSTERDATA}" />