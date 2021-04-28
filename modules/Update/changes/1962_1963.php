<?php

// crmv@195947
// put index.html files where needed

$storage = 'storage/';
$index = "<html></html>\n";

createIndexInDir($storage.'logo', $index);
createIndexInDir($storage.'images_uploaded', $index);
createIndexInDir($storage.'custom_modules', $index);
createIndexInDir($storage.'signatures', $index);
createIndexInDir($storage.'touch_uploads', $index);
createIndexInDir($storage.'mailscanner', $index, true);
createIndexInDir($storage.'home', $index, true);


$dirs = glob($storage.'uploads_emails_*', GLOB_ONLYDIR);
if (is_array($dirs)) {
	foreach ($dirs as $dir) {
		createIndexInDir($dir, $index, false);
	}
}

$dirs = glob($storage.'[1-2][0-9][0-9][0-9]', GLOB_ONLYDIR);
if (is_array($dirs)) {
	foreach ($dirs as $dir) {
		createIndexInDir($dir, $index, true);
	}
}


function createIndexInDir($dir, $index, $recurse = false) {
	if (is_dir($dir)) {
		if (!is_file($dir."/index.html")) {
			file_put_contents($dir."/index.html", $index);
		}
		if ($recurse) {
			// and now go with the recursion!
			$dirs = glob($dir.'/*', GLOB_ONLYDIR);
			if (is_array($dirs)) {
				foreach ($dirs as $dir) {
					createIndexInDir($dir, $index, true);
				}
			}
		}
	}
}
