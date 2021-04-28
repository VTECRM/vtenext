{*+*************************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@173153 *}

<!-- Javascript -->
<script src="js/bootstrap.min.js"></script>

<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{'UPDATE_PROFILE'|getTranslatedString}</h1>
	</div>
	<div class="col-sm-12">
		<form action="index.php" method="post">
			<input type="hidden" name="module" value="Contacts">
			<input type="hidden" name="action" value="Save">
			<input type="hidden" name="id" value="{$CUSTOMERID}">
			<input type="hidden" name="ajax" value="true">
			
			<div class="row">
				<div class="col-sm-6 text-left">
					<button class="btn btn-default" type="button" onclick="location.href='?module=Contacts&action=index&id={$CUSTOMERID}&profile=yes'">{'LBL_BACK_BUTTON'|getTranslatedString}</button>
				</div>
				<div class="col-sm-6 text-right">
					<button class="btn btn-success" type="submit">{'LBL_SAVE'|getTranslatedString}</button>
				</div>
			</div>
			
			<div class="row">
				{foreach from=$FIELDLIST item=FIELD}
					 {foreach from=$FIELD item=VALUE}
						<div class="col-md-12">
							<h4><label>{$VALUE.0|getTranslatedString}</label></h4>
						</div>
						<div class="col-md-12">
							<div class="form-group">
							{if in_array($VALUE.2, $SELECTFIELDS)}
								<select name="{$VALUE.2}" class="form-control">
									{if $VALUE.1 eq "Yes"}
										<option value="1" selected>{"LBL_YES"|getTranslatedString}</option>
										<option value="0">{"LBL_NO"|getTranslatedString}</option>
									{else}
										<option value="1">{"LBL_YES"|getTranslatedString}</option>
										<option value="0" selected>{"LBL_NO"|getTranslatedString}</option>
									{/if}
								</select>
							{else}
								{* crmv@157490 *}
								{if $VALUE.2 eq 'birthday'}
									<input name="{$VALUE.2}" type="date" class="form-control" value="{$VALUE.1}">
								{elseif $VALUE.2 eq 'mobile'}
									<input name="{$VALUE.2}" type="number" class="form-control" value="{$VALUE.1}">
								{elseif $VALUE.2 eq 'email'}
									{$VALUE.1}
								{else}
									<input name="{$VALUE.2}" type="text" class="form-control" value="{$VALUE.1}">
								{/if}
								{* crmv@157490e *}
							{/if}
							</div>
						</div>
					{/foreach}
				{/foreach}
			</div>
		</form>
	</div>
</div>