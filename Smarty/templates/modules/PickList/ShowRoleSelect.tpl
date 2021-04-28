{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<b>{$MOD.LBL_SELECT_ROLES}</b><br>
<select multiple id="roleselect" name="roleselect" class="small crmFormList" style="overflow:auto; height: 80px;width:200px;border:1px solid #666666;font-family:Arial, Helvetica, sans-serif;font-size:11px;">
	{foreach item=rolename key=roleid from=$ROLES}
		<option value="{$roleid}">{$rolename}</option>
	{/foreach}
</select>