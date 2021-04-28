{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* TODO: Transform to BS Navbar *}
{* crmv@140887 *}

<ul id="Buttons_List_4" class="vteUlTable buttonsList buttonsListFixed" data-minified="{$MENU_TOGGLE_STATE}">

	<li>
		{include file="Buttons_List_Contestual.tpl"}
	</li>
	
	<li>
		{* vtlib customization: use translated label if available *}
		{assign var="SINGLE_MOD_LABEL" value=$SINGLE_MOD}
		{if $APP.$SINGLE_MOD} {assign var="SINGLE_MOD_LABEL" value=$APP.SINGLE_MOD} {/if}
				
		{if $OP_MODE eq 'edit_view'} 
			{assign var="USE_ID_VALUE" value=$MOD_SEQ_ID}
			{if $USE_ID_VALUE eq ''} {assign var="USE_ID_VALUE" value=$ID} {/if}			
			{* crmv@199229 *}
			<span class="dvHeaderText {if $LAYOUT_CONFIG['record_title_inline'] eq 1}dvHeaderTextInline{else}dvHeaderTextMultiLine{/if}" data-record-inline="{$LAYOUT_CONFIG['record_title_inline']}">
				<div class="recordTitleName">
					<span class="recordTitle1">{$APP.LBL_EDITING} {$SINGLE_MOD|@getTranslatedString:$MODULE}</span>
					{if $SHOW_RECORD_NUMBER eq true}
						[ {$USE_ID_VALUE} ]&nbsp;
					{/if}
					<span class="recordName" title="{$NAME}">{$NAME}&nbsp;</span>
				</div>	
				<span class="updateInfo">
					{if $LAYOUT_CONFIG['hide_update_info'] eq 0}
						{$UPDATEINFO}
					{/if}
				</span>
			</span>
			<span class="dvHeaderTextMin">
				<i class="vteicon" title="{$APP.LBL_EDITING} {$SINGLE_MOD|@getTranslatedString:$MODULE} - {$NAME} [{$USE_ID_VALUE}]">chat</i>
			</span>
			{* crmv@199229e *}
			{* crmv@25620 *}
	 		<script type="text/javascript">
				updateBrowserTitle('{$APP.LBL_EDITING} {$SINGLE_MOD|@getTranslatedString:$MODULE} - {$NAME} [{$USE_ID_VALUE}]');
			</script>
			{* crmv@25620e *}
		{elseif $OP_MODE eq 'create_view'}
			{if $DUPLICATE neq 'true'}
				{assign var=create_new value="LBL_CREATING_NEW_"|cat:$SINGLE_MOD}
				{* vtlib customization: use translation only if present *}
				{assign var="create_newlabel" value=$APP.$create_new}
				{* crmv@54375 *}
				{if $create_newlabel eq ''}
					{assign var="create_newlabel_tmp" value=$SINGLE_MOD|@getTranslatedString:$MODULE}
					{assign var="create_newlabel" value=$APP.LBL_CREATING|cat:' '|cat:$create_newlabel_tmp}
				{/if}
				<span class="dvHeaderText {if $LAYOUT_CONFIG['record_title_inline'] eq 1}dvHeaderTextInline{else}dvHeaderTextMultiLine{/if}" data-record-inline="{$LAYOUT_CONFIG['record_title_inline']}">
					<div class="recordTitleName">
						<span class="recordTitle1">
							{if !empty($RETURN_RECORD_NAME)}
								{$RETURN_RECORD_NAME} <span style="font-weight:normal;">></span>
							{/if}
							{$create_newlabel}
						</span>
					</div>
				</span>
				<span class="dvHeaderTextMin">
					<i class="vteicon" title="{$create_newlabel}">chat</i>
				</span>
		 		<script type="text/javascript">
					updateBrowserTitle('{$create_newlabel}');
				</script>
				{* crmv@54375e *}
			{else}
				<span class="dvHeaderText {if $LAYOUT_CONFIG['record_title_inline'] eq 1}dvHeaderTextInline{else}dvHeaderTextMultiLine{/if}" data-record-inline="{$LAYOUT_CONFIG['record_title_inline']}">
					<div class="recordTitleName">
						<span class="recordTitle1">
							{$APP.LBL_DUPLICATING} {$SINGLE_MOD|@getTranslatedString:$MODULE} "{$NAME}"
						</span>
					</div>
				</span>
				<span class="dvHeaderTextMin">
					<i class="vteicon" title="{$APP.LBL_DUPLICATING} {$SINGLE_MOD|@getTranslatedString:$MODULE} {$NAME}">chat</i>
				</span>
				{* crmv@25620 *}
		 		<script type="text/javascript">
					updateBrowserTitle('{$APP.LBL_DUPLICATING} {$SINGLE_MOD|@getTranslatedString:$MODULE} "{$NAME}"');
				</script>
				{* crmv@25620e *}
			{/if}
		{* crmv@22223 *}
		{elseif $EXIST eq "true" && $EXIST neq ''}
			<span class="recordTitle1">{$MOD.Edit_Custom_View}</span>
			{* crmv@25620 *}
	 		<script type="text/javascript">
				updateBrowserTitle('{$MOD.Edit_Custom_View}');
			</script>
			{* crmv@25620e *}
		{else}
		 	<span class="recordTitle1">{$MOD.New_Custom_View}</span>
		 	{* crmv@25620 *}
	 		<script type="text/javascript">
				updateBrowserTitle('{$MOD.New_Custom_View}');
			</script>
			{* crmv@25620e *}
		{* crmv@22223e *}
		{/if}
	</li>
	
	<li class="pull-right">
		<ul class="vteUlTable dvHeaderRight">
			<li>
				{* crmv@45561 *}
				{if !empty($ERROR_STR)}
					<span class="errorString" style="padding-right:5px">{$ERROR_STR}</span>
				{/if}
				{* crmv@45561e *}
				{if $smarty.request.close_window eq 'yes'}
	 			{assign var="CANCEL_LINK" value="window.close()"}
		 		{else}
		 			{assign var="CANCEL_LINK" value="window.history.back()"}
		 		{/if}
				{if $MODULE eq 'CustomView' or $smarty.request.action eq 'CustomView'}
					<button title="{$APP.LBL_SAVE_BUTTON_LABEL}" accesskey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton save success" name="button2" type="submit" onClick="return customViewSubmit();">{$APP.LBL_SAVE_BUTTON_LABEL}</button>	{* crmv@29615 *}	{* crmv@31775 *}
					<button title="{$APP.LBL_CANCEL_BUTTON_LABEL}" accesskey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton cancel" name="button2" onclick='{$CANCEL_LINK}' type="button">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
				{elseif $INVENTORY_VIEW eq 'true'}
					{* crmv@58638 *}
					{if $MODULE|isInventoryModule}
						{assign var="DISABLE_SAVE" value="disabled='disabled'"}
					{else}
						{assign var="DISABLE_SAVE" value=""}
					{/if}
					{if $OP_MODE eq 'edit_view'}
						{assign var="SAVE_ACTION" value="this.form.action.value='Save'; displaydeleted(); return validateInventory('$MODULE');"}
						<input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton save success inventory_submit" onclick="this.form.run_processes.value=''; {$SAVE_ACTION}" type="submit" name="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" {$DISABLE_SAVE}>
						{if $SHOW_RUN_PROCESSES_BUTTON}<button title="{'LBL_SAVE_AND_RUN_PROCESSES_BUTTON_TITLE'|getTranslatedString:'Processes'}" class="crmbutton save success inventory_submit" onclick="this.form.run_processes.value='yes'; {$SAVE_ACTION}" type="button" name="button">{'LBL_SAVE_AND_RUN_PROCESSES_BUTTON_LABEL'|getTranslatedString:'Processes'} <i class="button-image icon-module icon-processes valign-bottom" data-first-letter="P"></i></button>{/if}	{* crmv@100495 crmv@102879 *}
					{elseif $OP_MODE eq 'create_view'}
						{assign var="SAVE_ACTION" value="this.form.action.value='Save'; return validateInventory('$MODULE')"}
						{* crmv@54375 *}
						{if !empty($RETURN_ID) && !empty($RETURN_MODULE)}
							<input class="crmbutton save success backButtonSave inventory_submit" onclick="{$SAVE_ACTION}" type="submit" name="button" value="{$APP.LBL_SAVE_AND_BACK_BUTTON_LABEL}" {$DISABLE_SAVE}>
							<input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton save success inventory_submit" onclick="this.form.return2detail.value='yes'; this.form.run_processes.value=''; {$SAVE_ACTION}" type="submit" name="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" {$DISABLE_SAVE}>
							{if $SHOW_RUN_PROCESSES_BUTTON}<button title="{'LBL_SAVE_AND_RUN_PROCESSES_BUTTON_TITLE'|getTranslatedString:'Processes'}" class="crmbutton save success inventory_submit" onclick="this.form.run_processes.value='yes'; {$SAVE_ACTION}" type="button" name="button">{'LBL_SAVE_AND_RUN_PROCESSES_BUTTON_LABEL'|getTranslatedString:'Processes'} <i class="button-image icon-module icon-processes valign-bottom" data-first-letter="P"></i></button>{/if}	{* crmv@100495 *}
						{else}
						{* crmv@54375e *}
							<input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton save success inventory_submit" onclick="this.form.run_processes.value=''; {$SAVE_ACTION}" type="submit" name="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" {$DISABLE_SAVE}>
							{if $SHOW_RUN_PROCESSES_BUTTON}<button title="{'LBL_SAVE_AND_RUN_PROCESSES_BUTTON_TITLE'|getTranslatedString:'Processes'}" class="crmbutton save success inventory_submit" onclick="this.form.run_processes.value='yes'; {$SAVE_ACTION}" type="button" name="button">{'LBL_SAVE_AND_RUN_PROCESSES_BUTTON_LABEL'|getTranslatedString:'Processes'} <i class="button-image icon-module icon-processes valign-bottom" data-first-letter="P"></i></button>{/if}	{* crmv@100495 *}
						{/if}
						<input type="hidden" name="convert_from" value="{$CONVERT_MODE}">
						<input type="hidden" name="duplicate_from" value="{$DUPLICATE_FROM}">
					{/if}
					<button title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton cancel" onclick="{$CANCEL_LINK}" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
					{* crmv@58638e *}
				{else}
					{* crmv@27061 *} {* crmv@105416 *}
			 		{if $smarty.request.module eq 'Calendar'}
			 			{if $ACTIVITY_MODE neq 'Task'}
			 				{* crmv@54375 crmv@95751 *}
							{if $OP_MODE eq 'create_view' && !empty($RETURN_ID) && !empty($RETURN_MODULE)}
								<input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accesskey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton save success backButtonSave" name="button" value="{$APP.LBL_SAVE_AND_BACK_BUTTON_LABEL}" onclick="this.form.action.value='Save'; jQuery(this.form).submit();" type="button" />
								<input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accesskey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton save success" name="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" onclick="this.form.return2detail.value='yes'; this.form.action.value='Save'; VteJS_DialogBox.block(); jQuery(this.form).submit();" type="button" />
							{else}
			 					<input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accesskey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton save success" name="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" onclick="this.form.action.value='Save'; VteJS_DialogBox.block(); jQuery(this.form).submit();" type="button" />
			 				{/if}
			 			{else}
							{if $OP_MODE eq 'create_view' && !empty($RETURN_ID) && !empty($RETURN_MODULE)}
								<input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accesskey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton save success backButtonSave" name="button" value="{$APP.LBL_SAVE_AND_BACK_BUTTON_LABEL}" onclick="this.form.action.value='Save'; VteJS_DialogBox.block(); jQuery(this.form).submit();" type="button" />
								<input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accesskey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton save success" name="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" onclick="this.form.return2detail.value='yes'; this.form.action.value='Save'; VteJS_DialogBox.block(); jQuery(this.form).submit();" type="button" />
							{else}
			 					<input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accesskey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton save success" name="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" onclick="this.form.action.value='Save'; VteJS_DialogBox.block(); jQuery(this.form).submit();" type="button" />
			 				{/if}
			 				{* crmv@54375e crmv@95751e *}
			 			{/if}
						<input title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accesskey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton cancel" name="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="{$CANCEL_LINK}" type="button" />
			 		{* crmv@27061e *} {* crmv@105416e *}
					{* crmv@20054 *}
			 		{elseif $smarty.request.module eq 'Users'}
			 			<input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accesskey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton save" name="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" onclick="EditView.action.value='Save'; return verify_data(EditView)" type="button" />
						<input title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accesskey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton cancel" name="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="{$CANCEL_LINK}" type="button" />
			 		{* crmv@20054e *}
			 		{* crmv@29386 *}
					{elseif $MODULE eq 'Webforms'}
						<input title="{'LBL_SAVE_BUTTON_TITLE'|@getTranslatedString:$MODULE}" accesskey="{'LBL_SAVE_BUTTON_KEY'|@getTranslatedString:$MODULE}" class="crmbutton save success" onclick="javascript:return Webforms.validateForm('webform_edit','index.php?module=Webforms&action=Save')" name="button" value="{'LBL_SAVE_BUTTON_LABEL'|@getTranslatedString:$MODULE} " type="submit">
						<input title="{'LBL_CANCEL_BUTTON_TITLE'|@getTranslatedString:$MODULE}" accesskey="{'LBL_CANCEL_BUTTON_KEY'|@getTranslatedString:$MODULE}" class="crmbutton cancel" onclick="{$CANCEL_LINK}" name="button" value="{'LBL_CANCEL_BUTTON_LABEL'|@getTranslatedString:$MODULE}" type="button">
					{* crmv@29386e *}
					{elseif $OP_MODE eq 'edit_view'}
						{assign var="SAVE_ACTION" value="this.form.action.value='Save'; displaydeleted(); SubmitForm(this.form,$ID,'$MODULE');"}
						<button title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton save success" onclick="this.form.run_processes.value=''; {$SAVE_ACTION}" type="button" name="button">{$APP.LBL_SAVE_BUTTON_LABEL}</button><!-- ds@19-->
						{if $SHOW_RUN_PROCESSES_BUTTON}<button title="{'LBL_SAVE_AND_RUN_PROCESSES_BUTTON_TITLE'|getTranslatedString:'Processes'}" class="crmbutton save success" onclick="this.form.run_processes.value='yes'; {$SAVE_ACTION}" type="button" name="button">{'LBL_SAVE_AND_RUN_PROCESSES_BUTTON_LABEL'|getTranslatedString:'Processes'} <i class="button-image icon-module icon-processes valign-bottom" data-first-letter="P"></i></button>{/if}	{* crmv@100495 crmv@102879 *}
						<button title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton cancel" onclick="{$CANCEL_LINK}" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
					{elseif $OP_MODE eq 'create_view'}
						{assign var="SAVE_ACTION" value="this.form.action.value='Save'; SubmitForm(this.form,'','$MODULE');"}
						{* crmv@54375 *}
						{if !empty($RETURN_ID) && !empty($RETURN_MODULE)}
							<button class="crmbutton save success backButtonSave" onclick="{$SAVE_ACTION}" type="button" name="button">{$APP.LBL_SAVE_AND_BACK_BUTTON_LABEL}</button>
							<button title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton save success" onclick="this.form.return2detail.value='yes'; this.form.run_processes.value=''; {$SAVE_ACTION}" type="button" name="button">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
							{if $SHOW_RUN_PROCESSES_BUTTON}<button title="{'LBL_SAVE_AND_RUN_PROCESSES_BUTTON_TITLE'|getTranslatedString:'Processes'}" class="crmbutton save success" onclick="this.form.run_processes.value='yes'; {$SAVE_ACTION}" type="button" name="button">{'LBL_SAVE_AND_RUN_PROCESSES_BUTTON_LABEL'|getTranslatedString:'Processes'} <i class="button-image icon-module icon-processes valign-bottom" data-first-letter="P"></i></button>{/if}	{* crmv@100495 *}
						{else}
						{* crmv@54375e *}
							<button title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton save success" onclick="this.form.run_processes.value=''; {$SAVE_ACTION}" type="button" name="button">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
							{if $SHOW_RUN_PROCESSES_BUTTON}<button title="{'LBL_SAVE_AND_RUN_PROCESSES_BUTTON_TITLE'|getTranslatedString:'Processes'}" class="crmbutton save success" onclick="this.form.run_processes.value='yes'; {$SAVE_ACTION}" type="button" name="button">{'LBL_SAVE_AND_RUN_PROCESSES_BUTTON_LABEL'|getTranslatedString:'Processes'} <i class="button-image icon-module icon-processes valign-bottom" data-first-letter="P"></i></button>{/if}	{* crmv@100495 *}
						{/if}
						{* crmv@53056 *}
						{if $MODULE eq 'Timecards' && $smarty.request.newtcdone eq 'yes'}
							<button title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton cancel" onclick="this.form.module.value='HelpDesk'; this.form.action.value='DetailView'; this.form.record.value={$smarty.request.ticket_id};" type="submit" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
						{else}
						{* crmv@53056e *}
							<button title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton cancel" onclick="{$CANCEL_LINK}" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
						{/if}
					{* crmv@22223 *}
					{else}
						<button title="{$APP.LBL_SAVE_BUTTON_LABEL}" accesskey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton save success" name="button2" type="submit" onClick="return checkDuplicate();">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
						<button title="{$APP.LBL_CANCEL_BUTTON_LABEL}" accesskey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton cancel" name="button2" onclick='{$CANCEL_LINK}' type="button" >{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
					{* crmv@22223e *}
					{/if}
				{/if}
			</li>
		</ul>
	</li>
</ul>

<div id="Buttons_List_4_Placeholder"></div>

{literal}
<script type="text/javascript">
	var navbarHeight = jQuery('#Buttons_List_4').height();
	jQuery('#Buttons_List_4_Placeholder').height(navbarHeight);
	
	if (jQuery('#vte_menu').length > 0) {
		jQuery('#Buttons_List_4').css('top', jQuery('#vte_menu').height());
	}
</script>
{/literal}