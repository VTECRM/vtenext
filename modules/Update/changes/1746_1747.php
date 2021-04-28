<?php
SDK::deleteLanguageEntry('Settings', null, 'LBL_PM_IMPORT_BLOCK');
SDK::deleteLanguageEntry('Settings', null, 'LBL_PM_IMPORT_BLOCKS_TITLE');
SDK::setLanguageEntries('Settings', 'LBL_PM_IMPORT_DYNAFORM_BLOCK', array('it_it'=>'Importa da form dinamica','en_us'=>'Import from dynaform'));
SDK::setLanguageEntries('Settings', 'LBL_PM_IMPORT_DYNAFORM_BLOCK_TITLE', array('it_it'=>'Importa blocchi da altre form dinamiche','en_us'=>'Import blocks from other dynamic forms'));
SDK::setLanguageEntries('Settings', 'LBL_PM_IMPORT_MODULE_BLOCK', array('it_it'=>'Importa da modulo','en_us'=>'Import from module'));
SDK::setLanguageEntries('Settings', 'LBL_PM_IMPORT_MODULE_BLOCK_TITLE', array('it_it'=>'Importa blocchi da altre entità','en_us'=>'Import blocks from other entities'));