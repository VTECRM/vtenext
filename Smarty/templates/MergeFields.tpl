{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@168103 *}
{* crmv@193710 *}

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			{if $EDIT_DUPLICATE eq 'permitted'}
				<form name="merge" method="POST" action="index.php" id="form" onsubmit="if (validate_merge('{$MODULENAME}')) {ldelim} VteJS_DialogBox.block(); return true; {rdelim} else {ldelim} return false; {rdelim};">
					<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
					<input type=hidden name="module" value="{$MODULENAME}">
					<input type=hidden name="return_module" value="{$MODULENAME}">
					<input type="hidden" name="action" value="ProcessDuplicates">
					<input type="hidden" name="mergemode" value="mergesave">
					<input type="hidden" name="parent" value="{$PARENT_TAB}">
					<input type="hidden" name="pass_rec" value="{$IDSTRING}">
					<input type="hidden" name="return_action" value="FindDuplicateRecords">				
					<br>

					<div class="row">
						<div class="col-sm-12">
							<div class="alert alert-info">{$APP.LBL_DESC_FOR_MERGE_FIELDS}</div>
						</div>
					</div>

					<br>
					
					<div class="row">
						<div class="col-sm-12">
							<table class="vtetable">
								<thead>
									<tr>
										<th>{$APP.LBL_FIELDLISTS}</td>
										{assign var=count value=1}
										{assign var=cnt_rec value=0}
										{if $NO_EXISTING eq 1}
											{foreach key=cnt item=record from=$ID_ARRAY}
												<th>
													{$APP.LBL_RECORD}{$count}
													{if $count eq 1}
														<input name="record" value="{$record}" onclick="select_All('{$JS_ARRAY}','{$cnt}','{$MODULENAME}');" type="radio" checked>&nbsp;<span style="font-size:11px">{$APP.LBL_SELECT_AS_PARENT}</span>
													{else}
														<input name="record" value="{$record}" onclick="select_All('{$JS_ARRAY}','{$cnt}','{$MODULENAME}');" type="radio">&nbsp;<span style="font-size:11px">{$APP.LBL_SELECT_AS_PARENT}</span>
													{/if}
												</th>
												{assign var=cnt_rec value=$cnt_rec+1}
												{assign var=count value=$count+1}
											{/foreach}
										{else}
											{foreach key=cnt item=record from=$ID_ARRAY}
												<th>
													{$APP.LBL_RECORD}{$count}
													{assign var=found value=0}
													{foreach item=child key=k from=$IMPORTED_RECORDS}
														{if $record eq $child}
															{assign var=found value=1}
														{/if}
													{/foreach}
													{if $found eq 0}
														{if $count eq 1}
															<input name="record" value="{$record}" onclick="select_All('{$JS_ARRAY}','{$cnt}','{$MODULENAME}');" type="radio" checked>&nbsp;<span style="font-size:11px">{$APP.LBL_SELECT_AS_PARENT}</span>
														{else}
															<input name="record" value="{$record}" onclick="select_All('{$JS_ARRAY}','{$cnt}','{$MODULENAME}');" type="radio">&nbsp;<span style="font-size:11px">{$APP.LBL_SELECT_AS_PARENT}</span>
														{/if}
													{/if}
												</th>
												{assign var=cnt_rec value=$cnt_rec+1}
												{assign var=count value=$count+1}
											{/foreach}
										{/if}
									</tr>
								</thead>
								<tbody>
									{foreach item=data key=cnt from=$ALLVALUES}
										{foreach item=fld_array key=label from=$data}
											<tr>
												<td><b>{$label|@getTranslatedString:$MODULE}</b></td>
												{foreach item=fld_value key=cnt2 from=$fld_array}
													{if $fld_value.disp_value neq ''}
														{if $cnt2 eq 0}
															<td class="text-nowrap"><input name="{$FIELD_ARRAY[$cnt]}" value="{$fld_value.org_value}" type="radio" checked>&nbsp;{$fld_value.disp_value|truncate:30}</td>
														{else}
															<td class="text-nowrap"><input name="{$FIELD_ARRAY[$cnt]}" value="{$fld_value.org_value}" type="radio">&nbsp;{$fld_value.disp_value|truncate:30}</td>
														{/if}
													{else}
														{if $cnt2 eq 0}
															<td><input name="{$FIELD_ARRAY[$cnt]}" value="" type="radio" checked>{$APP.LBL_NONE}</td>
														{else}
															<td><input name="{$FIELD_ARRAY[$cnt]}" value="" type="radio">{$APP.LBL_NONE}</td>
														{/if}
													{/if}
												{/foreach}
											</tr>
										{/foreach}
									{/foreach}
								</tbody>
							</table>
						</div>
					</div>

					<div class="row">
						<div class="col-sm-12 text-center">
							<button type="submit" class="crmbutton save" name="button">{$APP.LBL_MERGE_BUTTON_LABEL}</button>
						</div>
					</div>
					
					<br>
				</form>
			{else}
				<div class="listview-emptylist-container">
					<div class="listview-emptylist">
						<div class="listview-emptylist-message">
							<div class="listview-emptylist-icon">
								<i class="vteicon">not_interested</i>
							</div>
							<div class="listview-emptylist-message-content">
								<div class="listview-emptylist-message-not-found">
									<span>
										{$APP.LBL_PERMISSION}
									</span>
								</div>
								<div class="listview-emptylist-message-action text-right">
									<a href='javascript:closePopup();'>{$APP.LBL_GO_BACK}</a>
									<br>
								</div>
							</div>
						</div>
					</div>
				</div>
			{/if}
		</div>
	</div>
</div>

{literal}
<script type="text/javascript">
	jQuery(document).ready(function() {
		loadedPopup();
	});
</script>
{/literal}

</body>
</html>