{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@176547 *}

<script type="text/javascript" src="{"modules/VteSync/Settings/VteSyncConfig.js"|resourcever}"></script>
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tbody><tr>
<td valign="top"></td>
<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">
<!-- form action="index.php" method="post" id="form" onsubmit="VteJS_DialogBox.block();">
<input type='hidden' name='module' value='Users'>
<input type='hidden' name='action' value='DefModuleView'>
<input type='hidden' name='return_action' value='ListView'>
<input type='hidden' name='return_module' value='Users'>
<input type='hidden' name='parenttab' value='Settings' -->

	<div align=center>
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'}
				<!-- DISPLAY -->
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
				<tr>
					<td width=50 rowspan=2 valign=top><img src="{'vtesync.png'|resourcever}" alt="{$MOD.LBL_SYNC_SETTINGS}" width="48" height="48" border=0 title="{$MOD.LBL_SYNC_SETTINGS}"></td>
					<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_SYNC_SETTINGS}</b></td>
				</tr>
				<tr>
					<td valign=top class="small">{$MOD.LBL_SYNC_SETTINGS_DESC} </td>
				</tr>
				</table>
				<br>
				
				<table border=0 cellspacing=0 cellpadding=10 width=100% >
				
				<!-- tr><td><b>{$MOD.LBL_LOG_GENERAL_CONFIG}</b></td></tr>
				<tr><td>
					TODO
				</td></tr -->
				
				<tr><td>
					<table width="100%"><tr>
						<td><b>{$MOD.LBL_VTESYNC_SYNCLIST}</b></td>
						<td align="right">
							<a href="javascript:void(0)" onclick="VteSyncConfig.addSync();"><button type="button" class="crmbutton small create">{$MOD.LBL_ADD}</button></a>
						</td>
					</tr></table>
				</td></tr>
				
				<tr>
				<td>
					
					<table class="table table-hover">
						<thead>
							<tr>
								<th width="100">{$MOD.LBL_STATUS}</th>
								<th width="20%">{$MOD.LBL_VTESYNC_TYPE}</th>
								<th>{$APP.LBL_MODULES}</th>
								<th>{$MOD.LBL_VTESYNC_LAST_RUN}</th>
								<th width="120" class="text-right">{$MOD.LBL_LIST_TOOLS}</th>
							</tr>
						</thead>
						<tbody>
					{foreach item="SYNC" from=$SYNCS}
						{assign var="SYNCID" value=$SYNC.syncid}
						<tr>
							<td width="10%" class="cell-vcenter">
								{if $SYNC.active}
									<a href="javascript:void(0);" onclick="VteSyncConfig.toggleSyncStatus('{$SYNCID}', false)"><i class="vteicon checkok" title="{$MOD.LBL_DISABLE}">check</i></a>
								{else}
									<a href="javascript:void(0);" onclick="VteSyncConfig.toggleSyncStatus('{$SYNCID}', true)"><i class="vteicon checkko" title="{$MOD.LBL_ENABLE}">clear</i></a>
								{/if}
							</td>
							<td>
								{$SYNC.type_name}
							</td>
							<td>
								{$SYNC.modules}
							</td>
							<td>
								<span title="{$SYNC.lastrun}">{$SYNC.lastrun_friendly}</span>
							</td>
							<td align="right">
								<a href="javascript:void(0);" onclick="VteSyncConfig.editSync('{$SYNCID}')"><i class="vteicon" title="{$APP.LBL_EDIT_BUTTON}">create</i></a>
								<a href="javascript:void(0);" onclick="VteSyncConfig.deleteSync('{$SYNCID}')"><i class="vteicon" title="{$APP.LBL_DELETE_BUTTON}">delete</i></a>
							</td>
						</tr>
					{/foreach}
						</tbody>
					</table>
					
				</td>
				</tr>
				</table>
				
			</td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
		
	</div>
</td>
<td valign="top"></td>
</tr>
</tbody>
<!-- /form -->
</table>