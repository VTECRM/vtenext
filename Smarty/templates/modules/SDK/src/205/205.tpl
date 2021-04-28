{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{if $sdk_mode eq 'detail'}
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div class="{$DIVCLASS}" {if $AJAXEDITTABLEPERM}onClick="openPopup('index.php?module=SDK&action=SDKAjax&file=src/205/CropImage&record={$smarty.request.record}');" style="cursor:pointer;"{/if}>
		{if $keyval eq '' || $keyval eq 'NO_USER_IMAGE'}
			{''|getUserAvatarImg}
		{/if}
		{if $keyval eq 'NO_USER_IMAGE'}
			<br /><i>{'LBL_AVATAR_INSTRUCTIONS'|getTranslatedString:$MODULE}</i>
		{else}
			{$keyval}
		{/if}
	</div>
{elseif $sdk_mode eq 'edit'}
	<input type="hidden" name="{$fldname}" value="{$fldvalue}">
{/if}