{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@57221 *}

{* crmv@160733 *}
{if $ajaxEditablePerm && $MODULE eq 'HelpDesk' && $keyfldname eq 'comments' && $ID > 0}
	<div id="centerbuttons_{$label}" class="checkbox" style="display:none;text-align:center;width:50%;margin:-20px auto -16px auto;"> {* trick to have everything on the same line, 16px for firefox bug *}
		<label for="confinfo_check">
			<input type="checkbox" name="confinfo_check" id="confinfo_check" onchange="VTE.HelpDesk.ConfidentialInfo.onChangeCheckbox('{$MODULE}', '{$ID}', this);" />
			{$MOD.LBL_SAVE_CONFIDENTIAL_COMMENT}
		</label>
		<i id="confinfo_edit_icon" class="vteicon md-link md-sm valign-bottom" style="display:none" title="{$APP.LBL_EDIT_BUTTON_LABEL}" onclick="VTE.HelpDesk.ConfidentialInfo.onEditPassword('{$MODULE}', '{$ID}');">create</i>
		<input type="hidden" name="confinfo_save_pwd" id="confinfo_save_pwd" />
		<input type="hidden" name="confinfo_save_more" id="confinfo_save_more" />
	</div>
{/if}
{* crmv@160733e *}
<div id="buttons_{$label}" style="float:right;display:none;">
	{if $ajaxEditablePerm}
		<a class="simpleSave" href="javascript:;" onclick="{if !empty($AJAXSAVEFUNCTION)}{$AJAXSAVEFUNCTION}{else}dtlViewAjaxSave{/if}('{$label}','{$MODULE}',{$uitype},'{$keytblname}','{$keyfldname}','{$ID}');fnhide('crmspanid');">{$APP.LBL_SAVE_LABEL}</a> -
		<a class="simpleCancel" href="javascript:;" onclick="hndCancel('dtlview_{$label}','editarea_{$label}','{$label}')">{$APP.LBL_CANCEL_BUTTON_LABEL}</a>
	{/if}
</div>