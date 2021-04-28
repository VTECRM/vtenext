<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@198038 */

require('vteversion.php'); // crmv@181168

/**
 * Provides utility APIs to work with Version detection
 * @package vtlib
 */
class Vtecrm_Version {

    /**
     * Get current version in use.
     */
    public static function current() {
        global $enterprise_current_version;
        return $enterprise_current_version;
    }

    /**
     * Check current version of Vtenext with given version
     * @param String Version against which comparision to be done
     * @param String Condition like ( '=', '!=', '<', '<=', '>', '>=')
     */
    public static function check($with_version, $condition='=') {
        return version_compare(self::current(), self::getUpperLimitVersion($with_version), $condition);
    }

    public static function getUpperLimitVersion($version) {
        if(!self::endsWith($version, '.*')) return $version;

        $version = rtrim($version, '.*');
        $lastVersionPartIndex = strrpos($version, '.');
        if ($lastVersionPartIndex === false) {
            $version = ((int) $version) + 1;
        } else {
            $lastVersionPart = substr($version, $lastVersionPartIndex+1, strlen($version));
            $upgradedVersionPart = ((int) $lastVersionPart) + 1;
            $version = substr($version, 0, $lastVersionPartIndex+1) . $upgradedVersionPart;
        }
        return $version;
    }

    public static function endsWith($string, $endString) {
        $strLen = strlen($string);
        $endStrLen = strlen($endString);
        if ($endStrLen > $strLen) return false;
        return substr_compare($string, $endString, -$endStrLen) === 0;
    }
}
