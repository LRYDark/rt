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
      $config = new self();
      $config->getFromDB(1);

      $config->showFormHeader(['colspan' => 4]);
      echo "<tr><th colspan='2'>" . __('Chronomètre', 'rp') . "</th></tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Affichage du chronomètre dans le ticket", "rt") . "</td><td>";
            Dropdown::showYesNo('showtimer', $config->showTimer(), -1);
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Activation automatique du chronomètre à l'ouverture du ticket", "rt") . "</td><td>";
            Dropdown::showYesNo('showactivatetimer', $config->showactivatetimer(), -1);
            if ($config->showactivatetimer() == 0){
               echo ' Un bouton Play/Pause apparaitra pour lancé le chronomètre';
            }
         echo "</td>";
      echo "</tr>";

      if ($config->showactivatetimer() == 1){
         echo "<tr class='tab_bg_1'>";
            echo "<td>" . __("Possibilité de mettre sur Play/Pause et remettre à zéro le chronomètre", "rt") . "</td><td>";
               Dropdown::showYesNo('showPlayPauseButton', $config->showPlayPauseButton(), -1);
            echo "</td>";
         echo "</tr>";
      }

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

      if ($config->showactivatetimer() == 0 || $config->showPlayPauseButton() == 1){
         $values = [
            0 => __('Blanc (default)','rt'),
            1 => __('Noir','rt'),
         ];
         echo "<tr class='tab_bg_1'>";
            echo "<td>" . __("Couleur des boutons Play/Pause", "rt") . "</td><td>";
               Dropdown::showFromArray(
                  'showcolorbutton',
                  $values,
                  [
                     'value' => $config->fields['showcolorbutton']
                  ]
               );
            echo "</td>";
         echo "</tr>";
      }

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Affichage du recapitulatif des durées dans le ticket.", "rt") . "</td><td>";
            Dropdown::showYesNo('showtime', $config->showTime(), -1);
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Affichage de l'onglet temps de trajet dans le ticket.", "rt") . "</td><td>";
            Dropdown::showYesNo('fromonglettrajet', $config->fromOngletTrajet(), -1);
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Token ORS Maps.", "rt") . "</td><td>";
            echo Html::input('ORS_API_KEY', ['value' => $config->ORS_API_KEY(), 'size' => 120]);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      $config->showFormHeader(['colspan' => 4]);
      echo "<tr><th colspan='2'>" . __('Configuration mail', 'rp') . "</th></tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Autorisation d'envoyer les informations d'identification par mail lors de la création d'un demandeur.", "rt") . "</td><td>";
            Dropdown::showYesNo('mail', $config->mail(), -1);
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td> Gabarit : Modèle de notifications </td>";
      echo "<td>";

      //notificationtemplates_id
      Dropdown::show('NotificationTemplate', [
         'name' => 'gabarit',
         'value' => $config->gabarit(),
         'display_emptychoice' => 1,
         'specific_tags' => [],
         'itemtype' => 'NotificationTemplate',
         'displaywith' => [],
         'emptylabel' => "-----",
         'used' => [],
         'toadd' => [],
         'entity_restrict' => 0,
      ]); 
      echo "</td></tr>";

      // balises prise en charge
         echo "<tr class='tab_bg_1'><td> <b>Balises prisent en charge :</b>  </td></tr>";
         echo "<tr class='tab_bg_1'><td> ##id.user## </td><td> Information d'identification de l'utilisateur : prénom.nom </td></tr>";
         echo "<tr class='tab_bg_1'><td> ##user.password## </td><td> Information du mot de passe de l'utilisateur </td></tr>";
         echo "<tr class='tab_bg_1'><td> ##user.lastname## </td><td> Informations sur le nom de famille </td></tr>";
         echo "<tr class='tab_bg_1'><td> ##user.firstname## </td><td> Informations sur le prénom </td></tr>";
      // balises prise en charge
   
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center' colspan='2'>";
      echo "<input type='submit' class='submit' name='update' value=\"" . __('Save') . "\">";
      echo "</td>";
      echo "</tr>";
      $config->showFormButtons(['candel' => false]);
      return false;
   }

   // return fonction (retourn les values enregistrées en bdd)
   function fromOngletTrajet()
   {
      return ($this->fields['fromonglettrajet'] ? true : false);
   }
   function showTime()
   {
      return ($this->fields['showtime'] ? true : false);
   }
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
   function showactivatetimer()
   {
      return ($this->fields['showactivatetimer'] ? true : false);
   }
   function showPlayPauseButton()
   {
      return ($this->fields['showPlayPauseButton'] ? true : false);
   }
   function gabarit()
   {
      return ($this->fields['gabarit']);
   }
   function mail()
   {
      return ($this->fields['mail'] ? true : false);
   }
   function ORS_API_KEY()
   {
      return ($this->fields['ORS_API_KEY']);
   }
   // return fonction

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {

      if ($item->getType() == 'Config') {
         //return __("Chrono / Temps de trajet ", "rt");
         return __('<span class="d-flex align-items-center"><i class="fa-solid fa-car me-2"></i>Chrono / Temps de trajet</span>', "rt");
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
      if ($DB->tableExists($table)) {
         $query = "DROP TABLE $table";
         $DB->doQuery($query) or die($DB->error());
      }
      if (!$DB->tableExists($table)) {

         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS $table (
                  `id` int {$default_key_sign} NOT NULL auto_increment,
                  `showtimer` TINYINT NOT NULL DEFAULT '1',
                  `showColorTimer` VARCHAR(255) NOT NULL DEFAULT '#000000',
                  `showBackgroundTimer` VARCHAR(255) NOT NULL DEFAULT '#fec95c',
                  `showcolorbutton` TINYINT NOT NULL DEFAULT '0',
                  `showactivatetimer` TINYINT NOT NULL DEFAULT '1',
                  `showPlayPauseButton` TINYINT NOT NULL DEFAULT '1',
                  `fromonglettrajet` TINYINT NOT NULL DEFAULT '1',
                  `showtime` TINYINT NOT NULL DEFAULT '1',
                  /*`gabarit` INT(10) NOT NULL DEFAULT '0',
                  `mail` TINYINT NOT NULL DEFAULT '0',*/
                  PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
         $DB->doQuery($query) or die($DB->error());
         $config->add(['id' => 1,]);
      }

      // Mise à jour de la table si elle existe déjà
      if ($DB->tableExists($table)) {
         $migration->displayMessage("Updating $table");

         // Ajouter les nouvelles colonnes si elles n'existent pas
         $fieldsToAdd = [
            'gabarit' => "INT(10) NOT NULL DEFAULT '0'",
            'mail' => "TINYINT NOT NULL DEFAULT '0'",
         ];

         foreach ($fieldsToAdd as $field => $definition) {
            if (!$DB->fieldExists($table, $field)) {
               $query = "ALTER TABLE $table ADD `$field` $definition;";
               $DB->doQuery($query) or die($DB->error());
            }
         }
      }

      $result = $DB->doQuery("SELECT id FROM glpi_notificationtemplates WHERE NAME = 'RT Ajout Demandeur' AND comment = 'Created by the plugin RT'");

      while ($ID = $result->fetch_object()) {
          if (!empty($ID->id)) {
              // Suppression de la ligne dans glpi_notificationtemplates
              $deleteTemplateQuery = "DELETE FROM glpi_notificationtemplates WHERE id = {$ID->id}";
              $DB->doQuery($deleteTemplateQuery);
      
              // Suppression de la ligne correspondante dans glpi_notificationtemplatetranslations
              $deleteTranslationQuery = "DELETE FROM glpi_notificationtemplatetranslations WHERE notificationtemplates_id = {$ID->id}";
              $DB->doQuery($deleteTranslationQuery);
          }
      }

      // Préparer le contenu HTML
      $content_html = '
         &#60;div class="es-wrapper-color" dir="ltr" lang="fr" style="background-color: #f6f6f6;"&#62;
         &#60;table class="es-wrapper" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px; padding: 0; margin: 0; width: 100%; height: 100%; background-repeat: repeat; background-position: center top; background-color: #f6f6f6;" role="none" width="100%" cellspacing="0" cellpadding="0"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0;" valign="top"&#62;
         &#60;table class="es-header" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px; table-layout: fixed !important; width: 100%; background-color: transparent; background-repeat: repeat; background-position: center top;" role="none" cellspacing="0" cellpadding="0" align="center"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0;" align="center"&#62;
         &#60;table class="es-header-body" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px; background-color: transparent; width: 600px;" role="none" cellspacing="0" cellpadding="0" align="center" bgcolor="transparent"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="margin: 0; background-position: left top; padding: 20px;" align="left"&#62;
         &#60;table class="es-left" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px; float: left;" role="none" cellspacing="0" cellpadding="0" align="left"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td class="es-m-p20b" style="padding: 0; margin: 0; width: 270px;" align="left"&#62;
         &#60;table style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px;" role="presentation" width="100%" cellspacing="0" cellpadding="0"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0; font-size: 0px;" align="center"&#62;&#60;img class="adapt-img" style="display: block; border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic;" src="https://fvjwbn.stripocdn.email/content/guids/CABINET_c5c51ff0181c45244ed7878853b49cd87c5602ebd7aea7b1e1b5fb0395aca382/images/logoeasisupportnew_c3i.png" alt="" width="270"&#62;&#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;table class="es-right" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px; float: right;" role="none" cellspacing="0" cellpadding="0" align="right"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0; width: 270px;" align="left"&#62;
         &#60;table style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px;" role="none" width="100%" cellspacing="0" cellpadding="0"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0; display: none;" align="center"&#62; &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;table class="es-content" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px; table-layout: fixed !important; width: 100%;" role="none" cellspacing="0" cellpadding="0" align="center"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0;" align="center"&#62;
         &#60;table class="es-content-body" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px; background-color: #ffffff; width: 600px;" role="none" cellspacing="0" cellpadding="0" align="center" bgcolor="#ffffff"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="margin: 0; background-color: transparent; padding: 20px 20px 10px 20px;" align="left" bgcolor="transparent"&#62;
         &#60;table style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px;" role="none" width="100%" cellspacing="0" cellpadding="0"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0; width: 560px;" align="center" valign="top"&#62;
         &#60;table style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px; background-position: left top;" role="presentation" width="100%" cellspacing="0" cellpadding="0"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td class="es-m-txt-l" style="padding: 0; margin: 0; padding-bottom: 10px;" align="center"&#62;
         &#60;h2 style="margin: 0; line-height: 28.8px; mso-line-height-rule: exactly; font-family: helvetica, "helvetica neue", arial, verdana, sans-serif; font-size: 24px; font-style: normal; font-weight: bold; color: #040404;"&#62;Vos informations d’authentification&#60;/h2&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 20px; margin: 0;" align="center"&#62;
         &#60;table style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px;" role="presentation" border="0" width="100%" cellspacing="0" cellpadding="0"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0px; border-bottom: 1px solid #cccccc; background: none; height: 1px; width: 100%;"&#62; &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td class="es-m-txt-l" style="padding: 0; margin: 0; padding-bottom: 10px;" align="left"&#62;
         &#60;h2 style="margin: 0; line-height: 27.6px; mso-line-height-rule: exactly; font-family: helvetica, "helvetica neue", arial, verdana, sans-serif; font-size: 23px; font-style: normal; font-weight: bold; color: #040404;"&#62;&#60;strong&#62;Bonjour ##user.lastname##&#60;/strong&#62;,&#60;/h2&#62;
         &#60;p style="margin: 0; -webkit-text-size-adjust: none; -ms-text-size-adjust: none; mso-line-height-rule: exactly; font-family: helvetica, "helvetica neue", arial, verdana, sans-serif; line-height: 21px; color: #040404; font-size: 14px;"&#62; &#60;/p&#62;
         &#60;p style="margin: 0; -webkit-text-size-adjust: none; -ms-text-size-adjust: none; mso-line-height-rule: exactly; font-family: helvetica, "helvetica neue", arial, verdana, sans-serif; line-height: 21px; color: #040404; font-size: 14px;"&#62;Vous trouverez ci-dessous vos informations d\'identification pour accéder à vos tickets sur le site &#60;a style="-webkit-text-size-adjust: none; -ms-text-size-adjust: none; mso-line-height-rule: exactly; text-decoration: underline; color: #040404; font-size: 14px;" href="https://servicedesk.easisupport.fr/" target="_new"&#62;ServiceDesk&#60;/a&#62;.&#60;/p&#62;
         &#60;ul&#62;
         &#60;li style="-webkit-text-size-adjust: none; -ms-text-size-adjust: none; mso-line-height-rule: exactly; font-family: helvetica, "helvetica neue", arial, verdana, sans-serif; line-height: 21px; margin-bottom: 15px; margin-left: 0; color: #040404; font-size: 14px;"&#62;&#60;strong&#62;Nom d\'utilisateur&#60;/strong&#62; : ##id.user##&#60;/li&#62;
         &#60;li style="-webkit-text-size-adjust: none; -ms-text-size-adjust: none; mso-line-height-rule: exactly; font-family: helvetica, "helvetica neue", arial, verdana, sans-serif; line-height: 21px; margin-bottom: 15px; margin-left: 0; color: #040404; font-size: 14px;"&#62;&#60;strong&#62;Mot de passe&#60;/strong&#62; : ##user.password##&#60;/li&#62;
         &#60;/ul&#62;
         &#60;p style="margin: 0; -webkit-text-size-adjust: none; -ms-text-size-adjust: none; mso-line-height-rule: exactly; font-family: helvetica, "helvetica neue", arial, verdana, sans-serif; line-height: 21px; color: #040404; font-size: 14px;"&#62;Pour des raisons de sécurité, nous vous invitons à modifier votre mot de passe lors de votre première connexion.&#60;/p&#62;
         &#60;p style="margin: 0; -webkit-text-size-adjust: none; -ms-text-size-adjust: none; mso-line-height-rule: exactly; font-family: helvetica, "helvetica neue", arial, verdana, sans-serif; line-height: 21px; color: #040404; font-size: 14px;"&#62; &#60;/p&#62;
         &#60;p style="margin: 0; -webkit-text-size-adjust: none; -ms-text-size-adjust: none; mso-line-height-rule: exactly; font-family: helvetica, "helvetica neue", arial, verdana, sans-serif; line-height: 21px; color: #040404; font-size: 14px;"&#62;Nous restons à votre disposition pour toute assistance complémentaire.&#60;/p&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;table class="es-content" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px; table-layout: fixed !important; width: 100%;" role="none" cellspacing="0" cellpadding="0" align="center"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0;" align="center"&#62;
         &#60;table class="es-content-body" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px; background-color: #ffffff; width: 600px;" role="none" cellspacing="0" cellpadding="0" align="center" bgcolor="#ffffff"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0;" align="left"&#62;
         &#60;table style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px;" role="none" width="100%" cellspacing="0" cellpadding="0"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0; width: 600px;" align="center" valign="top"&#62;
         &#60;table style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px;" role="presentation" width="100%" cellspacing="0" cellpadding="0"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0;" align="left"&#62;
         &#60;p style="margin: 0; -webkit-text-size-adjust: none; -ms-text-size-adjust: none; mso-line-height-rule: exactly; font-family: helvetica, "helvetica neue", arial, verdana, sans-serif; line-height: 21px; color: #040404; font-size: 14px;"&#62;Cordialement,&#60;/p&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;table class="es-content" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px; table-layout: fixed !important; width: 100%;" role="none" cellspacing="0" cellpadding="0" align="center"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0;" align="center"&#62;
         &#60;table class="es-content-body" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px; background-color: #ffffff; width: 600px;" role="none" cellspacing="0" cellpadding="0" align="center" bgcolor="#ffffff"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="margin: 0; padding: 20px;" align="left"&#62;
         &#60;table class="es-left" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px; float: left;" role="none" cellspacing="0" cellpadding="0" align="left"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td class="es-m-p20b" style="padding: 0; margin: 0; width: 180px;" align="left"&#62;
         &#60;table style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px;" role="presentation" width="100%" cellspacing="0" cellpadding="0"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0; font-size: 0px;" align="left"&#62;&#60;img class="adapt-img" style="display: block; border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic;" src="https://fvjwbn.stripocdn.email/content/guids/CABINET_c5c51ff0181c45244ed7878853b49cd87c5602ebd7aea7b1e1b5fb0395aca382/images/logo_jcd_rZM.png" alt="" width="80"&#62;&#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;table style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px;" role="none" cellspacing="0" cellpadding="0" align="right"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0; width: 360px;" align="left"&#62;
         &#60;table style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px;" role="presentation" width="100%" cellspacing="0" cellpadding="0"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0;" align="left"&#62;
         &#60;p style="margin: 0; -webkit-text-size-adjust: none; -ms-text-size-adjust: none; mso-line-height-rule: exactly; font-family: helvetica, "helvetica neue", arial, verdana, sans-serif; line-height: 16.8px; color: #040404; font-size: 14px;"&#62;&#60;br&#62;&#60;br&#62;&#60;strong&#62;L’équipe Easi Support&#60;/strong&#62;&#60;/p&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;table class="es-content" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px; table-layout: fixed !important; width: 100%;" role="none" cellspacing="0" cellpadding="0" align="center"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0;" align="center"&#62;
         &#60;table class="es-content-body" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px; background-color: #ffffff; width: 600px;" role="none" cellspacing="0" cellpadding="0" align="center" bgcolor="#ffffff"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="margin: 0; padding: 20px;" align="left"&#62;
         &#60;table style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px;" role="none" width="100%" cellspacing="0" cellpadding="0"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0; width: 560px;" align="center" valign="top"&#62;
         &#60;table style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; border-spacing: 0px;" role="presentation" width="100%" cellspacing="0" cellpadding="0"&#62;
         &#60;tbody&#62;
         &#60;tr style="border-collapse: collapse;"&#62;
         &#60;td style="padding: 0; margin: 0;" align="left"&#62;
         &#60;p style="margin: 0; -webkit-text-size-adjust: none; -ms-text-size-adjust: none; mso-line-height-rule: exactly; font-family: helvetica, "helvetica neue", arial, verdana, sans-serif; line-height: 18px; color: #040404; font-size: 12px;"&#62;&#60;br&#62;&#60;em&#62;Ce courrier électronique est envoyé automatiquement par le centre de service Easi Support.&#60;/em&#62;&#60;br&#62;&#60;br&#62;&#60;br&#62;Généré automatiquement par GLPI.&#60;/p&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/td&#62;
         &#60;/tr&#62;
         &#60;/tbody&#62;
         &#60;/table&#62;
         &#60;/div&#62;
      ';
      // Échapper le contenu HTML
      $content_html_escaped = Toolbox::addslashes_deep($content_html);

      // Construire la requête d'insertion
      $insertQuery1 = "INSERT INTO `glpi_notificationtemplates` (`name`, `itemtype`, `date_mod`, `comment`, `css`, `date_creation`) VALUES ('RT Ajout Demandeur', 'Ticket', NULL, 'Created by the plugin RT', '', NULL);";
      // Exécuter la requête
      $DB->doQuery($insertQuery1);

      // Construire la requête d'insertion
      $insertQuery2 = "INSERT INTO `glpi_notificationtemplatetranslations` 
         (`notificationtemplates_id`, `language`, `subject`, `content_text`, `content_html`) 
         VALUES (LAST_INSERT_ID(), 'fr_FR', '[GLPI] | Vos informations de connexion JCD', '', '{$content_html_escaped}')";
      // Exécuter la requête
      $DB->doQuery($insertQuery2);

      $ID = $DB->doQuery("SELECT id FROM glpi_notificationtemplates WHERE NAME = 'RT Ajout Demandeur' AND comment = 'Created by the plugin RT'")->fetch_object();

      $query= "UPDATE glpi_plugin_rt_configs SET gabarit = $ID->id WHERE id=1;";
      $DB->doQuery($query) or die($DB->error());

      if ($_SESSION['PLUGIN_RT_VERSION'] > '1.5.3'){
         // Vérifier si les colonnes existent déjà
         $columns = $DB->doQuery("SHOW COLUMNS FROM `$table`")->fetch_all(MYSQLI_ASSOC);

         // Liste des colonnes à vérifier
         $required_columns = [
            'ORS_API_KEY'
         ];

         // Liste pour les colonnes manquantes
         $missing_columns = array_diff($required_columns, array_column($columns, 'Field'));

         if (!empty($missing_columns)) {
            $query= "ALTER TABLE $table
               ADD COLUMN `ORS_API_KEY` TEXT DEFAULT NULL;";
            $DB->doQuery($query) or die($DB->error());
         }
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
