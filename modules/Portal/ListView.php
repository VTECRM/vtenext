<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/* crmv@208173 */

global $theme;
$theme_path="themes/{$theme}/";
$image_path=$theme_path."images/";
global $table_prefix;
global $current_language;
global $app_strings;
global $mod_strings;
global $currentModule;
$current_module_strings = return_module_language($current_language, 'Portal');
global $adb;

$query="select * from {$table_prefix}_portal";
$result=$adb->pquery($query, []);
//Getting the Default URL Value if any
$default_url = $adb->query_result($result,0,'portalurl');
$no_of_portals = $adb->num_rows($result);
$portal_info = [];
//added as an enhancement to set default
?>
    <script type="text/javascript">
        var mysitesArray = new Array()
        <?php
        for($i=0; $i<$no_of_portals; $i++)
        {
            $portalname = $adb->query_result($result,$i,'portalname');
            $portalurl = $adb->query_result($result,$i,'portalurl');
            //added as an enhancement to set default value
            $portalid = $adb->query_result($result,$i,'portalid');
            $set_default = $adb->query_result($result,$i,'setdefault');
            $portal_array['set_def'] =  $set_default;
            $portal_array['portalid'] =  $portalid;
            if($set_default == 1)
            {
                $default = $portalurl;
            }
            $portal_array['portalname'] = (strlen($portalname) > 100) ? (substr($portalname,0,100).'...') : $portalname;
            $portal_array['portalurl'] = $portalurl;
            $portal_array['portaldisplayurl'] = (strlen($portalurl) > 100) ? (substr($portalurl,0,100).'...') : $portalurl;
            $portal_info[]=$portal_array;
        ?>
            mysitesArray['<?php echo $portalid;?>'] = "<?php echo $portalurl;?>";
        <?php
        }
        ?>
    </script>
<?php
if($default == '')
    $default = $adb->query_result($result,0,'portalurl');
$smarty = new VteSmarty();
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("MOD", $mod_strings);
$smarty->assign("DEFAULT_URL", $default);
$smarty->assign("APP", $app_strings);
$smarty->assign("PORTAL_COUNT", count($portal_info));
$smarty->assign("PORTALS", $portal_info);
$smarty->assign("MODULE", $currentModule);
$smarty->assign("DEFAULT",'yes');
$smarty->assign("CATEGORY", getParentTab());
if($_REQUEST['datamode'] == 'manage')
    $smarty->display("MySitesManage.tpl");
else if($_REQUEST['datamode'] == 'data')
    $smarty->display("MySitesContents.tpl");
else
    $smarty->display("MySites.tpl");