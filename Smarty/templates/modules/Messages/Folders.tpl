{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<table class="messages-folder-list" cellspacing="0" cellpadding="0" width="100%">
	{if $VIEW eq 'create'}
		<tr class="lvtColDataMessage" height="40px" valign="middle">
			<td align="right" style="padding-left:5px;padding-right:5px;">
				<div class="dvtCellInfo">
					<input type="text" class="detailedViewTextBox" id="foldername" name="foldername">
				</div>
			</td>
			<td style="padding-right:5px;">
				<input type="button" class="crmbutton save" value="{'LBL_CREATE'|@getTranslatedString}" onClick="CreateFolder();">
			</td>
		</tr>
	{/if}
	{foreach key=entity_id item=entity from=$FOLDERS}
		<tr id="row_{$entity_id|rawurlencode}" class="lvtColDataMessage" onMouseOut="this.className='lvtColDataMessage'" onMouseOver="this.className='lvtColDataHoverMessage'">
			<td colspan="2" class="folderMessageRow listMessageFrom"
				{if $entity.selectable eq true}
					{if $VIEW eq 'list'}
						{* style="cursor: pointer;" onClick="changeLeftView('list','{$DIV_DIMENSION.Folders}','{$DIV_DIMENSION.ListViewContents}','{$entity_id}','{$entity.label}')" *}
						style="cursor: pointer;" onClick="selectFolder('{$DIV_DIMENSION.Folders}','{$DIV_DIMENSION.ListViewContents}','{$entity_id}','{$entity.label}','{$entity_id|rawurlencode}')" {* crmv@131944 *}
					{elseif $VIEW eq 'move' && $MODE eq 'single'}
						style="cursor: pointer;" onClick="Move('{$entity_id}','{$ID}');"
					{elseif $VIEW eq 'move' && $MODE eq 'mass'}
						style="cursor: pointer;" onClick="massMove('{$entity_id}');"
					{elseif $VIEW eq 'move' && $MODE eq 'folders'}
						style="cursor: pointer;" onClick="folderMove('{$entity_id}');"
					{elseif $VIEW eq 'create'}
						style="cursor: pointer;" onClick="selectCreateFolder('{$entity_id}','{$entity_id|rawurlencode}')" {* crmv@131944 *}
					{/if}
				{/if}
			>
				<div style="padding-left: {$entity.depth*20+10}px; padding-right: 3px;">
					<table cellpadding="0" cellspacing="0" width="100%">
					<tr>
						<td width="100%"
							{if $entity.selectable neq true}
								style="-ms-filter:'progid:DXImageTransform.Microsoft.Alpha(Opacity=50)';filter:alpha(opacity=50);opacity:0.5;"
							{/if}>
							{if $entity_id|in_array:$FOCUS->fakeFolders neq true}
								{if $VIEW eq 'list'}
									<input type="radio" name="selected_folder" id="check_{$entity_id|rawurlencode}" value="{$entity_id}" style="display:none;" />
								{elseif $VIEW eq 'create'}
									<input type="radio" name="selected_folder_create" id="check_create_{$entity_id|rawurlencode}" value="{$entity_id}" {if $smarty.request.current_folder eq $entity_id}checked{/if} />
								{/if}
							{/if}
							{* crmv@192843 *}
							{if !empty($entity.vteicon)}
								<i class="vteicon" style="padding-right:10px; vertical-align:middle; ">{$entity.vteicon}</i>
							{/if}
							{$entity.label}
							{* crmv@192843e *}
						</td>
						{if $VIEW eq 'list'}
							{if $entity.count gt 0}
								<td class="listMessageSubject" align="right" nowrap>
									{include file="BubbleNotification.tpl" COUNT=$entity.count BN_BGCOLOR=$entity.bg_notification_color}
								</td>
							{/if}
						{/if}
					</tr>
					</table>
				</div>
			</td>
		</tr>
	{foreachelse}
		<tr><td colspan="2" style="height:340px" align="center" colspan="{$smarty.foreach.listviewforeach.iteration+1}">
            <div style="width: 100%; position: relative;">
                <table border="0" cellpadding="5" cellspacing="0" width="100%">
	                <tr>
	                	<td align="center"><span class="genHeaderSmall">{$APP.LBL_NO_F} {$MOD.Folder} {$APP.LBL_FOUND_F}</span></td>
	                </tr>
                </table>
			</div>
		</td></tr>
	{/foreach}
</table>