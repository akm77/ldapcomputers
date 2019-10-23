<?php
/*
 -------------------------------------------------------------------------
 ldapcomputers plugin for GLPI
 Copyright (C) 2019 by the ldapcomputers Development Team.

 https://github.com/pluginsGLPI/ldapcomputers
 -------------------------------------------------------------------------

 LICENSE

 This file is part of ldapcomputers.

 ldapcomputers is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 ldapcomputers is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with ldapcomputers. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_ldapcomputers_install() {
   global $DB;

   $migration = new Migration(PLUGIN_BARCODE_VERSION);

   //Create config table only if it does not exists yet!
   if (!$DB->tableExists('glpi_plugin_ldapcomputers_configs')) {
      $query = '';
      $DB->queryOrDie($query, $DB->error());
   }

   //Create computers table only if it does not exists yet!
   if (!$DB->tableExists('glpi_plugin_ldapcomputers_computers')) {
      $query = '';
      $DB->queryOrDie($query, $DB->error());
   }
   /* Placeholder for further update process in future
   if (!$DB->tableExists('glpi_plugin_ldapcomputers_configs')) {
      $query = '';
      $DB->queryOrDie($query, $DB->error());
   }
   */

   //execute the whole migration
   $migration->executeMigration();

   return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_ldapcomputers_uninstall() {
   global $DB;

   $tables = [
      'configs',
      'computers'
   ];

   foreach ($tables as $table) {
      $tablename = 'glpi_plugin_ldapcomputers_' . $table;
      //Drop table only if it does not exists yet!
      if ($DB->tableExists($tablename)) {
         $DB->queryOrDie(
            "DROP TABLE `$tablename`",
            $DB->error()
         );
      }
   }
   return true;
}
