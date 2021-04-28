{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@82831 *}

{include file='QuickCreateHidden.tpl'}

<input type="hidden" name="return_module" value="Reports" />
<input type="hidden" name="return_action" value="SaveAndRun&tab=Charts" />
<input type="hidden" name="return_id" value="{$REPORTID}" />

<input type="hidden" name="reportid" value="{$REPORTID}" />
<input type="hidden" name="assigned_user_id" value="{$REPORT_OWNER}" />

<table border="0" align="center" cellspacing="0" cellpadding="0" width="100%">
	<tr>
		<td>
			<table border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr height="34">
					<td class="level3Bg">
						<table cellpadding="0" cellspacing="0" width="100%">
							<tr>
								<td width="100%" class="small" id="qcreate_handle"><b>{$APP.LBL_QUICK_CREATE} {$QCMODULE}</b></td>
								{if $QUICKCREATEPOPUP eq true}
									<td align=right>
										<button class="crmbutton save" onClick="if(SubmitQCForm('{$MODULE}',getObj('QcEditView'))) return true; else return false;" type="submit" name="button">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
									</td>
								{else}
									<td align=right>
										<button class="crmbutton save" type="submit" name="button">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
									</td>
								{/if}
							</tr>
						</table>
					</td>
				</tr>
			</table>
			
			<table border=0 cellspacing=0 cellpadding=0 width="100%">
				<tr>
					<td>
						<!-- quick create UI starts -->
						<table border="0" cellspacing="0" cellpadding="5" width="100%" class="small" bgcolor="white" >
							<tr>
								<td colspan="4">
									<table border="0" cellspacing="0" cellpadding="0" width="100%">
										<tr>
											<td><span style="font-weight:bold">&nbsp;{$APP.Type}&nbsp;</span></td>
										</tr>
										<tr>
											<td>
												{assign var="imageBack" value="chart_button_bg.png"}
												{foreach key=ckey item=ctype from=$CHART_TYPES}
													{assign var="imageName" value="chart_$ckey.png"}
													<button id="button_ctype_{$ckey}" name="button_ctype_{$ckey}" type="button" onclick="chartSelectType(this)" style="background:none;border:none">
														<div style="background-image: url('{$imageBack|@vtecrm_imageurl:$THEME}'); float:left;">
															<img src="{$imageName|@vtecrm_imageurl:$THEME}" alt="{$ctype}" border="0" />
														</div>
													</button>
												{/foreach}
												<input id="chart_type" name="chart_type" type="hidden" value="" />
											</td>
										</tr>
									</table>
								</td>
							</tr>
							{assign var="fromlink_val" value="qcreate"}
							{assign var="data" value=$QUICKCREATE}
							{include file='DisplayFields.tpl'}
						</table>
						<!-- save cancel buttons -->
						<table border="0" cellspacing="0" cellpadding="5" width="100%" class=qcTransport>
							<tr></tr>
						</table>
					</td>
			
					<td align="center" valign="middle" width="340" height="280">
						<div id="chart_create_preview">{$MOD.LBL_CHART_PREVIEW}</div>
						<div id="chart_create_preview_wait" style="display:none">
							{include file="LoadingIndicator.tpl"}
						</div>
					</td>
				</tr>
			</table>
			<div class="closebutton" onClick="VTE.hideModal('qcform');"></div> {* crmv@104740 *}
		</td>
	</tr>
</table>

{if $ACTIVITY_MODE eq 'Events'}
	<script type="text/javascript" id="qcvalidate">
		var qcfieldname = new Array('subject','date_start','time_start','eventstatus','activitytype','due_date','time_end');
		var qcfieldlabel = new Array('Subject','Start Date & Time','Start Date & Time','Status','Activity Type','End Date & Time','End Date & Time');
		var qcfielddatatype = new Array('V~M','DT~M~time_start','T~O','V~O','V~O','D~M~OTH~GE~date_start~Start Date & Time','T~M','DT~M~time_end');
		var qcfielduitype = new Array(1,6,1,15,15,23,1); //crmv@83877
		var qcfieldwstype = new Array('string','date','string','picklist','picklist','date','string'); //crmv@112297
	</script>
{elseif $MODULE eq 'Task'}
	<script type="text/javascript" id="qcvalidate">
		var qcfieldname = new Array('subject','date_start','time_start','taskstatus');
		var qcfieldlabel = new Array('Subject','Start Date & Time','Start Date & Time','Status');
		var qcfielddatatype = new Array('V~M','DT~M~time_start','T~O','V~O');
		var qcfielduitype = new Array(1,6,1,15); //crmv@83877
		var qcfieldwstype = new Array('string','date','string','picklist'); //crmv@112297
	</script>
{else}
	<script type="text/javascript" id="qcvalidate">
		var qcfieldname = new Array({$VALIDATION_DATA_FIELDNAME});
		var qcfieldlabel = new Array({$VALIDATION_DATA_FIELDLABEL});
		var qcfielddatatype = new Array({$VALIDATION_DATA_FIELDDATATYPE});
		var qcfielduitype = new Array({$VALIDATION_DATA_FIELDUITYPE}); // crmv@83877
		var qcfieldwstype = new Array({$VALIDATION_DATA_FIELDWSTYPE}); //crmv@112297
	</script>
{/if}

</form>

{* attach a listener on every input element *}
<script type="text/javascript">
	jQuery('#qcform form[name="QcEditView"] :input').change(generatePreview);
</script>