<?php 


/* crmv@191067 */

$trans = array(
    'ALERT_ARR' => array(
        'en_us' => array(
            'LBL_TRANS_SETTINGS_SAVED' => 'Settings have been saved',
            'LBL_TRANS_DELETED' => 'Status Field has been removed successfully',
        ),
        'it_it' => array(
            'LBL_TRANS_SETTINGS_SAVED' => 'Campo di stato salvato',
            'LBL_TRANS_DELETED' => 'Il campo di stato è stato rimosso correttamente',
        ),
    ),
    'Transitions' => array(
        'en_us' => array(
            'LBL_TRANS_ST_FLD_PERM'=>'Configured Status Fields:',
			'LBL_TRANS_ST_FLD_NO_PERM'=>"There are no configured Status Fields",
        ),
        'it_it' => array(
            'LBL_TRANS_ST_FLD_PERM'=>'Campi di stato configurati:',
			'LBL_TRANS_ST_FLD_NO_PERM'=>"Non ci sono campi di stato configurati",
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
