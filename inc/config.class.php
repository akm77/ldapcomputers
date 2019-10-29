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
 * Class PluginLdapcomputersConfig
 */
class PluginLdapcomputersConfig extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;

   static $rightname = 'plugin_ldapcomputers_config';

   //connection caching stuff
   static $conn_cache = [];

   static function getTypeName($nb = 0) {
         return _n('LDAP computers', 'LDAP computers', $nb);
   }

   static function canCreate() {
      return static::canUpdate();
   }

   static function canPurge() {
      return static::canUpdate();
   }

   function post_getEmpty() {

      $this->fields['port']                        = '389';
      $this->fields['condition']                   = '';
      $this->fields['use_tls']                     = 0;
      $this->fields['comment_field']               = '';
      $this->fields['use_dn']                      = 0;
   }

   static public function unsetUndisclosedFields(&$fields) {
      unset($fields['rootdn_passwd']);
   }

   /**
    * Preconfig datas for standard system
    *
    * @param string $type type of standard system : AD
    *
    * @return void
    */
   function preconfig($type) {

      switch ($type) {
         case 'AD' :
            $this->fields['port']                 = "389";
            $this->fields['condition']
               = '(&(&(&(samAccountType=805306369)(!(primaryGroupId=516)))
                  (objectCategory=computer)(!(operatingSystem=Windows Server*))))';
            $this->fields['use_tls']              = 0;
            $this->fields['use_dn']               = 1;
            $this->fields['can_support_pagesize'] = 1;
            $this->fields['pagesize']             = '1000';
            break;

         default:
            $this->post_getEmpty();
      }
   }

   function prepareInputForUpdate($input) {

      if (isset($input["rootdn_passwd"])) {
         if (empty($input["rootdn_passwd"])) {
            unset($input["rootdn_passwd"]);
         } else {
            $input["rootdn_passwd"] = Toolbox::encrypt(stripslashes($input["rootdn_passwd"]),
                                                          GLPIKEY);
         }
      }

      if (isset($input["_blank_passwd"]) && $input["_blank_passwd"]) {
         $input['rootdn_passwd'] = '';
      }

      // Set attributes in lower case
      if (count($input)) {
         foreach ($input as $key => $val) {
            if (preg_match('/_field$/', $key)) {
               $input[$key] = Toolbox::strtolower($val);
            }
         }
      }
   }

   /**
    * Print the ldapcomputers config form
    *
    * @param integer $ID      ID of the item
    * @param array   $options Options
    *     - target for the form
    *
    * @return void (display)
    */
   function showForm($ID, $options = []) {

      if (!Config::canUpdate()) {
         return false;
      }
      if (empty($ID)) {
         $this->getEmpty();
         if (isset($options['preconfig'])) {
            $this->preconfig($options['preconfig']);
         }
      } else {
         $this->getFromDB($ID);
      }

      if (Toolbox::canUseLdap()) {
         $this->showFormHeader($options);
         if (empty($ID)) {
            $target = $this->getFormURL();
            echo "<tr class='tab_bg_2'><td>".__('Preconfiguration')."</td> ";
            echo "<td colspan='3'>";
            echo "<a href='$target?preconfig=AD'>".__('Active Directory')."</a>";
            echo "&nbsp;&nbsp;/&nbsp;&nbsp;";
            echo "<a href='$target?preconfig=default'>".__('Default values');
            echo "</a></td></tr>";
         }
         echo "<tr class='tab_bg_1'><td><label for='name'>" . __('Name') . "</label></td>";
         echo "<td><input type='text' id='name' name='name' value='". $this->fields["name"] ."'></td>";
         if ($ID > 0) {
            echo "<td>".__('Last update')."</td><td>".Html::convDateTime($this->fields["date_mod"]);
         } else {
            echo "<td colspan='2'>&nbsp;";
         }
         echo "</td></tr>";

         $defaultrand = mt_rand();
         echo "<tr class='tab_bg_1'><td><label for='dropdown_is_default$defaultrand'>" . __('Default server') . "</label></td>";
         echo "<td>";
         Dropdown::showYesNo('is_default', $this->fields['is_default'], -1, ['rand' => $defaultrand]);
         echo "</td>";
         $activerand = mt_rand();
         echo "<td><label for='dropdown_is_active$activerand'>" . __('Active'). "</label></td>";
         echo "<td>";
         Dropdown::showYesNo('is_active', $this->fields['is_active'], -1, ['rand' => $activerand]);
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td><label for='host'>" . __('Server') . "</label></td>";
         echo "<td><input type='text' id='host' name='host' value='" . $this->fields["host"] . "'></td>";
         echo "<td><label for='port'>" . __('Port (default=389)') . "</label></td>";
         echo "<td><input id='port' type='text' id='port' name='port' value='".$this->fields["port"]."'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td><label for='condition'>" . __('Connection filter') . "</label></td>";
         echo "<td colspan='3'>";
         echo "<textarea cols='100' rows='1' id='condition' name='condition'>".$this->fields["condition"];
         echo "</textarea>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td><label for='basedn'>" . __('BaseDN') . "</label></td>";
         echo "<td colspan='3'>";
         echo "<input type='text' id='basedn' name='basedn' size='100' value=\"".$this->fields["basedn"]."\">";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td><label for='rootdn'>" . __('RootDN (for non anonymous binds)') . "</label></td>";
         echo "<td colspan='3'><input type='text' name='rootdn' id='rootdn' size='100' value=\"".
                $this->fields["rootdn"]."\">";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td><label for='rootdn_passwd'>" .
            __('Password (for non-anonymous binds)') . "</label></td>";
         echo "<td><input type='password' id='rootdn_passwd' name='rootdn_passwd' value='' autocomplete='off'>";
         if ($ID) {
            echo "<input type='checkbox' name='_blank_passwd' id='_blank_passwd'>&nbsp;"
               . "<label for='_blank_passwd'>" . __('Clear') . "</label>";
         }
         echo "</td>";
         echo "<td rowspan='3'><label for='comment'>".__('Comments')."</label></td>";
         echo "<td rowspan='3' class='middle'>";
         echo "<textarea cols='40' rows='4' name='comment' id='comment'>".$this->fields["comment"]."</textarea>";
         echo "</td></tr>";

         echo ">";
         echo "</td></tr>";

         //Fill fields when using preconfiguration models
         if (!$ID) {
            $hidden_fields = ['comment_field', 'condition', 'port', 'use_dn', 'use_tls'];

            foreach ($hidden_fields as $hidden_field) {
               echo "<input type='hidden' name='$hidden_field' value='".
                      $this->fields[$hidden_field]."'>";
            }
         }

         echo "</td></tr>";

         $this->showFormButtons($options);

      } else {
         echo "<div class='center'>&nbsp;<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . self::getTypeName(1) . "</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<p class='red'>".sprintf(__('%s extension is missing'), 'LDAP')."</p>";
         echo "<p>".__('Impossible to use LDAP as external source of connection')."</p>".
              "</td></tr></table>";

         echo "<p><strong>".GLPINetwork::getErrorMessage()."</strong></p>";
         echo "</div>";

      }
   }

   /**
    * Show advanced config form
    *
    * @return void
    */
   function showFormAdvancedConfig() {

      $ID = $this->getField('id');
      $hidden = '';

      echo "<div class='center'>";
      echo "<form method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_2'><th colspan='4'>";
      echo "<input type='hidden' name='id' value='$ID'>". __('Advanced information')."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Use TLS') . "</td><td>";
      if (function_exists("ldap_start_tls")) {
         Dropdown::showYesNo('use_tls', $this->fields["use_tls"]);
      } else {
         echo "<input type='hidden' name='use_tls' value='0'>".__('ldap_start_tls does not exist');
      }
      echo "</td>";
      echo "<td>" . __('LDAP directory time zone') . "</td><td>";
      Dropdown::showGMT("time_offset", $this->fields["time_offset"]);
      echo"</td></tr>";

      if (self::isLdapPageSizeAvailable(false, false)) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('Use paged results') . "</td><td>";
         Dropdown::showYesNo('can_support_pagesize', $this->fields["can_support_pagesize"]);
         echo "</td>";
         echo "<td>" . __('Page size') . "</td><td>";
         Dropdown::showNumber("pagesize", ['value' => $this->fields['pagesize'],
                                                'min'   => 100,
                                                'max'   => 100000,
                                                'step'  => 100]);
         echo"</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('Maximum number of results') . "</td><td>";
         Dropdown::showNumber('ldap_maxlimit', ['value' => $this->fields['ldap_maxlimit'],
                                                     'min'   => 100,
                                                     'max'   => 999999,
                                                     'step'  => 100,
                                                     'toadd' => [0 => __('Unlimited')]]);
         echo "</td><td colspan='2'></td></tr>";

      } else {
         $hidden .= "<input type='hidden' name='can_support_pagesize' value='0'>";
         $hidden .= "<input type='hidden' name='pagesize' value='0'>";
         $hidden .= "<input type='hidden' name='ldap_maxlimit' value='0'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('How LDAP aliases should be handled') . "</td><td colspan='4'>";
      $alias_options = [
         LDAP_DEREF_NEVER     => __('Never dereferenced (default)'),
         LDAP_DEREF_ALWAYS    => __('Always dereferenced'),
         LDAP_DEREF_SEARCHING => __('Dereferenced during the search (but not when locating)'),
         LDAP_DEREF_FINDING   => __('Dereferenced when locating (not during the search)'),
      ];
      Dropdown::showFromArray("deref_option", $alias_options,
                              ['value' => $this->fields["deref_option"]]);
      echo"</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
      echo "<input type='submit' name='update' class='submit' value=\"".__s('Save')."\">";
      echo $hidden;
      echo "</td></tr>";

      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }

   /**
    * Show ldap test form
    *
    * @return void
    */
   function showFormTestLDAP () {

      $ID = $this->getField('id');

      if ($ID > 0) {
         echo "<div class='center'>";
         echo "<form method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         echo "<input type='hidden' name='id' value='$ID'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='4'>" . __('Test of connection to LDAP directory') . "</th></tr>";

         if (isset($_SESSION["LDAP_TEST_MESSAGE"])) {
            echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
            echo $_SESSION["LDAP_TEST_MESSAGE"];
            echo"</td></tr>";
            unset($_SESSION["LDAP_TEST_MESSAGE"]);
         }

         echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
         echo "<input type='submit' name='test_ldap' class='submit' value=\"".
                _sx('button', 'Test')."\">";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }
   }
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => $this->getTypeName(1)
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'datatype'           => 'number',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'host',
         'name'               => __('Server'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'port',
         'name'               => __('Port'),
         'datatype'           => 'integer'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'basedn',
         'name'               => __('BaseDN'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'condition',
         'name'               => __('Connection filter'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'is_default',
         'name'               => __('Default server'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '18',
         'table'              => $this->getTable(),
         'field'              => 'use_dn',
         'name'               => __('Use DN in the search'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '121',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '30',
         'table'              => $this->getTable(),
         'field'              => 'is_active',
         'name'               => __('Active'),
         'datatype'           => 'bool'
      ];

      return $tab;
   }

   /**
    * Show system informations form
    *
    * @param integer $width The number of characters at which the string will be wrapped.
    *
    * @return void
    */
   function showSystemInformations($width) {

      // No need to translate, this part always display in english (for copy/paste to forum)

      $ldap_servers = self::getLdapServers();

      if (!empty($ldap_servers)) {
         echo "<tr class='tab_bg_2'><th>" . self::getTypeName(Session::getPluralNumber()) . "</th></tr>\n";
         echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";
         foreach ($ldap_servers as $value) {
            $fields = ['Server'            => 'host',
                            'Port'              => 'port',
                            'BaseDN'            => 'basedn',
                            'Connection filter' => 'condition',
                            'RootDN'            => 'rootdn',
                            'Use TLS'           => 'use_tls'];
            $msg   = '';
            $first = true;
            foreach ($fields as $label => $field) {
               $msg .= (!$first ? ', ' : '').
                        $label.': '.
                        ($value[$field]? '\''.$value[$field].'\'' : 'none');
               $first = false;
            }
            echo wordwrap($msg."\n", $width, "\n\t\t");
         }
         echo "\n</pre></td></tr>";
      }
   }

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
    * Test a LDAP connection
    *
    * @param integer $auths_id     ID of the LDAP server
    * @param integer $primary_ldap_id use a backup if > 0 (default -1)
    *
    * @return boolean connection succeeded?
    */
   static function testLDAPConnection($auths_id, $primary_ldap_id = -1) {

      $config_ldap = new self();
      $res         = $config_ldap->getFromDB($auths_id);

      // we prevent some delay...
      if (!$res) {
         return false;
      }

      //Test connection to a backup
      if ($primary_ldap_id != -1) {
         $backup_ldap = new PluginLdapcomputersConfigbackupldap();
         $backup_ldap->getFromDB($primary_ldap_id);
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
    * Display a warnign about size limit
    *
    * @since 0.84
    *
    * @param boolean $limitexceeded (false by default)
    *
    * @return void
    */
   static function displaySizeLimitWarning($limitexceeded = false) {
      global $CFG_GLPI;

      if ($limitexceeded) {
         echo "<div class='firstbloc'><table class='tab_cadre_fixe'>";
         echo "<tr><th class='red'>";
         echo "<img class='center' src='".$CFG_GLPI["root_doc"]."/pics/warning.png'
                alt='".__('Warning')."'>&nbsp;".
             __('Warning: The request exceeds the limit of the directory. The results are only partial.');
         echo "</th></tr></table><div>";
      }
   }

   /**
    * Check if a user DN exists in a ldap user search result
    *
    * @since 0.84
    *
    * @param array  $ldap_infos ldap user search result
    * @param string $user_dn    user dn to look for
    *
    * @return boolean false if the user dn doesn't exist, user ldap infos otherwise
    */
   static function dnExistsInLdap($ldap_infos, $user_dn) {

      $found = false;
      foreach ($ldap_infos as $ldap_info) {
         if ($ldap_info['user_dn'] == $user_dn) {
            $found = $ldap_info;
            break;
         }
      }
      return $found;
   }
   /**
    * Open LDAP connection to current server
    *
    * @return resource|boolean
    */
   function connect() {

      return $this->connectToServer($this->fields['host'], $this->fields['port'],
                                    $this->fields['rootdn'],
                                    Toolbox::decrypt($this->fields['rootdn_passwd'], GLPIKEY),
                                    $this->fields['use_tls'],
                                    $this->fields['deref_option']);
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
      if (!$ds
          && !empty($login)) {
         $ds = self::connectToServer($ldap_method['host'], $ldap_method['port'], $login,
                                     $password, $ldap_method['use_tls'],
                                     $ldap_method['deref_option']);
      }

      //If connection is not successfull on this directory, try backup (if backup exists)
      if (!$ds
          && ($ldap_method['id'] > 0)) {
         foreach (self::getAllBackupsForAPrimary($ldap_method['id']) as $ldap_backup) {
            $ds = self::connectToServer($ldap_backup["host"], $ldap_backup["port"],
                                        $ldap_method['rootdn'],
                                        Toolbox::decrypt($ldap_method['rootdn_passwd'], GLPIKEY),
                                        $ldap_method['use_tls'], $ldap_method['deref_option']);

            // Test with login and password of the user
            if (!$ds
                && !empty($login)) {
               $ds = self::connectToServer($ldap_backup["host"], $ldap_backup["port"], $login,
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
    * Get all backup LDAP servers for a primary one
    *
    * @param integer $primary_ldap_id primary ldap server ID
    *
    * @return array of the backup LDAP servers
    */
   static function getAllBackupsForAPrimary($primary_ldap_id) {
      global $DB;

      $ldap_backups = [];
      $criteria = ['FIELDS' => ['id', 'host', 'port'],
                'FROM'   => 'glpi_plugin_ldapcomputers_ldap_backups',
                'WHERE'  => ['primary_ldap_id' => $primary_ldap_id]
               ];
      foreach ($DB->request($criteria) as $ldap_backup) {
         $ldap_backups[] = ["id"   => $ldap_backup["id"],
                          "host" => $ldap_backup["host"],
                          "port" => $ldap_backup["port"]
                         ];
      }
      return $ldap_backups;
   }

   /**
    * Get LDAP servers
    *
    * @return array
    */
   static function getLdapServers() {
      return getAllDatasFromTable('glpi_plugin_ldapcomputers_configs', [], false, '`is_default` DESC');
   }


   /**
    * Is the LDAP computers used?
    *
    * @return boolean
    */
   static function useComputersLdap() {
      return (countElementsInTable('glpi_plugin_ldapcomputers_configs', ['is_active' => 1]) > 0);
   }

}