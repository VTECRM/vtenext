{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@3082m *}

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	{foreach name=messagesettings item=setting from=$LIST}
		{assign var=i value=$smarty.foreach.messagesettings.iteration}
		{if $i is odd}
	    	<tr>
	    {/if}
	    	<td width="50%">
	    		<div class="cpanel_div" style="cursor:pointer;" onClick="location.href='{$setting.link}';">
	    			<div class="cpanel_left_credits" align="center" {if !empty($setting.description)}style="padding-top:10px;"{else}style="padding-top:22px;"{/if}>{$setting.title}</div>
	    			{if !empty($setting.description)}<div style="padding-top:20px;">{$setting.description}</div>{/if}
	    		</div>
	    	</td>
		{if $i is even}
	    	</tr>
	    {/if}
    {/foreach}
</table>