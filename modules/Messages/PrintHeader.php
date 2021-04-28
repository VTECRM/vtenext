<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@42801 crmv@107654 */
$record = $_REQUEST['record'];
$small_page_title = getTranslatedString('LNK_PRINT');
$small_page_buttons = '
<script language="javascript" type="text/javascript" src="modules/Messages/Messages.js"></script>
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td style="padding:5px"></td>
 	<td align="right" style="padding: 5px;" nowrap>
 		<button class="crmbutton small edit" onClick="printMessage('.$record.');" type="button" name="button" title="'.getTranslatedString('LNK_PRINT').'">'.getTranslatedString('LNK_PRINT').'</button>
 	</td>
 </tr>
 </table>';
include('themes/SmallHeader.php');
include('themes/SmallFooter.php');
exit;