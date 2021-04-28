{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@28295 crmv@36871 *}
<style type="text/css">
	{literal}
	.todobtn_image{
		background-size: 20px 20px;
		background-repeat: no-repeat;
		width: 20px;
		height: 20px;
		line-height: 20px;
		display: inline-block;
	}
	.todobtn_image_duration {
		{/literal}
		background-image:  url('{"todo_hourglass.png"|resourcever}');
		{literal}
	}
	.todobtn_image_calendar {
		{/literal}
		background-image:  url('{"todo_calendar.png"|resourcever}');
		{literal}
	}
	.todobtn {
		display: inline-block;
		width: 26px;
		height: 26px;
		padding: 0px;
		margin: 0px;
		border: none;
		border-top: 1px solid #c3c3c3;
		border-bottom: 1px solid #c3c3c3;
		background-color: #dddddd;
		cursor: pointer;
	}
	.todobtn_first {
		border-left: 1px solid #c3c3c3;
	}
	.todobtn_last {
		border-right: 1px solid #c3c3c3;
	}
	.todobtn:hover {
		background-color: #f0f0f0;
		border-color: #c0c0c0;
	}
	.todobtn_active {
		background-color: #c0c0c0;
		border-color: #909090;
	}
	{/literal}
</style>
<div id="todos" style="display:none;position:fixed;width:520px" class="crmvDiv">
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td style="padding:5px" class="level3Bg">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td style="width:56px">
						<div style="margin:0px;padding:0px">
							<button class="todobtn todobtn_active todobtn_first" id="todo_btn_date" onclick="todoShowByDate()" title="{$APP.TodoByDate|capitalize}"><span class="todobtn_image todobtn_image_calendar"></span></button><button class="todobtn todobtn_last" id="todo_btn_duration" onclick="todoShowByDuration()" title="{$APP.TodoByDuration|capitalize}"><span class="todobtn_image todobtn_image_duration"></span></button>
						</div>
					</td>
					<td style="width:4px">
						&nbsp;
					</td>
					<td id="Todos_Handle" style="cursor:move;">
						<b>{$APP.Todos} <span id="divTodo_bydate">{$APP.TodoByDate}</span><span id="divTodo_byduration" style="display:none">{$APP.TodoByDuration}</span></b>
					</td>
					<td align="right" width="180">
						{include file="LoadingIndicator.tpl" LIID="indicatorTodos" LIEXTRASTYLE="display:none;"}&nbsp;
						<input id="todos_button" type="button" value="{$APP.LBL_ALL}" name="button" class="crmbutton small edit" title="{$APP.LBL_ALL}" onClick="get_more_todos();">
						<input type="button" value="{$APP.LBL_CREATE}" name="button" class="crmbutton small create" title="{$APP.LBL_CREATE}" onClick="fninvsh('todos');NewQCreate('Calendar');">
					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div id="todos_div" class="crmvDivContent"></div>
	<div class="closebutton" onClick="fninvsh('todos');"></div>
</div>
<script>
	// crmv@192014
	jQuery("#todos").draggable({ldelim}
		handle: '#Todos_Handle'
	{rdelim});
	// crmv@192014e
</script>
{* crmv@28295e crmv@36871e *}