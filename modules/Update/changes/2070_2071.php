<?php 

// crmv@204525

/* new release 20.04.1 ! */

global $enterprise_current_version, $enterprise_mode, $enterprise_website;
SDK::setLanguageEntries('APP_STRINGS', 'LBL_BROWSER_TITLE', array(
	'it_it'=>"$enterprise_mode $enterprise_current_version",
	'en_us'=>"$enterprise_mode $enterprise_current_version",
	'de_de'=>"$enterprise_mode $enterprise_current_version",
	'nl_nl'=>"$enterprise_mode $enterprise_current_version",
	'pt_br'=>"$enterprise_mode $enterprise_current_version")
);


$trans = array(
    'ALERT_ARR' => array(
        'en_us' => array(
            'LBL_ATTACHMENT_NOT_EXIST' => 'Attachment {name} doesn\'t exist, probably the message has been moved to another folder.',
            'LBL_ATTACHMENT_DELETED' => 'The message has been moved to another folder. You have to wait a few minutes for it to be synchronized.',
        ),
        'it_it' => array(
            'LBL_ATTACHMENT_NOT_EXIST' => 'L\'allegato {name} non esiste, probabilmente il messaggio è stato spostato in un\'altra cartella.',
            'LBL_ATTACHMENT_DELETED' => 'Il messaggio è stato spostato in un\'altra cartella. Attendi qualche minuto affinché si sincronizzi.',
        ),
    ),
    
    'Messages' => array(
        'en_us' => array(
            'LBL_MESSAGE_MOVED' => 'Message has been moved to another folder. Wait a few minutes for it to be synchronized.',
        ),
        'it_it' => array(
            'LBL_MESSAGE_MOVED' => 'Il messaggio è stato spostato in un\'altra cartella. Attendi qualche minuto affinché si sincronizzi.',
        ),
    ),
    'APP_STRINGS' => array(
        'en_us' => array(
            'LBL_EDIT_MODHOME_VIEW'=>'Edit tab',
        ),
        'it_it' => array(
            'LBL_EDIT_MODHOME_VIEW'=>'Modifica tab',
        ),
    ),
);
$languages = vtlib_getToggleLanguageInfo();
foreach ($trans as $module => $modlang) {
    foreach ($modlang as $lang => $translist) {
        if (array_key_exists($lang, $languages)) {
            foreach ($translist as $label => $translabel) {
                SDK::setLanguageEntry($module, $lang, $label, $translabel);
            }
        }
    }
}
