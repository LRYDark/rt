<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginRtConfig
 */
class PluginRtConfig extends CommonDBTM
{
   static private $_instance = null;

   /**
    * PluginRtConfig constructor.
    */
   function __construct()
   {
      global $DB;

      if ($DB->tableExists($this->getTable())) {
         $this->getFromDB(1);
      }
   }

   static function canCreate()
   {
      return Session::haveRight('config', UPDATE);
   }

   static function canView()
   {
      return Session::haveRight('config', READ);
   }

   static function canUpdate()
   {
      return Session::haveRight('config', UPDATE);
   }

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0)
   {
      return __("Task timer configuration", "rt");
   }

   static function getInstance()
   {
      if (!isset(self::$_instance)) {
         self::$_instance = new self();
         if (!self::$_instance->getFromDB(1)) {
            self::$_instance->getEmpty();
         }
      }
      return self::$_instance;
   }

   /**
    * @param bool $update
    *
    * @return PluginRtConfig
    */
   static function getConfig($update = false)
   {
      static $config = null;
      if (is_null(self::$config)) {
         $config = new self();
      }
      if ($update) {
         $config->getFromDB(1);
      }
      return $config;
   }

   static function showConfigForm()
   {
      $rand = mt_rand();

      $config = new self();
      $config->getFromDB(1);

      $config->showFormHeader(['colspan' => 4]);

      /*$values = [
         0 => __('In Standard interface only (default)', 'rt'),
         1 => __('Both in Standard and Helpdesk interfaces', 'rt'),
      ];
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Enable timer on tasks", "rt") . "</td><td>";
      Dropdown::showFromArray(
         'displayinfofor',
         $values,
         [
            'value' => $config->fields['displayinfofor']
         ]
      );
      echo "</td>";
      echo "</tr>";*/

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Affichage et activation du chronomètre à l'ouverture du ticket", "rt") . "</td><td>";
      Dropdown::showYesNo('showtimer', $config->showTimer(), -1);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Affichage et activation du chronomètre à l'ouverture du ticket", "rt") . "</td><td>";
      Dropdown::showYesNo('showColorTimer', $config->showColorTimer(), -1);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Affichage et activation du chronomètre à l'ouverture du ticket", "rt") . "</td><td>";
      Dropdown::showYesNo('showBackgroundTimer', $config->showBackgroundTimer(), -1);
      echo "</td>";
      echo "</tr>";

      $config->showFormButtons(['candel' => false]);

      return false;
   }

   // return fonction 
   function showTimer()
   {
      return ($this->fields['showtimer'] ? true : false);
   }
   function showColorTimer()
   {
      return ($this->fields['showColorTimer'] ? true : false);
   }
   function showBackgroundTimer()
   {
      return ($this->fields['showBackgroundTimer'] ? true : false);
   }
   // return fonction

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {

      if ($item->getType() == 'Config') {
         return __("Chronomètre ", "rt");
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {

      if ($item->getType() == 'Config') {
         self::showConfigForm();
      }
      return true;
   }

   static function install(Migration $migration)
   {
      global $DB;

      $default_charset = DBConnection::getDefaultCharset();
      $default_collation = DBConnection::getDefaultCollation();
      $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

      $table = self::getTable();
      $config = new self();
      if (!$DB->tableExists($table)) {

         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS $table (
                      `id` int {$default_key_sign} NOT NULL auto_increment,
                      `displayinfofor` smallint NOT NULL DEFAULT '0',
                      `showtimer` TINYINT NOT NULL DEFAULT '1',
                      `showColorTimer` TINYINT NOT NULL DEFAULT '1',
                      `showBackgroundTimer` TINYINT NOT NULL DEFAULT '1',
                      PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
         $DB->query($query) or die($DB->error());
         $config->add([
            'id' => 1,
            'displayinfofor' => 0,
         ]);
      } else {
         $migration->changeField($table, 'showtimer', 'showtimer', 'bool', ['value' => 1]);
         $migration->changeField($table, 'showColorTimer', 'showColorTimer', 'bool', ['value' => 1]);
         $migration->changeField($table, 'showBackgroundTimer', 'showBackgroundTimer', 'bool', ['value' => 1]);

         $migration->dropField($table, 'autoopennew');

         $migration->migrationOneTable($table);
      }
   }

   static function uninstall(Migration $migration)
   {
      global $DB;

      $table = self::getTable();
      if ($DB->TableExists($table)) {
         $migration->displayMessage("Uninstalling $table");
         $migration->dropTable($table);
      }
   }
}
