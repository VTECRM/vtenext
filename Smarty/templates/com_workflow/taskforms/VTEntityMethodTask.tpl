{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script type="text/javascript" charset="utf-8">
var moduleName = '{$entityName}';
var methodName = '{$task->methodName}';
{literal}
	function entityMethodScript($){
		
		function jsonget(operation, params, callback){
			var obj = {
					module:'com_workflow',
					action:'com_workflowAjax',
					file:operation, ajax:'true'};
			$.each(params,function(key, value){
				obj[key] = value;
			});
			$.get('index.php', obj, 
				function(result){
					//crmv@18199
					var parsed = JSON.parse(result);
					callback(parsed);
					//crmv@18199e
			});
		}
		
		
		$(document).ready(function(){
			jsonget('entitymethodjson', {module_name:moduleName}, function(result){
				$('#method_name_select_busyicon').hide();
				if(result.length==0){
					$('#method_name_select').hide();
					$('#message_text').show();
				}else{					
					$('#method_name_select').show();
					$('#message_text').hide();
					$.each(result, function(i, v){
						var optionText = '<option value="'+v+'" '+(v==methodName?'selected':'')+'>'+v+'</option>';
						$('#method_name_select').append(optionText);
					});
				}
			});
		});
	}
{/literal}
entityMethodScript(jQuery);
</script>

<table border="0" cellpadding="0" cellspacing="5" width="100%" class="small">
	<tr valign="middle">
		<td class='dvtCellLabel' align="right" width=15% nowrap="nowrap">{$MOD.LBL_METHOD_NAME}</td>
		<td class='dvtCellInfo'>
			<span id="method_name_select_busyicon"><b>{$MOD.LBL_LOADING}</b>{include file="LoadingIndicator.tpl"}</span>
			<select name="methodName" id="method_name_select" class="detailedViewTextBox" style="display: none;"></select>
			<span id="message_text" style="display: none;">{$MOD.LBL_NO_METHOD_AVAILABLE}</sspan>
		</td>
	</tr>
</table>