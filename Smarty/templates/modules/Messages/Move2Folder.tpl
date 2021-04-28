{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<div id="mode2Folder" style="display:none;position:fixed;" class="crmvDiv">
	<table border="0" cellpadding="5" cellspacing="0" width="250">
		<tr style="cursor:move;" height="34">
			<td id="mode2Folder_Handle" style="padding:5px" class="level3Bg">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="50%"><b>{'LBL_MOVE_ACTION'|@getTranslatedString:$MODULE}</b></td>
					<td width="50%" align="right">&nbsp;
						{include file="LoadingIndicator.tpl" LIID="indicatorMode2Folder" LIEXTRASTYLE="display:none;"}&nbsp;
					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div width="250" class="hdrNameBg" id="mode2Folder_list" style="height:400px;overflow:auto;"></div>
	<div class="closebutton" onClick="fninvsh('mode2Folder');"></div>
</div>
<script>
	// crmv@192014
	jQuery("#mode2Folder").draggable({ldelim}
		handle: '#mode2Folder_Handle'
	{rdelim});
	// crmv@192014e
</script>