{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *}

{if !$disableStyle}
	{assign var="tableClass" value="table"}
{else}
	{assign var="tableClass" value=""}
{/if}

<table class="{$tableClass}" width="100%" cellpadding="5" cellspacing="0" border="0">
	<tr>
		<td width="30%" valign="top" align=right><b>{$MOD.LBL_USERS}</b></td>
		<td width="70%" align=left valign="top" >
			<!-- crmv@17001 : Inviti -->
			<table cellspacing="5" cellpadding="0">
				{foreach item=arr key=userid from=$INVITEDUSERS}
					{assign var=username value=$arr.0}
					{assign var=partecipation value=$arr.1}
					<tr>
						<td>{$username} ({$userid|getUserFullName})</td>
						{* crmv@20324 *}
						<td>
							{$MOD.LBL_CAL_INVITATION}:
							{if $CURRENT_USER eq $userid}
								<input type="radio" name="invite_partecipation_{$userid}" value="2" {if $partecipation eq 2}checked{/if} onClick="savePartecipation({$ID},{$userid},this.value)">{$APP.LBL_YES}
								<input type="radio" name="invite_partecipation_{$userid}" value="1" {if $partecipation eq 1}checked{/if} onClick="savePartecipation({$ID},{$userid},this.value)">{$APP.LBL_NO}
							{* <input type="radio" name="invite_partecipation_{$userid}" value="3" {if $partecipation eq 3}checked{/if} onClick="savePartecipation({$ID},{$userid},this.value)">{$MOD.LBL_MAYBE} *}
							{else}
								{if $partecipation eq 2}{$APP.LBL_YES}
								{elseif $partecipation eq 1}{$APP.LBL_NO}
								{elseif $partecipation eq 0}{$MOD.LBL_PENDING}
								{/if}
							{/if}
						</td>
						<td>
							<div style="float: right; padding-left: 10px;">
								<div id="loadingpannel" class="fbutton" style="display:none; color: gray;">{include file="LoadingIndicator.tpl"}</div>
								<div id="savedpannel" class="fbutton" style="display:none; color: green;"><img alt="saved!" src="{'enabled.gif'|resourcever}"/>&nbsp;{$MOD.LBL_SAVED}</div>
								<div id="errorpannel" class="fbutton" style="display:none; color: red;">{$LBL_ERROR}</div>
							</div>
						</td>
						{* crmv@20324e *}
					</tr>
				{/foreach}
			</table>
			<!-- crmv@17001e -->
		</td>
	</tr>
	{* crmv@26807 *}
	<tr>
		<td width="30%" valign="top" align=right><b>{$APP.LBL_CONTACT_TITLE}</b></td>
		<td width="70%" align=left valign="top" >
			<table cellspacing="5" cellpadding="0">
				{foreach item=arr key=userid from=$INVITEDCONTACTS}
					{assign var=username value=$arr.0}
					{assign var=partecipation value=$arr.1}
					<tr>
						<td>{$username}</td>
						<td colspan="2">
							{$MOD.LBL_CAL_INVITATION}:
							{if $partecipation eq 2}{$APP.LBL_YES}
							{elseif $partecipation eq 1}{$APP.LBL_NO}
							{elseif $partecipation eq 0}{$MOD.LBL_PENDING}
							{/if}
						</td>
					</tr>
				{/foreach}
			</table>
		</td>
	</tr>
	{* crmv@26807e *}
</table>