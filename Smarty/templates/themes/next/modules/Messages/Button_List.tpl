{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

<div id="Buttons_List_3_Accounts" style="display:none">
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			<td align="left" width="15%"></td>
			<td align="center" style="font-weight:bold;" width="60%">
				<div class="listMessageTitle">{'Accounts'|getTranslatedString}</div>
			</td>
			<td align="right" width="25%"></td>
		</tr>
	</table>
</div>

<div id="Buttons_List_3_Folders" style="display:none">
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			<td align="left" width="15%" style="padding-left:5px">
				{if $MULTIACCOUNT eq true}
					<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="changeLeftView('accounts','{$DIV_DIMENSION.Folders}','{$DIV_DIMENSION.ListViewContents}')">
						<i data-toggle="tooltip" data-placement="auto" class="vteicon md-link" title="{'Accounts'|getTranslatedString}">arrow_back</i>
					</button>
				{/if}
			</td>
			<td align="center" style="font-weight:bold;" width="60%">
				<div class="listMessageTitle">{$MOD.Folders}</div>
			</td>
			<td align="right">
				<div style="float:right">
					<ul class="vteUlTable">
						<li>
							<button type="button" class="crmbutton only-icon success crmbutton-nav" id="editfolder" onclick="editViewFolders(true);">
								<i data-toggle="tooltip" data-placement="bottom" class="vteicon md-link" title="{$APP.LBL_CHANGE_BUTTON_LABEL}">create</i>
							</button>
						</li>
						<li>
							<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="openPopup('index.php?module=Messages&action=MessagesAjax&file=Settings/index','','','auto',720,500);">
								<i data-toggle="tooltip" data-placement="bottom" class="vteicon md-link" title="{$APP.LBL_SETTINGS}">settings</i>
							</button>
						</li>
					</ul>
				</div>
			</td>
		</tr>
	</table>
</div>

<div id="Buttons_List_3_Folders_Edit" style="display:none">
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			<td align="left" width="15%">
				<button type="button" class="crmbutton cancel" onclick="editViewFolders(false);">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
			</td>
			<td align="center" style="font-weight:bold;" width="60%">
				<div class="listMessageTitle">{$MOD.Folders}</div>
			</td>
			<td align="right">
				<a href="javascript:;" onClick="folderAction(this,'unseen');"><i class="vteicon" title="{'LBL_SEEN_ACTION'|@getTranslatedString:$MODULE}">markunread</i></a>
				<a href="javascript:;" onClick="folderAction(this,'seen');"><i class="vteicon" title="{'LBL_UNSEEN_ACTION'|@getTranslatedString:$MODULE}">drafts</i></a>
				<a href="javascript:;" onClick="folderAction(this,'move');"><i class="vteicon" title="{'LBL_MOVE_ACTION'|@getTranslatedString:$MODULE}">move_to_inbox</i></a>
				<a href="javascript:;" onClick="folderAction(this,'create');"><i class="vteicon" title="{'LBL_CREATE'|@getTranslatedString}">add</i></a>
			</td>
		</tr>
	</table>
</div>

<div id="Buttons_List_3_ListView" style="display:none">
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			<td align="left" width="15%" style="padding-left:5px">
                <button type="button" class="crmbutton only-icon save crmbutton-nav" id="go2accounts" style="display:none;" onclick="changeLeftView('accounts','{$DIV_DIMENSION.Folders}','{$DIV_DIMENSION.ListViewContents}')">
					<i data-toggle="tooltip" data-placement="auto" class="vteicon md-link" title="{'Accounts'|getTranslatedString}">arrow_back</i>
				</button>
                <button type="button" class="crmbutton only-icon save crmbutton-nav" id="go2folders" style="display:none;" onclick="changeLeftView('folders','{$DIV_DIMENSION.Folders}','{$DIV_DIMENSION.ListViewContents}')">
					<i data-toggle="tooltip" data-placement="auto" class="vteicon md-link" title="{$MOD.Folders}">arrow_back</i>
				</button>
			</td>
			<td align="center" style="font-weight:bold;" width="60%">
				<div class="listMessageTitle" title="{$CURRENT_FOLDER_LABEL}">{$CURRENT_FOLDER_LABEL}</div><span id="rec_string" style="display:none">{$RECORD_COUNTS}</span><span id="rec_string3"></span>
			</td>
			<td align="right" width="25%">
				<div style="float:right">
					<ul class="vteUlTable">
						<li>
							<button type="button" class="crmbutton only-icon success crmbutton-nav" id="editfolder" onclick="editViewList(true);">
								<i data-toggle="tooltip" data-placement="bottom" class="vteicon md-link" title="{$APP.LBL_CHANGE_BUTTON_LABEL}">create</i>
							</button>
						</li>
						<li>
							<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="openPopup('index.php?module=Messages&action=MessagesAjax&file=Settings/index','','','auto',720,500);">
								<i data-toggle="tooltip" data-placement="bottom" class="vteicon md-link" title="{$APP.LBL_SETTINGS}">settings</i>
							</button>
						</li>
					</ul>
				</div>
			</td>
		</tr>
	</table>
</div>

<div id="Buttons_List_3_ListView_Edit" style="display:none">
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			<td align="left" width="15%">
               	<button type="button" class="crmbutton cancel" onclick="editViewList(false);">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
			</td>
			<td align="center" style="font-weight:bold;" width="60%">
				<div class="listMessageTitle"></div>
			</td>
			<td align="right" width="25%">
				{* crmv@48159 *}
				{if $CURRENT_FOLDER eq $SPECIAL_FOLDERS.INBOX or $CURRENT_FOLDER eq $SPECIAL_FOLDERS.Sent}
					{assign var="display" value="display:none;"}
				{else}
					{assign var="display" value=""}
				{/if}
				<a id="empty_button" href="javascript:;" onClick="emptyFolder();" style="{$display}"><i class="vteicon" title="{'LBL_EMPTY_FOLDER'|@getTranslatedString:$MODULE}">clear_all</i></a>			                		
				{* crmv@48159e *}
				{* crmv@79192 *}
				<a id="unseen_button" href="javascript:;" onClick="massFlag('Unseen');"><i class="vteicon" title="{'LBL_SEEN_ACTION'|@getTranslatedString:$MODULE}">markunread</i></a>
				<a id="seen_button" href="javascript:;" onClick="massFlag('Seen');"><i class="vteicon" title="{'LBL_UNSEEN_ACTION'|@getTranslatedString:$MODULE}">drafts</i></a>
				{*
				<a href="javascript:;" onClick="massFlag('Flagged');"><i class="vteicon" title="{'LBL_UNFLAGGED_ACTION'|@getTranslatedString:$MODULE}">flag</i></a>
				<a href="javascript:;" onClick="massFlag('Unflagged');"><i class="vteicon" title="{'LBL_FLAGGED_ACTION'|@getTranslatedString:$MODULE}" style="color:red">flag</i></a>
				*}
				<a id="move_button" href="javascript:;" onClick="MoveDisplay(this,'mass');"><i class="vteicon" title="{'LBL_MOVE_ACTION'|@getTranslatedString:$MODULE}">move_to_inbox</i></a>
				{foreach key=button_check item=button_label from=$BUTTONS}
					{if $button_check eq 'del'}
						<a id="trash_button" href="javascript:;" onClick="massFlag('Delete');"><i class="vteicon" title="{$button_label}">delete</i></a>
					{/if}
				{/foreach}
				{* crmv@79192e *}
							
				<div style="display:none;">
					{if ($ALL_IDS eq 1)}
						<button type="button" style="display:none;" class="crmbutton edit" id="select_all_button_top" onClick="selectAllIds();">{$APP.LBL_UNSELECT_ALL_IDS}</button>
					{else}
						<button type="button" style="display:none;" class="crmbutton edit" id="select_all_button_top" onClick="selectAllIds();">{$APP.LBL_SELECT_ALL_IDS}</button>
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
						<button type="button" class="crmbutton edit" onclick="{$customlink_href}">{$customlink_label}</button>
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
			<td align="left" width="15%" style="padding-left:5px">
				<button type="button" class="crmbutton only-icon save crmbutton-nav" id="go2inbox" style="display:none;" onclick="returnToINBOXFolder('{$DIV_DIMENSION.Folders}','{$DIV_DIMENSION.ListViewContents}');">
					<i data-toggle="tooltip" data-placement="auto" class="vteicon md-link" title="">arrow_back</i>
				</button>
				<button type="button" class="crmbutton only-icon save crmbutton-nav" id="go2folder" style="display:none;" onclick="returnToFolder('{$DIV_DIMENSION.Folders}','{$DIV_DIMENSION.ListViewContents}');">
					<i data-toggle="tooltip" data-placement="auto" class="vteicon md-link" title="">arrow_back</i>
				</button>
			</td>
			<td align="center" style="font-weight:bold;" width="60%">
				<span class="threadMessageTitle"></span>
			</td>
			<td align="right" width="25%">
				<div style="float:right">
					<ul class="vteUlTable">
						<li>
							<button type="button" class="crmbutton only-icon success crmbutton-nav" id="editthread" style="display:none;" onclick="editViewThread(true);">
								<i data-toggle="tooltip" data-placement="bottom" class="vteicon md-link" title="{$APP.LBL_CHANGE_BUTTON_LABEL}">create</i>
							</button>
						</li>
						<li>
							<button type="button" class="crmbutton only-icon success crmbutton-nav" onclick="openPopup('index.php?module=Messages&action=MessagesAjax&file=Settings/index','','','auto',720,500);">
								<i data-toggle="tooltip" data-placement="bottom" class="vteicon md-link" title="{$APP.LBL_SETTINGS}">settings</i>
							</button>
						</li>
					</ul>
				</div>
			</td>
		</tr>
	</table>
</div>

<div id="Buttons_List_3_Thread_Edit" style="display:none">
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			<td align="left" width="15%">
               	<button type="button" class="crmbutton cancel" onclick="editViewThread(false);">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
			</td>
			<td align="center" style="font-weight:bold;" width="60%">
				<span class="threadMessageTitle"></span>
			</td>
			<td align="right" width="25%">
				<a href="javascript:;" onClick="massFlag('Unseen');"><i class="vteicon" title="{'LBL_SEEN_ACTION'|@getTranslatedString:$MODULE}">markunread</i></a>
				<a href="javascript:;" onClick="massFlag('Seen');"><i class="vteicon" title="{'LBL_UNSEEN_ACTION'|@getTranslatedString:$MODULE}">drafts</i></a>
				<a href="javascript:;" onClick="MoveDisplay(this,'mass');"><i class="vteicon" title="{'LBL_MOVE_ACTION'|@getTranslatedString:$MODULE}">move_to_inbox</i></a>
				{foreach key=button_check item=button_label from=$BUTTONS}
					{if $button_check eq 'del'}
						<a href="javascript:;" onClick="massFlag('Delete');"><i class="vteicon" title="{$button_label}">delete</i></a>
					{/if}
				{/foreach}
							
				<div style="display:none;">
					{if ($ALL_IDS eq 1)}
						<button type="button" style="display:none;" class="crmbutton edit" id="select_all_button_top" onClick="selectAllIds();">{$APP.LBL_UNSELECT_ALL_IDS}</button>
					{else}
						<button type="button" style="display:none;" class="crmbutton edit" id="select_all_button_top" onClick="selectAllIds();">{$APP.LBL_SELECT_ALL_IDS}</button>
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
						<button type="button" class="crmbutton edit" onclick="{$customlink_href}">{$customlink_label}</button>
					{/foreach}
				{/if}
				
				{* vtlib customization: Custom link buttons on the List view *}
				{if $CUSTOM_LINKS && !empty($CUSTOM_LINKS.LISTVIEW)}
					&nbsp;
					<a href="javascript:;" onmouseover="fnvshobj(this,'vtlib_customLinksLay');" onclick="fnvshobj(this,'vtlib_customLinksLay');">
						<b>{$APP.LBL_MORE} {$APP.LBL_ACTIONS} <img src="{'arrow_down.gif'|@vtecrm_imageurl:$THEME}" border="0"></b>
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