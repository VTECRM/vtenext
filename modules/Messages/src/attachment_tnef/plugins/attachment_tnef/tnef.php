<?php
/**
 * Displays and downloads tnef attachments
 *
 * Copyright (c) 2002 Bernd Wiegmann <bernd@wib-software.de>
 *                    Graham Norbury <gnorbury@bondcar.com>
 * Copyright (c) 2008 The NaSMail Project
 * This file is part of NaSMail attachment_tnef plugin.
 *
 * This plugin is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This plugin is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * plugin; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA  02111-1307  USA
 * @package plugins
 * @subpackage attachment_tnef
 * @version $Id: tnef.php 1173 2008-02-23 18:21:11Z tokul $
 */

/** @ignore */
define('SM_PATH', '../../');

include_once(SM_PATH . 'include/validate.php');

include_once(SM_PATH . 'functions/date.php');
include_once(SM_PATH . 'functions/mime.php');

include_once(SM_PATH . 'plugins/attachment_tnef/constants.php');
include_once(SM_PATH . 'plugins/attachment_tnef/functions.php');
include_once(SM_PATH . 'plugins/attachment_tnef/class/tnef.php');

// FIXME: explain why information is cached
header("Pragma: ");
header("Cache-Control: cache");

if (sqgetGlobalVar('passed_id', $passed_id,SQ_GET)) {
    $passed_id = nsm_setint_u32($passed_id);
} else {
    die('Message ID is not set');
}
if (!sqgetGlobalVar('ent_id', $ent_id,SQ_GET)) {
    die('Message Part ID is not set');
}
if (! sqgetGlobalVar('mailbox', $mailbox,SQ_GET)) {
    $mailbox = 'INBOX';
}
if (sqgetGlobalVar('startMessage',$startMessage,SQ_GET)) {
    $startMessage = (int) $startMessage;
} else {
    $startMessage = 1;
}
if (! sqgetGlobalVar('absolute_dl', $absolute_dl,SQ_GET)) {
    $absolute_dl = false;
}

$tnef_debug = false; // change this to true to enable debugging output

// extract the tnef attachment and decode it
$imapConnection = nsm_imap_login($username, nsm_auth_read_password(), $imapServerAddress, $imapPort, false);
sqimap_mailbox_select($imapConnection, $mailbox);
$message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
$entity_tnef = getEntity($message,$ent_id);

$tnef = mime_fetch_body ($imapConnection, $passed_id, $ent_id);
$tnef = decodeBody($tnef, $entity_tnef->header->encoding);

/** Set domain for translations stored in classes */
nsm_bindtextdomain('attachment_tnef',SM_PATH . 'locale');

$attachment = new TnefAttachment($tnef_debug);
$result = $attachment->decodeTnef($tnef);
$tnef_files = &$attachment->getFilesNested();

if (! sqgetGlobalVar('file_id', $file_id,SQ_GET)) {
    // file_id triggers download

    displayMessageHeader($color, '');

    nsm_textdomain('attachment_tnef');

    echo nsm_page_title(_("Viewing TNEF attachment"),'',true);

    echo '<table width="100%" cellspacing="0" cellpadding="2" border="0"'
        .' bgcolor="' . $color[0] . '" style="border: 1px solid ' . $color[9] ."\">\n<tr>\n" .
         html_tag('th', '<b>' . _("Mail Info:") . '</b>','left',$color[9],'colspan="2"') . "</tr>\n";

    $mailinfo = $attachment->getMailinfo();
    $subject = $mailinfo->getSubject();
    $topic = $mailinfo->getTopic();
    $from_mail = $mailinfo->getFrom();
    $from_name = $mailinfo->getFromName();
    $obj = $mailinfo->getDateSent();

    // Prepare URL for use in all downloadable TNEF part links
    $url = 'tnef.php?startMessage=' . $startMessage
        . '&amp;mailbox=' . urlencode($mailbox)
        . '&amp;passed_id=' . $passed_id
        . '&amp;ent_id=' . urlencode($ent_id);
    if (sqgetGlobalVar('passed_ent_id',$passed_ent_id,SQ_GET)) {
        $url.='&amp;passed_ent_id=' . urlencode($passed_ent_id);
    }
    if (sqgetGlobalVar('where',$where,SQ_GET) && sqgetGlobalVar('what',$what,SQ_GET)) {
        $url.='&amp;where=' . urlencode($where) . '&amp;what=' . urlencode($what);
    }

    if ($subject) {
        echo html_tag('tr',
            html_tag('td','<b>' . _("Subject:") . '&nbsp;&nbsp;</b>','right','','width="20%"') .
            html_tag('td',$subject,'left','','width="80%"'));
    }

    if (($topic) && ($topic != $subject)) {
        echo html_tag('tr',
            html_tag('td','<b>' . _("Topic:") . '&nbsp;&nbsp;</b>','right','','width="20%"') .
            html_tag('td',$topic,'left','','width="80%"'));
    }

    if ($from_name) {
        echo html_tag('tr',
            html_tag('td','<b>' . _("Name:") . '&nbsp;&nbsp;</b>','right','','width="20%"') .
            html_tag('td',$from_name,'left','','width="80%"'));
    }

    if ($from_mail) {
        echo html_tag('tr',
            html_tag('td','<b>' . _("Email:") . '&nbsp;&nbsp;</b>','right','','width="20%"') .
            html_tag('td',$from_mail,'left','','width="80%"'));
    }

    if ($obj) {
        echo html_tag('tr',
            html_tag('td','<b>' . _("Date:") . '&nbsp;&nbsp;</b>','right','','width="20%"') .
            html_tag('td',getLongDateString($obj->getUnixtime()),'left','','width="80%"'));
    }
    echo "</table>\n";

    // Attachments
    $body = '<table width="100%" cellspacing="0" cellpadding="2" border="0"'
        .' bgcolor="' . $color[0] . '" style="margin-top:5px;border: 1px solid ' . $color[9] ."\">\n<tr>\n" .
         html_tag('th', '<b>' . _("Attachments:") . '</b>','left',$color[9],'colspan="5"') . "</tr>\n";

    $show_it = false;
    $id = 0;

    foreach ($tnef_files as $file) {
        if (nsm_cstrtolower(get_class($file)) != "tnefvcard") {
            $dl_href = $url . '&amp;file_id=' . $id;
            $body .= '<tr><td>' .
                  "<a href=\"$dl_href\">" . $file->getName() . "</a>&nbsp;</td>" .
                  '<td><small><b>' . show_readable_size($file->getSize()) .
                  '</b>&nbsp;&nbsp;</small></td>' .
                  '<td><small>[ ' . $file->getType() . ' ]&nbsp;</small></td>'.
                  '<td><small><b></b></small></td>' .
                  '<td><small>&nbsp;' .
                  "<a href=\"$dl_href&absolute_dl=1\">" . _("Download") . "</a>" .
                  "</small></td></tr>\n";
              $show_it = true;
        }
        $id++;
    }

    $body .= "</table>";

    if ($show_it > 0) {
        echo $body;
    }

    /** Vcard attachments */
    foreach ($tnef_files as $file) {
        if (nsm_cstrtolower(get_class($file)) == "tnefvcard") {
            echo '<table width="100%" cellspacing="0" cellpadding="2" border="0"'
                .' bgcolor="' . $color[0]
                . '" style="margin-top:5px;border: 1px solid ' . $color[9] ."\">\n<tr>\n" .
                html_tag('th',
                    '<b>' . sprintf(_("Contact Information for %s"),$file->getName()) . '</b>',
                    'left',$color[9],'colspan="2"')
                . "</tr>\n";

            $disp = '';
            $value = $file->getGivenName();
            if ($value) {
                $disp .= $value;
            }
            $value = $file->getMiddleName();
            if ($value) {
                $disp .= ' ' . $value;
            }
            $value = $file->getSurname();
            if ($value) {
                $disp .= ' ' . $value;
            }
            if ($disp) {
                echo html_tag('tr',
                    html_tag('td','<b>' . _("Name:") . '&nbsp;&nbsp;</b>','right','','width="20%"') .
                    html_tag('td',$disp,'left','','width="80%"'));
            }

            $disp = $file->getCompany();
            if ($disp) {
                echo html_tag('tr',
                    html_tag('td','<b>' . _("Company:") . '&nbsp;&nbsp;</b>','right','','width="20%"') .
                    html_tag('td',$disp,'left','','width="80%"'));
            }

            $telefones = $file->getTelefones();
            ksort($telefones);
            foreach ($telefones as $telkey => $telvalue) {
                echo html_tag('tr',
                    html_tag('td','<b>' . $telkey . ':&nbsp;&nbsp;</b>','right','','width="20%"') .
                    html_tag('td',$telvalue,'left','','width="80%"'));
            }

            $emails = $file->getEmails();
            ksort($emails);
            $email_display = '';
            foreach ($emails as $emailkey => $emailvalue) {
                if (!empty($email_display)) {
                    $email_display.='<br>';
                }
                if (!empty($emailvalue[EMAIL_DISPLAY])) {
                    $email_display.= $emailvalue[EMAIL_DISPLAY];
                } else {
                    $email_display.= $emailkey;
                }
                $email_display.= ' &lt;' . $emailvalue[EMAIL_EMAIL] . '&gt;';
            }
            echo html_tag('tr',
                html_tag('td','<b>' . _("E-mail:") . '&nbsp;&nbsp;</b>','right','','width="20%"') .
                html_tag('td',$email_display,'left','','width="80%"'));


            $homepages = $file->getHomepages();
            foreach ($homepages as $hpkey => $hpvalue) {
                echo html_tag('tr',
                    html_tag('td','<b>' . $hpkey . ':&nbsp;&nbsp;</b>','right','','width="20%"') .
                    html_tag('td',$hpvalue,'left','','width="80%"'));
            }
            echo "</table>";
        }
    }
    do_hook('test');
    echo '</td></tr></table></body></html>';
} else { //download the requested attachment
    session_write_close();

    // Make sure that file_id exists
    if (!isset($tnef_files[$file_id])) {
        echo error_box(dgettext('attachment_tnef',"Invalid TNEF part id."),$color);
        die('</body></html>');
    }

    if (!ini_get('safe_mode')) {
        set_time_limit(0); // disable 30 second timer in case download takes a while
    }

    list($type0,$type1) = explode('/',$tnef_files[$file_id]->getType(),2);

    SendDownloadHeaders($type0, $type1,
        $tnef_files[$file_id]->getName(),
        $absolute_dl,
        $tnef_files[$file_id]->getSize());

    echo $tnef_files[$file_id]->getContent();
}
