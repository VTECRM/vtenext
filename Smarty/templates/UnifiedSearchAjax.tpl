{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@187493 *}

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<div id="global_list_{$MODULE}" style="display:{$DISPLAY}">
				<form name="massdelete" method="POST">
					<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
					<input name="idlist" type="hidden">
					<input name="change_owner" type="hidden">
					<input name="change_status" type="hidden">
					<input name="search_criteria" type="hidden" value="{$SEARCH_STRING}">
					<input name="module" type="hidden" value="{$MODULE}" />
					<input name="{$MODULE}RecordCount" id="{$MODULE}RecordCount" type="hidden" value="{$ModuleRecordCount.$MODULE.count}" />
				
					<table border=0 cellspacing=0 cellpadding=2 width=100% class="small">
						<tr>
							{assign var="MODULELABEL" value=$MODULE|@getTranslatedString:$MODULE}	<!-- crmv@16886 -->
							<td style="padding-right:20px" nowrap ><b class=big>{$MODULELABEL}</b>{$SEARCH_CRITERIA}</td>					
							{* crmv@126908 - avoid php warning illegal string offset *}
							<td style="padding-right:20px" class="small" align="right" nowrap>
								{if !empty($ModuleRecordCount.$MODULE.recordListRangeMessage)}{$ModuleRecordCount.$MODULE.recordListRangeMessage}{/if}
							</td>
							{* crmv@126908e *}
							<td nowrap width="50%">
								<table border=0 cellspacing=0 cellpadding=0 class="small">
									<tr>{$NAVIGATION}</tr>
								</table>
							</td>					
						</tr>
					</table>
					<div class="searchResults">
						<table border=0 cellspacing=0 cellpadding=3 width=100% class="vtetable">
							<thead>
								<tr>
									{if $DISPLAYHEADER eq 1}
										{foreach item=header from=$LISTHEADER}
											<td class="mailSubHeader">{$header}</td>
										{/foreach}
									{else}
										<td class="searchResultsRow" colspan=$HEADERCOUNT> {$APP.LBL_NO_DATA} </td>
									{/if}
								</tr>
							</thead>
							<tbody>
								{foreach item=entity key=entity_id from=$LISTENTITY}
									{assign var=color value=$entity.clv_color}
									{assign var=foreground value=$entity.clv_foreground}
									{assign var=cell_class value="listview-cell listview-cell-simple popupLinkListDataCell"}
					
									{if !empty($foreground)}
										{assign var=cell_class value=$cell_class|cat:" color-`$foreground`"}
									{/if}
					
									<tr id="row_{$entity_id}">
										{foreach key=colname item=data from=$entity}    
											{if ($colname neq 'clv_color' and $colname neq 'clv_status' and $colname neq 'clv_foreground') or $colname eq '0'}
												<td bgcolor="{$color}" class="{$cell_class}">{$data}</td>
											{/if}		
										{/foreach}
									</tr>
								{/foreach}
							</tbody>
						</table>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>