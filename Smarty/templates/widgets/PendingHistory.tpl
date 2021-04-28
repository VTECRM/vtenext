{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr valign="top">
	<td width="50%">
		{if !empty($HISTORY)}
			<table border="0" cellpadding="5" cellspacing="0" width="100%" class="hdrNameBg">
				{foreach item=info from=$HISTORY}
					<tr>
						{foreach item=value from=$info}
							<td class="trackerList small">{$value}</td>
						{/foreach}
					</tr>
				{/foreach}
			</table>
		{/if}
	</td>
	<td width="50%">
		{if !empty($PENDING)}
			<table border="0" cellpadding="5" cellspacing="0" width="100%" class="hdrNameBg">
				{foreach item=info from=$PENDING}
					<tr>
						{foreach item=value from=$info}
							<td class="trackerList small">{$value}</td>
						{/foreach}
					</tr>
				{/foreach}
			</table>
		{/if}
	</td>
</tr>		
</table>