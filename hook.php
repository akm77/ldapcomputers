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

   $migration = new Migration(PLUGIN_LDAPCOMPUTERS_VERSION);

   //Create config table only if it does not exists yet!
   if (!$DB->tableExists('glpi_plugin_ldapcomputers_configs')) {
      $query = 'CREATE TABLE `glpi_plugin_ldapcomputers_configs` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `host` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `basedn` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `rootdn` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `port` int(11) NOT NULL DEFAULT 389,
                  `condition` text COLLATE utf8_unicode_ci DEFAULT NULL,
                  `use_tls` tinyint(1) NOT NULL DEFAULT 0,
                  `use_dn` tinyint(1) NOT NULL DEFAULT 1,
                  `time_offset` int(11) NOT NULL DEFAULT 0 COMMENT "in seconds",
                  `deref_option` int(11) NOT NULL DEFAULT 0,
                  `date_mod` datetime DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
                  `is_default` tinyint(1) NOT NULL DEFAULT 0,
                  `is_active` tinyint(1) NOT NULL DEFAULT 0,
                  `rootdn_passwd` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `pagesize` int(11) NOT NULL DEFAULT 0,
                  `ldap_maxlimit` int(11) NOT NULL DEFAULT 0,
                  `can_support_pagesize` tinyint(1) NOT NULL DEFAULT 0,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `date_mod` (`date_mod`),
                  KEY `is_default` (`is_default`),
                  KEY `is_active` (`is_active`),
                  KEY `date_creation` (`date_creation`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';
      $DB->queryOrDie($query, $DB->error());
   }

   //Create backup ldaps table only if it does not exists yet!
   if (!$DB->tableExists('glpi_plugin_ldapcomputers_ldap_backups')) {
      $query = 'CREATE TABLE `glpi_plugin_ldapcomputers_ldap_backups` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `primary_ldap_id` int(11) NOT NULL DEFAULT 0,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `host` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `port` int(11) NOT NULL DEFAULT 389,
                  PRIMARY KEY (`id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';
      $DB->queryOrDie($query, $DB->error());
   }

   //Create computers table only if it does not exists yet!
   if (!$DB->tableExists('glpi_plugin_ldapcomputers_computers')) {
      $query = 'CREATE TABLE `glpi_plugin_ldapcomputers_computers` (
                  `id` int(11) NOT NULL,
                  `name` varchar(255) NOT NULL,
                  `lastLogon` datetime NOT NULL,
                  `logonCount` int(11) NOT NULL,
                  `distinguishedName` text NOT NULL,
                  `ldap_status` tinyint(4) NOT NULL DEFAULT 0,
                  `is_in_glpi_computers` tinyint(4) NOT NULL DEFAULT 0,
                  `date_creation` datetime NOT NULL,
                  `date_mod` datetime NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `date_mod` (`date_mod`),
                  KEY `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';
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

   PluginLdapcomputersProfile::initProfile();

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
      'computers',
      'ldap_backups',
   ];

   foreach ($tables as $table) {
      $tablename = 'glpi_plugin_ldapcomputers_' . $table;
      //Drop table only if it does not exists yet!
      if ($DB->tableExists($tablename)) {
         $DB->queryOrDie(
            "DROP TABLE `$tablename`", $DB->error()
         );
      }
   }

   PluginLdapcomputersProfile::removeRights();

   return true;
}
