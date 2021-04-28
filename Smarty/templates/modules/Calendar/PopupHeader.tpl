{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 crmv@138006 *}

<table id="addEvent_Handle" border="0" cellspacing="0" cellpadding="5" width="100%">
	<tr>
		<td class="level3Bg" style="border:0px none">
			<table border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr>
					<td width="50%" align="left" valign="center" style="font-size:14px">
						<div id="headerTabCont">
							<ul id="header-tab" data-content="#header-tab-content" class="nav nav-tabs nopadding">
							    <li class="active">
							    	<a data-toggle="tab" href="#event-tab">{$MOD.LBL_ADD_EVENT}</a>
						    	</li>
							    <li>
							    	<a data-toggle="tab" href="#todo-tab">{$MOD.LBL_ADD_TODO}</a>
						    	</li>
					 	 	</ul>
				 	 	</div>
				 	 	<div id="headerTitleCont">
				 	 		<div style="text-indent:15px">
					 	 		<span class="dvHeaderText">
									<span class="recordTitle1"></span>
									<span class="recordTitle2"></span>
								</span>
							</div>
				 	 	</div>
					</td>
					<td width="50%" align="right">
						<input id="btnDetail" class="crmbutton edit" type="button" value="{$APP.LBL_SHOW_DETAILS}" />
						<input id="btnEdit" class="crmbutton edit success" type="button" value="{$MOD.LBL_EDIT}" />
						<input id="btnCloseActivity" class="crmbutton save" type="button" value="{$MOD.LBL_LIST_CLOSE} {$APP.Activity}" />
						<input id="btnSave" alt="{$APP.LBL_SAVE_BUTTON_TITLE}" title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey='S' type="submit" name="eventsave" class="crmbutton save success" value="{$MOD.LBL_SAVE}">
						<input id="btnCancel" alt="{$APP.LBL_CANCEL_BUTTON_TITLE}" title="{$APP.LBL_CANCEL_BUTTON_TITLE}" type="button" class="crmbutton cancel" name="eventcancel" value="{$MOD.LBL_DEL}">
					</td>
				</tr>
			</table>
	  	</td>
	</tr>
</table>

<script type="text/javascript">
	var deleteString = "{$MOD.LBL_DEL}";
	var cancelString = "{$APP.LBL_CANCEL_BUTTON_LABEL}";
</script>