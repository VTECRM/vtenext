{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
<script type="text/javascript" src="modules/Messages/Settings/Settings.js"></script>

{if !empty($ACCOUNTS)}
	{if $ACCOUNTS|@count eq 1}
		{assign var=style value="display:none;"}
	{/if}
	<span class="small" style="{$style}">{'LBL_ACCOUNTS'|getTranslatedString:'Messages'}</span>
	<select id="accountspicklist" name="accountspicklist" class="detailedViewTextBox input-inline" style="{$style}" {if !empty($JS_FUNCT)}onChange="{$JS_FUNCT}()"{/if}>
		{foreach item=ACCOUNT from=$ACCOUNTS}
			<option value="{$ACCOUNT.id}" {if $SEL_ACCOUNT eq $ACCOUNT.id}selected{/if}>{if !empty($ACCOUNT.description)}{$ACCOUNT.description}{else}{$ACCOUNT.server}{/if}</option>
		{/foreach}
	</select>
{/if}