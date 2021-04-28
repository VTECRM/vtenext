<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@203590 */

if (isset($_REQUEST['filename'])) {
	$file = $_REQUEST['filename'];
	$path = '../../../logs/AuditTrail/' . str_replace(array('..', '/'), '', $file);
	downloadFile($path);
}

function downloadFile($path) {

	$downloadName = basename($path);
	$downloadName = str_replace('"', '', $downloadName);

	header('Content-Type: text/csv; charset=utf-8');
	header("Cache-Control: private");
	header("Content-Disposition: attachment; filename=$downloadName");
	header("Content-Description: PHP Generated Data");

	$chunksize = 1024 * 512;

	ob_clean();
	$input = gzopen($path, "rb");
	$output = fopen('php://output', 'w');

	while(!gzeof($input)) {
		$chunk = gzread($input, $chunksize);
		fwrite($output, $chunk, strlen($chunk));
		flush();
	}

	gzclose($input);
	fclose($output);
	exit();
}