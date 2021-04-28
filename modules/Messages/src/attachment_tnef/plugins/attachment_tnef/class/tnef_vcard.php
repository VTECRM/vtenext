<?php
/**
 * tnef_vcard.php
 *
 * Copyright (c) 2003 Bernd Wiegmann <bernd@wib-software.de>
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
 * @version $Id: tnef_vcard.php 1173 2008-02-23 18:21:11Z tokul $
 */

/** */
define ("EMAIL_DISPLAY",       1);
define ("EMAIL_TRANSPORT",     2);
define ("EMAIL_EMAIL",         3);
define ("EMAIL_EMAIL2",        4);

/**
 * @package plugins
 * @subpackage attachment_tnef
 */
class TnefvCard extends TnefReceiver {
    var $name;
    var $type;
    var $metafile;
    var $created;
    var $modified;
    var $surname;
    var $given_name;
    var $middle_name;
    var $nickname;
    var $company;

    var $homepages;
    var $addresses;
    var $emails;
    var $telefones;

    function TnefvCard($tnef_debug) {
        $this->tnef_debug = $tnef_debug;
        $this->name = "Untitled";
        $this->type = "text/x-vcard";
        $this->content = "";
        $this->telefones = array();
        $this->homepages = array();
        $this->emails = array();
        $this->addresses = array();
    }

    function getName() {
        return $this->name;
    }
    function getType() { return $this->type;  }
    function getMetafile()          { return $this->metafile;   }
    function getSize()              { return strlen($this->content);   }

    /**
     * Returns contents of vcard (unimplemented)
     * @todo create vcard file from object properties
     * @return string
     */
    function getContent() {
        return $this->content;
    }

    function &getCreated()          { return $this->created;   }
    function &getModified()         { return $this->modified;   }
    function &getTelefones()        { return $this->telefones;   }
    function getSurname()           { return $this->surname;  }
    function getGivenName()         { return $this->given_name;  }
    function getMiddleName()        { return $this->middle_name;  }
    function getNickname()          { return $this->nickname;  }
    function getCompany()           { return $this->company;  }
    function &getHomepages()        { return $this->homepages;  }
    function &getEmails()           { return $this->emails;  }
    function &getAddresses()        { return $this->addresses;  }

    function receiveMapiAttribute($attr_type, $attr_name, $value, $length) {
        switch($attr_name) {
        case TNEF_MAPI_DISPLAY_NAME:
            $this->name = $value;
            break;

        case TNEF_MAPI_SURNAME:
            $this->surname = $value;
            break;

        case TNEF_MAPI_GIVEN_NAME:
            $this->given_name = $value;
            break;

        case TNEF_MAPI_MIDDLE_NAME:
            $this->middle_name = $value;
            break;

        case TNEF_MAPI_NICKNAME:
            $this->nickname = $value;
            break;

        case TNEF_MAPI_COMPANY_NAME:
            $this->company = $value;
            break;

        default:
            if (! $this->evaluateTelefoneAttribute($attr_type, $attr_name, $value, $length) &&
                ! $this->evaluateEmailAttribute($attr_type, $attr_name, $value, $length) &&
                ! $this->evaluateAddressAttribute($attr_type, $attr_name, $value, $length) &&
                ! $this->evaluateHomepageAttribute($attr_type, $attr_name, $value, $length) &&
                $this->tnef_debug) {
                    tnef_log("Unsupported vcard attribute: type=$attr_type, name=$attr_name, value=$value,length=$length");
            }
            break;
        }
    }

    /**
     * Detects phone attributes and adds them to telefones property
     *
     * @param integer $attr_type
     * @param integer $attr_name
     * @param string $value
     * @param integer $length
     * @return boolean true, if phone attribute type is detected.
     */
    function evaluateTelefoneAttribute($attr_type, $attr_name, $value, $length) {
        $telefone_mapping = array (
            TNEF_MAPI_PRIMARY_TEL_NUMBER    => dgettext('attachment_tnef',"Primary Phone"),
            TNEF_MAPI_HOME_TEL_NUMBER       => dgettext('attachment_tnef',"Home Phone"),
            TNEF_MAPI_HOME2_TEL_NUMBER      => dgettext('attachment_tnef',"Second Home Phone"),
            TNEF_MAPI_BUSINESS_TEL_NUMBER   => dgettext('attachment_tnef',"Business Phone"),
            TNEF_MAPI_BUSINESS2_TEL_NUMBER  => dgettext('attachment_tnef',"Second Business Phone"),
            TNEF_MAPI_MOBILE_TEL_NUMBER     => dgettext('attachment_tnef',"Mobile Phone"),
            TNEF_MAPI_RADIO_TEL_NUMBER      => dgettext('attachment_tnef',"Radio Phone"),
            TNEF_MAPI_CAR_TEL_NUMBER        => dgettext('attachment_tnef',"Car Phone"),
            TNEF_MAPI_OTHER_TEL_NUMBER      => dgettext('attachment_tnef',"Other Phone"),
            TNEF_MAPI_PAGER_TEL_NUMBER      => dgettext('attachment_tnef',"Pager"),
            TNEF_MAPI_PRIMARY_FAX_NUMBER    => dgettext('attachment_tnef',"Primary Fax"),
            TNEF_MAPI_BUSINESS_FAX_NUMBER   => dgettext('attachment_tnef',"Business Fax"),
            TNEF_MAPI_HOME_FAX_NUMBER       => dgettext('attachment_tnef',"Home Fax"));

        $rc = false;

        if ($length > 0) {
            if (array_key_exists($attr_name, $telefone_mapping)) {
                $telefone_key = $telefone_mapping[$attr_name];
                $this->telefones[$telefone_key] = $value;
                $rc = true;
                if ($this->tnef_debug) {
                    tnef_log("Setting telefone '$telefone_key' to value '$value'");
                }
            }
        }

        return $rc;
    }

    /**
     * Detects email attributes and adds them to emails property
     *
     * @param integer $attr_type
     * @param integer $attr_name
     * @param string $value
     * @param integer $length
     * @return boolean true, if email attribute type is detected.
     */
    function evaluateEmailAttribute($attr_type, $attr_name, $value, $length) {
        $email_mapping = array (
            TNEF_MAPI_EMAIL1_DISPLAY   => array (dgettext('attachment_tnef',"eMail 1"), EMAIL_DISPLAY ),
            TNEF_MAPI_EMAIL1_TRANSPORT => array (dgettext('attachment_tnef',"eMail 1"), EMAIL_TRANSPORT ),
            TNEF_MAPI_EMAIL1_EMAIL     => array (dgettext('attachment_tnef',"eMail 1"), EMAIL_EMAIL ),
            TNEF_MAPI_EMAIL1_EMAIL2    => array (dgettext('attachment_tnef',"eMail 1"), EMAIL_EMAIL2 ),
            TNEF_MAPI_EMAIL2_DISPLAY   => array (dgettext('attachment_tnef',"eMail 2"), EMAIL_DISPLAY ),
            TNEF_MAPI_EMAIL2_TRANSPORT => array (dgettext('attachment_tnef',"eMail 2"), EMAIL_TRANSPORT ),
            TNEF_MAPI_EMAIL2_EMAIL     => array (dgettext('attachment_tnef',"eMail 2"), EMAIL_EMAIL ),
            TNEF_MAPI_EMAIL2_EMAIL2    => array (dgettext('attachment_tnef',"eMail 2"), EMAIL_EMAIL2 ),
            TNEF_MAPI_EMAIL3_DISPLAY   => array (dgettext('attachment_tnef',"eMail 3"), EMAIL_DISPLAY ),
            TNEF_MAPI_EMAIL3_TRANSPORT => array (dgettext('attachment_tnef',"eMail 3"), EMAIL_TRANSPORT ),
            TNEF_MAPI_EMAIL3_EMAIL     => array (dgettext('attachment_tnef',"eMail 3"), EMAIL_EMAIL ),
            TNEF_MAPI_EMAIL3_EMAIL2    => array (dgettext('attachment_tnef',"eMail 3"), EMAIL_EMAIL2 ));

        $rc = false;

        if ($length > 0) {
            if (array_key_exists($attr_name, $email_mapping)) {
                $email_key = $email_mapping[$attr_name];
                if (!array_key_exists($email_key[0], $this->emails)) {
                    $this->emails[$email_key[0]] = array ( EMAIL_DISPLAY => "", EMAIL_TRANSPORT => "", EMAIL_EMAIL => "", EMAIL_EMAIL2 => "");
                }
                $this->emails[$email_key[0]][$email_key[1]] = $value;
                $rc = true;
            }
        }

        return $rc;
    }

    /**
     * Detects address attributes and adds them to addresses property
     *
     * @param integer $attr_type
     * @param integer $attr_name
     * @param string $value
     * @param integer $length
     * @return boolean true, if address attribute type is detected.
     */
    function evaluateAddressAttribute($attr_type, $attr_name, $value, $length) {
        $address_mapping = array (
            TNEF_MAPI_LOCALTY           => array (dgettext('attachment_tnef',"Address"),
                                                  dgettext('attachment_tnef',"City") ),
            TNEF_MAPI_COUNTRY           => array (dgettext('attachment_tnef',"Address"),
                                                  dgettext('attachment_tnef',"Country") ),
            TNEF_MAPI_POSTAL_CODE       => array (dgettext('attachment_tnef',"Address"),
                                                  dgettext('attachment_tnef',"Zip") ),
            TNEF_MAPI_STATE_OR_PROVINCE => array (dgettext('attachment_tnef',"Address"),
                                                  dgettext('attachment_tnef',"State") ),
            TNEF_MAPI_STREET_ADDRESS    => array (dgettext('attachment_tnef',"Address"),
                                                  dgettext('attachment_tnef',"Street") ),
            TNEF_MAPI_POST_OFFICE_BOX   => array (dgettext('attachment_tnef',"Address"),
                                                  dgettext('attachment_tnef',"PO Box") ),
            TNEF_MAPI_HOME_ADDR_CITY    => array (dgettext('attachment_tnef',"Home Address"),
                                                  dgettext('attachment_tnef',"City") ),
            TNEF_MAPI_HOME_ADDR_COUNTRY => array (dgettext('attachment_tnef',"Home Address"),
                                                  dgettext('attachment_tnef',"Country") ),
            TNEF_MAPI_HOME_ADDR_ZIP     => array (dgettext('attachment_tnef',"Home Address"),
                                                  dgettext('attachment_tnef',"Zip") ),
            TNEF_MAPI_HOME_ADDR_STATE   => array (dgettext('attachment_tnef',"Home Address"),
                                                  dgettext('attachment_tnef',"State") ),
            TNEF_MAPI_HOME_ADDR_STREET  => array (dgettext('attachment_tnef',"Home Address"),
                                                  dgettext('attachment_tnef',"Street") ),
            TNEF_MAPI_HOME_ADDR_PO_BOX  => array (dgettext('attachment_tnef',"Home Address"),
                                                  dgettext('attachment_tnef',"PO Box") ),
            TNEF_MAPI_OTHER_ADDR_CITY   => array (dgettext('attachment_tnef',"Other Address"),
                                                  dgettext('attachment_tnef',"City") ),
            TNEF_MAPI_OTHER_ADDR_COUNTRY => array (dgettext('attachment_tnef',"Other Address"),
                                                   dgettext('attachment_tnef',"Country") ),
            TNEF_MAPI_OTHER_ADDR_ZIP     => array (dgettext('attachment_tnef',"Other Address"),
                                                   dgettext('attachment_tnef',"Zip") ),
            TNEF_MAPI_OTHER_ADDR_STATE   => array (dgettext('attachment_tnef',"Other Address"),
                                                   dgettext('attachment_tnef',"State") ),
            TNEF_MAPI_OTHER_ADDR_STREET  => array (dgettext('attachment_tnef',"Other Address"),
                                                   dgettext('attachment_tnef',"Street") ),
            TNEF_MAPI_OTHER_ADDR_PO_BOX  => array (dgettext('attachment_tnef',"Other Address"),
                                                   dgettext('attachment_tnef',"PO Box") ));

        $rc = false;

        if ($length > 0) {
            if (array_key_exists($attr_name, $address_mapping)) {
                $address_key = $address_mapping[$attr_name];
                if (!array_key_exists($address_key[0], $this->addresses)) {
                    $this->addresses[$address_key[0]] = array ( );
                }
                $this->addresses[$address_key[0]][$address_key[1]] = $value;
                $rc = true;
            }
        }
        return $rc;
    }

    /**
     * Detects website attributes and adds them to homepages property
     *
     * @param integer $attr_type
     * @param integer $attr_name
     * @param string $value
     * @param integer $length
     * @return boolean true, if website attribute type is detected.
     */
    function evaluateHomepageAttribute($attr_type, $attr_name, $value, $length) {
        $homepage_mapping = array (
            TNEF_MAPI_PERSONAL_HOME_PAGE    =>  dgettext('attachment_tnef',"Personal Homepage"),
            TNEF_MAPI_BUSINESS_HOME_PAGE    =>  dgettext('attachment_tnef',"Business Homepage"),
            TNEF_MAPI_OTHER_HOME_PAGE       =>  dgettext('attachment_tnef',"Other Homepage"));

        $rc = false;

        if ($length > 0) {
            if (array_key_exists($attr_name, $homepage_mapping)) {
                $homepage_key = $homepage_mapping[$attr_name];
                $this->homepages[$homepage_key] = $value;
                $rc = true;
                if ($this->tnef_debug) {
                    tnef_log("Setting homepage '$homepage_key' to value '$value'");
                }
            }
        }

        return $rc;
    }
}
