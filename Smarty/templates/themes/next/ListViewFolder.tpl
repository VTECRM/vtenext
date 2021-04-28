{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@30967 crmv@104853 crmv@107103 crmv@194449 *}

<script language="JavaScript" type="text/javascript" src="{"include/js/ListView.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/search.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/Merge.js"|resourcever}"></script> {* crmv@8719 *}
<script language="JavaScript" type="text/javascript" src="{"modules/`$MODULE`/`$MODULE`.js"|resourcever}"></script>

{literal}
<script type="text/javascript">
	var lviewFolder = { x: 0, y: 0, hidden: true };
</script>
{/literal}

{include file='Buttons_List.tpl'}

<div id="Buttons_List_3_Container" style="display:none;">
	<nav class="navbar buttonsList buttonsListFixed" data-minified="{$MENU_TOGGLE_STATE}">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle primary" data-toggle="collapse" data-target="#vteNavbar">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span> 
				</button>
			</div>
			<div class="collapse navbar-collapse" id="vteNavbar">
				{if $MODULE eq 'Reports'}
					<ul class="nav navbar-nav">
						<li>
							<button type="button" class="crmbutton with-icon success crmbutton-nav" onclick="Reports.createNew('{$FOLDERID}')">
								<i class="vteicon">add</i>
								{'LBL_CREATE_REPORT'|@getTranslatedString:$MODULE}
							</button>
						</li>
					</ul>
				{/if}
				<ul class="nav navbar-nav">
					<li>
						<form method="GET" action="index.php">
							<input type="hidden" name="module" value ="{$MODULE}" />
							<input type="hidden" name="action" value ="ListView" />
							<button id="lviewfolder_button_list" type="submit" class="crmbutton with-icon save crmbutton-nav">
								<i class="vteicon">list</i>
								{$APP.LBL_LIST}
							</button>
						</form>
					</li>
					<li>
						{if $CHECK.EditView eq 'yes'}
							<button id="lviewfolder_button_add" type="button" class="crmbutton with-icon save crmbutton-nav" onclick="showFloatingDiv('lview_folder_add');">
								<i class="vteicon">create_new_folder</i>
								{$APP.LBL_ADD_NEW_FOLDER}
							</button>
							<button id="lviewfolder_button_del" type="button" class="crmbutton with-icon delete crmbutton-nav" onclick="lviewfold_del();">
								<i class="vteicon">delete</i>
								{$APP.LBL_DELETE_FOLDERS}
							</button>
							<button id="lviewfolder_button_del_save" style="display:none" type="button" class="crmbutton with-icon delete crmbutton-nav" onclick="lviewfold_del_save('{$MODULE}');">
								<i class="vteicon">delete</i>
								{$APP.LBL_DELETE_BUTTON}
							</button>
							<button id="lviewfolder_button_del_cancel" style="display:none" type="button" class="crmbutton with-icon delete crmbutton-nav" onclick="lviewfold_del_cancel();">
								<i class="vteicon">undo</i>
								{$APP.LBL_CANCEL_BUTTON_LABEL}
							</button>
						{/if}
					</li>
				</ul>
				<ul class="nav navbar-nav navbar-right">
					<li>
						{if $HIDE_BUTTON_SEARCH eq false}
							<form id="basicSearch" name="basicSearch" method="post" action="index.php">
								<input type="hidden" name="module" value="{$MODULE}" />
								<input type="hidden" name="action" value="ListView" />
								<input type="hidden" name="parenttab" value="{$CATEGORY}" />
								<input type="hidden" name="viewmode" value="ListView" />
								<input type="hidden" name="searchtype" value="BasicSearch" />
								<input type="hidden" name="query" value="true" />
								<input type="hidden" name="search" value="true" />
								<input type="hidden" id="basic_search_cnt" name="search_cnt" />

								<div class="form-group basicSearch advIconSearch folderBasicSearch">
									<input type="text" class="form-control searchBox" id="basic_search_text" name="search_text" value="{$APP.LBL_SEARCH_TITLE}{$MODULE|getTranslatedString:$MODULE}" onclick="clearText(this)" onblur="restoreDefaultText(this, '{$APP.LBL_SEARCH_TITLE}{$MODULE|getTranslatedString:$MODULE}')" />
									<span class="cancelIcon" style="right:20px">
										<i class="vteicon md-link md-sm" id="basic_search_icn_canc" style="display:none" title="Reset" onclick="cancelSearchText('{$APP.LBL_SEARCH_TITLE}{$MODULE|getTranslatedString:$MODULE}')">cancel</i>&nbsp;
									</span>
									<span class="searchIcon" style="right:0px">
										<i id="basic_search_icn_go" class="vteicon" title="{$APP.LBL_FIND}" style="cursor:pointer" onclick="jQuery('#basicSearch').submit();" >search</i>
									</span>
								</div>
							</form>
						{/if}
					</li>
				</ul>
			</div>
		</div>
	</nav>
</div>
<script>calculateButtonsList3();</script>

{assign var="FLOAT_WIDTH" value="400px"}
{assign var="FLOAT_TITLE" value=$APP.LBL_ADD_NEW_FOLDER}
{capture assign="FLOAT_BUTTONS"}
<button id="lview_folder_save" type="button" name="button" class="crmbutton save" onclick="lviewfold_add()">{$APP.LBL_SAVE_LABEL}</button>
{/capture}
{capture assign="FLOAT_CONTENT"}
<form name="lview_folder_addform" id="lview_folder_addform">
	<input type="hidden" name="formodule" value="{$MODULE}" />
	<input type="hidden" name="subaction" value="add" />
	<table class="table borderless">
		<tr>
			<td width="30%">{$APP.LBL_FOLDER_NAME}</td>
			<td width="70%">
				<div class="dvtCellInfo">
					<input type="text" maxlength="50" name="foldername" class="detailedViewTextBox" value="" />
				</div>
			</td>
		</tr>
		<tr>
			<td>{$APP.LBL_DESCRIPTION}</td>
			<td>
				<div class="dvtCellInfo">
					<input type="text" maxlength="100" name="folderdesc" class="detailedViewTextBox" value="" />
				</div>
			</td>
		</tr>
	</table>
</form>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="lview_folder_add"}

{assign var="FLOAT_WIDTH" value="400px"}
{assign var="FLOAT_TITLE" value='LBL_EDIT_FOLDER'|@getTranslatedString:'Documents'}
{capture assign="FLOAT_BUTTONS"}
<button id="lview_folder_save" type="button" name="button" class="crmbutton save" onclick="folder_edit(this,'lview_folder_edit','{$MODULE}','','save');">{$APP.LBL_SAVE_LABEL}</button>
{/capture}
{capture assign="FLOAT_CONTENT"}
<form name="lview_folder_editform" id="lview_folder_editform">
	<input type="hidden" name="formodule" value="{$MODULE}" />
	<input type="hidden" name="subaction" value="edit" />
	<input type="hidden" name="folderid" id="folderid" value="" />
	<input type="hidden" name="filecount" id="filecount" value="" />
	<table class="table borderless">
		<tr>
			<td width="30%">{$APP.LBL_FOLDER_NAME}</td>
			<td width="70%">
				<div class="dvtCellInfo">
					<input type="text" maxlength="50" name="foldername" id="foldername" class="detailedViewTextBox" value="" />
				</div>		
			</td>
		</tr>
		<tr>
			<td>{$APP.LBL_DESCRIPTION}</td>
			<td>
				<div class="dvtCellInfo">
					<input type="text" maxlength="100" name="folderdesc" id="folderdesc" class="detailedViewTextBox" value="" />
				</div>
			</td>
		</tr>
	</table>
</form>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="lview_folder_edit"}

<div id="lview_table_cont" class="container-fluid lview_folder_table" style="padding-top:60px;">
	{assign var=foldercount value=0}
	{foreach item=folder from=$FOLDERLIST}
		{if $foldercount % 6 eq 0}
			<div class="row">
		{/if}

		{assign var=foldercontent value=$folder.content}
			
		<div class="lview_folder_td col-xs-2" data-deletable="{if $foldercontent.count eq 0}1{else}0{/if}" {if $folder.editable eq true}onmouseover="showPencil({$folder.folderid},1);" onmouseout="showPencil({$folder.folderid},2);"{/if}>
			<div>
				{if $folder.editable eq true}
					<i class="vteicon lview_folder_pencil" id="pencil_{$folder.folderid}" onmouseover="showPencil({$folder.folderid},3);" onclick="folder_edit(this,'lview_folder_edit','{$MODULE}',{$folder.folderid},'',{$foldercontent.count});" style="display:none;">create</i>
				{/if}
				<a href="index.php?action=ListView&module={$MODULE}&folderid={$folder.folderid}"> 
					<img class="img-responsive lview_folder_img" src="{'listview_folder.png'|resourcever}" />
				</a>
				<br />
			</div>
			<div>
				{if $foldercontent.count eq 0}
					<span id="lview_folder_checkspan_{$folder.folderid}" style="display:none"><input type="checkbox" name="lvidefold_check_{$folder.folderid}" id="lvidefold_check_{$folder.folderid}" value="" /></span>
				{/if}
				<span class="lview_folder_span">{$folder.foldername} ({$foldercontent.count})</span>
				<br />
				<div class="lview_folder_desc">{$folder.description}&nbsp;</div>
			</div>
			<div class="lview_folder_tooltip" id="lviewfold_tooltip_{$folder.folderid}">
				{$foldercontent.html}
			</div>
		</div>
		{assign var=foldercount value=$foldercount+1}
		{if $foldercount % 6 eq 0}
			</div>
		{/if}
	{/foreach}
	{if $foldercount % 6 neq 0}
		</div>
	{/if}
</div>

{literal}
<script type="text/javascript">
jQuery(document).mousemove(function(event){
	lviewFolder.x = event.pageX;
	lviewFolder.y = event.pageY;
});
</script>
{/literal}