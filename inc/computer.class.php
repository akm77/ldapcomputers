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
 *  Class used to view and import LDAP computers
 */
class PluginLdapcomputersComputer extends CommonDBTM {

   static $rightname = 'plugin_ldapcomputers_view';

   //connection caching stuff
   static $conn_cache = [];

   static function getTypeName($nb = 0) {
      return _n('View LDAP computers', 'View LDAP computers', $nb);
   }

   static function canCreate() {
      return static::canUpdate();
   }

   static function canPurge() {
      return static::canUpdate();
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
      $this->showFormHeader($options);
      echo "<tr class='tab_bg_1'><td><label for='name'>" . __('Name') . "</label></td>";
      echo "<td><input type='text'  id='name' name='name' value='". $this->fields["name"] ."'></td>";
      if ($ID > 0) {
         echo "<td>".__('Last update')."</td><td>".Html::convDateTime($this->fields["date_mod"]);
      } else {
         echo "<td colspan='2'>&nbsp;";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td><label for='lastLogon'>" . __('Last Logon') . "</label></td>";
      echo "<td><input type='text' readonly id='name' name='lastLogon' value='". $this->fields["lastLogon"] ."'></td>";
      echo "<td><label for='logonCount'>" . __('Logon Count') . "</label></td>";
      echo "<td><input type='text' readonly id='name' name='logonCount' value='". $this->fields["logonCount"] ."'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='distinguishedName'>" . __('Distinguished Name') . "</label></td>";
      echo "<td class=middle colspan='3'>";
      echo "<textarea cols='40' rows='4' readonly name='distinguishedName' id='distinguishedName'>".$this->fields["distinguishedName"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Computer status in LDAP') . "</td><td colspan='4'>";
      PluginLdapcomputersState::Dropdown([
         'name'   => "plugin_ldapcomputers_states_id",
         'value'  => $this->fields['plugin_ldapcomputers_states_id'],
      ]);
      echo"</td></tr>";

      $is_in_glpi_computers = mt_rand();
      echo "<tr class='tab_bg_1'><td><label for='dropdown_is_default$is_in_glpi_computers'>" . __('GLPI presence') . "</label></td>";
      echo "<td>";
      Dropdown::showYesNo('is_in_glpi_computers', $this->fields['is_in_glpi_computers'], -1, ['rand' => $is_in_glpi_computers]);
      echo "</td>";

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
         'field'              => 'lastLogon',
         'name'               => __('Last logon'),
         'datatype'           => 'datetime'
      ];
      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'logonCount',
         'name'               => __('Logon count'),
         'datatype'           => 'integer'
      ];
      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'distinguishedName',
         'name'               => __('Distinguished name'),
         'datatype'           => 'text'
      ];
      $tab[] = [
         'id'                 => '6',
         'table'              => 'glpi_plugin_ldapcomputers_states',
         'field'              => 'name',
         'name'               => __('LDAP computer status'),
         'datatype'           => 'dropdown',
         'displaytype'        => 'dropdown',
         'injectable'         => true
      ];
      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'is_in_glpi_computers',
         'name'               => __('GLPI presence'),
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
      echo "<span>" . __('Import computers', 'ldapcomputers');

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
      //If multi-entity mode and more than one entity visible
      //else no need to select entity
      if (Session::isMultiEntitiesMode()
         && (count($_SESSION['glpiactiveentities']) > 1)) {

         echo "<tr class='tab_bg_2'><td>".__('Select the desired entity')."</td>".
                 "<td colspan='3'>";
         Entity::dropdown(['value'       => $_SESSION['ldap_computers_import']['entities_id'],
                           'entity'      => $_SESSION['glpiactiveentities'],
                           'on_change'    => 'submit()']);
         echo "</td></tr>";
      } else {
            //Only one entity is active, store it
         echo "<tr><td><input type='hidden' name='entities_id' value='".
                        $_SESSION['glpiactive_entity']."'></td></tr>";
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
    * Search user
    *
    * @param resource $authldap AuthLDAP object
    *
    * @return void
    */
   static function searchComputers(PluginLdapcomputersConfig $ldap_server) {

      if (PluginLdapcomputersLdap::connectToServer($ldap_server->getField('host'), $ldap_server->getField('port'),
                                $ldap_server->getField('rootdn'),
                                Toolbox::decrypt($ldap_server->getField('rootdn_passwd'), GLPIKEY),
                                $ldap_server->getField('use_tls'),
                                $ldap_server->getField('deref_option'))) {
         self::showLdapUsers();

      } else {
         echo "<div class='center b firstbloc'>".__('Unable to connect to the LDAP directory');
      }
   }


}