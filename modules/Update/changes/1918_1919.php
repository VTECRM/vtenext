<?php 

/* crmv@190519 */

FSUtils::deleteFolder('jscalendar');
@unlink('modules/Timecards/ChangeType.php');
@unlink('modules/Timecards/Showcard.php');
FSUtils::deleteFolder('Smarty/templates/themes/next/modules/Webforms');

$updateLangPackages = array(
	'de_de' => 'packages/vte/optional/Deutsch.zip',
	'pt_br' => 'packages/vte/optional/PTBrasil.zip',
	'pl_pl' => 'packages/vte/beta/vte/Polish.zip',
	'fr_fr' => 'packages/vte/beta/vte/French.zip',
	'ru_ru' => 'packages/vte/beta/vte/Russian.zip',
	'es_es' => 'packages/vte/beta/vte/Spanish.zip',
);

$languages = vtlib_getToggleLanguageInfo();

foreach ($updateLangPackages as $lang => $package) {
	if (array_key_exists($lang,	$languages)) {
		$languageInstance = new Vtiger_Language();
		$languageInstance->update($languageInstance, $package, true);
	}
}

Update::info('The plugin jscalendar and its content have been moved in include/js folder');
Update::info('If you have customizations using the plugin, please review them.');
Update::info('');
