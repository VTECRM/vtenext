{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<div id="calc" class="crmvDiv">
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr style="cursor:move;" height="34">
			<td id="calc_Handle" colspan="2" style="padding:5px" class="level3Bg"><b>{$APP.LBL_CALCULATOR}</b></td>
		</tr>
	</table>
	<table  border="0" cellpadding="0" cellspacing="0" width="100%" class="hdrNameBg">
		<tr><td style="padding:10px;" colspan="2">{$CALC}</td></tr>
	</table>
	<div class="closebutton" onClick="fninvsh('calc');"></div>
</div>
<script>
	// crmv@192014
	jQuery("#calc").draggable({ldelim}
		handle: '#calc_Handle'
	{rdelim});
	// crmv@192014e
</script>