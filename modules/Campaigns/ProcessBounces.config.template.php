<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@22700 crmv@64516 */

/* Settings for handling bounces */

# Message envelope. This is the email that system messages come from
# it is useful to make this one where you can process the bounces on
# you will probably get a X-Authentication-Warning in your message
# when using this with sendmail
# NOTE: this is *very* different from the From: line in a message
# to use this feature, uncomment the following line, and change the email address
# to some existing account on your system
# requires PHP version > "4.0.5" and "4.3.1+" without safe_mode
$message_envelope = '';

# Handling bounces. Check README.bounces for more info
# This can be 'pop' or 'mbox'
// sia nel caso pop o imap usare 'pop',
// poi in base alla porta si arrangia la
// funzione 'imap_open' a stabilire il tipo di connessione
$bounce_protocol = 'pop';

# set this to 0, if you set up a cron to download bounces regularly by using the
# commandline option. If this is 0, users cannot run the page from the web
# frontend. Read README.commandline to find out how to set it up on the
# commandline
define ("MANUALLY_PROCESS_BOUNCES",1);

# when the protocol is pop, specify these three
$bounce_mailbox_host = 'mail.yourdomain.com';
$bounce_mailbox_user = 'bounces@yourdomain.com';
$bounce_mailbox_password = 'yourpassword';

# the "port" is the remote port of the connection to retrieve the emails
# the default should be fine but if it doesn't work, you can try the second
# one. To do that, add a # before the first line and take off the one before the
# second line

//$bounce_mailbox_port = "110/pop3/notls";
#$bounce_mailbox_port = "110/pop3";
$bounce_mailbox_port = "143/imap/notls";

//cartella della mail in cui leggere le mail bounced
$bounce_mailbox_folder = 'INBOX';

# when the protocol is mbox specify this one
# it needs to be a local file in mbox format, accessible to your webserver user
$bounce_mailbox = '/var/spool/mail/listbounces';

# set this to 0 if you want to keep your messages in the mailbox. this is potentially
# a problem, because bounces will be counted multiple times, so only do this if you are
# testing things.
$bounce_mailbox_purge = 1;

# set this to 0 if you want to keep unprocessed messages in the mailbox. Unprocessed
# messages are messages that could not be matched with a user in the system
# messages are still downloaded into PHPlist, so it is safe to delete them from
# the mailbox and view them in PHPlist
$bounce_mailbox_purge_unprocessed = 0;

# how many bounces in a row need to have occurred for a user to be marked unconfirmed
$bounce_unsubscribe_threshold = 5;

define("TEST", false);
define("VERBOSE", false);
?>