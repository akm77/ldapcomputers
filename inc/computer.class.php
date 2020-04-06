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
 *  Class used to view and get LDAP computers
 */
class PluginLdapcomputersComputer extends CommonDBTM {

   static $rightname = 'plugin_ldapcomputers_view';

   //connection caching stuff
   static $conn_cache = [];

   static function getTypeName($nb = 0) {
      return _n('View LDAP computer', 'View LDAP computers', $nb, 'ldapcomputers');
   }

   static function canCreate() {
      return false;
   }

   static function canPurge() {
      return static::canUpdate();
   }

   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids) {

      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch ($item::getType()) {
         case Computer::getType():
            return self::createTabEntry(__('LDAP info', 'ldapcomputers'), PluginLdapcomputersComputer::countForItem($item));
            break;
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch ($item::getType()) {
         case Computer::getType():
            //display form for computers
            self::displayTabContentForComputer($item);
            break;
         case Phone::getType():
            break;
      }
      return true;
   }

   static function countForItem(Computer $item) {
      global $DB;

      $iterator = $DB->request([
         'FROM'   => 'glpi_plugin_ldapcomputers_computers',
         'WHERE'  => [
         'name'   => $item->getField('name')
         ],
         'ORDER'  => ['name']
         ]);
      return count($iterator);
   }

   private static function displayTabContentForComputer(Computer $item) {
      global $CFG_GLPI;
      global $DB;

      $iterator = $DB->request([
         'FROM'   => 'glpi_plugin_ldapcomputers_computers',
         'WHERE'  => [
         'name'   => $item->getField('name')
         ],
         'ORDER'  => ['name']
         ]);
      if (($nb = count($iterator)) > 0) {
         echo "<br>";

         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr class='noHover'>".
              "<th colspan='8'>".__('List of LDAP computers', 'ldapcomputers') . "</th></tr>";

         $header_begin   = "<tr>";
         $header_top     = "<th class='center b'>" . __('Last logon', 'ldapcomputers') . "</th>";
         $header_bottom  = "<th class='center b'>" . __('Last logon', 'ldapcomputers') . "</th>";
         $header_end     = "<th class='center b'>" . __('Logon count', 'ldapcomputers') . "</th>" .
                           "<th class='center'>"   . __('LDAP computer status', 'ldapcomputers') . "</th>" .
                           "<th class='center b'>" . __('LDAP directory') ."</th>".
                           "<th class='center b'>" . __('Distinguished name', 'ldapcomputers') ."</th>".
                           "<th class='center b'>" . __('OS', 'ldapcomputers') ."</th>".
                           "<th class='center b'>" . __('OS version', 'ldapcomputers') ."</th>".
                           "<th class='center b'>" . __('Last update') . "</th></tr>";

         echo $header_begin.$header_top.$header_end;

         while ($computer = $iterator->next()) {
            echo "<tr class='tab_bg_1'>";
            echo "<td class='center'>" . Html::convDateTime($computer["lastLogon"]) . "</td>";
            echo "<td class='center'>" . $computer["logonCount"] . "</td>";
            echo "<td class='center'>" . Dropdown::getDropdownName('glpi_plugin_ldapcomputers_states',
                                         $computer["plugin_ldapcomputers_states_id"])  . "</td>";
            echo "<td class='center'>" . Dropdown::getDropdownName('glpi_plugin_ldapcomputers_configs',
                                         $computer["plugin_ldapcomputers_configs_id"])  . "</td>";
            echo "<td class='center'>" . "<a href=\"" . self::getFormURLWithID($computer["id"]) ."\">".
                                         $computer["distinguishedName"] . "</a>" . "</td>";
            echo "<td class='center'>" . $computer["operatingSystem"] . "</td>";
            echo "<td class='center'>" . $computer["operatingSystemVersion"] . "</td>";
            echo "<td class='center'>" . Html::convDateTime($computer["date_mod"]) . "</td>";
            echo "</tr>";
         }
         echo $header_begin.$header_bottom.$header_end;
         echo "</table>";

         Html::closeForm();
         echo "</div>";
      }
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
               = '(&(&(&(samAccountType=805306369)(!(primaryGroupId=516)))
                  (objectCategory=computer)(!(operatingSystem=Windows Server*))))';
            break;

         default:
            $this->post_getEmpty();
      }
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

      $ldap_server = new PluginLdapcomputersConfig();
      $ldap_server->getFromDB($this->fields['plugin_ldapcomputers_configs_id']);

      $options['formtitle'] = sprintf(
         '%1$s - %2$s',
         $this->getTypeName(1),
         $ldap_server->getField('name')
      );

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
      echo "<td><input type='text'  id='name' name='name' value='". $this->fields["name"] ."'></td>";
      if ($ID > 0) {
         echo "<td>".__('Last update')."</td><td>".Html::convDateTime($this->fields["date_mod"]);
      } else {
         echo "<td colspan='2'>&nbsp;";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td><label for='lastLogon'>" . __('Last logon', 'ldapcomputers') . "</label></td>";
      echo "<td><input type='text' readonly id='name' name='lastLogon' value='". $this->fields["lastLogon"] ."'></td>";
      echo "<td><label for='logonCount'>" . __('Logon count', 'ldapcomputers') . "</label></td>";
      echo "<td><input type='text' readonly id='name' name='logonCount' value='". $this->fields["logonCount"] ."'></td></tr>";

      echo "<tr class='tab_bg_1'><td><label for='lastLogonTimestamp'>" . __('Last logon time stamp', 'ldapcomputers') . "</label></td>";
      echo "<td><input type='text' readonly id='name' name='lastLogonTimestamp' value='". $this->fields["lastLogonTimestamp"] ."'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='distinguishedName'>" . __('Distinguished name', 'ldapcomputers') . "</label></td>";
      echo "<td class=middle colspan='3'>";
      echo "<textarea cols='60' rows='3' readonly name='distinguishedName' id='distinguishedName'>".$this->fields["distinguishedName"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td><label for='dNSHostName'>" . __('FQDN') . "</label></td>";
      echo "<td><input type='text' readonly id='dNSHostName' name='dNSHostName' value='". $this->fields["dNSHostName"] ."'></td>";
      echo "<td><label for='objectGUID'>" . __('Object GUID', 'ldapcomputers') . "</label></td>";
      echo "<td><input type='text' readonly id='objectGUID' name='objectGUID' value='". $this->fields["objectGUID"] ."'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='operatingSystem'>" . __('OS', 'ldapcomputers') . "</label></td>";
      echo "<td><input type='text' readonly id='operatingSystem' name='operatingSystem' value='". $this->fields["operatingSystem"] ."'></td>";

      echo "<td><label for='operatingSystemVersion'>" . __('OS version', 'ldapcomputers') . "</label></td>";
      echo "<td><input type='text' readonly id='operatingSystemVersion' name='operatingSystemVersion' value='". $this->fields["operatingSystemVersion"] ."'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='operatingSystemServicePack'>" . __('OS servicepack', 'ldapcomputers') . "</label></td>";
      echo "<td><input type='text' readonly id='operatingSystemServicePack' name='operatingSystemServicePack' value='". $this->fields["operatingSystemServicePack"] ."'></td>";

      echo "<td><label for='operatingSystemHotfix'>" . __('OS hotfix', 'ldapcomputers') . "</label></td>";
      echo "<td><input type='text' readonly id='operatingSystemHotfix' name='operatingSystemHotfix' value='". $this->fields["operatingSystemHotfix"] ."'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='whenChanged'>" . __('When changed', 'ldapcomputers') . "</label></td>";
      echo "<td><input type='text' readonly id='whenChanged' name='whenChanged' value='". $this->fields["whenChanged"] ."'></td>";

      echo "<td><label for='whenCreated'>" . __('When created', 'ldapcomputers') . "</label></td>";
      echo "<td><input type='text' readonly id='whenCreated' name='whenCreated' value='". $this->fields["whenCreated"] ."'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Computer status in LDAP', 'ldapcomputers') . "</td><td>";
      PluginLdapcomputersState::Dropdown([
         'name'   => "plugin_ldapcomputers_states_id",
         'value'  => $this->fields['plugin_ldapcomputers_states_id'],
      ]);
      echo"</td>";
      $is_in_glpi_computers = mt_rand();
      echo "<td><label for='dropdown_is_default$is_in_glpi_computers'>" . __('GLPI presence', 'ldapcomputers') . "</label></td>";
      echo "<td>";
      Dropdown::showYesNo('is_in_glpi_computers', $this->fields['is_in_glpi_computers'], -1, ['rand' => $is_in_glpi_computers]);
      echo "</td></tr>";

      $this->showFormButtons($options);

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
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'datatype'           => 'number',
         'massiveaction'      => false
      ];
      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'lastLogon',
         'name'               => __('Last logon', 'ldapcomputers'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];
      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'lastLogonTimestamp',
         'name'               => __('Last logon time stamp', 'ldapcomputers'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];
      $tab[] = [
         'id'                 => '9',
         'table'              => $this->getTable(),
         'field'              => 'logonCount',
         'name'               => __('Logon count', 'ldapcomputers'),
         'datatype'           => 'integer',
         'massiveaction'      => false
      ];
      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'distinguishedName',
         'name'               => __('Distinguished name', 'ldapcomputers'),
         'datatype'           => 'text',
         'massiveaction'      => false
      ];
      $tab[] = [
         'id'                 => '13',
         'table'              => $this->getTable(),
         'field'              => 'dNSHostName',
         'name'               => __('FQDN'),
         'datatype'           => 'text',
         'massiveaction'      => false
      ];
      $tab[] = [
         'id'                 => '15',
         'table'              => $this->getTable(),
         'field'              => 'objectGUID',
         'name'               => __('Object GUID', 'ldapcomputers'),
         'datatype'           => 'text',
         'massiveaction'      => false
      ];
      $tab[] = [
         'id'                 => '17',
         'table'              => $this->getTable(),
         'field'              => 'operatingSystem',
         'name'               => __('OS', 'ldapcomputers'),
         'datatype'           => 'text',
         'massiveaction'      => false
      ];
      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'operatingSystemHotfix',
         'name'               => __('OS hotfix', 'ldapcomputers'),
         'datatype'           => 'text',
         'massiveaction'      => false
      ];
      $tab[] = [
         'id'                 => '21',
         'table'              => $this->getTable(),
         'field'              => 'operatingSystemServicePack',
         'name'               => __('OS servicepack', 'ldapcomputers'),
         'datatype'           => 'text',
         'massiveaction'      => false
      ];
      $tab[] = [
         'id'                 => '23',
         'table'              => $this->getTable(),
         'field'              => 'operatingSystemVersion',
         'name'               => __('OS version', 'ldapcomputers'),
         'datatype'           => 'text',
         'massiveaction'      => false
      ];
      $tab[] = [
         'id'                 => '25',
         'table'              => $this->getTable(),
         'field'              => 'whenChanged',
         'name'               => __('When changed', 'ldapcomputers'),
         'datatype'           => 'text',
         'massiveaction'      => false
      ];
      $tab[] = [
         'id'                 => '27',
         'table'              => $this->getTable(),
         'field'              => 'whenCreated',
         'name'               => __('When created', 'ldapcomputers'),
         'datatype'           => 'text',
         'massiveaction'      => false
      ];
      $tab[] = [
         'id'                 => '29',
         'table'              => 'glpi_plugin_ldapcomputers_states',
         'field'              => 'name',
         'name'               => __('LDAP computer status', 'ldapcomputers'),
         'datatype'           => 'dropdown',
         'displaytype'        => 'dropdown',
         'injectable'         => true
      ];
      $tab[] = [
         'id'                 => '31',
         'table'              => 'glpi_plugin_ldapcomputers_configs',
         'field'              => 'name',
         'name'               => __('LDAP directory'),
         'datatype'           => 'itemlink'
      ];
      $tab[] = [
         'id'                 => '33',
         'table'              => $this->getTable(),
         'field'              => 'is_in_glpi_computers',
         'name'               => __('GLPI presence', 'ldapcomputers'),
         'datatype'           => 'bool',
         'massiveaction'      => true
      ];
      $tab[] = [
         'id'                 => '35',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];
      $tab[] = [
         'id'                 => '37',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];
      return $tab;
   }
   /**
    * Show import computers form
    *
    * @param object $ldap_server PluginLdapcomputersConfig object
    *
    * @return void
    */
   static function showComputersImportForm(PluginLdapcomputersConfig $ldap_server) {

      //Get data related to entity (directory and ldap filter)
      $ldap_server->getFromDB($_SESSION['ldap_computers_import']['primary_ldap_id'] );
      echo "<div class='center'>";

      echo "<form method='post' action='".$_SERVER['PHP_SELF']."'>";

      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4' class='middle'><div class='relative'>";
      echo "<span>" . __('Get computers', 'ldapcomputers');

      echo "</span></div>";
      echo "</th></tr>";

      if (PluginLdapcomputersConfig::getNumberOfServers() > 1) {
         $rand = mt_rand();
         echo "<tr class='tab_bg_2'><td><label for='dropdown_primary_ldap_id$rand'>".__('LDAP directory choice')."</label></td>";
         echo "<td colspan='3'>";
            PluginLdapcomputersConfig::dropdown(['name'                 => 'primary_ldap_id',
                            'value'                => $_SESSION['ldap_computers_import']['primary_ldap_id'],
                            'condition'            => ['is_active' => 1],
                            'display_emptychoice'  => false,
                            'rand'                 => $rand]);
         echo "&nbsp;<input class='submit' type='submit' name='change_directory'
                  value=\""._sx('button', 'Change')."\">";
         echo "</td></tr>";
      }

      if (($_SESSION['ldap_computers_import']['primary_ldap_id'] !=  NOT_AVAILABLE)
         && ($_SESSION['ldap_computers_import']['primary_ldap_id'] > 0)) {
         if ($_SESSION['ldap_computers_import']['primary_ldap_id']) {
            echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
            echo "<input class='submit' type='submit' name='search' value=\"".
                _sx('button', 'Search')."\">";
            echo "</td></tr>";
         } else {
            echo "<tr class='tab_bg_2'><".
                 "td colspan='4' class='center'>".__('No directory selected')."</td></tr>";
         }
      } else {
         echo "<tr class='tab_bg_2'><td colspan='4' class='center'>".
                __('No directory associated to entity: impossible search')."</td></tr>";
      }
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }

   /**
    * Search computers
    *
    * @param resource $ldap_server PluginLdapcomputersConfig object
    *
    * @return void
    */
   static function searchComputers(PluginLdapcomputersConfig $ldap_server) {
      //Connect to the directory

      if (isset(self::$conn_cache[$ldap_server->getField('id')])) {
          $ds = self::$conn_cache[$ldap_server->getField('id')];
      } else {
         $ds = PluginLdapcomputersLdap::connectToServer($ldap_server->getField('host'), $ldap_server->getField('port'), $ldap_server->getField('rootdn'),
                                                        Toolbox::decrypt($ldap_server->getField('rootdn_passwd'), GLPIKEY),
                                                        $ldap_server->getField('use_tls'), $ldap_server->getField('deref_option'));
      }
      if ($ds) {
         self::$conn_cache[$ldap_server->getField('id')] = $ds;
      }

      if ($ds) {
         self::importLdapComputers($ldap_server);
      } else {
         echo "<div class='center b firstbloc'>".__('Unable to connect to the LDAP directory');
      }
   }

   /**
    * Show LDAP computers to add
    * @param resource $ldap_server PluginLdapcomputersConfig object
    *
    * @return void
    */
   static function importLdapComputers(PluginLdapcomputersConfig $ldap_server) {

      foreach ($_SESSION['ldap_computers_import'] as $option => $value) {
         $values[$option] = $value;
      }

      self::deleteOutdatedComputers($ldap_server);

      $limitexceeded  = false;

      $ldap_computers = self::getAllComputers($values, $limitexceeded);

      if (is_array($ldap_computers)) {
         if (count($ldap_computers) > 0) {
            self::updateComputerStatus($ldap_server, $ldap_computers);
         } else {
            echo "<div class='center b'>". __('No computers to be imported', 'ldapcomputers')."</div>";
         }
      } else {
         echo "<div class='center b'>". __('No computers to be imported', 'ldapcomputers')."</div>";
      }
   }

    /**
    * Delete computer with status LDAP_STATUS_NOTFOUND older then given days in database
    *
    * @param PluginLdapcomputersConfig $ldap_server delete outdated computers after given days
    *
    * @return mysqli_result|boolean Query result handler
    */

      static function deleteOutdatedComputers(PluginLdapcomputersConfig $ldap_server) {
      global $DB;
      // Delete outdated records
      $days = $ldap_server->getField('retention_date');
      return $DB->delete(PluginLdapcomputersComputer::getTable(),
                 ['plugin_ldapcomputers_configs_id' => $ldap_server->getField('id'),
                  'plugin_ldapcomputers_states_id' => PluginLdapcomputersState::LDAP_STATUS_NOTFOUND,
                  'date_mod' => ['<', new QueryExpression("date_add(now(), interval - " . $days . " day)")]
                  ]);

   }

   /**
    * Update computer status in databse
    *
    * @param array $ldap_computers Computer list from LDAP
    *
    * @return void
    */
   static function updateComputerStatus(PluginLdapcomputersConfig $ldap_server, $ldap_computers) {
      global $DB;

      foreach ($ldap_computers as $computer) {
         self::getAndSync($ldap_server, $computer);
      }

      $select = "SELECT DISTINCT 
                        `glpi_plugin_ldapcomputers_computers`.`id`            ,
                        `objectGUID`                                          ,
                        `plugin_ldapcomputers_states_id`                      ,
                        `plugin_ldapcomputers_configs_id`                      ,
                        `is_in_glpi_computers`                                ,
                        IF ( ISNULL(`glpi_computers`.`name`) ,0 ,1 ) AS inglpi
                FROM
                        `glpi_plugin_ldapcomputers_computers`
                LEFT JOIN
                        `glpi_computers`
                ON
                        `glpi_plugin_ldapcomputers_computers`.`name` = `glpi_computers`.`name`";

      $iterator = $DB->request($select);
      while ($computer = $iterator->next()) {
         $temp_computer = new PluginLdapcomputersComputer();
         $temp_computer->getFromDBByCrit(['objectGUID' => $computer['objectGUID']]);
         $computer['plugin_ldapcomputers_configs_id'] = $ldap_server->getField('id');
         if (!empty($ldap_computers[$computer['objectGUID']])) {
            if ($computer['plugin_ldapcomputers_states_id'] == '') {
               $computer['plugin_ldapcomputers_states_id']  = PluginLdapcomputersState::LDAP_STATUS_ACTIVE;
            } 
            if ($temp_computer->getField('is_in_glpi_computers') != $computer['inglpi']) {
               $computer['is_in_glpi_computers'] = $computer['inglpi'];
            }
            $temp_computer->update($computer);
            continue;
         } else {
            $computer['plugin_ldapcomputers_states_id'] = PluginLdapcomputersState::LDAP_STATUS_NOTFOUND;
            $temp_computer->update($computer);
         }
      }
   }

   /**
    * Get the LDAP computer and add to database or sync with existing computer
    *
    * @param array   $computer computer
    *
    * @return void
    */
   static function getAndSync(PluginLdapcomputersConfig $ldap_server, $computer) {
      $temp_computer = new PluginLdapcomputersComputer();
      $computer['lastLogon']                       = date('Y-m-d H:i:s', $computer['lastLogon']);
      $computer['lastLogonTimestamp']              = date('Y-m-d H:i:s', $computer['lastLogonTimestamp']);
      $computer['whenChanged']                     = date('Y-m-d H:i:s', $computer['whenChanged']);
      $computer['whenCreated']                     = date('Y-m-d H:i:s', $computer['whenCreated']);
      $computer['plugin_ldapcomputers_configs_id'] = $ldap_server->getField('id');

      if ($temp_computer->getFromDBByCrit(['objectGUID' => $computer['objectGUID']])) {
         // Check for any changes
         if ($temp_computer->getField('name')                      != $computer['name'] ||
            $temp_computer->getField('lastLogon')                  != $computer['lastLogon'] ||
            $temp_computer->getField('lastLogonTimestamp')         != $computer['lastLogonTimestamp'] ||
            $temp_computer->getField('logonCount')                 != $computer['logonCount'] ||
            $temp_computer->getField('distinguishedName')          != $computer['distinguishedName'] ||
            $temp_computer->getField('dNSHostName')                != $computer['dNSHostName'] ||
            $temp_computer->getField('operatingSystem')            != $computer['operatingSystem'] ||
            $temp_computer->getField('operatingSystemHotfix')      != $computer['operatingSystemHotfix'] ||
            $temp_computer->getField('operatingSystemServicePack') != $computer['operatingSystemServicePack'] ||
            $temp_computer->getField('operatingSystemVersion')     != $computer['operatingSystemVersion'] ||
            $temp_computer->getField('whenChanged')                != $computer['whenChanged'] ||
            $temp_computer->getField('whenCreated')                != $computer['whenCreated']) {

            $computer['id'] = $temp_computer->getField('id');

             // If any value was changed, update current computer
            $computer['plugin_ldapcomputers_states_id'] = PluginLdapcomputersState::LDAP_STATUS_ACTIVE;
            $temp_computer->update($computer);
         }
      } else {
         $computer['plugin_ldapcomputers_states_id'] = PluginLdapcomputersState::LDAP_STATUS_NEW;
         $temp_computer->add($computer);
      }

   }
   /**
    * Get the list of LDAP computers to add
    *
    * @param array   $options       possible options:
    *          - basedn force basedn (default authldaps_id one)
    *          - script true if called by an external script
    * @param boolean $limitexceeded limit exceeded exception
    *
    * @return array of the computer
    */
   static function getAllComputers(array $options, &$limitexceeded) {

      $ldap_server = new PluginLdapcomputersConfig();
      $ldap_server->getFromDB($options['primary_ldap_id']);

      $values = [
                  'basedn' => $ldap_server->getField('basedn'),
                  'script' => 0, //Called by an external script or not
                ];

      foreach ($options as $option => $value) {
         // this test break mode detection - if ($value != '') {
         $values[$option] = $value;
         //}
      }

      $computer_infos    = [];
      $limitexceeded     = false;

      // we prevent some delay...
      if (!$ldap_server) {
         return false;
      }
      if (isset(self::$conn_cache[$ldap_server->getField('id')])) {
         $ds = self::$conn_cache[$ldap_server->getField('id')];
      } else {
           $ds = PluginLdapcomputersLdap::connectToServer($ldap_server->getField('host'), $ldap_server->getField('port'), $ldap_server->getField('rootdn'),
                                                          Toolbox::decrypt($ldap_server->getField('rootdn_passwd'), GLPIKEY),
                                                          $ldap_server->getField('use_tls'), $ldap_server->getField('deref_option'));
      }

      if ($ds) {
         self::$conn_cache[$ldap_server->getField('id')] = $ds;
      }

      if ($ds) {
         $attrs = ["name", "lastLogon", "lastLogonTimestamp","logonCount", "distinguishedName", "dNSHostName","objectGUID",
                   "operatingSystem", "operatingSystemHotfix", "operatingSystemServicePack", "operatingSystemVersion",
                   "whenChanged", "whenCreated"];
         /*** Need for debug purpous
         if (isset($_SESSION['glpi_use_mode'])
            && Session::DEBUG_MODE == $_SESSION['glpi_use_mode']) {
               $attrs = [];
         }
         ***/
         $filter = $ldap_server->fields['condition'];
         $result = self::searchForComputers($ds, $values, $filter, $attrs, $limitexceeded,
                                            $computer_infos, $ldap_server);
         if (!$result) {
            return false;
         }
      } else {
         return false;
      }

      return $computer_infos;
   }

   /**
    * Search computers
    *
    * @param resource $ds             An LDAP link identifier
    * @param array    $values         values to search
    * @param string   $filter         search filter
    * @param array    $attrs          An array of the required attributes
    * @param boolean  $limitexceeded  is limit exceeded
    * @param array    $computer_infos Computer informations
    * @param object   $config_ldap    ldap configuration
    *
    * @return boolean
    */
   static function searchForComputers($ds, $values, $filter, $attrs, &$limitexceeded,
                                      &$computer_infos, $config_ldap) {

      //If paged results cannot be used (PHP < 5.4)
      $cookie   = ''; //Cookie used to perform query using pages
      $count    = 0;  //Store the number of results ldap_search

      do {
         $filter = Toolbox::unclean_cross_side_scripting_deep(Toolbox::stripslashes_deep($filter));

         if (PluginLdapcomputersLdap::isLdapPageSizeAvailable($config_ldap)) {
            if (version_compare(PHP_VERSION, '7.3') < 0) {
               //prior to PHP 7.3, use ldap_control_paged_result
               ldap_control_paged_result($ds, $config_ldap->fields['pagesize'], true, $cookie);
               $sr = @ldap_search($ds, $values['basedn'], $filter, $attrs);
            } else {
               //since PHP 7.3, send serverctrls to ldap_search
               $controls = [
                  [
                     'oid'        =>LDAP_CONTROL_PAGEDRESULTS,
                     'iscritical' => true,
                     'value'      => [
                        'size'    => $config_ldap->fields['pagesize'],
                        'cookie'  => $cookie
                     ]
                  ]
               ];
               $sr = @ldap_search($ds, $values['basedn'], $filter, $attrs, 0, -1, -1, LDAP_DEREF_NEVER, $controls);
               ldap_parse_result($ds, $sr, $errcode, $matcheddn, $errmsg, $referrals, $controls);
               if (isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
                  $cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
               } else {
                  $cookie = '';
               }
            }
         } else {
            $sr = @ldap_search($ds, $values['basedn'], $filter, $attrs);
         }

         if ($sr) {
            if (in_array(ldap_errno($ds), [4,11])) {
               // openldap return 4 for Size limit exceeded
               $limitexceeded = true;
            }

            $info = PluginLdapcomputersLdap::get_entries_clean($ds, $sr);
            if (in_array(ldap_errno($ds), [4,11])) {
               $limitexceeded = true;
            }

            /*** Need for debug purpous
            if (isset($_SESSION['glpi_use_mode'])
                && Session::DEBUG_MODE == $_SESSION['glpi_use_mode']) {
                  Toolbox::logInFile('ldapcomputers', print_r($info, true));
            }
            ***/

            $count += $info['count'];
            //If page results are enabled and the number of results is greater than the maximum allowed
            //warn user that limit is exceeded and stop search
            if (PluginLdapcomputersLdap::isLdapPageSizeAvailable($config_ldap)
                && $config_ldap->fields['ldap_maxlimit']
                && ($count > $config_ldap->fields['ldap_maxlimit'])) {
               $limitexceeded = true;
               break;
            }

            for ($ligne = 0; $ligne < $info["count"]; $ligne++) {
               $objectGUID                          = PluginLdapcomputersLdap::getFieldValue($info[$ligne], 'objectguid');
               $computer_infos[$objectGUID] ['id']               = 0;
               $computer_infos[$objectGUID] ['objectGUID']       = $objectGUID;
               $computer_infos[$objectGUID] ['name']             = $info[$ligne]['name'][0];
               $computer_infos[$objectGUID]["distinguishedName"] = $info[$ligne]['distinguishedname'][0];
               if (isset($info[$ligne]['lastlogon'][0])) {
                  $computer_infos[$objectGUID]["lastLogon"]  = PluginLdapcomputersLdap::ldapFiletime2Timestamp(
                     $info[$ligne]['lastlogon'][0],
                     $config_ldap->fields['time_offset']
                  );
               } else {
                  $computer_infos[$objectGUID]["lastLogon"] = null;
               }
               if (isset($info[$ligne]['lastlogontimestamp'][0])) {
                  $computer_infos[$objectGUID]["lastLogonTimestamp"]  = PluginLdapcomputersLdap::ldapFiletime2Timestamp(
                     $info[$ligne]['lastlogontimestamp'][0],
                     $config_ldap->fields['time_offset']
                  );
               } else {
                  $computer_infos[$objectGUID]["lastLogonTimestamp"] = null;
               }
               if (isset($info[$ligne]['dnshostname'][0])) {
                  $computer_infos[$objectGUID]["dNSHostName"] = $info[$ligne]['dnshostname'][0];
               } else {
                  $computer_infos[$objectGUID]["dNSHostName"] = null;
               }
               if (isset($info[$ligne]['logoncount'][0])) {
                  $computer_infos[$objectGUID]["logonCount"] = $info[$ligne]['logoncount'][0];
               } else {
                  $computer_infos[$objectGUID]["logonCount"] = null;
               }
               if (isset($info[$ligne]['operatingsystem'][0])) {
                  $computer_infos[$objectGUID]["operatingSystem"] = $info[$ligne]['operatingsystem'][0];
               } else {
                  $computer_infos[$objectGUID]["operatingSystem"] = null;
               }
               if (isset($info[$ligne]['operatingsystemhotfix'][0])) {
                  $computer_infos[$objectGUID]["operatingSystemHotfix"] = $info[$ligne]['operatingsystemhotfix'][0];
               } else {
                  $computer_infos[$objectGUID]["operatingSystemHotfix"] = null;
               }
               if (isset($info[$ligne]['operatingsystemservicepack'][0])) {
                  $computer_infos[$objectGUID]["operatingSystemServicePack"] = $info[$ligne]['operatingsystemservicepack'][0];
               } else {
                  $computer_infos[$objectGUID]["operatingSystemServicePack"] = null;
               }
               if (isset($info[$ligne]['operatingsystemversion'][0])) {
                  $computer_infos[$objectGUID]["operatingSystemVersion"] = $info[$ligne]['operatingsystemversion'][0];
               } else {
                  $computer_infos[$objectGUID]["operatingSystemVersion"] = null;
               }

               if (isset($info[$ligne]['whenchanged'][0])) {
                  $computer_infos[$objectGUID]["whenChanged"]  = PluginLdapcomputersLdap::ldapStamp2UnixStamp(
                     $info[$ligne]['whenchanged'][0],
                     $config_ldap->fields['time_offset']
                  );
               } else {
                  $computer_infos[$objectGUID]["whenChanged"] = null;
               }
               if (isset($info[$ligne]['whencreated'][0])) {
                  $computer_infos[$objectGUID]["whenCreated"]  = PluginLdapcomputersLdap::ldapStamp2UnixStamp(
                     $info[$ligne]['whencreated'][0],
                     $config_ldap->fields['time_offset']
                  );
               } else {
                  $computer_infos[$objectGUID]["whenCreated"] = null;
               }
            }
         } else {
            return false;
         }
         if (PluginLdapcomputersLdap::isLdapPageSizeAvailable($config_ldap) && version_compare(PHP_VERSION, '7.3') < 0) {
            ldap_control_paged_result_response($ds, $sr, $cookie);
         }

      } while (($cookie !== null) && ($cookie != ''));
      return true;
   }

   /**
    * Check if a computer DN exists in a ldap user search result
    *
    * @since 0.84
    *
    * @param array  $ldap_infos ldap computer search result
    * @param string $computer_dn    computer dn to look for
    *
    * @return boolean false if the user dn doesn't exist, user ldap infos otherwise
    */
   static function dnExistsInLdap($ldap_infos, $computer_dn) {

      $found = false;

      foreach ($ldap_infos as $ldap_info) {
         if (isset($ldap_info['distinguishedName'])) {
            if ($ldap_info['distinguishedName'] == $computer_dn) {
               $found = $ldap_info;
               break;
            }
         }
      }
      return $found;
   }

   /**
    * Does LDAP computer already exists in the database?
    *
    * @param string $name Computer name
    *
    *
    * @return false|Computer
    */
   static function getLdapExistingComputer($name) {
      $computer = new PluginLdapcomputersComputer();
      if ($computer->getFromDBByCrit(['name' => $name])) {
         return $computer;
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

   // Cron action
   /**
    * @param $name
    *
    * @return array
    */
   static function cronInfo($name) {

      switch ($name) {
         case 'LdapComputersDeleteOutdatedComputers':
            return [
               'description' => __('Delete outdated computers', 'ldapcomputers')];   // Optional
            break;
         case 'LdapComputersGetComputers':
            return [
               'description' => __('Get computers from LDAP', 'ldapcomputers')];   // Optional
            break;
      }
      return [];
   }

   /**
    * Cron action
    *
    * @param $task for log, if NULL display
    *
    *
    * @return int
    */
   static function cronLdapComputersDeleteOutdatedComputers($task = null) {
      global $CFG_GLPI;

      $CronTask = new CronTask();
      if ($CronTask->getFromDBbyName("PluginLdapcomputersComputer", "LdapComputersDeleteOutdatedComputers") &&
         PluginLdapcomputersConfig::useLdapComputers()) {
         if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
            return 0;
         }
      } else {
         return 0;
      }
      $cron_status = 0;

      $ldap_server_id = PluginLdapcomputersConfig::getDefault();
      $ldap_server = new PluginLdapcomputersConfig();
      $ldap_server->getFromDB($ldap_server_id);
      if (!$ldap_server) {
         return 0;
      }

      $result = self::deleteOutdatedComputers($ldap_server);
      if ($result !== false) {
         $cron_status = 1;
         if (!is_null($task)) {
            $task->addVolume($result);
         }
      }
      return $cron_status;
   }
   /**
    * Cron action
    *
    * @param $task for log, if NULL display
    *
    *
    * @return int
    */
   static function cronLdapComputersGetComputers($task = null) {
      global $CFG_GLPI;
      /*
      if (!$CFG_GLPI["notifications_mailing"]) {
         return 0;
      }
      */
      $CronTask = new CronTask();
      if ($CronTask->getFromDBbyName("PluginLdapcomputersComputer", "LdapComputersGetComputers") &&
         PluginLdapcomputersConfig::useLdapComputers()) {
         if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
            return 0;
         }
      } else {
         return 0;
      }

      $cron_status = 0;

      // In automatic task we will retrive computers only from default server
      $ldap_server_id = PluginLdapcomputersConfig::getDefault();
      $ldap_server = new PluginLdapcomputersConfig();
      $ldap_server->getFromDB($ldap_server_id);

      if (!$ldap_server) {
         return 0;
      }
      $options['primary_ldap_id'] = $ldap_server_id;
      $limitexceeded  = false;
      $ldap_computers = self::getAllComputers($options, $limitexceeded);
      if (is_array($ldap_computers)) {
         if (count($ldap_computers) > 0) {
            self::updateComputerStatus($ldap_server, $ldap_computers);
         }
      }

      if (count($ldap_computers)) {
         $cron_status = 1;
         if (!is_null($task)) {
            $task->addVolume(1);
         }
      }
      return $cron_status;
   }
}