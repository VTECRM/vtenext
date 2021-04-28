{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<table id="home_page_components" class="tableHeading" width="100%">
	<tr>
		<td class="big">
			<strong>7. {$UMOD.LBL_HOME_PAGE_COMP}</strong>	{* crmv@164190 *}
		</td>
		<td class="small" align="right">&nbsp;</td>
	</tr>
</table>

<table class="table">
	<tr>
		{foreach item=homeitems key=values from=$HOMEORDER name="homeitems"} {assign var=homeidx value=$smarty.foreach.homeitems.iteration}
		<td align="right" width="25%" height="30">{if $UMOD.$values eq ''}{$values|@getTranslatedString:'Home'}{else}{$UMOD.$values|@getTranslatedString:'Home'}{/if}</td>
		{* crmv@3079m *} {if $homeitems neq ''} {assign var="homeitems_true_check" value="checked"} {assign var="homeitems_false_check" value=""} {else} {assign var="homeitems_true_check" value=""} {assign var="homeitems_false_check" value="checked"} {/if}
		<td align="center" width="15%">
			<div class="togglebutton">
				<label>
					<input id="{$values}_homeitems" name="{$values}" value="{$values}" type="checkbox"{$homeitems_true_check}>
				</label>
			</div>
		</td>
		{if $homeidx % 2 == 0}
	</tr>
	<tr>{/if} {/foreach}
	</tr>
</table>