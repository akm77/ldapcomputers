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

// Need REQUEST to manage initial walues and posted ones
PluginLdapcomputersLdap::manageValuesInSession($_REQUEST);

if (isset($_SESSION['ldap_computers_import']['_in_modal']) && $_SESSION['ldap_computers_import']['_in_modal']) {
    $_REQUEST['_in_modal'] = 1;
}

Html::header(__('LDAP directory link'), $_SERVER['PHP_SELF'], 'admin', 'PluginLdapcomputersLdapcomputersmenu', 'ldapcomputerscomputer');


$ldap_server = new PluginLdapcomputersConfig();
$ldap_server->getFromDB($_SESSION['ldap_computers_import']['primary_ldap_id']);

PluginLdapcomputersComputer::showComputersImportForm($ldap_server);

if (isset($_SESSION['ldap_computers_import']['primary_ldap_id'])
   && ($_SESSION['ldap_computers_import']['primary_ldap_id'] != NOT_AVAILABLE)
   && (isset($_POST['search']))) {

   echo "<br />";
   Html::createProgressBar(__('Work in progress...'));
   PluginLdapcomputersComputer::searchComputers($ldap_server);
   Html::changeProgressBarPosition(1, 1, __('Task completed.'));
   HTML::redirect($CFG_GLPI["root_doc"] . "/plugins/ldapcomputers/front/computer.php");
}

Html::footer();