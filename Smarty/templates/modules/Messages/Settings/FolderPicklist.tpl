{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
<script type="text/javascript" src="modules/Messages/Settings/Settings.js"></script>

{if !empty($FOLDER_LIST)}
	<select name="folder" class="detailedViewTextBox input-inline">
		{foreach key=value item=info from=$FOLDER_LIST}
    		<option value="{$value}" {if $SEL_FOLDER eq $value}selected{/if}>{$value}</option>
    	{/foreach}
	</select>
{/if}