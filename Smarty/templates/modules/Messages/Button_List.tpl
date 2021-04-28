{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<div id="Buttons_List_3_Accounts" style="display:none">
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			<td align="left" width="25%"></td>
			<td class="listMessageTitle" align="center" width="50%"><b>{'Accounts'|getTranslatedString}</b></td>
			<td align="right" width="25%"></td>
		</tr>
	</table>
</div>
<div id="Buttons_List_3_Folders" style="display:none">
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			<td align="left" width="25%">
				{if $MULTIACCOUNT eq true}
					<input class="crmbutton small edit" type="button" value="< {'Accounts'|getTranslatedString}" onclick="changeLeftView('accounts','{$DIV_DIMENSION.Folders}','{$DIV_DIMENSION.ListViewContents}')"/>
				{/if}
			</td>
			<td class="listMessageTitle" align="center" width="50%"><b>{$MOD.Folders}</b></td>
			<td align="right">
				<input class="crmbutton small edit" type="button" value="{$APP.LBL_CHANGE_BUTTON_LABEL}" onclick="editViewFolders(true);"/>
			</td>
		</tr>
	</table>
</div>
<div id="Buttons_List_3_Folders_Edit" style="display:none">
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			<td align="left" width="25%">
				<input class="crmbutton small cancel" type="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="editViewFolders(false);"/>
			</td>
			<td class="listMessageTitle" align="center" width="50%"><b>{$MOD.Folders}</b></td>
			<td align="right">
				<a href="javascript:;" onClick="folderAction(this,'unseen');"><i class="vteicon" title="{'LBL_SEEN_ACTION'|@getTranslatedString:$MODULE}">markunread</i></a>
                <a href="javascript:;" onClick="folderAction(this,'seen');"><i class="vteicon" title="{'LBL_UNSEEN_ACTION'|@getTranslatedString:$MODULE}">drafts</i></a>
                <a href="javascript:;" onClick="folderAction(this,'move');"><i class="vteicon" title="{'LBL_MOVE_ACTION'|@getTranslatedString:$MODULE}" border="0">move_to_inbox</i></a>
                <a href="javascript:;" onClick="folderAction(this,'create');"><i class="vteicon" title="{'LBL_CREATE'|@getTranslatedString}" border="0">add</i></a>
			</td>
		</tr>
	</table>
</div>
<div id="Buttons_List_3_ListView" style="display:none">
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			<td align="left" width="25%">
                <input id="go2accounts" style="display:none;" class="crmbutton small edit" type="button" value="< {'Accounts'|getTranslatedString}" onclick="changeLeftView('accounts','{$DIV_DIMENSION.Folders}','{$DIV_DIMENSION.ListViewContents}')"/>
                <input id="go2folders" style="display:none;" class="crmbutton small edit" type="button" value="< {$MOD.Folders}" onclick="changeLeftView('folders','{$DIV_DIMENSION.Folders}','{$DIV_DIMENSION.ListViewContents}')"/>
			</td>
			<td align="center" style="font-weight:bold;" width="50%">
				<div class="listMessageTitle" title="{$CURRENT_FOLDER_LABEL}">{$CURRENT_FOLDER_LABEL}</div><span id="rec_string" style="display:none">{$RECORD_COUNTS}</span><span id="rec_string3"></span>
			</td>
			<td align="right" width="25%">
				<input id="editfolder" style="display:none;" class="crmbutton small edit" type="button" value="{$APP.LBL_CHANGE_BUTTON_LABEL}" onclick="editViewList(true);" />
			</td>
		</tr>
	</table>
</div>
<div id="Buttons_List_3_ListView_Edit" style="display:none">
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			<td align="left" width="25%">
               	<input class="crmbutton small cancel" type="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="editViewList(false);"/>
			</td>
			<td align="center" style="font-weight:bold;" width="50%">
				<div class="listMessageTitle"></div>
			</td>
			<td align="right" width="25%">
				{* crmv@48159 *}
				{if $CURRENT_FOLDER eq $SPECIAL_FOLDERS.INBOX or $CURRENT_FOLDER eq $SPECIAL_FOLDERS.Sent}
					{assign var="display" value="display:none;"}
				{else}
					{assign var="display" value=""}
				{/if}
				<a id="empty_button" href="javascript:;" onClick="emptyFolder();" style="{$display}"><i class="vteicon" title="{'LBL_EMPTY_FOLDER'|@getTranslatedString:$MODULE}" border="0">clear_all</i></a>			                		
				{* crmv@48159e *}
				{* crmv@79192 *}
				<a id="unseen_button" href="javascript:;" onClick="massFlag('Unseen');"><i class="vteicon" title="{'LBL_SEEN_ACTION'|@getTranslatedString:$MODULE}">markunread</i></a>
				<a id="seen_button" href="javascript:;" onClick="massFlag('Seen');"><i class="vteicon" title="{'LBL_UNSEEN_ACTION'|@getTranslatedString:$MODULE}">drafts</i></a>
				{*
				<a href="javascript:;" onClick="massFlag('Flagged');"><i class="vteicon" title="{'LBL_UNFLAGGED_ACTION'|@getTranslatedString:$MODULE}">flag</i></a>
				<a href="javascript:;" onClick="massFlag('Unflagged');"><i class="vteicon" title="{'LBL_FLAGGED_ACTION'|@getTranslatedString:$MODULE}" style="color:red">flag</i></a>
				*}
				<a id="move_button" href="javascript:;" onClick="MoveDisplay(this,'mass');"><i class="vteicon" title="{'LBL_MOVE_ACTION'|@getTranslatedString:$MODULE}" border="0">move_to_inbox</i></a>
				{foreach key=button_check item=button_label from=$BUTTONS}
					{if $button_check eq 'del'}
						<a id="trash_button" href="javascript:;" onClick="massFlag('Delete');"><i class="vteicon" title="{$button_label}" border="0">delete</i></a>
					{/if}
				{/foreach}
				{* crmv@79192e *}
							
				<div style="display:none;">
					{if ($ALL_IDS eq 1)}
						<input style="display:none;" class="crmbutton small edit" id="select_all_button_top" type="button" value="{$APP.LBL_UNSELECT_ALL_IDS}" onClick="selectAllIds();"/>
					{else}
						<input style="display:none;" class="crmbutton small edit" id="select_all_button_top" type="button" value="{$APP.LBL_SELECT_ALL_IDS}" onClick="selectAllIds();"/>
					{/if}
				</div>
							
                {* vtlib customization: Custom link buttons on the List view basic buttons *}
				{if $CUSTOM_LINKS && $CUSTOM_LINKS.LISTVIEWBASIC}
					{foreach item=CUSTOMLINK from=$CUSTOM_LINKS.LISTVIEWBASIC}
						{assign var="customlink_href" value=$CUSTOMLINK->linkurl}
						{assign var="customlink_label" value=$CUSTOMLINK->linklabel}
						{if $customlink_label eq ''}
							{assign var="customlink_label" value=$customlink_href}
						{else}
							{* Pickup the translated label provided by the module *}
							{assign var="customlink_label" value=$customlink_label|@getTranslatedString:$CUSTOMLINK->module()}
						{/if}
						<input class="crmbutton small edit" type="button" value="{$customlink_label}" onclick="{$customlink_href}" />
					{/foreach}
				{/if}
				
				{* vtlib customization: Custom link buttons on the List view *}
				{if $CUSTOM_LINKS && !empty($CUSTOM_LINKS.LISTVIEW)}
					&nbsp;
					<a href="javascript:;" onmouseover="fnvshobj(this,'vtlib_customLinksLay');" onclick="fnvshobj(this,'vtlib_customLinksLay');">
						<b>{$APP.LBL_MORE} {$APP.LBL_ACTIONS} <img src="{'arrow_down.gif'|resourcever}" border="0"></b>
					</a>
					<div class="drop_mnu" style="display: none; left: 193px; top: 106px;width:155px; position:absolute;" id="vtlib_customLinksLay"
						onmouseout="fninvsh('vtlib_customLinksLay')" onmouseover="fnvshNrm('vtlib_customLinksLay')">
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
						<tr><td style="border-bottom: 1px solid rgb(204, 204, 204); padding: 5px;"><b>{$APP.LBL_MORE} {$APP.LBL_ACTIONS} &#187;</b></td></tr>
						<tr>
							<td>
								{foreach item=CUSTOMLINK from=$CUSTOM_LINKS.LISTVIEW}
									{assign var="customlink_href" value=$CUSTOMLINK->linkurl}
									{assign var="customlink_label" value=$CUSTOMLINK->linklabel}
									{if $customlink_label eq ''}
										{assign var="customlink_label" value=$customlink_href}
									{else}
										{* Pickup the translated label provided by the module *}
										{assign var="customlink_label" value=$customlink_label|@getTranslatedString:$CUSTOMLINK->module()}
									{/if}
									<a href="{$customlink_href}" class="drop_down">{$customlink_label}</a>
								{/foreach}
							</td>
						</tr>
						</table>
					</div>
				{/if}
			</td>
		</tr>
	</table>
</div>
<div id="Buttons_List_3_Thread" style="display:none">
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			<td align="left" width="25%">
                <input id="go2inbox" style="display:none;" class="crmbutton small edit ellipsisbtn-100" type="button" value="" onclick="returnToINBOXFolder('{$DIV_DIMENSION.Folders}','{$DIV_DIMENSION.ListViewContents}');"/>
                <input id="go2folder" style="display:none;" class="crmbutton small edit ellipsisbtn-100" type="button" value="" onclick="returnToFolder('{$DIV_DIMENSION.Folders}','{$DIV_DIMENSION.ListViewContents}');"/>
			</td>
			<td align="center" style="font-weight:bold;" width="50%">
				<span class="threadMessageTitle"></span>
			</td>
			<td align="right" width="25%">
				<input style="display:none;" id="editthread" class="crmbutton small edit" type="button" value="{$APP.LBL_CHANGE_BUTTON_LABEL}" onclick="editViewThread(true);" />
			</td>
		</tr>
	</table>
</div>
<div id="Buttons_List_3_Thread_Edit" style="display:none">
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			<td align="left" width="25%">
               	<input class="crmbutton small cancel" type="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="editViewThread(false);"/>
			</td>
			<td align="center" style="font-weight:bold;" width="50%">
				<span class="threadMessageTitle"></span>
			</td>
			<td align="right" width="25%">
				<a href="javascript:;" onClick="massFlag('Unseen');"><i class="vteicon" title="{'LBL_SEEN_ACTION'|@getTranslatedString:$MODULE}" border="0">markunread</i></a>
				<a href="javascript:;" onClick="massFlag('Seen');"><i class="vteicon" title="{'LBL_UNSEEN_ACTION'|@getTranslatedString:$MODULE}">drafts</i></a>
				<a href="javascript:;" onClick="MoveDisplay(this,'mass');"><i class="vteicon" title="{'LBL_MOVE_ACTION'|@getTranslatedString:$MODULE}" border="0">move_to_inbox</i></a>
				{foreach key=button_check item=button_label from=$BUTTONS}
					{if $button_check eq 'del'}
						<a href="javascript:;" onClick="massFlag('Delete');"><i class="vteicon" title="{$button_label}" border="0">delete</i></a>
					{/if}
				{/foreach}
							
				<div style="display:none;">
					{if ($ALL_IDS eq 1)}
						<input style="display:none;" class="crmbutton small edit" id="select_all_button_top" type="button" value="{$APP.LBL_UNSELECT_ALL_IDS}" onClick="selectAllIds();"/>
					{else}
						<input style="display:none;" class="crmbutton small edit" id="select_all_button_top" type="button" value="{$APP.LBL_SELECT_ALL_IDS}" onClick="selectAllIds();"/>
					{/if}
				</div>
							
                {* vtlib customization: Custom link buttons on the List view basic buttons *}
				{if $CUSTOM_LINKS && $CUSTOM_LINKS.LISTVIEWBASIC}
					{foreach item=CUSTOMLINK from=$CUSTOM_LINKS.LISTVIEWBASIC}
						{assign var="customlink_href" value=$CUSTOMLINK->linkurl}
						{assign var="customlink_label" value=$CUSTOMLINK->linklabel}
						{if $customlink_label eq ''}
							{assign var="customlink_label" value=$customlink_href}
						{else}
							{* Pickup the translated label provided by the module *}
							{assign var="customlink_label" value=$customlink_label|@getTranslatedString:$CUSTOMLINK->module()}
						{/if}
						<input class="crmbutton small edit" type="button" value="{$customlink_label}" onclick="{$customlink_href}" />
					{/foreach}
				{/if}
				
				{* vtlib customization: Custom link buttons on the List view *}
				{if $CUSTOM_LINKS && !empty($CUSTOM_LINKS.LISTVIEW)}
					&nbsp;
					<a href="javascript:;" onmouseover="fnvshobj(this,'vtlib_customLinksLay');" onclick="fnvshobj(this,'vtlib_customLinksLay');">
						<b>{$APP.LBL_MORE} {$APP.LBL_ACTIONS} <img src="{'arrow_down.gif'|resourcever}" border="0"></b>
					</a>
					<div class="drop_mnu" style="display: none; left: 193px; top: 106px;width:155px; position:absolute;" id="vtlib_customLinksLay"
						onmouseout="fninvsh('vtlib_customLinksLay')" onmouseover="fnvshNrm('vtlib_customLinksLay')">
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
						<tr><td style="border-bottom: 1px solid rgb(204, 204, 204); padding: 5px;"><b>{$APP.LBL_MORE} {$APP.LBL_ACTIONS} &#187;</b></td></tr>
						<tr>
							<td>
								{foreach item=CUSTOMLINK from=$CUSTOM_LINKS.LISTVIEW}
									{assign var="customlink_href" value=$CUSTOMLINK->linkurl}
									{assign var="customlink_label" value=$CUSTOMLINK->linklabel}
									{if $customlink_label eq ''}
										{assign var="customlink_label" value=$customlink_href}
									{else}
										{* Pickup the translated label provided by the module *}
										{assign var="customlink_label" value=$customlink_label|@getTranslatedString:$CUSTOMLINK->module()}
									{/if}
									<a href="{$customlink_href}" class="drop_down">{$customlink_label}</a>
								{/foreach}
							</td>
						</tr>
						</table>
					</div>
				{/if}
			</td>
		</tr>
	</table>
</div>