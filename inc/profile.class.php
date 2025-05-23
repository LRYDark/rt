<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginRtProfile extends Profile {

   static function getTypeName($nb = 0) {
      return _n('Right management', 'Rights management', $nb, 'rt');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == 'Profile') {
         return __('<span class="d-flex align-items-center"><i class="fa-solid fa-car me-2"></i>RT</span>', "rt");
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Profile') {
         $ID   = $item->getID();
         $prof = new self();
         //self::addDefaultProfileInfos($ID,['plugin_rp_rapport_tech' => ALLSTANDARDRIGHT]);
         //self::addDefaultProfileInfos($ID,['plugin_rp' => ALLSTANDARDRIGHT]);
         $prof->showForm($ID);
      }
      return true;
   }

   function showForm($profiles_id = 0, $openform = TRUE, $closeform = TRUE) {

      echo "<div class='firstbloc'>";
      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) && $openform) {
         $profile = new Profile();
         echo "<form method='post' action='" . $profile->getFormURL() . "'>";
      }

      $profile = new Profile();
      $profile->getFromDB($profiles_id);

      $rights = $this->getAllRights();
      $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                                                    'default_class' => 'tab_bg_2',
                                                    'title'         => __('General')]);
      if ($canedit
          && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }

   static function getAllRights($all = false) {
      $rights = [
         ['itemtype' => 'PluginRtConfig',
         'label'    => __('Temps de trajet', 'rt'),
         'field'    => 'plugin_rt_rt',
         ],
         ['itemtype' => 'PluginRtConfig',
         'label'    => __('Affichage des infos sur les entitées', 'rt'),
         'field'    => 'plugin_rt_affichage',
         'rights'   => [READ    => __('Read')]
         ],
         ['itemtype' => 'PluginRtConfig',
         'label'    => __('Affichage du chronomètre', 'rt'),
         'field'    => 'plugin_rt_chrono',
         'rights'   => [READ    => __('Read')]
         ],
         ['itemtype' => 'PluginRtConfig',
         'label'    => __('Ajout de demandeur', 'rt'),
         'field'    => 'plugin_rt_add',
         'rights'   => [CREATE  => __('Create')]
         ]
      ];

      return $rights;
   }

   /**
    * Init profiles
    *
    **/
   static function translateARight($old_right) {
      switch ($old_right) {
         case '':
            return 0;
         case 'r' :
            return READ;
         case 'w':
            return ALLSTANDARDRIGHT;
         case '0':
         case '1':
            return $old_right;

         default :
            return 0;
      }
   }

   /**
    * Initialize profiles, and migrate it necessary
    */
   static function initProfile() {
      global $DB;
      $profile = new self();
      $dbu     = new DbUtils();

   /* --------------- Nettoyage de BDD en fonction de la suppression des tickets à chaque ouverture de session--------------- */
   /*
   $DB->doQuery("DELETE FROM glpi_plugin_rp_cridetails WHERE NOT EXISTS(SELECT id FROM glpi_tickets WHERE glpi_tickets.id = glpi_plugin_rp_cridetails.id_ticket);");
   $DB->doQuery("DELETE FROM glpi_plugin_rp_dataclient WHERE NOT EXISTS(SELECT id FROM glpi_tickets WHERE glpi_tickets.id = glpi_plugin_rp_dataclient.id_ticket);");
   */
   /*---------*/

      //Add new rights in glpi_profilerights table
      foreach ($profile->getAllRights(true) as $data) {
         if ($dbu->countElementsInTable("glpi_profilerights",
                                        ["name" => $data['field']]) == 0) {
            ProfileRight::addProfileRights([$data['field']]);
         }
      }

      /*foreach ($DB->request("SELECT *
                           FROM `glpi_profilerights` 
                           WHERE `profiles_id`='" . $_SESSION['glpiactiveprofile']['id'] . "' 
                              AND `name` LIKE '%plugin_rp%'") as $prof) {
         $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
      }*/
      $criteria = [
         'SELECT' => '*',
         'FROM'   => 'glpi_profilerights',
         'WHERE'  => [
            'profiles_id' => $_SESSION['glpiactiveprofile']['id'],
            'name'        => ['LIKE', '%plugin_rp%']
         ]
      ];

      foreach ($DB->request($criteria) as $prof) {
         $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
      }
   }

   /**
    * Initialize profiles, and migrate it necessary
    */
   static function changeProfile() {
      global $DB;

      /*foreach ($DB->request("SELECT *
                           FROM `glpi_profilerights` 
                           WHERE `profiles_id`='" . $_SESSION['glpiactiveprofile']['id'] . "' 
                              AND `name` LIKE '%plugin_rp%'") as $prof) {
         $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
      }*/
      $criteria = [
         'SELECT' => '*',
         'FROM'   => 'glpi_profilerights',
         'WHERE'  => [
            'profiles_id' => $_SESSION['glpiactiveprofile']['id'],
            'name'        => ['LIKE', '%plugin_rp%']
         ]
      ];

      foreach ($DB->request($criteria) as $prof) {
         $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
      }
   }

   static function createFirstAccess($profiles_id) {
      self::addDefaultProfileInfos($profiles_id,
                                   ['plugin_rt_rt'             => ALLSTANDARDRIGHT,
                                    'plugin_rt_chrono'         => ALLSTANDARDRIGHT,
                                    'plugin_rt_affichage'      => ALLSTANDARDRIGHT,
                                    'plugin_rt_add'            => ALLSTANDARDRIGHT], true);

   }

   static function removeRightsFromSession() {
      foreach (self::getAllRights(true) as $right) {
         if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
            unset($_SESSION['glpiactiveprofile'][$right['field']]);
         }
      }
   }

   static function removeRightsFromDB() {
      $plugprof = new ProfileRight();
      foreach (self::getAllRights(true) as $right) {
         $plugprof->deleteByCriteria(['name' => $right['field']]);
      }
   }

   /**
    * @param $profile
    **/
   static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false) {

      $profileRight = new ProfileRight();
      $dbu          = new DbUtils();

      foreach ($rights as $right => $value) {
         if ($dbu->countElementsInTable('glpi_profilerights',
                                        ["profiles_id" => $profiles_id,
                                         "name"        => $right]) && $drop_existing) {
            $profileRight->deleteByCriteria(['profiles_id' => $profiles_id, 'name' => $right]);
         }
         if (!$dbu->countElementsInTable('glpi_profilerights',
                                         ["profiles_id" => $profiles_id,
                                          "name"        => $right])) {
            $myright['profiles_id'] = $profiles_id;
            $myright['name']        = $right;
            $myright['rights']      = $value;
            $profileRight->add($myright);

            //Add right to the current session
            $_SESSION['glpiactiveprofile'][$right] = $value;
         }
      }
   }
}
