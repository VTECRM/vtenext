{*+*************************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@90004 *} {* crmv@173153 *}

<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">
			{$TITLE}
		</h1>
	</div>
</div>

{*
{if $ALLOW_ALL eq 'true' && $MODULE neq 'HelpDesk'}
	<div class="row">
 		<div class="col-lg-12" align="right">
			{'SHOW'|getTranslatedString}
			<select name="list_type" onchange="getList(this, '{$MODULE}');">
 				<option value="mine" {$MINE_SELECTED}>{'MINE'|getTranslatedString}</option>
				<option value="all" {$ALL_SELECTED}>{'ALL'|getTranslatedString}</option>
			</select>
		</div>
	</div>
{/if}
*}

<!-- <div class="table-responsive">  -->

{if $FIELDLISTVIEWFOLDER eq 'MODULE_INACTIVE' || $FIELDLISTVIEWFOLDER eq 'LBL_NOT_AVAILABLE'}
	{include file='ListViewEmpty.tpl' ERR_MESSAGE=$FIELDLISTVIEW}
{else if $FIELDLISTVIEWFOLDER neq ''}
	<div class="row">
		<div class="col-lg-12 col-md-12 col-xs-12">
			<div class="row">
				{foreach from=$FIELDLISTVIEWFOLDER key=key item=VALUE}	
					<div class="col-lg-3 col-md-3 col-sm-6  col-xs-12 text-center" onClick="window.location.href='index.php?module=Documents&action=index&onlymine=true&fun=detail&folderid={$VALUE.folderid}'">
						<center><img class="img-responsive" style="max-width:100%" src="images/listview_folder.png"></center>
						<spam>{$VALUE.foldername}</spam>
					</div>
				{/foreach}
			</div>
		</div>
	</div>
{/if}
