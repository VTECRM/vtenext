<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Morphsuit/utils/MorphsuitUtils.php');
if (isFreeVersion()) {
	echo 'yes';
} else {
	echo 'no';
}
exit;
?>