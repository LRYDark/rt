<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginRtConfig extends CommonDBTM
{
   static private $_instance = null;

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

   static function getTypeName($nb = 0)
   {
      return __("Chrono / Temps de trajet ", "rt");
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

   static function showConfigForm() //formulaire de configuration du plugin
   {
      $rand = mt_rand();

      $config = new self();
      $config->getFromDB(1);

      $config->showFormHeader(['colspan' => 4]);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Affichage et activation du chronomètre à l'ouverture du ticket", "rt") . "</td><td>";
      Dropdown::showYesNo('showtimer', $config->showTimer(), -1);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Couleur du text du chronomètre", "rt") . "</td><td>";
      echo '<input type="color" name="showColorTimer" value="'.$config->showColorTimer().'">';
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Couleur de fond du chronomètre", "rt") . "</td><td>";
      echo '<input type="color" name="showBackgroundTimer" value="'.$config->showBackgroundTimer().'">';
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Appliquée la configuration par défaut", "rt") . "</td><td>";
      echo Html::submit('Defaut', ['value' => 'Defaut', 'class' => 'btn btn-info me-2']); // bouton / config par defaut
      echo "</td>";
      echo "</tr>";

      $config->showFormButtons(['candel' => false]);
      return false;
   }

   // return fonction (retourn les values enregistrées en bdd)
   function showTimer()
   {
      return ($this->fields['showtimer'] ? true : false);
   }
   function showColorTimer()
   {
      return ($this->fields['showColorTimer']);
   }
   function showBackgroundTimer()
   {
      return ($this->fields['showBackgroundTimer']);
   }
   // return fonction

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {

      if ($item->getType() == 'Config') {
         return __("Chrono / Temps de trajet ", "rt");
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
                      `showtimer` TINYINT NOT NULL DEFAULT '1',
                      `showColorTimer` VARCHAR(255) NOT NULL DEFAULT '#000000',
                      `showBackgroundTimer` VARCHAR(255) NOT NULL DEFAULT '#fec95c',
                      PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
         $DB->query($query) or die($DB->error());
         $config->add(['id' => 1,]);
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