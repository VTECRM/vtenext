{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

<div class="hide_tab" id="editRowmodrss_{$HOME_STUFFID}" {if {$HOME_STUFFTYPE} eq 'URL'} style="position: absolute;" {/if}> {* crmv@204903 *}
	<table width="100%" border="0" cellpadding="0" cellspacing="0" valign="top">
		<tr>
			{if $HOME_STUFFTYPE eq "Module" || $HOME_STUFFTYPE eq "RSS" || $HOME_STUFFTYPE eq "Default"}	
				<td class="homePageMatrixHdr text-left text-nowrap" width="40%">
					{$MOD.LBL_HOME_SHOW}&nbsp;
					<select id="maxentries_{$HOME_STUFFID}" name="maxid" class="detailedViewTextBox input-inline" style="width:40px;">
						{section name=iter start=1 loop=13 step=1}
							<option value="{$smarty.section.iter.index}" {if $HOME_STUFF.Maxentries==$smarty.section.iter.index}selected{/if}>
								{$smarty.section.iter.index}
							</option>
						{/section}
					</select>&nbsp;{$MOD.LBL_HOME_ITEMS}
				</td>
				<td class="homePageMatrixHdr text-right text-nowrap" width="60%">
					<button type="button" name="save" class="crmbutton save" onclick="VTE.Homestuff.saveEntries('maxentries_{$HOME_STUFFID}')">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
					<button type="button" name="cancel" class="crmbutton cancel" onclick="VTE.Homestuff.cancelEntries('editRowmodrss_{$HOME_STUFFID}')">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
				</td>
			{elseif $HOME_STUFFTYPE eq "URL"}
					<td class="homePageMatrixHdr" width="60%">
						<input type="text" class="detailedViewTextBox" id="url_{$HOME_STUFFID}" name="url" value="{$URL}">
					</td>
				</tr>
				<tr>
					<td class="homePageMatrixHdr text-nowrap" width="40%">
						<button type="button" name="save" class="crmbutton save" onclick="VTE.Homestuff.saveEditurl('url_{$HOME_STUFFID}')">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
						<button type="button" name="cancel" class="crmbutton cancel" onclick="VTE.Homestuff.cancelEntries('editRowmodrss_{$HOME_STUFFID}')">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
					</td>
				</tr>		
			{/if}
		</tr>
	</table>
</div>

{if $HOME_STUFFTYPE eq "Module"}
	<input type="hidden" id="more_{$HOME_STUFFID}" value="{$HOME_STUFF.ModuleName}">
	<input type="hidden" id="cvid_{$HOME_STUFFID}" value="{$HOME_STUFF.cvid}">

	{if is_array($HOME_STUFF.Entries) && $HOME_STUFF.Entries|@count > 0} {* crmv@172864 *}
		<table class="vtetable">
			{assign var='cvid' value=$HOME_STUFF.cvid}
			{assign var='modulename' value=$HOME_STUFF.ModuleName}
			<thead>
				<tr>
					{foreach item=header from=$HOME_STUFF.Header}
						<th align="left">{$header}</th>
					{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach item=row key=crmid from=$HOME_STUFF.Entries}
					<tr>
						{assign var=color value=$row.clv_color}
						{assign var=foreground value=$row.clv_foreground}
						{assign var=cell_class value="listview-cell"}
						
						{if !empty($foreground)}
							{assign var=cell_class value=$cell_class|cat:" color-`$foreground`"}
						{/if}
						
						{foreach key=index item=element from=$row}
							{if ($index neq 'clv_color' and $index neq 'clv_status' and $index neq 'clv_foreground') or $index eq '0'} {* crmv@108022 *}
								<td bgcolor="{$color}" class="{$cell_class}" align="left">
									{$element}
								</td>
							{/if}
						{/foreach}
					</tr>
				{/foreach}
			</tbody>
		</table>
	{else}
		<div class="componentName">{$APP.LBL_NO_DATA}</div>
	{/if}
{elseif $HOME_STUFFTYPE eq "Default"}
	<input type="hidden" id="more_{$HOME_STUFFID}" value="{$HOME_STUFF.Details.ModuleName}">

	{if is_array($HOME_STUFF.Details.Entries) && $HOME_STUFF.Details.Entries|@count > 0} {* crmv@172864 *}
		<table class="vtetable">
			<thead>
				<tr>
					{foreach item=header from=$HOME_STUFF.Details.Header}
						<th align="left">{$header}</th>
					{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach item=row key=crmid from=$HOME_STUFF.Details.Entries}
					<tr>
						{foreach item=element from=$row}
							<td align="left">{$element}</td>
						{/foreach}
					</tr>
				{/foreach}
			</tbody>
		</table>
	{else}
		<div class="componentName">{$APP.LBL_NO_DATA}</div>
	{/if}
{elseif $HOME_STUFFTYPE eq "RSS"}
	<input type="hidden" id="more_{$HOME_STUFFID}" value="{$HOME_STUFF.Entries.More}">

	<table class="vtetable">
		<tbody>
			{foreach item="details" from=$HOME_STUFF.Entries.Details}
				<tr>
					<td align="left">
						<a href="{$details.1}" target="_blank">{$details.0|truncate:50}...</a>
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
{elseif $HOME_STUFFTYPE eq "DashBoard"}
	<input type="hidden" id="more_{$HOME_STUFFID}" value="{$DASHDETAILS[$HOME_STUFFID].DashType}">

	<table border=0 cellspacing=0 cellpadding=5 width=100%>
		<tr>
			<td align="left">{$HOME_STUFF}</td>
		</tr>
	</table>
{/if}

{if $HOME_STUFF.Details|@is_array == 'true'}
<input id="search_qry_{$HOME_STUFFID}" name="search_qry_{$HOME_STUFFID}" type="hidden" value="{$HOME_STUFF.Details.search_qry}">
{/if}