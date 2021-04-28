<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@148163 - security fix + clean old code
// crmv@181168 - rename file

if (isset($_REQUEST['service'])) {
	if($_REQUEST['service'] == "customerportal") {
		include("soap/customerportal.php");
	} else {
		echo "No Service Configured for ". strip_tags($_REQUEST['service']);
	}
} else {
	echo "<h1>VTECRM Soap Services</h1>\n";
	echo "<ul>\n";
	echo "<li>VTECRM Legacy Customer Portal EndPoint URL -- Click <a href='vteservice.php?service=customerportal'>here</a></li>\n";
	echo "</ul>\n";
}