{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@37679 *}
<script language="javascript" type="text/javascript" src="modules/SDK/src/208/Settings/208Settings.js"></script>
<link rel="stylesheet" href="modules/SDK/src/208/Settings/208Settings.css">
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td valign="top"></td>
		<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">
			<div align="center">
				{include file='SetMenu.tpl'}
				{include file='Buttons_List.tpl'}
				<table border="0" cellspacing="0" cellpadding="5" width="100%" class="settingsSelUITopLine">
					<tr>
						<td width=50 rowspan=2 valign=top>
							<img src="{'uitype208.png'|resourcever}" alt="LBL_EDIT_UITYPE208" width="48" height="48" border=0 title="{$MOD.LBL_PROFILES}">
						</td>
						<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_EDIT_UITYPE208}</b></td>
					</tr>
					<tr>
						<td valign=top class="small">{$MOD.LBL_EDIT_UITYPE208_DESC}</td>
					</tr>
				</table>

				{if $SELFTEST eq false}
					<br>
					<p id="encField_testFailed">{$MOD.LBL_UT208_SELFTEST_FAILED}</p>

				{else}

				<br>
				<div style="width:100%;text-align:right">
					<input type="button" class="small crmbutton create" value="{$APP.LBL_ADD_BUTTON}" onclick="addEncryptedField()" />
				</div>
				<br>
				{if count($LISTFIELDS) > 0}
				<table width="100%" cellspacing="0" cellpadding="5" border="0" class="listTable">
					<tr>
						<td class="colHeader small">{$APP.LBL_MODULE}</td>
						<td class="colHeader small">{$MOD.FieldName}</td>
						<td class="colHeader small">{$APP.LBL_TOOLS}</td>
					</tr>

					{foreach item=rowfield from=$LISTFIELDS}
					<tr>
						<td class="listTableRow small">{$rowfield.module|getTranslatedString}
						<td class="listTableRow small">{$rowfield.fieldlabel_trans}</td>
						<td class="listTableRow small">
							<i class="vteicon md-link" onclick="editEncryptedField('{$rowfield.fieldid}')" title="{'LBL_EDIT'|getTranslatedString}" >create</i>
							<i class="vteicon md-link" onclick="showDeleteForm(this, '{$rowfield.fieldid}')" title="{'LBL_DELETE'|getTranslatedString}">delete</i>
						</td>
					</tr>
					{/foreach}
				</table>

				{* delete form *}
				<div id="encField_DelForm_div" class="crmvDiv">
					<div class="closebutton" onClick="fninvsh('encField_DelForm_div');"></div>
					<table border="0" cellpadding="5" cellspacing="0" width="100%">
						<tr style="cursor:move;" height="34">
							<td id="encField_DelForm_Handle" style="padding:5px" class="level3Bg">
								<table cellpadding="0" cellspacing="0" width="100%"><tr>
									<td width="80%"><b>{$APP.LBL_RESET}</b></td>
									<td width="20%" align="right"><div id="encField_DelForm_loader" style="display:none;">{include file="LoadingIndicator.tpl"}</div></td>
								</tr></table>
							</td>
						</tr>
					</table>

					<div id="encField_DelForm_cont" >
							<table width="100%" cellspacing="0" cellpadding="2" border="0">
								<tr><td>
									<p>{$MOD.LBL_UT208_TYPEPWD}:</p>
								</td></tr>
								<tr><td>
									<input type="hidden" id="encField_DelForm_fieldid" name="encField_DelForm_fieldid" value="" />
									<div class="dvtCellInfo">
										<input type="password" class="detailedViewTextBox" id="encField_DelForm_pwd" name="password" value="" autocomplete="off" placeholder="password" />
									</div>
									<p>{$MOD.LBL_UT208_TYPEPWD_DESC}</p>
								</td></tr>
								<tr>
									<td align="right">
										<div style="height:5px">&nbsp;</div>
										<input id="encField_DelForm_btn" type="button" class="small crmbutton delete" value="{$APP.LBL_RESET}" onclick="deleteEncryptedField()">
									</td>
								</tr>
							</table>
					</div>
				</div>
				<script>
					// crmv@192014
					jQuery('#encField_DelForm_div').draggable({ldelim}
						handle: '#encField_DelForm_Handle'
					{rdelim});
					// crmv@192014e
				</script>

				{else}
					<br>
					<p id="encField_noFields">{$MOD.LBL_UT208_NOFIELDS}</p>
				{/if}

				{/if}

			</div>
		</td>
	</tr>
</table>