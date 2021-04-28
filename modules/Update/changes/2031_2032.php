<?php

// crmv@200585
$adb->pquery("UPDATE {$table_prefix}_field SET displaytype = 1 WHERE tabid = 16 AND displaytype = 3 AND fieldname = ?", array('organizer'));
