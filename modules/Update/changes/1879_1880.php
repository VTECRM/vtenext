<?php
$trans = array(
	'Settings' => array(
		'it_it' => array(
			'LBL_PM_ACTION_CreatePDF' => 'Crea PDF',
		),
		'en_us' => array(
			'LBL_PM_ACTION_CreatePDF' => 'Create PDF',
		),
	),
	'PDFMaker' => array(
		'it_it' => array(
			'LBL_CREATEPDF_INFORMATION' => 'Informazioni PDF',
			'LBL_CREATEPDF_CUSTOM_INFORMATION' => 'Informazioni personalizzate PDF',
			'LBL_NO_TEMPLATE' => 'Nessun Template PDF disponibile',
			'LBL_PDF_ENTITY' => 'Entità documento PDF',
			'LBL_SELECT_PDF_ENTITY' => 'Seleziona entità PDF',
			'Related To Entity' => 'Relaziona PDF a',
			'Language' => 'Lingua template PDF',
			'Template' => 'Template PDF',
			'Folder' => 'Cartella documenti',
			'Subject' => 'Titolo PDF',
		),
		'en_us' => array(
			'LBL_CREATEPDF_INFORMATION' => 'PDF Info',
			'LBL_CREATEPDF_CUSTOM_INFORMATION' => 'PDF custom Info',
			'LBL_NO_TEMPLATE' => 'There is no available PDF Template',
			'LBL_PDF_ENTITY' => 'PDF document Entity',
			'LBL_SELECT_PDF_ENTITY' => 'Select PDF entity',
			'Related To Entity' => 'Related PDF to',
			'Language' => 'PDF template language',
			'Template' => 'PDF template',
			'Folder' => 'Document folder',
			'Subject' => 'PDF subject',
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