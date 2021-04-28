<?php
// remove $CALCULATOR_DISPLAY
$configInc = file_get_contents('config.inc.php');
if (empty($configInc)) {
    Update::info("Unable to get config.inc.php contents, please modify it manually.");
} else {
    // backup it (only if it doesn't exist
    $newConfigInc = 'config.inc.1582.php';
    if (!file_exists($newConfigInc)) {
        file_put_contents($newConfigInc, $configInc);
    }
    // change value
    $configInc = preg_replace('/^\$CALCULATOR_DISPLAY.*$/m', "", $configInc);
    if (is_writable('config.inc.php')) {
        file_put_contents('config.inc.php', $configInc);
    } else {
        Update::info("Unable to update config.inc.php, please modify it manually.");
    }
}
