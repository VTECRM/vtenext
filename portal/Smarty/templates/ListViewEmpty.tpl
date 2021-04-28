{*+*************************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{if empty($ERR_MESSAGE)}
	{assign var=ERR_MESSAGE value='LBL_NONE_SUBMITTED'}
{/if}

{if empty($MODULE)}
<div class="row">
	<div class="col-md-12 col-sm-12">
		<div class="col-md-6 col-md-offset-6 col-sm-6 col-sm-offset-6">
			<div class="col-md-4 col-md-offset-4 col-sm-4 col-sm-offset-4 col-xs-12 " align="right"> 
				{'SHOW'|getTranslatedString}
				<select name="list_type" onchange="getList(this, '{$MODULE}');">
 					<option value="mine" {$MINE_SELECTED}>{'MINE'|getTranslatedString}</option>
					<option value="all" {$ALL_SELECTED}>{'ALL'|getTranslatedString}</option>
				</select>
			</div>
			{* crmv@80441 *}
			<div class="col-md-4 col-sm-4 col-xs-12" align="right"> 
			</div>			
			{* crmv@80441 *}
		</div>
	</div>
</div>
{/if}
<div class="row">
	{include file='SearchForm.tpl'}
	<div class="alert alert-danger alert-dismissable authorised">
		<button type="button" class="close" data-dismiss="alert" onclick="location.href='index.php?module={$MODULE}&amp;action=index'">&times;</button> {* crmv@90004 *}
		{$ERR_MESSAGE|getTranslatedString}
	</div>
</div>