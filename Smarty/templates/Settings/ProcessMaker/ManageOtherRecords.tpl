{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{include file="SmallHeader.tpl"}

<style type="text/css">
	{literal}
	html, body {
		height: 95%; /* leave space for top panel */
	}
	#linkMsgMainTab {
		width:100%;
		/*height: 100%;
		position: absolute;*/
		z-index: -10;
	}
	#linkMsgLeftPane {
		width:20%;
		min-width:200px;
		border-right: 2px solid #e0e0e0;
		vertical-align: top;
	}
	#linkMsgRightPane {
		min-width:400px;
		vertical-align: top;
		padding:8px;
	}
	#linkMsgRightPaneTop {
		vertical-align: top;
		padding-bottom: 2px;
	}
	#linkMsgModTab {
		width:100%;
	}
	.linkMsgModTd {
		margin:2px;
		padding:10px;
		font-weight: 700;
		background-color: #f0f0f0;
		cursor: pointer;
	}
	.linkMsgModTd:hover {
		background-color: #e0e0e0;
	}
	.linkMsgModTdSelected {
		background-color: #e0e0e0;
	}
	.popupLinkTitleRow {
	}
	.popupLinkListTitleRow {
	}
	.popupLinkListTitleCell {
		padding: 4px;
		font-weight: 700;
		border-bottom: 1px solid #b0b0b0;
		text-align: left;
	}
	.popupLinkListDataRow {
		cursor: pointer;
		border-bottom: 1px solid #a0a0a0;
		text-align: left;
		height: 24px;
	}
	.popupLinkListDataRow0 {
		background-color: #f0f0f0;
	}
	.popupLinkListDataRow1 {
	}
	.popupLinkListDataRow:hover, .popupLinkListDataRow.hovered {
		background-color: #e0e0e0;
	}
	.popupLinkListDataExtraRow {
		padding: 6px;
	}
	.popupLinkListDataExtraCell {
		color:#606060;
		border-bottom:1px solid #d0d0d0;
		padding-bottom: 6px !important;
	}
	.popupLinkListDataCell {
		padding: 2px;
	}
	.popupLinkTitle {
		font-weight: 700;
		padding: 4px;
	}
	.popupLinkList {
		overflow-y: auto;
		overflow-x: hidden;
	}
	.popupLinkListLoading {
	}
	.popupLinkListNoData {
		width: 90%;
		text-align: center;
		padding: 10px;
		font-style: italic;
	}
	.navigationBtn {
		cursor: pointer;
	}
	#popupAttachDiv {
		background-color:  #f0f0f0;
		border-top: 2px solid  #e0e0e0;
		width: 100%
	}
	#popupMsgAttachTitle {
		font-weight: 700;
	}
	{/literal}
</style>
{*
<script type="text/javascript">
{literal}
	jQuery('#linkMsgModCont').slimScroll({
		wheelStep: 10,
		height: jQuery('body').height()+'px',
		width: '100%'
	});
{/literal}
</script>
*}
{* popup status *}
<div id="status" name="status" style="display:none;position:fixed;right:2px;top:45px;z-index:100">
	{include file="LoadingIndicator.tpl"}
</div>

<table id="linkMsgMainTab" border="0" height="100%">
	<tr>
		<td id="linkMsgLeftPane">
		{* modules list *}
		<div id="linkMsgModCont" height="100%" style="overflow-y:hidden">
			<table id="linkMsgModTab">
				{foreach item=mod from=$LINK_MODULES}
					<tr><td class="linkMsgModTd" id="linkMsgMod_{$mod}" onclick="LPOP.clickLinkModule('{$mod}','list','')">{$mod|getTranslatedString:$mod}</td></tr>
				{/foreach}
			</table>
		</div>

		</td>
		<td id="linkMsgRightPane">

			<table border="0" cellspacing="0" cellpadding="0" width="100%" height="100%">
			<tr><td id="linkMsgRightPaneTop">

				{* placeholder *}
				<div id="linkMsgDescrCont">{'LBL_SELECT_A_MODULE'|getTranslatedString}</div>

				{* list *}
				<div id="linkMsgListCont" style="display:none"></div>

				{* details *}
				<div id="linkMsgDetailCont" style="display:none"></div>

				{* edit *}
				<div id="linkMsgEditCont" style="display:none"></div>

			</td></tr>

			</table>

		</td>

	</tr>
</table>

</body>
</html>