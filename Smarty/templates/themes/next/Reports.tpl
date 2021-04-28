{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@82831 crmv@97862 crmv@194449 *}

<script type="text/javascript" src="{"modules/Reports/Reports.js"|resourcever}"></script> {* crmv@128369 *}

<script type="text/javascript">
	/* labels for reports used in javascript */
	var ReportLabels = {ldelim}
		LBL_ADD_NEW_GROUP: '{$MOD.LBL_ADD_NEW_GROUP}',
		DELETE_FOLDER_CONFIRMATION: '{$APP.DELETE_FOLDER_CONFIRMATION}',
		FOLDERNAME_CANNOT_BE_EMPTY: '{$APP.FOLDERNAME_CANNOT_BE_EMPTY}',
		FOLDER_NAME_ALLOW_20CHARS: '{$APP.FOLDER_NAME_ALLOW_20CHARS}',
		FOLDER_NAME_ALREADY_EXISTS: '{$APP.FOLDER_NAME_ALREADY_EXISTS}',
		SPECIAL_CHARS_NOT_ALLOWED: '{$APP.SPECIAL_CHARS_NOT_ALLOWED}',
		LBL_RENAME_FOLDER: '{$MOD.LBL_RENAME_FOLDER}',
		DELETE_CONFIRMATION: '{$APP.DELETE_CONFIRMATION}',
		SELECT_ATLEAST_ONE_REPORT: '{$APP.SELECT_ATLEAST_ONE_REPORT}',
		DELETE_REPORT_CONFIRMATION: '{$APP.DELETE_REPORT_CONFIRMATION}',
	{rdelim}
</script>

{include file="Buttons_List1.tpl"}

<div id="reportContents" style="padding-top:60px;">
	{include file="ReportContents.tpl"}
</div>

{assign var="FLOAT_WIDTH" value="400px"}
{assign var="FLOAT_TITLE" value=$MOD.Move_Reports}
{capture assign="FLOAT_BUTTONS"}
<button type="button" title="{$APP.LBL_MOVE}" class="crmbutton save" onclick="MoveReport()">{$APP.LBL_MOVE}</button>
{/capture}
{capture assign="FLOAT_CONTENT"}
<form name="lview_folder_addform" id="lview_folder_addform">
	<input type="hidden" name="formodule" value="{$MODULE}" />
	<input type="hidden" name="subaction" value="add" />
	<table cellpadding="5" cellspacing="0" class="hdrNameBg" >
		<tr>
			<td>{$APP.LBL_SELECT_FOLDER}</td>
			<td>
				<select id="select_move_report" name="select_move_report" class="detailedViewTextBox">
					{foreach item=rfold from=$REPT_FOLDERS}
						<option value="{$rfold.id}">{$rfold.name}</option>
					{/foreach}
				</select>
			</td>
		</tr>
	</table>
</form>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="ReportMove"}

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
				<ul class="nav navbar-nav">
					<li>
						<button type="button" class="crmbutton with-icon success crmbutton-nav" onclick="Reports.createNew('{$FOLDERID}')">
							<i class="vteicon">add</i>
							{'LBL_CREATE_REPORT'|@getTranslatedString:$MODULE}
						</button>
					</li>
					<li>
						{if $FOLDERID > 0}
							<a class="crmbutton with-icon save crmbutton-nav" href="index.php?module={$MODULE}&action=index">
								<i class="vteicon">undo</i>
								{$APP.LBL_GO_BACK}
							</a>
						{else}
							<button type="button" class="crmbutton with-icon save crmbutton-nav" onclick="location.href='index.php?module={$MODULE}&action=index';">
								<i class="vteicon">folder</i>
								{$APP.LBL_FOLDERS}
							</button>
						{/if}
					</li>
					<li>
						<button type="button" class="crmbutton with-icon delete crmbutton-nav" onclick="return massDeleteReport()">
							<i class="vteicon">delete</i>
							{$APP.LBL_MASS_DELETE}
						</button>
					</li>
					<li>
						<button type="button" class="crmbutton with-icon save crmbutton-nav" onclick="showMoveReport(this)">
							<i class="vteicon">open_with</i>
							{$MOD.Move_Reports}
						</button>
					</li>
				</ul>
			</div>
		</div>
	</nav>
</div>
<script type="text/javascript">calculateButtonsList3();</script>