{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@44323 *}
{* un semplice div spostabile, con il classico stile *}

<div id="{$CRMVDIV_ID}" style="display:none; position:fixed;" class="crmvDiv">
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr style="cursor:move;" height="34">
			<td id="{$CRMVDIV_ID}_handle" style="padding:5px" class="level3Bg">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="80%"><span style="font-weight:700">{$CRMVDIV_TITLE}</span></td>
					<td width="20%" align="right">{$CRMVDIV_BUTTONS}</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div id="{$CRMVDIV_ID}_content">
		{$CRMVDIV_CONTENT}
	</div>
	<div class="closebutton" onClick="fninvsh('{$CRMVDIV_ID}');"></div>
</div>
{if $CRMVDIV_DRAGGABLE}
<script type="text/javascript">
(function(){ldelim}
	// crmv@192014
	jQuery("#{$CRMVDIV_ID}").draggable({ldelim}
		handle: '#{$CRMVDIV_ID}_handle'
	{rdelim});
	// crmv@192014e
{rdelim})();
</script>
{/if}