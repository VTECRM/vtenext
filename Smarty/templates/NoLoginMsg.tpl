{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@94525 *}
{if $THEME eq ''} {* crmv@200310 *}
	{assign var="THEME" value="next"}
{/if}
{assign var="RELPATH" value=$PATH}
{assign var="BROWSER_TITLE" value='LBL_BROWSER_TITLE'|@getTranslatedString:'APP_STRINGS'}
{include file="HTMLHeader.tpl" head_include="icons,jquery,jquery_plugins,prototype"}

<body class="morphsuitactivationbody">
<div align="center">
	<div class="small" style="width: 500px; padding-top: 5px;">
		<div class="small" style="width: 500px; padding-top: 5px;">
			<table class="hdrBg" width="100%" cellspacing="0" cellpadding="0">
				<tr height="50">
					<td style="padding-left:5px;padding-right:5px;" nowrap>
					{CRMVUtils::getEnterpriseLogo('project', $PATH)} {* crmv@181170 *}
					</td>
					<td width="100%" align="right" style="padding-right:10px">
						<img src="{$PATH}{'header'|get_logo}" border="0">
					</td>
				</tr>
			</table>
			<table id="Standard" class="small morphsuittable" width="500" cellpadding="3">
				<tr>
					<td>{$BODY}</td>
				</tr>
			</table>
		</div>
	</div>
</div>
</body>
</html>