<?php

// crmv@174672

$cond = 'checkPermissionSDKButton:modules/MyNotes/widgets/Utils.php';
$adb->pquery("UPDATE sdk_menu_fixed SET cond = ? WHERE title = ? AND (cond IS NULL OR cond = '')", array($cond, 'MyNotes'));
