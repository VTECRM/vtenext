<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@150748 */

/**
 * Autoload a class from include/utils, with the file name equals to the class name.
 * Some special cases are handled as well
 */

function startsWith($string, $startString)
{
    $len = strlen($startString);
    return (substr($string, 0, $len) === $startString);
}

function VTEAutoloadUtils($class) {
    // crmv@151308
    // support some special cases
    // TODO: make order in all classes to avoid special cases
    switch ($class) {
        case 'GDPRWS':
            $file = 'include/utils/GDPRWS/'.$class.'.php';
            break;
        case 'RelationManager':
        case 'ModuleRelation':
        case 'FakeModules':
            $file = 'include/utils/RelationManager/'.$class.'.php';
            break;
        // crmv@164120
        case 'ChangeLog':
            $file = 'modules/ChangeLog/ChangeLog.php';
            break;
        // crmv@164120e
        // crmv@164122
        case 'ModNotifications':
            $file = 'modules/ModNotifications/ModNotifications.php';
            break;
        // crmv@208173
        case 'ListViewSession':
            $file = "include/ListView/ListViewSession.php";
                break;
        case 'ListViewController':
            $file = "include/ListView/ListViewController.php";
            break;
        // crmv@208173e
        // crmv@164122e
        default:
            $file = 'include/utils/'.str_replace('.', '', $class).'.php';
            break;
    }
    
    //crmv@198038
    if (substr($class, 0, 7) === 'Vtecrm_') {
        $class = str_replace('Vtecrm_', '', $class);
        $file = 'vtlib/Vtecrm/' . $class . '.php';
    } elseif (substr($class, 0, 7) === 'Vtiger_') {
        $class = str_replace('Vtiger_', '', $class);
        $file = 'vtlib/Vtiger/' . $class . '.php';
    }
    //crmv@198038e
    // crmv@151308e

    if (file_exists($file)) {
        require_once($file);
    }
}

spl_autoload_register('VTEAutoloadUtils');