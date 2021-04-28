<?php 

/* crmv@191501 */


$trans = array(
    'ModNotifications' => array(
        'en_us' => array(
            'LBL_CONVERTED_IN' => 'converted in',
        ),
        'it_it' => array(
            'LBL_CONVERTED_IN' => 'convertito in',
        ),
    ),
);

foreach ($trans as $module=>$modlang) {
    foreach ($modlang as $lang=>$translist) {
        foreach ($translist as $label=>$translabel) {
            SDK::setLanguageEntry($module, $lang, $label, $translabel);
        }
    }
}
