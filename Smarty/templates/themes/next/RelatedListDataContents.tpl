{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@104568 *}

{if $smarty.request.load_header eq 'yes'}
	<div relation_id="{$RELATIONID}" class="relatedListDataContainer" id="container_{$MODULE}_{$HEADER|replace:' ':''}" data-relationid="{$RELATIONID}" {if $FIXED}data-isfixed="1"{/if}>
	<table width="100%" cellspacing="0" cellpadding="0" border="0">	{* crmv@26896 *} {* crmv@62415 *}
		<tr>
			<td>
				<div class="dvInnerHeader">
					<div class="dvInnerHeaderLeft">
						{assign var="related_module" value=$RELATED_MODULE}
						{assign var="related_module_lower" value=$related_module|strtolower}
						{assign var="trans_related_module" value=$RELATED_MODULE|@getTranslatedString:$RELATED_MODULE}
						{assign var="first_letter" value=$trans_related_module|substr:0:1|strtoupper}
					
						<div class="dvInnerHeaderTitle">
							<div class="vcenter" style="margin-right:5px">
								<i class="icon-module icon-{$related_module_lower}" data-first-letter="{$first_letter}"></i>				
							</div>
							
							{* crmv@64792 *}
							{if empty($RELATED_MODULE)}
								<div class="vcenter">{$HEADER|@getTranslatedString:$RELATED_MODULE}</div>
							{else}
								<div class="vcenter">{$RELATED_MODULE|@getTranslatedString:$RELATED_MODULE}</div>
							{/if}
							{* crmv@64792e *}
							
							<span class="vcenter" id="cnt_{$MODULE}_{$HEADER|replace:' ':''}"></span> {* crmv@25809 *}
							- <span class="vcenter" id="dtl_{$MODULE}_{$HEADER|replace:' ':''}" style="font-weight:normal">{'LBL_LIST'|@getTranslatedString}</span>	{* crmv@3086m *}
							&nbsp;{include file="LoadingIndicator.tpl" LIID="indicator_"|cat:$MODULE|cat:"_"|cat:$HEADER|replace:' ':'' LIEXTRASTYLE="display:none;"}
						</div>
					</div>
					<div class="dvInnerHeaderRight">
						{* crmv@167019 *}
						{if $HEADER eq 'Documents'}
							<span class="drop-area-support hidden">
								<i class="vteicon valign-middle">insert_link</i>
								{'LBL_SELECT_OR_DROP_FILES'|getTranslatedString}
							</span>
						{/if}
						{* crmv@167019e *}
						{if !$FIXED}
						<i class="vteicon2 fa-thumb-tack md-link valign-middle" id="pin_{$MODULE}_{$HEADER|replace:' ':''}" style="display:none;" onClick="pinRelated('{$MODULE}_{$HEADER|replace:' ':''}','{$MODULE}','{$RELATED_MODULE}');"></i>
						<i class="vteicon2 fa-thumb-tack md-link valign-middle" id="unPin_{$MODULE}_{$HEADER|replace:' ':''}" style="{if $PIN eq true}display:block;{else}display:none;{/if}opacity:0.5" onClick="unPinRelated('{$MODULE}_{$HEADER|replace:' ':''}','{$MODULE}','{$RELATED_MODULE}');"></i>
						<i class="vteicon md-link valign-middle" id="hideDynamic_{$MODULE}_{$HEADER|replace:' ':''}" style="display:none" onClick="hideDynamicRelatedList(jQuery('#tl_{$MODULE}_{$HEADER|replace:' ':''}'));">clear</i>
						{/if}
					</div>
				</div>
			</td>
		</tr>
		<tr>
			<td>
				<div relation_id="{$RELATIONID}" id="tbl_{$MODULE}_{$HEADER|replace:' ':''}"> {* crmv@62415 *}
{/if}

<table border=0 cellspacing=0 cellpadding=0 width=100%>
	<tr>
		<td width="40%" align="left">
			{$RELATEDLISTDATA.navigation.0}
			{* crmv@22700 *}
			{* crmv@181170 *}
			{if isModuleInstalled('Newsletter')}
				{assign var="CUSTOM_MODULE" value="Targets"}
			{else}
				{assign var="CUSTOM_MODULE" value="Campaigns"}
			{/if}
			{* crmv@181170e *}
			{if $MODULE eq $CUSTOM_MODULE && ($RELATED_MODULE eq 'Contacts' || $RELATED_MODULE eq 
				'Leads' || $RELATED_MODULE eq 'Accounts') && $RELATEDLISTDATA.entries|@count > 0}
				<br>{$APP.LBL_SELECT_BUTTON_LABEL}: <a href="javascript:void(0);"
					onclick="clear_checked_all('{$RELATED_MODULE}');">{$APP.LBL_NONE_NO_LINE}</a>
			{/if}
		</td>
		<td width="20%" align="center" nowrap>{$RELATEDLISTDATA.navigation.1} </td>
		<td width="40%" align="right" nowrap>
			{$RELATEDLISTDATA.CUSTOM_BUTTON}
			{* crmv@22700 *}
			{if $HEADER eq 'Contacts' && $MODULE neq $CUSTOM_MODULE && $MODULE neq 'Accounts' && $MODULE neq 'Potentials' && $MODULE neq 'Products' && $MODULE neq 'Vendors' && $MODULE neq 'Fairs'}	{* crmv@2285m *}
				{if $MODULE eq 'Calendar'}
					<input alt="{$APP.LBL_SELECT_CONTACT_BUTTON_LABEL}" title="{$APP.LBL_SELECT_CONTACT_BUTTON_LABEL}" accessKey="" class="crmbutton small edit" value="{$APP.LBL_SELECT_BUTTON_LABEL} {$APP.Contacts}" LANGUAGE=javascript onclick='openPopup("index.php?module=Contacts&return_module={$MODULE}&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid={$ID}{$search_string}","test","width=640,height=602,resizable=0,scrollbars=0");' type="button"  name="button"></td>{*crmv@21048m*}
				{/if}
			{elseif $HEADER eq 'Users' && $MODULE eq 'Calendar'}
				<input title="Change" accessKey="" tabindex="2" type="button" class="crmbutton small edit" value="{$APP.LBL_SELECT_USER_BUTTON_LABEL}" name="button" LANGUAGE=javascript onclick='openPopup("index.php?module=Users&return_module=Calendar&return_action={$return_modname}&activity_mode=Events&action=Popup&popuptype=detailview&form=EditView&form_submit=true&select=enable&return_id={$ID}&recordid={$ID}","test","width=640,height=525,resizable=0,scrollbars=0")';>{* crmv@21048m *}
            {/if}
		</td>
	</tr>
</table>

<table class="vtetable">
	{if is_array($RELATEDLISTDATA.entries) && $RELATEDLISTDATA.entries|@count > 0} {* crmv@167234 *}
		<thead>
			<tr>
				{* crmv@22700 *}
		        {if $MODULE eq $CUSTOM_MODULE && ($RELATED_MODULE eq 'Contacts' || $RELATED_MODULE eq 'Leads' || $RELATED_MODULE eq 'Accounts')
					&& $RELATEDLISTDATA.entries|@count > 0}
					<th>
						<input name ="{$RELATED_MODULE}_selectall" onclick="rel_toggleSelect(this.checked,'{$RELATED_MODULE}_selected_id','{$RELATED_MODULE}');"  type="checkbox">
					</th>
		        {/if}
				{foreach key=index item=_HEADER_FIELD from=$RELATEDLISTDATA.header}
					<th>{$_HEADER_FIELD}</th>
				{/foreach}
			</tr>
		</thead>
	{/if}
	<tbody>
		{foreach key=_RECORD_ID item=_RECORD from=$RELATEDLISTDATA.entries}
			{* crmv@80758 *}
			{if isset($_RECORD.clv_color)}
				{assign var=color value=$_RECORD.clv_color}
			{else}
				{assign var=color value=""}
			{/if}
			{if isset($_RECORD.clv_foreground)}
				{assign var=foreground value=$_RECORD.clv_foreground}
			{else}
				{assign var=foreground value=""}
			{/if}
			
			{assign var=cell_class value="listview-cell listview-cell-related"}
							
			{if !empty($foreground)}
				{assign var=cell_class value=$cell_class|cat:" color-`$foreground`"}
			{/if}
			{* crmv@80758e *}
			<!-- crmv@17408 -->
			{assign var=header_rep value=$HEADER|replace:' ':''}
			{if $header_rep eq 'TicketHistory'}
				{assign var=color value=""}
			{/if}
			<!-- crmv@17408e -->
			<tr>
				{* crmv@22700 *}
	        	{if $MODULE eq $CUSTOM_MODULE && ($RELATED_MODULE eq 'Contacts' || $RELATED_MODULE eq 'Leads' || $RELATED_MODULE eq 'Accounts')}
					<td><input name="{$RELATED_MODULE}_selected_id" id="{$_RECORD_ID}" value="{$_RECORD_ID}" onclick="rel_check_object(this,'{$RELATED_MODULE}');" type="checkbox" {$RELATEDLISTDATA.checked.$_RECORD_ID}></td>	{*<!-- crmv@19139 -->*}
	        	{/if}
				{foreach key=index item=_RECORD_DATA from=$_RECORD}
					 {* vtlib customization: Trigger events on listview cell *}
					 {if ($index neq 'clv_color' and $index neq 'clv_foreground') or $index eq '0'}
	                 	<td bgcolor="{$color}" class="{$cell_class}">{$_RECORD_DATA}</td>
	                 {/if}
	                 {* END *}
				{/foreach}
			</tr>
		{foreachelse}
			<tr><td><i>{$APP.LBL_NONE_INCLUDED}</i></td></tr>
		{/foreach}
	</tbody>
</table>

{if $smarty.request.load_header eq 'yes'}
				</div>
			</td>
		</tr>
	</table></div>
{/if}

{* crmv@26896 crmv@100492 *}
{if $PERFORMANCE_CONFIG.RELATED_LIST_COUNT eq true && $RELATEDLISTDATA.count != ''}
	<script type='text/javascript'>
	var target = "cnt_{$MODULE}_{$HEADER|replace:' ':''}";
	var count = {$RELATEDLISTDATA.count};
	jQuery('#'+target+'_tl').html("("+count+")");
	jQuery('#'+target).html("("+count+")");
	</script>
{/if}
{* crmv@26896e crmv@100492e *}

{if $MODULE eq $CUSTOM_MODULE && ($RELATED_MODULE eq 'Contacts' || $RELATED_MODULE eq 'Leads' || $RELATED_MODULE eq 'Accounts') && $RELATEDLISTDATA.entries|@count > 0 && $RESET_COOKIE eq 'true'}
	<script type='text/javascript'>set_cookie('{$RELATED_MODULE}_all', '');</script>
{/if}