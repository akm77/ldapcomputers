<?php
/*
 -------------------------------------------------------------------------
 LDAP computers plugin for GLPI
 Copyright (C) 2019 by the ldapcomputers Development Team.

 https://github.com/pluginsGLPI/ldapcomputers
 -------------------------------------------------------------------------

 LICENSE

 This file is part of LDAP computers.

 LDAP computers is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 LDAP computers is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with LDAP computers. If not, see <http://www.gnu.org/licenses/>.

------------------------------------------------------------------------

   @package   Plugin LDAP Computers
   @author    Aleksey Kotryakhov
   @co-author
   @copyright Copyright (c) 2009-2016 Barcode plugin Development team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://github.com/akm77/ldapcomputers
   @since     2019


 --------------------------------------------------------------------------
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginLdapcomputersLdap
 */
class PluginLdapcomputersLdap extends CommonDBTM {

   /**
    * Converts LDAP timestamps over to Unix timestamps
    *
    * @param string  $ldapstamp        LDAP timestamp
    * @param integer $ldap_time_offset time offset (default 0)
    *
    * @return integer unix timestamp
    */

   static function ldapStamp2UnixStamp($ldapstamp, $ldap_time_offset = 0) {
      global $CFG_GLPI;
      //Check if timestamp is well format, otherwise return ''
      if (!preg_match("/[\d]{14}(\.[\d]{0,4})*Z/", $ldapstamp)) {
         return '';
      }
      $year    = substr($ldapstamp, 0, 4);
      $month   = substr($ldapstamp, 4, 2);
      $day     = substr($ldapstamp, 6, 2);
      $hour    = substr($ldapstamp, 8, 2);
      $minute  = substr($ldapstamp, 10, 2);
      $seconds = substr($ldapstamp, 12, 2);
      $stamp   = gmmktime($hour, $minute, $seconds, $month, $day, $year);
      $stamp  += $CFG_GLPI["time_offset"]-$ldap_time_offset;
      return $stamp;
   }

   /**
    * Converts a Unix timestamp to an LDAP timestamps
    *
    * @param string $date datetime
    *
    * @return string ldap timestamp
    */
   static function date2ldapTimeStamp($date) {
       return date("YmdHis", strtotime($date)).'.0Z';
   }

   /**
    * Connect to a LDAP server
    *
    * @param string  $host          LDAP host to connect
    * @param string  $port          port to use
    * @param string  $login         login to use (default '')
    * @param string  $password      password to use (default '')
    * @param boolean $use_tls       use a TLS connection? (false by default)
    * @param integer $deref_options deref options used
    *
    * @return resource link to the LDAP server : false if connection failed
    */
   static function connectToServer($host, $port, $login = "", $password = "",
                                   $use_tls = false, $deref_options = 0) {
      $ds = @ldap_connect($host, intval($port));
      if ($ds) {
         @ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
         @ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
         @ldap_set_option($ds, LDAP_OPT_DEREF, $deref_options);
         if ($use_tls) {
            if (!@ldap_start_tls($ds)) {
               return false;
            }
         }
         // Auth bind
         if ($login != '') {
            $b = @ldap_bind($ds, $login, $password);
         } else { // Anonymous bind
            $b = @ldap_bind($ds);
         }
         if ($b) {
            return $ds;
         }
      }
      return false;
   }

   /**
    * Try to connect to a ldap server
    *
    * @param array  $ldap_method ldap_method array to use
    * @param string $login       User Login
    * @param string $password    User Password
    *
    * @return resource|boolean link to the LDAP server : false if connection failed
    */
   static function tryToConnectToServer($ldap_method, $login, $password) {
      if (!function_exists('ldap_connect')) {
         Toolbox::logError("ldap_connect function is missing. Did you miss install php-ldap extension?");
         return false;
      }
      $ds = self::connectToServer($ldap_method['host'], $ldap_method['port'],
      $ldap_method['rootdn'],
      Toolbox::decrypt($ldap_method['rootdn_passwd'], GLPIKEY),
      $ldap_method['use_tls'], $ldap_method['deref_option']);
      // Test with login and password of the user if exists
      if (!$ds && !empty($login)) {
         $ds = self::connectToServer($ldap_method['host'], $ldap_method['port'], $login,
         $password, $ldap_method['use_tls'],
         $ldap_method['deref_option']);
      }
      //If connection is not successfull on this directory, try LDAP backups (if backups exists)
      if (!$ds && ($ldap_method['id'] > 0)) {
         foreach (PluginLdapcomputersConfig::getAllBackupsForAMaster($ldap_method['id']) as $backup_ldap) {
            $ds = self::connectToServer($backup_ldap["host"], $backup_ldap["port"],
            $ldap_method['rootdn'],
            Toolbox::decrypt($ldap_method['rootdn_passwd'], GLPIKEY),
            $ldap_method['use_tls'], $ldap_method['deref_option']);
            // Test with login and password of the user
            if (!$ds && !empty($login)) {
               $ds = self::connectToServer($backup_ldap["host"], $backup_ldap["port"], $login,
               $password, $ldap_method['use_tls'],
               $ldap_method['deref_option']);
            }
            if ($ds) {
               return $ds;
            }
         }
      }
      return $ds;
   }

   /**
    * Test a LDAP connection
    *
    * @param integer $ldap_id     ID of the LDAP server
    * @param integer $backup_ldap_id use a backup if > 0 (default -1)
    *
    * @return boolean connection succeeded?
    */
   static function testLDAPConnection($ldap_id, $backup_ldap_id = -1) {

      $config_ldap = new PluginLdapcomputersConfig();
      $res         = $config_ldap->getFromDB($ldap_id);
      // we prevent some delay...
      if (!$res) {
         return false;
      }
      //Test connection to a LDAP backup
      if ($backup_ldap_id != -1) {
         $backup_ldap = new PluginLdapcomputersLdapbackup();
         $backup_ldap->getFromDB($backup_ldap_id);
         $host = $backup_ldap->fields["host"];
         $port = $backup_ldap->fields["port"];
      } else {
         //Test connection to a master ldap server
         $host = $config_ldap->fields['host'];
         $port = $config_ldap->fields['port'];
      }
      $ds = self::connectToServer($host, $port, $config_ldap->fields['rootdn'],
                                  Toolbox::decrypt($config_ldap->fields['rootdn_passwd'], GLPIKEY),
                                  $config_ldap->fields['use_tls'],
                                  $config_ldap->fields['deref_option']);
      if ($ds) {
         return true;
      }
      return false;
   }

   /**
    * Get ldap query results and clean them at the same time
    *
    * @param resource $link   link to the directory connection
    * @param array    $result the query results
    *
    * @return array which contains ldap query results
    */
   static function get_entries_clean($link, $result) {
      return ldap_get_entries($link, $result);
   }

   /**
    * Get a LDAP field value
    *
    * @param array  $infos LDAP entry infos
    * @param string $field Field name to retrieve
    *
    * @return string
    */
   public static function getFieldValue($infos, $field) {
      $value = null;
      if (is_array($infos[$field])) {
         $value = $infos[$field][0];
      } else {
         $value = $infos[$field];
      }
      if ($field != 'objectguid') {
         return $value;
      }
      //handle special objectguid from AD directories
      try {
         //prevent double encoding
         if (!self::isValidGuid($value)) {
            $value = self::guidToString($value);
            if (!self::isValidGuid($value)) {
               throw new \RuntimeException('Not an objectguid!');
            }
         }
      } catch (\Exception $e) {
           //well... this is not an objectguid apparently
           $value = $infos[$field];
      }
         return $value;
   }

   /**
    * Converts a string representation of an objectguid to hexadecimal
    * Used to build filters
    *
    * @param string $guid_str String representation
    *
    * @return string
    */
   public static function guidToHex($guid_str) {
      $str_g = explode('-', $guid_str);
      $str_g[0] = strrev($str_g[0]);
      $str_g[1] = strrev($str_g[1]);
      $str_g[2] = strrev($str_g[2]);
      $guid_hex = '\\';
      $strrev = 0;
      foreach ($str_g as $str) {
         for ($i = 0; $i < strlen($str)+2; $i++) {
            if ($strrev < 3) {
               $guid_hex .= strrev(substr($str, 0, 2)).'\\';
            } else {
               $guid_hex .= substr($str, 0, 2).'\\';
            }
            $str = substr($str, 2);
         }
         if ($strrev < 3) {
            $guid_hex .= strrev($str);
         } else {
            $guid_hex .= $str;
         }
         $strrev++;
      }
      return $guid_hex;
   }

   /**
    * Converts binary objectguid to string representation
    *
    * @param mixed $binary_guid Binary objectguid from AD
    *
    * @return string
    */
   public static function guidToString($guid_bin) {
      $guid_hex = unpack("H*hex", $guid_bin);
      $hex = $guid_hex["hex"];
      $hex1 = substr($hex, -26, 2) . substr($hex, -28, 2) . substr($hex, -30, 2) . substr($hex, -32, 2);
      $hex2 = substr($hex, -22, 2) . substr($hex, -24, 2);
      $hex3 = substr($hex, -18, 2) . substr($hex, -20, 2);
      $hex4 = substr($hex, -16, 4);
      $hex5 = substr($hex, -12, 12);
      $guid_str = $hex1 . "-" . $hex2 . "-" . $hex3 . "-" . $hex4 . "-" . $hex5;
      return $guid_str;
   }

   /**
    * Check if text representation of an objectguid is valid
    *
    * @param string $string Strign representation
    *
    * @return boolean
    */
   public static function isValidGuid($guid_str) {
      return (bool) preg_match('/^([0-9a-fA-F]){8}(-([0-9a-fA-F]){4}){3}-([0-9a-fA-F]){12}$/', $guid_str);
   }

}