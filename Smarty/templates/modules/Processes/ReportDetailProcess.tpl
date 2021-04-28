{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@193096 *}

{include file='SmallHeader.tpl' HEADER_Z_INDEX=1 PAGE_TITLE="SKIP_TITLE" HEAD_INCLUDE="all" BUTTON_LIST_CLASS="navbar navbar-default"}

{SDK::checkJsLanguage()}	{* crmv@sdk-18430 *} {* crmv@181170 *}
{include file='CachedValues.tpl'}	{* crmv@26316 *}

{include file="modules/Processes/ProcessGraph.tpl"}
<script type="text/javascript">
jQuery('#ProcessGraph').show();
var data = JSON.parse('{$GRAPHINFO}');
{literal}
bpmnLoad(window.BpmnJS, data, function(){
	jQuery('#status').hide();
});
{/literal}
</script>