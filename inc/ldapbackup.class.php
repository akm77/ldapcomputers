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
 * Class PluginLdapcomputersLdapbackup
 */
class PluginLdapcomputersLdapbackup extends CommonDBTM {

   static $rightname = 'plugin_ldapcomputers_config';


   static function canCreate() {
      return static::canUpdate();
   }

   static function canPurge() {
      return static::canUpdate();
   }

   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

   function prepareInputForAdd($input) {

      if (isset($input["port"]) && (intval($input["port"]) == 0)) {
         $input["port"] = 389;
      }
      return $input;
   }

   function prepareInputForUpdate($input) {

      return $this->prepareInputForAdd($input);
   }

   /**
    * Form to add a backup to a ldap server
    *
    * @param string  $target    target page for add new backup
    * @param integer $master_id master ldap server ID
    *
    * @return void
    */
   static function addNewBackupLdapForm($target, $master_id) {

      echo "<form action='$target' method='post' name='add_backup_ldap_form' id='add_backup_ldap_form'>";
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>".__('Add a LDAP directory replica'). "</th></tr>";
      echo "<tr class='tab_bg_1'><td class='center'>".__('Name')."</td>";
      echo "<td class='center'>".__('Server')."</td>";
      echo "<td class='center'>".__('Port')."</td><td></td></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'><input type='text' name='name'></td>";
      echo "<td class='center'><input type='text' name='host'></td>";
      echo "<td class='center'><input type='text' name='port'></td>";
      echo "<td class='center'><input type='hidden' name='next' value='ext_ldap'>";
      echo "<input type='hidden' name='primary_ldap_id' value='$master_id'>";
      echo "<input type='submit' name='add_backup_ldap' value='"._sx('button', 'Add') ."' class='submit'></td>";
      echo "</tr></table></div>";
      Html::closeForm();
   }

}