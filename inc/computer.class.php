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

      echo "<tr class='tab_bg_1'><td><label for='objectGUID'>" . __('Object GUID', 'ldapcomputers') . "</label></td>";
      echo "<td><input type='text'  id='objectGUID' name='objectGUID' value='". $this->fields["objectGUID"] ."'></td></tr>";

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
         'table'              => $this->getTable(),
         'field'              => 'objectGUID',
         'name'               => __('Object GUID'),
         'datatype'           => 'text'
      ];
      $tab[] = [
         'id'                 => '7',
         'table'              => 'glpi_plugin_ldapcomputers_states',
         'field'              => 'name',
         'name'               => __('LDAP computer status'),
         'datatype'           => 'dropdown',
         'displaytype'        => 'dropdown',
         'injectable'         => true
      ];
      $tab[] = [
         'id'                 => '8',
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

      if (PluginLdapcomputersLdap::connectToServer($ldap_server->getField('host'), $ldap_server->getField('port'), $ldap_server->getField('rootdn'),
                                Toolbox::decrypt($ldap_server->getField('rootdn_passwd'), GLPIKEY),
                                $ldap_server->getField('use_tls'), $ldap_server->getField('deref_option'))) {
         self::showLdapComputers();

      } else {
         echo "<div class='center b firstbloc'>".__('Unable to connect to the LDAP directory');
      }
   }

   /**
    * Show LDAP computers to add
    *
    * @return void
    */
   static function showLdapComputers() {

      $values = [
         'order' => 'DESC',
         'start' => 0,
      ];

      foreach ($_SESSION['ldap_computers_import'] as $option => $value) {
         $values[$option] = $value;
      }

      $rand              = mt_rand();
      $results           = [];
      $limitexceeded     = false;
      $ldap_computers    = self::getComputers($values, $results, $limitexceeded);

      $ldap_server   = new PluginLdapcomputersConfig();
      $ldap_server->getFromDB($values['primary_ldap_id']);

      if (is_array($ldap_computers)) {
         $numrows = count($ldap_computers);

         if ($numrows > 0) {
            self::displaySizeLimitWarning($limitexceeded);

            Html::printPager($values['start'], $numrows, $_SERVER['PHP_SELF'], '');

            // delete end
            array_splice($ldap_computers, $values['start'] + $_SESSION['glpilist_limit']);
            // delete begin
            if ($values['start'] > 0) {
               array_splice($ldap_computers, 0, $values['start']);
            }

            $form_action = '';
            $textbutton  = '';
            $textbutton  = _x('button', 'Import');
            $form_action = __CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'import';

            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = ['num_displayed'    => min(count($ldap_computers),
                                                        $_SESSION['glpilist_limit']),
                              'container'        => 'mass'.__CLASS__.$rand,
                              'specific_actions' => [$form_action => $textbutton]];
            Html::showMassiveActions($massiveactionparams);

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr>";
            echo "<th width='10'>";
            echo Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            echo "</th>";
            $num = 0;

            echo Search::showHeaderItem(Search::HTML_OUTPUT, _n('Computer', 'Computers', Session::getPluralNumber()), $num,
                                        $_SERVER['PHP_SELF'].
                                            "?order=".($values['order']=="DESC"?"ASC":"DESC"));
            echo "<th>".__('Last update in the LDAP directory')."</th>";
            echo "</tr>";

            foreach ($ldap_computers as $computerinfos) {
               Toolbox::logInFile('computerinfos', json_encode($computerinfos) . "\n");
               echo "<tr class='tab_bg_2 center'>";
               //Need to use " instead of ' because it doesn't work with names with ' inside !
               echo "<td>";
               echo Html::getMassiveActionCheckBox(__CLASS__, $computerinfos['objectGUID']);
               echo "</td>";
               echo "<td>";
               if (isset($computerinfos['id']) && User::canView()) {
                  echo "<a href='".$computerinfos['link']."'>". $computerinfos['name'] . "</a>";
               } else {
                  echo $computerinfos['link'];
               }
               echo "</td>";

               if ($computerinfos['lastLogon'] != '') {
                  echo "<td>" .Html::convDateTime(date("Y-m-d H:i:s", $computerinfos['lastLogon'])). "</td>";
               } else {
                  echo "<td>&nbsp;</td>";
               }
               echo "</tr>";
            }
            echo "<tr>";
            echo "<th width='10'>";
            echo Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            echo "</th>";
            $num = 0;

            echo Search::showHeaderItem(Search::HTML_OUTPUT, _n('Computer', 'Computers', Session::getPluralNumber()), $num,
                                        $_SERVER['PHP_SELF'].
                                                "?order=".($values['order']=="DESC"?"ASC":"DESC"));
            echo "<th>".__('Last update in the LDAP directory')."</th>";
            echo "</tr>";
            echo "</table>";

            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();

            Html::printPager($values['start'], $numrows, $_SERVER['PHP_SELF'], '');
         } else {
            echo "<div class='center b'>". __('No computers to be imported', 'ldapcomputers')."</div>";
         }
      } else {
         echo "<div class='center b'>". __('No computers to be imported', 'ldapcomputers')."</div>";
      }
   }

   /**
    * Get the list of LDAP computers to add
    * When importing, already existing computers will be filtered
    *
    * @param array   $options       possible options:
    *          - primary_ldap_id ID of the server to use
    *          - mode user to synchronise or add?
    *          - ldap_filter ldap filter to use
    *          - basedn force basedn (default primary_ldap_id one)
    *          - order display order
    *          - begin_date begin date to time limit
    *          - end_date end date to time limit
    *          - script true if called by an external script
    * @param array   $results       result stats
    * @param boolean $limitexceeded limit exceeded exception
    *
    * @return array
    */
   public static function getComputers($values, &$results, &$limitexceeded) {
      $computers = [];
      $ldap_computers    = self::getAllComputers($values, $results, $limitexceeded);

      $config_ldap   = new PluginLdapcomputersConfig();
      $config_ldap->getFromDB($values['primary_ldap_id']);

      if (!is_array($ldap_computers) || count($ldap_computers) == 0) {
         return $computers;
      }

      foreach ($ldap_computers as $key => $computerinfos) {
         $computer_to_add = [];
         $computer = new PluginLdapcomputersComputer();

         $computer = self::getLdapExistingComputer($computerinfos['name']);
         if (isset($_SESSION['ldap_computers_import']) && $computer) {
            continue;
         }
         $computer_to_add['link'] = $computerinfos["name"];
         if (isset($computerinfos['id']) && User::canView()) {
            //$computer_to_add['id']   = $computerinfos['id'];
            $computer_to_add['name'] = $computer->fields['name'];
            $computer_to_add['link'] = Toolbox::getItemTypeFormURL('User').'?id='.$computerinfos['id'];
         }

         $computer_to_add['lastLogon']      = (isset($computerinfos["lastLogon"])) ? $computerinfos["lastLogon"] : '';
         $computer_to_add['logonCount']  = (isset($computerinfos["logonCount"])) ? $computerinfos["logonCount"] : '';

         $computer_to_add['objectGUID'] = $key;

         $computers[] = $computer_to_add;
      }

      return $computers;
   }

   /**
    * Get the list of LDAP computers to add
    *
    * @param array   $options       possible options:
    *          - basedn force basedn (default authldaps_id one)
    *          - order display order
    *          - script true if called by an external script
    * @param array   $results       result stats
    * @param boolean $limitexceeded limit exceeded exception
    *
    * @return array of the computer
    */
   static function getAllComputers(array $options, &$results, &$limitexceeded) {
      global $DB;

      $ldap_server = new PluginLdapcomputersConfig();
      $res = $ldap_server->getFromDB($options['primary_ldap_id']);

      $values = [
                  'order'  => 'DESC',
                  'basedn' => $ldap_server->getField('basedn'),
                  'script' => 0, //Called by an external script or not
                ];

      foreach ($options as $option => $value) {
         // this test break mode detection - if ($value != '') {
         $values[$option] = $value;
         //}
      }

      $ldap_computers    = [];
      $computer_infos    = [];
      $limitexceeded     = false;

      // we prevent some delay...
      if (!$res) {
         return false;
      }
      if ($values['order'] != "DESC") {
         $values['order'] = "ASC";
      }
      $ds = PluginLdapcomputersLdap::connectToServer($ldap_server->getField('host'), $ldap_server->getField('port'), $ldap_server->getField('rootdn'),
                                                     Toolbox::decrypt($ldap_server->getField('rootdn_passwd'), GLPIKEY),
                                                     $ldap_server->getField('use_tls'), $ldap_server->getField('deref_option'));
      if ($ds) {
         $attrs = ["name", "lastLogon", "logonCount", "distinguishedName", "objectGUID"];
         $filter = $ldap_server->fields['condition'];
         $result = self::searchForComputers($ds, $values, $filter, $attrs, $limitexceeded,
                                            $computer_infos, $ldap_server);
         if (!$result) {
            return false;
         }
      } else {
         return false;
      }

      $ldapcomputers_computers = [];

      $select = [
         'FROM'   => self::getTable(),
         'ORDER'  => ['name ' . $values['order']]
      ];

      $iterator = $DB->request($select);

      while ($computer = $iterator->next()) {
         $tmpcomputer = new PluginLdapcomputersComputer();
         $ldapcomputers_computers[$computer['name']] = $computer['name'];
         $computerfound = self::dnExistsInLdap($computer_infos, $computer['distinguishedName']);
         if ($computerfound) {
            if (!$tmpcomputer->getFromDBByCrit(['distinguishedName' =>
                                       Toolbox::addslashes_deep($computer['distinguishedName'])])) {
               //This should never happened
               //If a computer_dn is present more than one time in database
               //Just skip computer
               continue;
            }
            $ldapcomputers_computers[] = ['id'                => $computer['id'],
                                          'name'              => $computerfound['name'],
                                          'lastLogon'         => $computerfound['$lastLogon'],
                                          'logonCount'        => $computerfound['logonCount'],
                                          'distinguishedName' => $computerfound['distinguishedName']];

         } else {
            $ldapcomputers_computers[] = ['id'                => $computer['id'],
                                          'name'              => $computerfound['name'],
                                          'lastLogon'         => $computerfound['$lastLogon'],
                                          'logonCount'        => $computerfound['logonCount'],
                                          'distinguishedName' => $computerfound['distinguishedName']];

         }
      }
      return $computer_infos;
   }

   /**
    * Search computers
    *
    * @param resource $ds            An LDAP link identifier
    * @param array    $values        values to search
    * @param string   $filter        search filter
    * @param array    $attrs         An array of the required attributes
    * @param boolean  $limitexceeded is limit exceeded
    * @param array    $user_infos    user informations
    * @param array    $ldap_users    ldap users
    * @param object   $config_ldap   ldap configuration
    *
    * @return boolean
    */
   static function searchForComputers($ds, $values, $filter, $attrs, &$limitexceeded,
                                      &$computer_infos, $config_ldap) {

      //If paged results cannot be used (PHP < 5.4)
      $cookie   = ''; //Cookie used to perform query using pages
      $count    = 0;  //Store the number of results ldap_search

      do {
         if (PluginLdapcomputersLdap::isLdapPageSizeAvailable($config_ldap)) {
            ldap_control_paged_result($ds, $config_ldap->fields['pagesize'], true, $cookie);
         }
         $filter = Toolbox::unclean_cross_side_scripting_deep(Toolbox::stripslashes_deep($filter));
         $sr     = @ldap_search($ds, $values['basedn'], $filter, $attrs);
         if ($sr) {
            if (in_array(ldap_errno($ds), [4,11])) {
               // openldap return 4 for Size limit exceeded
               $limitexceeded = true;
            }
            $info = PluginLdapcomputersLdap::get_entries_clean($ds, $sr);
            if (in_array(ldap_errno($ds), [4,11])) {
               $limitexceeded = true;
            }

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
               $computer_infos[$objectGUID] ['name']      = $info[$ligne]['name'][0];
               if (isset($info[$ligne]['lastlogon'][0])) {
                  $computer_infos[$objectGUID]["lastLogon"]  = PluginLdapcomputersLdap::ldapFiletime2Timestamp(
                     $info[$ligne]['lastlogon'][0],
                     $config_ldap->fields['time_offset']
                  );
               } else {
                  $computer_infos[$objectGUID]["lastLogon"] = null;
               }
               if (isset($info[$ligne]['logoncount'][0])) {
                  $computer_infos[$objectGUID]["logonCount"] = $info[$ligne]['logoncount'][0];
                  $computer_infos[$objectGUID]["distinguishedName"] = $info[$ligne]['distinguishedname'][0];
               } else {
                  $computer_infos[$objectGUID]["logonCount"] = null;
               }
            }
         } else {
            return false;
         }
         if (PluginLdapcomputersLdap::isLdapPageSizeAvailable($config_ldap)) {
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
    * Does LDAP user already exists in the database?
    *
    * @param string $name Computer name
    *
    *
    * @return false|Computer
    */
   static function getLdapExistingComputer($name) {
      global $DB;
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

}