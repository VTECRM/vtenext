{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@43942 crmv@54707 *}

<table border="0" cellspacing="0" cellpadding="1" width="100%" class="small">
	<tr>
		<td>
			<table border=0 cellspacing=0 cellpadding=0 width=100%>
				<tr valign="top">
					<td rowspan="3">
						<img src="{$module.img}" style="cursor:pointer;width:70px;" onClick="goToListView('{$module.list_url}',false);" />
					</td>
					<td class="hdrLink" style="width:100%;padding:10px 5px 5px 5px;">
						<a href="javascript:;" style="cursor:pointer;" onClick="goToListView('{$module.list_url}',false);">{$module.translabel}</a>
					</td>
				</tr>
				<tr>
					<td></td>
				</tr>
				<tr valign="bottom">
					<td style="padding:5px 5px 10px 5px;">
						{if !empty($module.create_url)}
							<a href="{$module.create_url}">{'LBL_CREATE'|getTranslatedString}</a>
						{/if}
						&nbsp;
						{if !empty($module.list_url)}
							<a href="javascript:;" onClick="goToListView('{$module.list_url}',false);">{'LBL_FULL_LIST'|getTranslatedString}</a>
						{/if}
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			{assign var=ENTRIES value=$AREAMODULELIST[$module.tabid].entries}
			{if !empty($ENTRIES)}
				<table border="0" cellspacing="0" cellpadding="5" width="100%" class="rptTable">
					<tr class="reportRowTitle">
						<td class="rptCellLabel">{$AREAMODULELIST[$module.tabid].header}</td>
					</tr>
				</table>
				<table border="0" cellspacing="0" cellpadding="5" width="100%" class="rptTable">
					{foreach name=areamodulelistentries item=entry from=$ENTRIES}
						<tr class="reportRow{if $smarty.foreach.areamodulelistentries.iteration % 2 == 0}1{else}0{/if}">
							<td class="rptData">
								{assign var=areamodulelistentry1 value=""}
								{assign var=areamodulelistentry2 value=""}
								{foreach name=areamodulelistentry key=id item=value from=$entry}
									{if $smarty.foreach.areamodulelistentry.iteration-1|@in_array:$AREAMODULELIST[$module.tabid].name_field_position}
										{if empty($areamodulelistentry1)}
											{assign var=areamodulelistentry1 value=$value}
										{else}
											{assign var=areamodulelistentry1 value=$areamodulelistentry1|cat:" "|cat:$value}
										{/if}
									{elseif $value neq ''}
										{if empty($areamodulelistentry2)}
											{assign var=areamodulelistentry2 value=$value}
										{else}
											{assign var=areamodulelistentry2 value=$areamodulelistentry2|cat:", "|cat:$value}
										{/if}
									{/if}
								{/foreach}
								{* TODO stop propagation of clicks *}
								{* <div style="cursor:pointer;" onClick="location.href=jQuery(this).children('.listMessageSubject').children('a').attr('href');console.log('esterno');"> *}
								<div>
									<div class="listMessageSubject" style="font-weight:bold;">{$areamodulelistentry1}</div>
									<div class="gray linkNoPropagate">{$areamodulelistentry2}</div>
								</div>
							</td>
						</tr>
					{/foreach}
					{if $AREAMODULELIST[$module.tabid].show_other_button eq true}
						{math equation="(x+y)%z" x=$smarty.foreach.areamodulelistentries.total y=1 z=2 assign=othercount}
						<tr class="reportRow{if $othercount eq 0}1{else}0{/if}" style="cursor:pointer;" onClick="goToListView('{$module.list_url}',true);">
							<td class="rptData" align="right">
								{$APP.LBL_OTHERS} >>
							</td>
						</tr>
					{/if}
				</table>
			{else}
				<table border="0" cellspacing="0" cellpadding="5" width="100%" class="rptTable">
					<tr class="reportRowTitle"><td class="rptCellLabel">{$APP.LBL_NO_M} {$APP.LBL_RECORDS} {$APP.LBL_FOUND}</td></tr>
				</table>
			{/if}
		</td>
	</tr>
</table>
{* TODO stop propagation of clicks
<script type="text/javascript">
{literal}
jQuery('.linkNoPropagate a').each(function(k,v) {
	if ((jQuery(this).attr('href') != '' && jQuery(this).attr('target') == '_blank') || jQuery(this).attr('onclick') != null) {
	
		jQuery(this).click(function(e) {
		
			console.log('onclick:',jQuery(this).attr('onclick'));
			console.log('href:',jQuery(this).attr('href'));
			console.log('target:',jQuery(this).attr('target'));
		
			e.stopPropagation();
			return false;
		});
	}
});
{/literal}
</script>
*}