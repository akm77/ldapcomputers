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

   function __construct() {
      $this->table = "glpi_plugin_ldapcomputers_configs";
   }

   static function getTypeName($nb = 0) {
      return _n('LDAP directory', 'LDAP directories', $nb);
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
            $this->fields['port']                      = "389";
            $this->fields['condition']
               = '(&(&(&(samAccountType=805306369)(!(primaryGroupId=516)))(objectCategory=computer)(!(operatingSystem=Windows Server*))))';
            $this->fields['use_tls']                   = 0;
            $this->fields['use_dn']                    = 1;
            $this->fields['can_support_pagesize']      = 1;
            $this->fields['pagesize']                  = '1000';
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
      return $input;
   }

   static function getSpecificValueToDisplay($field, $values, array $options = []) {
      if (!is_array($values)) {
         $values = [$field => $values];
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {
      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids) {
      $input = $ma->getInput();
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }

   /**
    * Print the config ldap form
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
         echo "</td></tr>";
         echo "<tr class='tab_bg_1'>";
         echo "<td><label for='comment'>".__('Comments')."</label></td>";
         echo "<td class='middle'>";
         echo "<textarea cols='40' rows='4' name='comment' id='comment'>".$this->fields["comment"]."</textarea>";
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
      if (PluginLdapcomputersLdap::isLdapPageSizeAvailable(false, false)) {
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

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('How long to keep outdated computers', 'ldapcomputers') . "</td><td colspan='4'>";
      Dropdown::showNumber("retention_date", ['value' => $this->fields['retention_date'],
                                              'min'   => 5,
                                              'max'   => 365,
                                              'step'  => 5]);
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
    * Show config LDAP backups form
    *
    * @var DBmysql $DB
    *
    * @return void
    */
   function showFormLdapBackupsConfig() {
      global $DB;
      $ID     = $this->getField('id');
      $target = $this->getFormURL();
      $rand   = mt_rand();
      PluginLdapcomputersLdapbackup::addNewBackupLdapForm($target, $ID);
      $iterator = $DB->request([
         'FROM'   => 'glpi_plugin_ldapcomputers_ldapbackups',
         'WHERE'  => [
            'primary_ldap_id' => $ID
         ],
         'ORDER'  => ['name']
      ]);
      if (($nb = count($iterator)) > 0) {
         echo "<br>";
         echo "<div class='center'>";
         Html::openMassiveActionsForm('massLdapBackups'.$rand);
         $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $nb),
                                      'container'     => 'massLdapBackups'.$rand];
         Html::showMassiveActions($massiveactionparams);
         echo "<input type='hidden' name='id' value='$ID'>";
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr class='noHover'>".
              "<th colspan='4'>".__('List of LDAP directory replicates') . "</th></tr>";
         if (isset($_SESSION["LDAP_TEST_MESSAGE"])) {
            echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
            echo $_SESSION["LDAP_TEST_MESSAGE"];
            echo"</td></tr>";
            unset($_SESSION["LDAP_TEST_MESSAGE"]);
         }
         $header_begin   = "<tr>";
         $header_top     = "<th>".Html::getCheckAllAsCheckbox('massLdapBackups'.$rand)."</th>";
         $header_bottom  = "<th>".Html::getCheckAllAsCheckbox('massLdapBackups'.$rand)."</th>";
         $header_end     = "<th class='center b'>".__('Name')."</th>";
         $header_end    .= "<th class='center b'>"._n('Replicate', 'Replicates', 1)."</th>".
              "<th class='center'></th></tr>";
         echo $header_begin.$header_top.$header_end;
         while ($ldap_backup = $iterator->next()) {
            echo "<tr class='tab_bg_1'><td class='center' width='10'>";
            Html::showMassiveActionCheckBox('PluginLdapcomputersLdapbackup', $ldap_backup["id"]);
            echo "</td>";
            echo "<td class='center'>" . $ldap_backup["name"] . "</td>";
            echo "<td class='center'>".sprintf(__('%1$s: %2$s'), $ldap_backup["host"],
                                               $ldap_backup["port"]);
            echo "</td>";
            echo "<td class='center'>";
            Html::showSimpleForm(static::getFormURL(),
                                 'test_ldap_backup', _sx('button', 'Test'),
                                 ['id'                => $ID,
                                       'ldap_backup_id' => $ldap_backup["id"]]);
            echo "</td></tr>";
         }
         echo $header_begin.$header_bottom.$header_end;
         echo "</table>";
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
         echo "</div>";
      }
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
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'retention_date',
         'name'               => __('Delete after days'),
         'datatype'           => 'integer',
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
    * Form to choose a ldap server
    *
    * @param string $target target page for the form
    *
    * @return void
    */
   static function ldapChooseDirectory($target) {
      global $DB;
      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'is_active' => 1
         ],
         'ORDER'  => 'name ASC'
      ]);
      if (count($iterator) == 1) {
         //If only one server, do not show the choose ldap server window
         $ldap                    = $iterator->next();
         $_SESSION['ldap_computers_import']['primary_ldap_id'] = $ldap["id"];
         Html::redirect($_SERVER['PHP_SELF']);
      }
      echo "<div class='center'>";
      echo "<form action='$target' method=\"post\">";
      echo "<p>" . __('Please choose LDAP directory to import users and groups from') . "</p>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'><th colspan='2'>" . __('LDAP directory choice') . "</th></tr>";
      //If more than one ldap server
      if (count($iterator) > 1) {
         echo "<tr class='tab_bg_2'><td class='center'>" . __('Name') . "</td>";
         echo "<td class='center'>";
         PluginLdapcomputersConfig::Dropdown(['name'                => 'ldap_server',
                             'display_emptychoice' => false,
                             'comment'             => true,
                             'condition'           => ['is_active' => 1]]);
         echo "</td></tr>";
         echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
         echo "<input class='submit' type='submit' name='ldap_showcomputers' value=\"".
               _sx('button', 'Post') . "\"></td></tr>";
      } else {
         //No ldap server
         echo "<tr class='tab_bg_2'>".
              "<td class='center' colspan='2'>".__('No LDAP directory defined in GLPI')."</td></tr>";
      }
      echo "</table>";
      Html::closeForm();
      echo "</div>";
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
    * Is the LDAP authentication used?  AKM - need to clarify
    *
    * @return boolean
    */
   static function useLdapComputers() {
      return (countElementsInTable('glpi_plugin_ldapcomputers_configs', ['is_active' => 1]) > 0);
   }


   /**
    * Get number of servers
    *
    * @var DBmysql $DB
    *
    * @return integer
    */
   static function getNumberOfServers() {
      return countElementsInTable('glpi_plugin_ldapcomputers_configs', ['is_active' => 1]);
   }

   /**
    * Get default ldap
    *
    * @var DBmysql $DB DB instance
    *
    * @return integer
    */
   static function getDefault() {
      global $DB;

      foreach ($DB->request('glpi_plugin_ldapcomputers_configs', ['is_default' => 1, 'is_active' => 1]) as $data) {
         return $data['id'];
      }
      return 0;
   }

   function post_updateItem($history = 1) {
      global $DB;
      if (in_array('is_default', $this->updates) && $this->input["is_default"]==1) {
         $DB->update(
            $this->getTable(),
            ['is_default' => 0],
            ['id' => ['<>', $this->input['id']]]
         );
      }
   }

   function post_addItem() {
      global $DB;
      if (isset($this->fields['is_default']) && $this->fields["is_default"]==1) {
         $DB->update(
            $this->getTable(),
            ['is_default' => 0],
            ['id' => ['<>', $this->fields['id']]]
         );
      }
   }

   function prepareInputForAdd($input) {
      //If it's the first ldap directory then set it as the default directory
      if (!self::getNumberOfServers()) {
         $input['is_default'] = 1;
      }
      if (isset($input["rootdn_passwd"]) && !empty($input["rootdn_passwd"])) {
         $input["rootdn_passwd"] = Toolbox::encrypt(stripslashes($input["rootdn_passwd"]), GLPIKEY);
      }
      return $input;
   }

   function cleanDBonPurge() {
      Rule::cleanForItemCriteria($this, 'LDAP_SERVER');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (!$withtemplate
          && $item->can($item->getField('id'), READ)) {
         $ong     = [];
         $ong[1]  = _sx('button', 'Test');                     // test connexion
         // TODO clean fields entity_XXX if not used
         // $ong[4]  = __('Entity');                  // params for entity config
         $ong[2]  = __('Advanced information');   // params for entity advanced config
         $ong[3]  = _n('Replicate', 'Replicates', Session::getPluralNumber());
         return $ong;
      }
      return '';
   }

   /**
    * Choose wich form to show
    *
    * @param CommonGLPI $item         Item instance
    * @param integer    $tabnum       Tab number
    * @param integer    $withtemplate Unused
    *
    * @return boolean (TRUE)
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch ($tabnum) {
         case 1 :
            $item->showFormTestLDAP();
            break;
         case 2 :
            $item->showFormAdvancedConfig();
            break;
         case 3 :
            $item->showFormLdapBackupsConfig();
            break;
      }
      return true;
   }

   /**
    * Get all LDAP backup servers for a master one
    *
    * @param integer $master_id master ldap server ID
    *
    * @return array of the LDAP backup servers
    */
   static function getAllBackupsForAMaster($master_id) {
      global $DB;
      $backup_ldaps = [];
      $criteria = ['FIELDS' => ['id', 'host', 'port'],
                   'FROM'   => 'glpi_plugin_ldapcomputers_ldapbackups',
                   'WHERE'  => ['primary_ldap_id' => $master_id]
                  ];
      foreach ($DB->request($criteria) as $backup_ldap) {
         $backup_ldaps[] = ["id"   => $backup_ldap["id"],
                            "host" => $backup_ldap["host"],
                            "port" => $backup_ldap["port"]
                           ];
      }
      return $backup_ldaps;
   }

}