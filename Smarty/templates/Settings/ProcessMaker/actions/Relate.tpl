{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@113775 *}

<table border="0" cellpadding="2" cellspacing="0" width="100%">
	<tr>{include file="Settings/ProcessMaker/actions/RelateRecord.tpl" RECORDPICK=$RECORDPICK1 ENTITY="1"}</tr>
	<tr id="record2_container">
	{if !empty($RECORDPICK2)}
		{include file="Settings/ProcessMaker/actions/RelateRecord.tpl" RECORDPICK=$RECORDPICK2 ENTITY="2"}
	{/if}
	</tr>
</table>
<br>
<select id='task-fieldnames' class="notdropdown" style="display:none;">
	<option value="">{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
</select>