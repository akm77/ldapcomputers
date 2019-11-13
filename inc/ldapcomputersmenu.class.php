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

class PluginLdapcomputersLdapcomputersmenu extends CommonGLPI {
   static $rightname = 'plugin_ldapcomputers_view';

   static function getMenuName() {
      return __("View LDAP computers", "ldapcomputers");
   }

   static function getMenuContent() {
      global $CFG_GLPI;

      if (!Session::haveRight('plugin_ldapcomputers_view', READ)) {
         return;
      }

      $front_ldapcomputers = "/plugins/ldapcomputers/front";
      $menu = [];
      $menu['title'] = self::getMenuName();
      $menu['page']  = "$front_ldapcomputers/computer.php";
      $menu['links']['search'] = PluginLdapcomputersComputer::getSearchURL(false);
      if (PluginLdapcomputersComputer::canCreate()) {
         $menu['links']['add'] = PluginLdapcomputersComputer::getFormURL(false);
         // Add icon for import
         $img = Html::image($CFG_GLPI["root_doc"] . "/plugins/ldapcomputers/pics/import.png",
                                      ['alt' => __('Import', 'ldapcomputers'), 'width' => '16', 'height' => '16']);
         $menu['links'][$img] = "$front_ldapcomputers/ldap.import.php?action=show";
      }

      return $menu;
   }

   static function removeRightsFromSession() {
      if (isset($_SESSION['glpimenu']['admin']['types']['PluginLdapcomputersLdapcomputersmenu'])) {
         unset($_SESSION['glpimenu']['admin']['types']['PluginLdapcomputersLdapcomputersmenu']);
      }
      if (isset($_SESSION['glpimenu']['admin']['content']['pluginldapcomputersldapcomputersmenu'])) {
         unset($_SESSION['glpimenu']['admin']['content']['pluginldapcomputersldapcomputersmenu']);
      }
   }

}