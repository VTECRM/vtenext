{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@146670 crmv@146671 *}

{*<div>
	<p>{$MOD.LBL_EXTWS_EDIT_INTRO}</p>
</div>
*}
<br><br>

<table border="0" width="100%">
	<tr>
		<td align="right" width="90%">
			<span id="extws_busy" style="display:none;">{include file="LoadingIndicator.tpl"}</span>
		</td>
		<td align="right" nowrap="">
			<button title="{$MOD.LBL_TEST_REQUEST}" class="crmbutton small save" type="button" onclick="ExtWSConfig.testWS()">{$MOD.LBL_TEST_REQUEST}</button>
			<button title="{$APP.LBL_SAVE_BUTTON_LABEL}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton small save" type="submit" onclick="return ExtWSConfig.validateAndSave()">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
			<button title="{$APP.LBL_CANCEL_BUTTON_LABEL}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton small cancel" type="button" onclick="window.history.back()">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
		</td>
	</tr>
</table>
<br>

<table border="0" width="100%">

	<tr>
		<td align="right" width="15%"><span>{$MOD.LBL_EXTWS_NAME}</span>&nbsp;&nbsp;</td>
		<td align="left" width="500">
			<div class="dvtCellInfoM">
				<input class="detailedViewTextBox" type="text" name="extws_name" id="extws_name" value="{$WSINFO.wsname}" maxlength="60">
			</div>
		</td>
		<td width="40">&nbsp;</td>
		<td>
			{$MOD.LBL_EXTWS_NAME_DESC}
		</td>
	</tr>
	
	<tr>
		<td align="right"><span>{$APP.LBL_DESCRIPTION}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<textarea class="detailedViewTextBox" name="extws_desc" id="extws_desc" rows="5" />{$WSINFO.wsdesc}</textarea>
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			&nbsp;
		</td>
	</tr>
	
	<tr>
		<td align="right"><span>{$MOD.LBL_EXTWS_ACTIVE}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<input type="checkbox" name="extws_active" id="extws_active" {if $WSINFO.active || $MODE eq 'create'}checked=""{/if} />
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_EXTWS_ACTIVE_DESC}
		</td>
	</tr>
	
	<tr>
		<td align="right"><span>{$APP.LBL_TYPE}</span>&nbsp;&nbsp;</td>
		<td align="left" width="250">
			<div class="dvtCellInfo">
				<select class="detailedViewTextBox" name="extws_type" id="extws_type">
					{foreach item=TYPE from=$WSTYPES}
						<option value="{$TYPE}" {if $WSINFO.wstype eq $TYPE}selected=""{/if}>{$TYPE}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_EXTWS_TYPE_DESC}
		</td>
	</tr>
	
	<tr>
		<td align="right"><span>{$MOD.LBL_REQUEST_METHOD}</span>&nbsp;&nbsp;</td>
		<td align="left" width="250">
			<div class="dvtCellInfo">
				<select class="detailedViewTextBox" name="extws_method" id="extws_method">
					{foreach item=METHOD from=$WSMETHODS}
						<option value="{$METHOD}" {if $WSINFO.method eq $METHOD}selected=""{/if}>{$METHOD}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_REQUEST_METHOD_DESC}
		</td>
	</tr>
	
	<tr>
		<td align="right"><span>{$MOD.LBL_EXTWS_ENDPOINT}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfoM">
				<input class="detailedViewTextBox" type="text" name="extws_url" id="extws_url" value="{$WSINFO.wsurl}" maxlength="1000">
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_EXTWS_ENDPOINT_DESC}
		</td>
	</tr>

	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	
	<tr>
		<td align="right"><span>{$MOD.LBL_AUTHENTICATION}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				{* crmv@167234 *}
				<button class="crmbutton small edit" id="auth_button" type="button" onclick="ExtWSConfig.showAuth()" {if isset($WSINFO.authinfo.username) && $WSINFO.authinfo.username neq ''}style="display:none"{/if}>{$MOD.LBL_SET_AUTHENTICATION}</button>
				<table id="auth_table" {if !isset($WSINFO.authinfo.username) || $WSINFO.authinfo.username eq ''}style="display:none"{/if} width="100%" cellspacing="5"">
					<tr>
						<td class="extws_subfield_header" width="40%">{$APP.LBL_LIST_USER_NAME}</td>
						<td class="extws_subfield_header">{$MOD.LBL_LIST_PASSWORD}</td>
						<td>
					</tr>
					<tr>
						<td><input class="detailedViewTextBox" type="text" id="extws_auth_username" maxlength="60" value="{if isset($WSINFO.authinfo.username)}{$WSINFO.authinfo.username}{/if}"></td>
						<td><input class="detailedViewTextBox" type="password" id="extws_auth_password" value="{if isset($WSINFO.authinfo.password)}{$WSINFO.authinfo.password}{/if}"></td>
						<td>
							<i class="vteicon md-link md-sm" id="auth_del_button" onclick="ExtWSConfig.hideAuth()">delete</i>
						</td>
					</tr>
					<tr>
				</table>
				{* crmv@167234e *}
				<input type="hidden" name="extws_auth" id="extws_auth" value="">
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			
		</td>
	</tr>
	
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	
	<tr>
		<td align="right"><span>{$MOD.LBL_HEADERS}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<table id="headers_table" {if !is_array($WSINFO.headers) || count($WSINFO.headers) == 0}style="display:none"{/if} width="100%" cellspacing="5"> {* crmv@167234 *}
					<tr>
						<td class="extws_subfield_header" width="40%">{$MOD.LBL_HEADER_NAME}</td>
						<td class="extws_subfield_header">{$MOD.LBL_HEADER_VALUE}</td>
						<td></td>
					</tr>
					<tr style="display:none" id="header_row_tpl">
						<td><input class="detailedViewTextBox headername" type="text" maxlength="60"></td>
						<td><input class="detailedViewTextBox headervalue" type="text"></td> {* crmv@OPER10174 *}
						<td>
							<i class="vteicon md-link md-sm" onclick="ExtWSConfig.delHeader(this)">delete</i>
						</td>
					</tr>
					{foreach item=HEADER from=$WSINFO.headers}
						<tr>
							<td><input class="detailedViewTextBox headername" type="text" maxlength="60" value="{$HEADER.name}"></td>
							<td><input class="detailedViewTextBox headervalue" type="text" value="{$HEADER.value}"></td> {* crmv@OPER10174 *}
							<td>
								<i class="vteicon md-link md-sm" onclick="ExtWSConfig.delHeader(this)">delete</i>
							</td>
						</tr>
					{/foreach}
				</table>
				<button class="crmbutton small edit" id="add_header" type="button" onclick="ExtWSConfig.addHeader()">{$MOD.LBL_ADD_HEADER}</button>
				<input type="hidden" name="extws_headers" id="extws_headers" value="">
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			&nbsp;
		</td>
	</tr>
	
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	
	<tr>
		<td align="right"><span>{$MOD.LBL_PARAMETERS}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<table id="params_table" {if !is_array($WSINFO.params) || count($WSINFO.params) == 0}style="display:none"{/if} width="100%" cellspacing="5"> {* crmv@167234 *}
					<tr>
						<td class="extws_subfield_header" width="40%">{$MOD.LBL_PARAMETER_NAME}</td>
						<td class="extws_subfield_header">{$MOD.LBL_PARAMETER_VALUE}</td>
						<td></td>
					</tr>
					<tr style="display:none" id="param_row_tpl">
						<td><input class="detailedViewTextBox paramname" type="text" maxlength="60"></td>
						<td><input class="detailedViewTextBox paramvalue" type="text"></td> {* crmv@OPER10174 *}
						<td>
							<i class="vteicon md-link md-sm" onclick="ExtWSConfig.delParam(this)">delete</i>
						</td>
					</tr>
					{foreach item=PARAM from=$WSINFO.params}
						<tr>
							<td><input class="detailedViewTextBox paramname" type="text" maxlength="60" value="{$PARAM.name}"></td>
							<td><input class="detailedViewTextBox paramvalue" type="text" value="{$PARAM.value}"></td> {* crmv@OPER10174 *}
							<td>
								<i class="vteicon md-link md-sm" onclick="ExtWSConfig.delParam(this)">delete</i>
							</td>
						</tr>
					{/foreach}
				</table>
				<button class="crmbutton small edit" id="add_param" type="button" onclick="ExtWSConfig.addParam()">{$MOD.LBL_ADD_PARAMETER}</button>
				<input type="hidden" name="extws_params" id="extws_params" value="">
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			&nbsp;
		</td>
	</tr>
	
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	
	{* crmv@190014 *}
	<tr>
		<td align="right"><span>{$MOD.LBL_EXTWS_RAWBODY}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<textarea class="detailedViewTextBox" type="text" name="extws_rawbody" id="extws_rawbody" rows="10">{$WSINFO.rawbody}</textarea>
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			&nbsp;
		</td>
	</tr>

	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	{* crmv@190014e *}
	
	<tr>
		<td align="right"><span>{$MOD.LBL_EXTWS_RESULTS}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<table id="results_table" {if !is_array($WSINFO.results) || count($WSINFO.results) == 0}style="display:none"{/if} width="100%" cellspacing="5"> {* crmv@167234 *}
					<tr>
						<td class="extws_subfield_header" width="40%">{$MOD.LBL_EXTWS_RESULT_NAME}</td>
						<td class="extws_subfield_header">{$MOD.LBL_EXTWS_RESULT_VALUE}</td>
						<td></td>
					</tr>
					<tr style="display:none" id="result_row_tpl">
						<td><input class="detailedViewTextBox paramname" type="text" maxlength="60"></td>
						<td><input class="detailedViewTextBox paramvalue" type="text" maxlength="200"></td>
						<td>
							<i class="vteicon md-link md-sm" onclick="ExtWSConfig.delResult(this)">delete</i>
						</td>
					</tr>
					{foreach item=RESULT from=$WSINFO.results}
						<tr>
							<td><input class="detailedViewTextBox paramname" type="text" maxlength="60" value="{$RESULT.name}"></td>
							<td><input class="detailedViewTextBox paramvalue" type="text" maxlength="200" value="{$RESULT.value}"></td>
							<td>
								<i class="vteicon md-link md-sm" onclick="ExtWSConfig.delResult(this)">delete</i>
							</td>
						</tr>
					{/foreach}
				</table>
				<button class="crmbutton small edit" id="add_result" type="button" onclick="ExtWSConfig.addResult()">{$MOD.LBL_EXTWS_ADD_RESULT}</button>
				<input type="hidden" name="extws_results" id="extws_results" value="">
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_EXTWS_RESULTS_DESC}
		</td>
	</tr>
	
</table>