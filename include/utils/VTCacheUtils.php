<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/* crmv@105600 */

/* crmv@208173 */

// TODO: deprecate this class in favor of the new RCache class

/**
 * Class to handle Caching Mechanism and re-use information during the current request only
 */
class VTCacheUtils {

    /** All tab information caching */
    static $_alltabrows_cache = false;
    static function lookupAllTabsInfo() {
        return self::$_alltabrows_cache;
    }
    static function updateAllTabsInfo($tabrows) {
        self::$_alltabrows_cache = $tabrows;
    }

    /** Field information caching */
    static $_fieldinfo_cache = array();
    static function updateFieldInfo($tabid, $fieldname, $fieldid, $fieldlabel,
                                    $columnname, $tablename, $uitype, $typeofdata, $presence) {

        self::$_fieldinfo_cache[$tabid][$fieldname] = array(
            'columnname'=> $columnname,
            'tablename' => $tablename,
            'uitype'    => $uitype,
            'typeofdata'=> $typeofdata,
            'presence'  => $presence,
            'tabid'     => $tabid,
            'fieldid'   => $fieldid,
            'fieldname' => $fieldname,
            'fieldlabel'=> $fieldlabel,
        );
    }
    static function lookupFieldInfo($tabid, $fieldname) {
        if(isset(self::$_fieldinfo_cache[$tabid]) && isset(self::$_fieldinfo_cache[$tabid][$fieldname])) {
            return self::$_fieldinfo_cache[$tabid][$fieldname];
            // crmv@193294
        } else {
            return FieldUtils::getField($tabid, $fieldname);
        }
        // crmv@193294e
    }
    static function lookupFieldInfo_Module($module, $presencein = ['0', '2']) {
        $tabid = getTabid($module);
        if(isset(self::$_fieldinfo_cache[$tabid])) {
            $modulefields = [];

            $fldcache = self::$_fieldinfo_cache[$tabid];
            foreach($fldcache as $fieldname=>$fieldinfo) {
                if(in_array($fieldinfo['presence'], $presencein)) {
                    $modulefields[] = $fieldinfo;
                }
            }
            // crmv@193294
        } else {
            // user the new field global cache
            $modulefields = FieldUtils::getFields($module, function($row) use ($presencein) {
                return in_array($row['presence'], $presencein);
            });
            if (is_array($modulefields)) $modulefields = array_values($modulefields);
        }
        // crmv@193294e

        return $modulefields;
    }

    /** Tab information caching */
    static $_tabidinfo_cache = [];
    static function lookupTabid($module) {
        $flip_cache = array_flip(self::$_tabidinfo_cache);

        if(isset($flip_cache[$module])) {
            return $flip_cache[$module];
        }
        return false;
    }

    static function lookupModulename($tabid) {
        if(isset(self::$_tabidinfo_cache[$tabid])) {
            return self::$_tabidinfo_cache[$tabid];
        }
        return false;
    }

    static function updateTabidInfo($tabid, $module) {
        if(!empty($tabid) && !empty($module)) {
            self::$_tabidinfo_cache[$tabid] = $module;
        }
    }

    static function lookupFieldInfoByColumn($tabid, $columnName) {
        if(!isset(self::$_fieldinfo_cache[$tabid])) {
            // user the new field global cache
            $modulefields = FieldUtils::getFields($tabid, function($row) use ($columnName) {
                return ($row['columnname'] == $columnName);
            });
            if (count($modulefields) > 0) {
                reset($modulefields);
                return current($modulefields);
            }
        } else {
            foreach(self::$_fieldinfo_cache[$tabid] as $fieldName=>$fieldInfo) {
                if($fieldInfo['columnname'] == $columnName) {
                    return $fieldInfo;
                }
            }
            // crmv@193294
        }
        // crmv@193294e
        return false;
    }

    static function updateCurrencyInfo($currencyid, $name, $code, $symbol, $rate) {
        self::$_currencyinfo_cache[$currencyid] = array(
            'currencyid' => $currencyid,
            'name'       => $name,
            'code'       => $code,
            'symbol'     => $symbol,
            'rate'       => $rate
        );
    }

    static function updateUserCurrencyId($userid, $currencyid) {
        self::$_usercurrencyid_cache[$userid] = array(
            'currencyid' => $currencyid
        );
    }


    /** User currency id caching */
    static $_usercurrencyid_cache = [];
    static function lookupUserCurrencyId($userid) {
        global $current_user;
        if(isset($current_user) && $current_user->id == $userid) {
            return array(
                'currencyid' => $current_user->column_fields['currency_id']
            );
        }

        if(isset(self::$_usercurrencyid_cache[$userid])) {
            return self::$_usercurrencyid_cache[$userid];
        }

        return false;
    }

    /** Currency information caching */
    static $_currencyinfo_cache = [];
    static function lookupCurrencyInfo($currencyid) {
        if(isset(self::$_currencyinfo_cache[$currencyid])) {
            return self::$_currencyinfo_cache[$currencyid];
        }
        return false;
    }

    /** ProfileId information caching */
    static $_userprofileid_cache = [];
    static function updateUserProfileId($userid, $profileid) {
        self::$_userprofileid_cache[$userid] = $profileid;
    }
    static function lookupUserProfileId($userid) {
        if(isset(self::$_userprofileid_cache[$userid])) {
            return self::$_userprofileid_cache[$userid];
        }
        return false;
    }

    /** Profile2Field information caching */
    static $_profile2fieldpermissionlist_cache = [];
    static function lookupProfile2FieldPermissionList($module, $profileId) {
        $pro2fld_perm = self::$_profile2fieldpermissionlist_cache;
        if(isset($pro2fld_perm[$module]) && isset($pro2fld_perm[$module][$profileId])) {
            return $pro2fld_perm[$module][$profileId];
        }
        return false;
    }
    static function updateProfile2FieldPermissionList($module, $profileId, $value) {
        self::$_profile2fieldpermissionlist_cache[$module][$profileId] = $value;
    }

    /** Role information */
    static $_subroles_roleid_cache = [];
    static function lookupRoleSubordinates($roleid) {
        if(isset(self::$_subroles_roleid_cache[$roleid])) {
            return self::$_subroles_roleid_cache[$roleid];
        }
        return false;
    }
    static function updateRoleSubordinates($roleid, $roles) {
        self::$_subroles_roleid_cache[$roleid] = $roles;
    }
    static function clearRoleSubordinates($roleId = false) {
        if($roleId === false) {
            self::$_subroles_roleid_cache = [];
        } else if(isset(self::$_subroles_roleid_cache[$roleId])) {
            unset(self::$_subroles_roleid_cache[$roleId]);
        }
    }

}