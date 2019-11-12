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

include ('../../../inc/includes.php');

Session::checkRight("plugin_ldapcomputers_view", UPDATE);

$computer = new PluginLdapcomputersComputer();

if (!isset($_GET['id'])) {
   $_GET['id'] = "";
}

//LDAP Server add/update/delete
if (isset($_POST["update"])) {
   $computer->update($_POST);
   Html::back();

} else if (isset($_POST["add"])) {
   //If no name has been given to this configuration, then go back to the page without adding
   if ($_POST["name"] != "") {
      if ($newID = $computer->add($_POST)) {
         Html::redirect($CFG_GLPI["root_doc"] . "/plugins/ldapcomputers/front/computer.php?next=ext_ldap&id=".$newID);
      }
   }
   Html::back();

} else if (isset($_POST["purge"])) {
   $computer->delete($_POST, 1);
   //$_SESSION['glpi_authconfig'] = 1;
   $computer->redirectToList();

}


Html::header(PluginLdapcomputersComputer::getTypeName(1), $_SERVER['PHP_SELF'], 'admin', 'PluginLdapcomputersLdapcomputersmenu', 'ldapcomputerscomputer');
$computer->display($_GET);

Html::footer();
