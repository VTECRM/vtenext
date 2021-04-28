{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@99316 crmv@106857 crmv@198388 *}

{* crmv@93990 *}
{if $smarty.request.ajxaction eq 'DYNAFORMPOPUP'}
	{include file='CachedValues.tpl'}	{* crmv@26316 *}
	{include file='modules/SDK/src/Reference/Autocomplete.tpl'}	{* crmv@29190 *}
	<script type="text/javascript">
		jQuery('#vte_menu .mailClientWriteEmailHeader h5').css('margin-left','');
		jQuery('#vte_menu .mailClientWriteEmailHeader h5').css('padding-top','2px');
		jQuery('#vte_menu .mailClientWriteEmailHeader h5').html('&gt; <a href="index.php?module=Processes&action=DetailView&record={$ID}" target="_blank">{$PROCESS_NAME}</a>');
		{* crmv@141827 *}
		{if $DYNA_BLOCKS_EMPTY}
			jQuery('#dynaform_button_save').text('{$APP.LBL_DONE_BUTTON_TITLE}');
		{/if}
		{* crmv@141827e *}
	</script>
	<div style="{if $THEME eq 'next'}padding:25px 25px 0px 25px{else}padding:10px{/if}">
		<div class="vte-card">
			<div class="dvInnerHeader">
				<table border="0" cellspacing="0" cellpadding="{if $OLD_STYLE eq true}2{else}5{/if}" width=100% class="small editBlockHeader">
				<tr>
					<td>
						<div class="dvInnerHeaderTitle">{'Requested action'|getTranslatedString:'Processes'}</div>
					</td>
				</tr>
				</table>
			</div>
			<div class="editBlockContent" style="padding-bottom:10px">
				{$REQUESTED_ACTION}
			</div>
		</div>
	</div>
{/if}
{* crmv@93990e *}

{include file=$TEMPLATE}

{if $ENABLE_DFCONDITIONALS eq true}
	<input type="hidden" id="enable_dfconditionals" value="1">
	<div id="df_fields" style="display:none">{$DFFIELDS}</div>
	<script type="text/javascript">
		DynaFormScript.initEditViewConditionals('{$ID}','{$DFFIELDS|addslashes}',true,function(){ldelim}
			{if !empty($FOCUS_ON_FIELD)}
				jQuery('[name="{$FOCUS_ON_FIELD}"]').focus();
			{/if}
		{rdelim});
	</script>
{/if}