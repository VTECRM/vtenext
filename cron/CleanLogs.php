<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@27164
$files = scandir('logs');
foreach($files as $file) {
	if (strpos($file,'.pid') !== false) {
		unlink('logs/'.$file);
	}
}
//crmv@27164e
?>