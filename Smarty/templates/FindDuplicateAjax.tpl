{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@36508 *}
{* crmv@193710 *}

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">

			<div class="row">
				<div class="col-sm-12 page-heading">
					<h4>{$APP.LBL_DUPLICATE_DATA_IN} {$MOD.LBL_MODULE_NAME}</h4>
				</div>
			</div>

			<br>

			<div class="row">
				<div class="col-sm-4 text-left">
					{if $DELETE neq ''}
						<button type="button" class="crmbutton delete" onclick="return delete_fields('{$MODULE}');">{$APP.LBL_DELETE}</button>
					{/if}
				</div>
				<div class="col-sm-8 text-center">
					<table>
						<tr>
							<td>{$NAVIGATION}</td>
							<td>{$APP.LBL_TOTAL} : {$NOOFROWS}</td>
						</tr>
						<input type="hidden" value="{$CURRENT_PAGE}" id="current_page" name="current_page">
					</table>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-12">
					<table class="vtetable">
						<thead>
							<tr>
								<th width="40px">
									<input type="checkbox" name="CheckAll" onclick="selectAllDel(this.checked,'del');" />
								</th>
								{foreach key=key item=field_val_ues from=$FIELD_NAMES}
									<th>
										{$key|@getTranslatedString:$MODULE}
									</th>
								{/foreach}
								<th>
									{$APP.LBL_MERGE_SELECT}
								</th>
								<th width="120px">
									{$APP.LBL_ACTION}
								</th>
							</tr>
						</thead>
						<tbody>
							{assign var=tdclass value='sep2'}
							{foreach key=key1 item=data from=$ALL_VALUES}
								{assign var=cnt value=$data|@sizeof}
								{assign var=cnt2 value=0}
								{if $tdclass eq 'sep1'}
									{assign var=tdclass value='sep2'}
								{else if $tdclass eq 'sep2'}
									{assign var=tdclass value='sep1'}
								{/if}
								{foreach key=key3 item=newdata1 from=$data}
									<tr class="{$tdclass}">
										<td>
											<input type="checkbox" name="del" value="{$data.$key3.recordid}" onclick="selectDel(this.name,'CheckAll');" />
										</td>
										{foreach key=key item=newdata2 from=$newdata1}
											<td>
												{if $key eq 'recordid'}
													<a href="index.php?module={$MODULE}&action=DetailView&record={$data.$key3.recordid}&parenttab={$CATEGORY}" target="_blank">{$newdata2}</a>
												{else}
													{if $key eq 'Entity Type'}
														{if $newdata2 eq 0 && $newdata2 neq NULL}
															{if $VIEW eq true}
																{$APP.LBL_LAST_IMPORTED}
															{else}
																{$APP.LBL_NOW_IMPORTED}
															{/if}
														{else}
															{$APP.LBL_EXISTING}
														{/if}
													{else}
														{$newdata2}
													{/if}
												{/if}
											</td>
										{/foreach}
										<td class="text-nowrap" width="80px">
											<input name="{$key1}" id="{$key1}" value="{$data.$key3.recordid}" type="checkbox" />
										</td>
										{if $cnt2 eq 0}
											<td align="center" rowspan="{$cnt}">
												<button type="button" class="crmbutton edit" name="merge" onclick="merge_fields('{$key1}','{$MODULE}','{$CATEGORY}');">{$APP.LBL_MERGE}</button>
											</td>
										{/if}
										{assign var=cnt2 value=$cnt2+1}
									</tr>
								{/foreach}
							{/foreach}
						</tbody>
					</table>
				</div>
			</div>

			<div name="group_count" id="group_count" style="display:none;">
				{$NUM_GROUP}
			</div>
		</div>
	</div>
</div>