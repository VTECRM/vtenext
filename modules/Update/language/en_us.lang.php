<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $enterprise_mode;
$mod_strings = array(
	'LBL_UPDATE'=>'Update',
	'LBL_UPDATE_DESC'=>'Update Version',
	'LBL_URL'=>'SVN address',
	'LBL_USERNAME'=>'User',
	'LBL_PASWRD'=>'Password',
	'LBL_SIGN_IN_DETAILS'=>'Login details',
	'LBL_SIGN_IN_CHANGE'=>'Change login',
	'LBL_CURRENT_VERSION'=>'Current build',
	'LBL_MAX_VERSION'=>'Last build available',
	'LBL_UPDATE_DETAILS'=>'Update details',
	'LBL_UPDATE_BUTTON'=>'Update',
	'LBL_UPDATE_TO'=>'Update to',
	'LBL_SPECIFIC_VERSION'=>'Specify version',
	'LBL_SPECIFICIED_VERSION'=>'Specified version',
	'LBL_UPDATE_PACK_INVALID'=>"This update is not supported by your $enterprise_mode version.<br />Please contact CRMVillage.BIZ or your Partner for the right version.",
	'LBL_POPUP_TITLE' => '<b>Version {version} of vtenext is available!</b>',
	'LBL_SCHEDULE' => 'Schedule',
	'LBL_SCHEDULE_UPDATE' => 'Schedule update',
	'LBL_REMIND' => 'Remind me',
	'LBL_REMIND_IN_4_HOURS' => 'In 4 hours',
	'LBL_REMIND_TOMORROW' => 'Tomorrow',
	'LBL_REMIND_NEXT_WEEK' => 'Next week',
	'LBL_IGNORE_UPDATE' => 'Ignore update',
	'LBL_WHEN_SCHEDULE_UPDATE' => 'When do you want to schedule the update?',
	'LBL_ALERT_USER_OF_UPDATE' => 'Alert users about the update',
	'LBL_SEND_THIS_MESSAGE' => 'Send this notice to users',
	'LBL_UPDATE_SCHEDULED' => 'Update scheduled succesfully',
	'LBL_UPDATE_DEFAULT_MESSAGE' => "Dear user,
an update for vtenext has been planned for {date} at {hour}.
During the update the system will be unavailable.

{cancel_text}",
	'LBL_UPDATE_MESSAGE_OK' => "Dear {name},
the update of vtenext has been completed succesfully.
Check the attached files for the update logs.",
	'LBL_UPDATE_MESSAGE_FAIL_RB' => "Dear {name},
the update of vtenext failed, and the system has been restored to the previous version.
It's necessary to proceed with a manual update.
Check the attached files for the update logs.",
	'LBL_UPDATE_MESSAGE_FAIL' => "Dear {name},
the update of vtenext failed, but it wasn't possible to restore the system to the previous version.
It's necessary to manually restore backups abd proceed with the update.
Check the attached files for the update logs.",
	'LBL_ALERT_CHANGES' => 'Some changes have been found in vte files. Do you want to update anyway (you might loose some customizations)?',
	'LBL_VIEW_FILES_LIST' => 'View files list',
	'LBL_MODIFIED_FILES' => 'Files with differencies',
	'LBL_NOTIFICATION_TPL_TEXT' => 'An update of vtenext is available. Click <a href="{url}">here</a> for details.',
	'LBL_ALREADY_CHOSEN' => 'Another user already scheduled or ignored the update',
	'LBL_OS_NOT_SUPPORTED' => 'Automatic update is not supported on this operating system.',
	'LBL_OS_NOT_SUPPORTED_UPDATE' => 'Automatic update is not supported on this operating system. You have to update manually.',
	'LBL_MANUAL_INFO_1' => 'Backup vtenext files and database, see <b><a href="%s" target="_blank">here</a></b> for instructions',
	'LBL_MANUAL_INFO_2' => 'Download the update package from this <b><a href="%s">address</a></b>',
	'LBL_MANUAL_INFO_3' => 'Unzip the downloaded file and overwrite with its contents the folder <i>%s</i>',
	'LBL_MANUAL_INFO_4' => 'Go to this <b><a href="%s">page</a></b> and click <i>update</i>',
	'LBL_ALL_USERS' => 'All users',
	'LBL_UPDATE_DEFAULT_CANCEL_TEXT' => "If you want to cancel the update, please click <b><a href=\"%s\">here</a></b>.",
	'LBL_CANNOT_CANCEL' => 'At this time it\'s not possible to cancel the update process',
	'LBL_CANCEL_UPDATE_TITLE' => 'Cancel Update',
	'LBL_CANCEL_UPDATE_TEXT' => 'An update for vtenext is planned for {date} at {hour}',
	'LBL_CANCEL_UPDATE_ASK' => 'Do you want to cancel it?',
	'LBL_CANCEL_UPDATE_INFO' => 'You can schedule it again by clicking on the original notification',
	'LBL_CANCEL_BODY' => "Dear user,
the update scheduled for {date} at {hour} has been canceled.",
	'LBL_NEED_PHP_70' => 'PHP version is too old. The new version of vtenext requires at least PHP 7.0',
	'LBL_PHP_OK_WAIT_CRON' => 'PHP is now updated. Wait a few hours and you\'ll be notified again about the update',
	'LBL_UPDATE_RUNNING_WAIT' => 'Updating, please wait...',
	'LBL_UPDATE_FINISHED' => 'Update finished',
	'LBL_UPDATE_FAILED' => 'Update failed. Please verify the error and try again, restoring the backup.',
	'LBL_CONTINUE' => 'Continue',
	'LBL_DATE_IS_PAST' => 'Date cannot be in the past',
	'LBL_DATE_TOO_CLOSE' => "Date must be at least 10 minutes from now",
	// crmv@199352
	'LBL_NO_UPDATES_AVAILABLE' => 'At the moment there are no updates available.',
	'LBL_NO_UPDATES_CRON' => 'Update check is not active, please verify cron configuration.',
	'LBL_LAST_CHECK' => 'Last check: ',
	'LBL_CHECK_NOW' => 'Check now',
	'LBL_CRON_FORCED' => 'Updates will be checked in a few minutes. You\'ll be notified in case of a new available version.',
	'LBL_PROCESSING_UPDATE' => 'The update is being verified, please wait a few minutes.',
	// crmv@199352e
);