{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{include file='QuickCreateHidden.tpl'}

<table border="0" align="center" cellspacing="0" cellpadding="0" width="100%">
	<tr>
		<td>
			<table border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr height="34">
					<td class="level3Bg">
						<table cellpadding="0" cellspacing="0" width="100%">
							<tr>
								<td width="100%" id="qcreate_handle"><b>{$APP.LBL_QUICK_CREATE} {$QCMODULE}</b></td> {* crmv@30014 *}
								{if $QUICKCREATEPOPUP eq true}
									<td align=right>
										<button class="crmbutton save" onClick="if(SubmitQCForm('{$MODULE}',getObj('QcEditView'))) return true; else return false;" type="submit" name="button">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
									</td> {* crmv@59091 *}
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
						<table border="0" cellspacing="0" cellpadding="5" width="100%" class="small" bgcolor="white">
							{assign var="fromlink_val" value="qcreate"}
							{assign var="data" value=$QUICKCREATE}
							{include file='DisplayFields.tpl'}
						</table>
						<!-- save cancel buttons -->
						<table border="0" cellspacing="0" cellpadding="5" width="100%" class=qcTransport>
							<tr></tr>
						</table>
					</td>
				</tr>
			</table>
			<div class="closebutton" onClick="VTE.hideModal('qcform');"></div>
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