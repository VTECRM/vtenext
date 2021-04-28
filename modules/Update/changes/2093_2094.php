<?php
// crmv@204327
global $table_prefix;
Update::change_field($table_prefix.'_running_processes_timer','timer','DT','',"DEFAULT '0000-00-00 00:00:00'");