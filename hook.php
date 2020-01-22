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
                  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
                  `is_default` tinyint(1) NOT NULL DEFAULT 0,
                  `is_active` tinyint(1) NOT NULL DEFAULT 0,
                  `rootdn_passwd` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `pagesize` int(11) NOT NULL DEFAULT 0,
                  `ldap_maxlimit` int(11) NOT NULL DEFAULT 0,
                  `can_support_pagesize` tinyint(1) NOT NULL DEFAULT 0,
                  `retention_date` int(11) DEFAULT 10,
                  `date_creation` datetime DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `date_mod` (`date_mod`),
                  KEY `is_default` (`is_default`),
                  KEY `is_active` (`is_active`),
                  KEY `date_creation` (`date_creation`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';
      $DB->queryOrDie($query, $DB->error());
   }

   //Create backup ldaps table only if it does not exists yet!
   if (!$DB->tableExists('glpi_plugin_ldapcomputers_ldapbackups')) {
      $query = 'CREATE TABLE `glpi_plugin_ldapcomputers_ldapbackups` (
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
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) NOT NULL,
                  `lastLogon` datetime DEFAULT NULL,
                  `logonCount` int(11) DEFAULT NULL,
                  `distinguishedName` text NOT NULL,
                  `objectGUID` varchar(255) DEFAULT NULL,
                  `operatingSystem` varchar(255) DEFAULT NULL,
                  `operatingSystemHotfix` varchar(255) DEFAULT NULL,
                  `operatingSystemServicePack` varchar(255) DEFAULT NULL,
                  `operatingSystemVersion` varchar(255) DEFAULT NULL,
                  `whenChanged` datetime DEFAULT NULL,
                  `whenCreated` datetime DEFAULT NULL,
                  `plugin_ldapcomputers_states_id` int(11) NOT NULL DEFAULT 0,
                  `is_in_glpi_computers` tinyint(4) NOT NULL DEFAULT 0,
                  `date_creation` datetime NOT NULL,
                  `date_mod` datetime NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `date_mod` (`date_mod`),
                  KEY `name` (`name`),
                  KEY `objectGUID` (`objectGUID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';
      $DB->queryOrDie($query, $DB->error());
   }

   //Create computers table only if it does not exists yet!
   if (!$DB->tableExists('glpi_plugin_ldapcomputers_states')) {
      $query = 'CREATE TABLE `glpi_plugin_ldapcomputers_states` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) NOT NULL,
                  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
                  `date_creation` datetime NOT NULL,
                  `date_mod` datetime NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';
      $DB->queryOrDie($query, $DB->error());
   }

   /* Placeholder for further update process in future
   */

   $state = new PluginLdapcomputersState();
   $table = $state->getTable();
   foreach ([$state::LDAP_STATUS_NEW      => __("New", "ldapcomputers"),
             $state::LDAP_STATUS_ACTIVE   => __("Active", "ldapcomputers"),
             $state::LDAP_STATUS_NOTFOUND => __("Not found", "ldapcomputers"),
            ] as $id => $label) {
      if (!countElementsInTable($table, ['id' => $id])) {
         $state->add(['id'   => $id,
                      'name' => Toolbox::addslashes_deep($label),
                     ]);
      }
   }

   //execute the whole migration
   $migration->executeMigration();

   PluginLdapcomputersProfile::initProfile();
   PluginLdapcomputersProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

   CronTask::Register('PluginLdapcomputersComputer', 'LdapComputersDeleteOutdatedComputers', DAY_TIMESTAMP);
   CronTask::Register('PluginLdapcomputersComputer', 'LdapComputersGetComputers', DAY_TIMESTAMP);

   return true;
}

/* define dropdown tables to be manage in GLPI : */
function plugin_ldapcomputers_getDropdown() {
   /* table => name */
   $plugin = new Plugin();
   if ($plugin->isActivated("ldapcomputers")) {
      return ['PluginLdapcomputersState' => PluginLdapcomputersState::getTypeName()];
   } else {
      return [];
   }
}

/* define dropdown relations */
function plugin_ldapcomputers_getDatabaseRelations() {
   $plugin = new Plugin();
   if ($plugin->isActivated("ldapcomputers")) {
      return [
         "glpi_plugin_ldapcomputers_states" => [
            "glpi_plugin_ldapcomputers_computers" => "plugin_ldapcomputers_states_id"
         ]
      ];

   } else {
      return [];
   }
}
////// SEARCH FUNCTIONS ///////(){

// Define search option for types of the plugins
function plugin_ldapcomputers_getAddSearchOptions($itemtype) {
   $sopt = [];
   if ($itemtype != 'Computer') {
      return $sopt;
   }

   $plugin = new Plugin();

   if ($plugin->isInstalled('ldapcomputers')
       && $plugin->isActivated('ldapcomputers')
       && Session::haveRight("plugin_ldapcomputers_view", READ)) {
        $sopt[3210]['table']         = 'glpi_plugin_ldapcomputers_computers';
        $sopt[3210]['field']         = 'name';
        $sopt[3210]['linkfield']     = '';
        $sopt[3210]['name']          = "LDAP " . __("Name");
        $sopt[3210]['joinparams']  = ['jointype' => 'child'];
        $sopt[3210]['forcegroupby']  = true;

        $sopt[3211]['table']         = 'glpi_plugin_ldapcomputers_computers';
        $sopt[3211]['field']         = 'lastLogon';
        $sopt[3211]['linkfield']     = '';
        $sopt[3211]['name']          = "LDAP " . __('Last logon', 'ldapcomputers');
        $sopt[3211]['joinparams']  = ['jointype' => 'child'];
        $sopt[3211]['forcegroupby']  = true;
        $sopt[3211]['datatype']      = 'datetime';

        $sopt[3212]['table']         = 'glpi_plugin_ldapcomputers_computers';
        $sopt[3212]['field']         = 'distinguishedName';
        $sopt[3212]['linkfield']     = '';
        $sopt[3212]['name']          = "LDAP " . __('Distinguished name', 'ldapcomputers');
        $sopt[3212]['joinparams']  = ['jointype' => 'child'];
        $sopt[3212]['forcegroupby']  = true;

        $sopt[3213]['table']         = 'glpi_plugin_ldapcomputers_computers';
        $sopt[3213]['field']         = 'operatingSystem';
        $sopt[3213]['linkfield']     = '';
        $sopt[3213]['name']          = "LDAP " . __('OS', 'ldapcomputers');
        $sopt[3213]['joinparams']  = ['jointype' => 'child'];
        $sopt[3213]['forcegroupby']  = true;
   }
   return $sopt;
}

function plugin_ldapcomputers_addLeftJoin($type, $ref_table, $new_table, $linkfield, &$already_link_tables) {
   $out = "";
   switch ($new_table) {
      case "glpi_plugin_ldapcomputers_computers": // From order list
         $out = " LEFT JOIN `glpi_plugin_ldapcomputers_computers`
                     ON `glpi_plugin_ldapcomputers_computers`.`name` = `glpi_computers`.`name` ";
         break;
   }

   return $out;
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
      'ldapbackups',
      'states',
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
   CronTask::Unregister('ldapcomputers');

   return true;
}
