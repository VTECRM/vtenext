{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{*<!-- crmv@18592 -->*}
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" class="level3Bg" id="Buttons_List_4" style="{if isMobile() neq true}position:fixed;{/if}z-index:19;">  {* crmv@30356 *}
<tr>
	<td width="100%" style="padding:5px">
		{* vtlib customization: use translated label if available *}
		{assign var="SINGLE_MOD_LABEL" value=$SINGLE_MOD}
		{if $APP.$SINGLE_MOD} {assign var="SINGLE_MOD_LABEL" value=$APP.SINGLE_MOD} {/if}
				
		{if $OP_MODE eq 'edit_view'} 
			{assign var="USE_ID_VALUE" value=$MOD_SEQ_ID}
	  		{if $USE_ID_VALUE eq ''} {assign var="USE_ID_VALUE" value=$ID} {/if}			
			<span class="lvtHeaderText">
				<span class="recordTitle1"">{$APP.LBL_EDITING} {$SINGLE_MOD|@getTranslatedString:$MODULE}</span>
				{if $SHOW_RECORD_NUMBER eq true}
					[ {$USE_ID_VALUE} ]&nbsp;
				{/if}
				{$NAME}&nbsp;<span style="font-weight:normal;">{$UPDATEINFO}</span>
			</span>
		{elseif $OP_MODE eq 'create_view'} {* crmv@62447 *}
			{if $DUPLICATE neq 'true'}
				{assign var=create_new value="LBL_CREATING_NEW_"|cat:$SINGLE_MOD}
				{* vtlib customization: use translation only if present *}
				{assign var="create_newlabel" value=$APP.$create_new}
				{if $create_newlabel neq ''}
					<span class="recordTitle1">{$create_newlabel}</span> <br>
				{else}
					<span class="recordTitle1">{$APP.LBL_CREATING} {$SINGLE_MOD|@getTranslatedString:$MODULE}</span>
				{/if}
			{else}
				<span class="lvtHeaderText">{$APP.LBL_DUPLICATING} "{$NAME}" </span> <br>
			{/if}
		{* crmv@62447 *}
        {elseif $OP_MODE eq 'calendar_buttons'} 
			&nbsp;<span class="small">{$APP.LBL_SELECT_TIME_AND_USERS}</span> <br>
		{* crmv@68357 *}
		{elseif $OP_MODE eq 'calendar_preview_buttons'}
			{* no labels here *}
		{/if}
		{* crmv@68357e *}
        {* crmv@62447e *}
	</td>
	<td style="padding:5px" nowrap>
		{if $INVENTORY_VIEW eq 'true'}
			{if $OP_MODE eq 'edit_view'}
				<!-- vtc -->
			   {if $MODULE eq 'Potentials'}
					<button title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton small save" onclick="this.form.action.value='Save';  displaydeleted();calcTotaleline();return validateLine('{$MODULE}')" type="submit" name="button">{$APP.LBL_SAVE_BUTTON_LABEL}"</button>
					<button title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton small cancel" onclick="window.history.back()" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}"</button>
			   {else}	
					<button title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton small save" onclick="this.form.action.value='Save'; displaydeleted(); return validateInventory('{$MODULE}')" type="submit" name="button">{$APP.LBL_SAVE_BUTTON_LABEL}"</button>
					<button title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton small cancel" onclick="window.history.back()" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}"</button>
				{/if}
				<!-- vtc e -->
			{elseif $OP_MODE eq 'create_view'}
				<!-- vtc -->
			   {if $MODULE eq 'Potentials'}
					<button title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton small save" onclick="this.form.action.value='Save';  calcTotaleline();return validateLine('{$MODULE}')" type="submit" name="button">{$APP.LBL_SAVE_BUTTON_LABEL}"</button>
					<button title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton small cancel" onclick="window.history.back()" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}"</button>
					<input type="hidden" name="convert_from" value="{$CONVERT_MODE}">
					<input type="hidden" name="duplicate_from" value="{$DUPLICATE_FROM}">
				{else}
					<button title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton small save" onclick="this.form.action.value='Save'; return validateInventory('{$MODULE}')" type="submit" name="button">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
					<button title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton small cancel" onclick="window.history.back()" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}"</button>
					<input type="hidden" name="convert_from" value="{$CONVERT_MODE}">
					<input type="hidden" name="duplicate_from" value="{$DUPLICATE_FROM}">
				{/if}
				<!-- vtc e -->
			{/if}
		{else}
			{if $OP_MODE eq 'edit_view'}
				<button title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmButton small save" onclick="this.form.action.value='Save';displaydeleted(); SubmitForm(this.form,{$ID},'{$MODULE}');" type="button" name="button">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
				<button title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton small cancel" onclick="window.history.back()" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
			{elseif $OP_MODE eq 'create_view'}
				{if $MODULE eq 'Emails'}
					<button title="{$APP.LBL_SELECTEMAILTEMPLATE_BUTTON_TITLE}" accessKey="{$APP.LBL_SELECTEMAILTEMPLATE_BUTTON_KEY}" class="crmbutton small create" onclick="window.open('index.php?module=Users&action=lookupemailtemplates&entityid={$ENTITY_ID}&entity={$ENTITY_TYPE}','emailtemplate','top=100,left=200,height=400,width=300,menubar=no,addressbar=no,status=yes')" type="button" name="button">{$APP.LBL_SELECTEMAILTEMPLATE_BUTTON_LABEL}</button>
					<button title="{$MOD.LBL_SEND}" accessKey="{$MOD.LBL_SEND}" class="crmbutton small save" onclick="this.form.action.value='Save';this.form.send_mail.value='true'; return formValidate()" type="submit" name="button">{$MOD.LBL_SEND}</button>
				{/if}
				<button title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton small save" onclick="this.form.action.value='Save';  SubmitForm(this.form,'','{$MODULE}');" type="button" name="button">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
				<button title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton small cancel" onclick="window.history.back()" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
			{* crmv@62447 *}
            {elseif $OP_MODE eq 'calendar_buttons'}
				{if $smarty.request.fast_save eq true && $smarty.request.from_module eq 'Messages'}
					<button title="{$APP.LBL_DONE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmButton edit small disabled" onclick="parent.LPOP.openEventCreateSave('{$smarty.request.from_module}', '{$smarty.request.from_crmid}', '{$smarty.request.activity_mode}','fast');"" type="button" name="button" id="done_button" disabled>{$APP.LBL_DONE_BUTTON_TITLE}</button>
				{/if}
				<button title="{$APP.LBL_FORWARD}" class="crmbutton nextButtonDisSave small disabled" onclick="parent.LPOP.openEventCreateSave('{$smarty.request.from_module}', '{$smarty.request.from_crmid}', '{$smarty.request.activity_mode}','');" type="button" name="button" id="more_button" disabled>{$APP.LBL_FORWARD}</button>
				<button title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton small cancel" onclick="closePopup();" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
            {* crmv@62447e *}
			{* crmv@93990 crmv@141827 *}
            {elseif $OP_MODE eq 'dynaform_popup'}
				<button title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmButton small save" onclick="document.forms['EditView'].action.value='Save';displaydeleted(); SubmitForm(document.forms['EditView'],{$smarty.request.record},'{$smarty.request.module}',true,function(){ldelim}DynaFormScript.reloadDetailView();{rdelim});" type="button" name="button" id="dynaform_button_save">{$APP.LBL_SAVE_BUTTON_LABEL}</button> {* crmv@200816 *}
				<button title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton small cancel" onclick="closePopup()" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
            {/if}
            {* crmv@93990e crmv@141827e *}
		{/if}
	</td>
</tr>
</table>
<div id="vte_menu_white_1"></div>
<script>
jQuery('#vte_menu_white_1').height(jQuery('#Buttons_List_4').height());
recalcFixedMenu();
</script>
{*<!-- crmv@18592e -->*}