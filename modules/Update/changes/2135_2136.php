<?php
global $adb, $table_prefix;

require_once('include/utils/FSUtils.php');
folderDetete('cron/modules/com_vtiger_workflow');

$customizations = SDK::getAllCustomizations();
$words = [
    'com_vtiger_workflow',
    'Vtiger_Utils_Log',
    'Vtiger_WSClient',
    'Vtiger_',
    'vtiger.entity.',
    'vtiger_authenticated_user_theme',
    'VtigerJS_DialogBox',
];

$recDirs = [
    "cron/modules/",
    "modules/SDK/src",
    "Smarty/templates/modules/SDK",
];

$recData = [];
foreach($recDirs as $recDir){
    $recData[] = getDirContents($recDir);
}

foreach($recData as $id => $files){
    foreach($files as $file){
        $fileData = file_exists($file) ? file_get_contents($file) : false;
        if($fileData){
            foreach($words as $word){
                if(strpos($fileData, $word) !== false){
                    Update::warn('We found some incompatibilities in custom files due to the use of some old functions: ' . $word . ' in file: ' . $file);
                }
            }
        }
    }
}


foreach($customizations as $customization){
    $fileData = file_exists($customization) ? file_get_contents($customization) : false;
    if($fileData){
        foreach($words as $word){
            if(strpos($fileData, $word) !== false){
                Update::warn('We found some incompatibilities in custom files due to the use of some old functions: ' . $word . ' in file: ' . $customization);
            }
        }
    }
}

function getDirContents($path) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

    $files = array();
    foreach ($rii as $file){
        $filename = $file->getPathname();
        if (!$file->isDir() && isAllowedFile($filename))
            $files[] = $filename;
    }


    return $files;
}

function isAllowedFile($file){
    if((strpos($file, ".php") !== false ||
            strpos($file, ".js") !== false ||
            strpos($file, ".tpl") !== false ||
            strpos($file, ".inc") !== false)
        &&
        (strpos($file, "modules/Update") === false)){
        return true;
    }
    return false;
}
