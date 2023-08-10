<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

//------------------------------------------------------------------------------------------
class PluginRtTicket extends CommonDBTM {

   public static $rightname = 'ticket';
   public  static  $EntitieAddress = 0 ;

   static function getTypeName($nb = 0) { // voir doc glpi 
      return _n('Temps de trajet', 'Temps de trajet', $nb, 'rt');
   }
   
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) { // voir doc glpi 
      $config = new PluginRtConfig();
      if ($config->fields['fromonglettrajet'] == 1){

         $nb = self::countForItem($item);
         switch ($item->getType()) {
            case 'Ticket' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(self::getTypeName($nb), $nb);
               } else {
                  return self::getTypeName($nb);
               }
            default :
               return self::getTypeName($nb);
         }
         return '';
      }
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) { // voir doc glpi 
      $config = new PluginRtConfig();
      if ($config->fields['fromonglettrajet'] == 1){
         switch ($item->getType()) {
            case 'Ticket' :
               self::showForTicket($item);
               break;
         }
         return true;
      }
   }

   /**
    * @param $item    CommonDBTM object
   **/
   public static function countForItem(CommonDBTM $item) { 
      return countElementsInTable(self::getTable(), ['tickets_id' => $item->getID()]);
   }

   /**
    * Get all routes times for a ticket.
    *
    * @param $ID           integer     tickets ID
    * @return array of vouchers
    */
   static function getAllForTicket($ID): array { // fonction qui va récupérer les informations sur le ticket 
      global $DB;

      $request = [
         'SELECT' => '*',
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'tickets_id' => $ID,
         ],
         'ORDER'  => ['id DESC'],
      ];

      $vouchers = [];
      foreach ($DB->request($request) as $data) {
         $vouchers[$data['id']] = $data;
      }

      return $vouchers;
   }

   /**
    * Get all tickets for a routes times.
    *
    * @param $ID           integer     plugin_rt_entities_id ID
    * @return array of vouchers
   **/
   /*static function getAllForRtEntity($ID): array {
      global $DB;

      $request = [
         'SELECT' => '*',
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'plugin_rt_entities_id' => $ID
         ],
         'ORDER'  => ['id DESC'],
      ];

      $tickets = [];
      foreach ($DB->request($request) as $data) {
         $tickets[$data['id']] = $data;
      }

      return $tickets;
   }*/

   /**
    * Get consumed tickets for rt entity entry
    *
    * @param $ID integer PluginRtEntity id
   **/
   /*static function getConsumedForRtEntity($ID) {
      global $DB;

      $tot   = 0;

      $request = [
         'SELECT' => ['SUM' => 'consumed as sum'],
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'plugin_rt_entities_id' => $ID
         ],
      ];

      if ($result = $DB->request($request)) {
         if ($row = $result->current()) {
            $tot = $row['sum'];
         }
      }

      return $tot;
   }*/

   /**
    * Show routes times consumed for a ticket
    *
    * @param $ticket Ticket object
   **/
   static function showForTicket(Ticket $ticket) { // formulaire sur le ticket
      global $DB, $CFG_GLPI;

      $ID = $ticket->getField('id'); // recupération de l'id ticket
      $sum = 0;
      $count = 0;

      if (!$ticket->can($ID, READ)) {
         return false;
      }

      $canedit = false;
      if (Session::haveRight(Entity::$rightname, UPDATE)) { // vérification des droits avanat d'affiché le canedit
         $canedit = true;
      } else if (
         $ticket->canEdit($ID)
         && !in_array($ticket->fields['status'], array_merge(Ticket::getSolvedStatusArray(), Ticket::getClosedStatusArray()))
      ) {
         $canedit = true;
      }

      $out = "";
      $out .= "<div class='spaced'>";
      $out .= "<table class='tab_cadre_fixe'>";
      $out .= "<tr class='tab_bg_1'><th colspan='2'>";
      $out .= __('Temps de Trajet', 'rt');
      $out .= "</th></tr></table></div>";

      $number = self::countForItem($ticket);
      $rand   = mt_rand();

      if ($number) {
         $out .= "<div class='spaced'>";

         if ($canedit) { // fonction massiveactions (utiliser dans la partie temps de trajet sur le ticket)
            $out .= Html::getOpenMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams =  [
               'num_displayed'    => $number,
               'container'        => 'mass'.__CLASS__.$rand,
               'rand'             => $rand,
               'display'          => false,
               'specific_actions' => [
                  'update' => _x('button', 'Update'),
                  'purge'  => _x('button', 'Delete permanently')
               ]
            ];
            $out .= Html::showMassiveActions($massiveactionparams);
         }

         $out .= "<table class='tab_cadre_fixehov'>";
         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';
         if ($canedit) {
            $header_begin  .= "<th width='10'>";
            $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_end    .= "</th>";
         }

         //tableau d'affichage des valeus
         $header_end .= "<th class='center'>".__('Tâche', 'rt')."</th>";
         $header_end .= "<th class='center'>".__('Entité', 'rt')."</th>";
         $header_end .= "<th class='center'>".__("Date d'ajout", 'rt')."</th>";
         $header_end .= "<th class='center'>".__('Utilisateur', 'rt')."</th>";
         $header_end .= "<th class='center'>".__('Temps du trajet', 'rt')."</th>";
         $header_end .= "</tr>";
         $out.= $header_begin.$header_top.$header_end;

         foreach (self::getAllForTicket($ID) as $data) {

            $out .= "<tr class='tab_bg_2'>";
            if ($canedit) {
               $out .= "<td width='10'>";
               $out .= Html::getMassiveActionCheckBox(__CLASS__, $data["id"]);
               $out .= "</td>";
            }

            $id_entities = $data['entities_id'];
            $rt_entity = $DB->query("SELECT completename FROM `glpi_entities` WHERE id= $id_entities")->fetch_object();

            $out .= "<td class='center'>";
            $out .= $data['tasks_id'];
            $out .= "</td>";
            $out .= "<td width='40%' class='center'>";
            $out .= $rt_entity->completename;
            $out .= "</td>";
            $out .= "<td class='center'>";
            $out .= Html::convDate($data["date_creation"]);
            $out .= "</td>";

            $showuserlink = 0;
            if (Session::haveRight('user', READ)) {
               $showuserlink = 1;
            }

            $out .= "<td class='center'>";
            $out .= getUserName($data["users_id"], $showuserlink);
            $out .= "</td>";
            $out .= "<td class='center'>";
            //$out .= $data['routetime'] . ' Minute(s)';
            $out .= str_replace(":", "h", gmdate("H:i",$data['routetime']*60));
            $out .= "</td></tr>";

            $sum += $data['routetime'];

            if($data['routetime'] != 0){
               $count++;
            }
         }

         $out .= $header_begin.$header_bottom.$header_end;
         $out .= "</table>";

         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            $out .= Html::showMassiveActions($massiveactionparams);
            $out .= Html::closeForm(false);
         }

      } else {
         $out .= "<p class='center b'>".__('Aucun temps de trajet ajouté', 'rt')."</p>";
      }
 
      //second tableau
      $out .= '<div style="margin-left: 20%; margin-right: 20%; margin-top: 3%; text-align: center;">';
      $out .= '<table class="table table-bordered">';
      $out .= '<thead>';
      $out .= '<tr>';
      $out .= '<th scope="col">Total minute(s)</th>';
      $out .= '<th scope="col">Total heure(s)</th>';
      $out .= '<th scope="col">Tâche(s) avec temps de trajet</th>';
      $out .= '<th scope="col">Nombres total de tâches</th>';
      $out .= '</tr>';
      $out .= '</thead>';
      $out .= '<tbody>';
      $out .= '<tr>';
      $out .= '<td>' . $sum . '</td>';
      $out .= '<td>' . str_replace(":", "h", gmdate("H:i",$sum*60)) . '</td>';
      $out .= '<td>' . $count . '</td>';
      $out .= '<td>' . countElementsInTable('glpi_tickettasks', ['tickets_id' => $ID]) . '</td>';
      $out .= '</tr>';
      $out .= '</tbody>';
      $out .= '</table>';
      $out .= '</div>';

      echo $out;
   }

   /**
    * Display voucher consumption fields at the end of a ticket processing form.
    *
    * @param array $params Array with "item" and "options" keys
    *
    * @return void
    */
   static public function formroutetime($params) { // ticket edit (appel a la fonction pour afficher les valeurs sur le ticket)

      global $CFG_GLPI, $DB;

      $item = $params['item'];

      if (!($item instanceof ITILSolution)
          && !($item instanceof TicketTask)
          && !($item instanceof ITILFollowup)) {
         return;
      }

      $ticket = null;
      if (array_key_exists('parent', $params['options'])
          && $params['options']['parent'] instanceof Ticket) {
         // Ticket can be found in `parent` option for TicketTask.
         $ticket = $params['options']['parent'];
      } else if (array_key_exists('item', $params['options'])
         && $params['options']['item'] instanceof Ticket) {
         // Ticket can be found in `'item'` option for ITILFollowup and ITILSolution.
         $ticket = $params['options']['item'];
      }

      if ($ticket === null) {
         return;
      }

      $out = "";
      $canedit = $ticket->canEdit($ticket->getID());
      if (in_array($ticket->fields['status'], Ticket::getSolvedStatusArray())
          || in_array($ticket->fields['status'], Ticket::getClosedStatusArray())) {
         $canedit = false;
      }

      if (($item instanceof ITILSolution) || ($item instanceof ITILFollowup)) {
            $canedit = false;
      }

      $rand = mt_rand();
      if ($canedit) {
         $out .= "<table class='table'>";
            $out .= "<th>";
               echo "<HR style='margin-top: 4px; margin-bottom: 2px;'>";

                     if (!$item->isNewItem()) { //si new ticket 
                        $id_tasks   = $item->getID();
                        $table      = self::getTable();
                        $result     = $DB->query("SELECT routetime FROM $table WHERE tasks_id = $id_tasks")->fetch_object();

                        if(!empty($result->routetime)){
                           $value = $result->routetime * 60;
                        }else{
                           $value = 0;
                        }
                           echo "<tr><td>" . __('<strong>Tâche N°'.$id_tasks.'</strong>', 'credit') . "</td>"; // affichage du numéro de la task 
                     }else{
                        $value = 0;
                     }
                        echo "<tr><td>" . __('<strong>Temps de trajet :</strong>', 'credit') . "</td>";
                        echo "<td>";

                        $toadd = [];
                        for ($i = 9; $i <= 100; $i++) {
                              $toadd[] = $i * HOUR_TIMESTAMP;
                        }
                        Dropdown::showTimeStamp("routetime_quantity", // liste deroulante avec le temps de trajet
                           [
                              'name'              => 'routetime_quantity',
                              'min'             => 0,
                              'max'             => 8 * HOUR_TIMESTAMP,
                              'value'           => $value,
                              'addfirstminutes' => true,
                              'inhours'         => true,
                              'toadd'           => $toadd,
                              'width'           => '30%',
                           ]
                        );
               echo "</td></tr>";
            $out .= "</th>";
         $out .= "</table>";
      
      }
      echo $out;
   }

   static function updateroutetime(TicketTask $item){ // fonction de la mise à jour du temps de trajet (appel a la fonction lorsque l'on modifie une tache)

      global $DB, $CFG_GLPI;

      $table      = self::getTable();
      $id_tasks   = $item->getID();
      $rt_ticket  = new self();

      if(!isset($item->input['routetime_quantity'])){
         $quantity = 0;
      }else{
         $quantity = $item->input['routetime_quantity']/60;
      }

      $result = $DB->query("SELECT routetime FROM $table WHERE tasks_id = $id_tasks")->fetch_object();

      if ($item->input['routetime_quantity']/60 != $result->routetime){
         if (!$item->isNewItem()){
            if(!empty($result->routetime) || $result->routetime == '0'){
               if($quantity != $result->routetime){

                  $OldTime = str_replace(":", "h", gmdate("H:i",$result->routetime*60));    
                  $NewTime = str_replace(":", "h", gmdate("H:i",$quantity*60)); 

                  if($DB->query("UPDATE $table SET routetime = $quantity WHERE tasks_id = $id_tasks")) { // affichage de la pop up d'information ou erreur lors de la modif
                     Session::addMessageAfterRedirect(
                        __('Temps de trajet modifié : '.$OldTime." -> ".$NewTime, 'rt'),
                        true,
                        INFO
                     );
                  }else{
                     Session::addMessageAfterRedirect(
                        __('Echec de la modification du temps de trajet', 'rt'),
                        true,
                        ERROR
                     );
                  }
               }
            }else{
               $rt_ticket = new self();
               $TaskId = $item->fields['tickets_id'];
               $result = $DB->query("SELECT entities_id FROM glpi_tickets WHERE id = $TaskId")->fetch_object();

               if(!empty($result->entities_id)){ // vérification de la requete (variable vide ou pas)
                  $EntityId = $result->entities_id;
               }else{
                  $EntityId = 0;
               }

               $input = [
                  'tasks_id'      => $id_tasks,
                  'tickets_id'    => $TaskId,
                  'entities_id'   => $EntityId,
                  'routetime'     => $quantity,
                  'users_id'      => Session::getLoginUserID(),
               ]; // valeurs
               $NewTime = str_replace(":", "h", gmdate("H:i",$quantity*60)); 

               if($rt_ticket->add($input)) { // fonction d'add (ajout des valeur dans la table)
                  Session::addMessageAfterRedirect( // pop up
                     __('Temps de trajet ajouté -> '.$NewTime, 'rt'),
                     true,
                     INFO
                  );
               }else{
                  Session::addMessageAfterRedirect(
                     __("Echec de l'ajout du temps de trajet", 'rt'),
                     true,
                     ERROR
                  );
               }
            }
         }
      }
   }

   static function addroutetime(CommonDBTM $item) { // fonction d'ajout du temps de trajet  (appel a la fonction pour ajouté du temps de trajet sur une nouvelle tache)

      global $CFG_GLPI,$DB;

      if (!is_array($item->input) || !count($item->input)) {
         return;
      }

      if (!($item instanceof ITILSolution) && !($item instanceof ITILFollowup)) {// verification qu'il s'agit bien d'une tache et non d'une solution ou d'un suivi

         $ticketId = null;
         if (array_key_exists('tickets_id', $item->fields)) {
            // Ticket ID can be found in `tickets_id` field for TicketTask.
            $ticketId = $item->fields['tickets_id'];
         } else if (array_key_exists('itemtype', $item->fields)
                  && array_key_exists('items_id', $item->fields)
                  && 'Ticket' == $item->fields['itemtype']) {
            // Ticket ID can be found in `items_id` field for ITILFollowup and ITILSolution.
            $ticketId = $item->fields['items_id'];
         }

         $ticket = new Ticket();
         if (null === $ticketId || !$ticket->getFromDB($ticketId)) {
            return;
         }

         if (!is_numeric(Session::getLoginUserID(false))
            || !Session::haveRightsOr('ticket', [Ticket::STEAL, Ticket::OWN])) {
            return;
         }

         if(!isset($item->input['routetime_quantity'])){
            $quantity = 0;
         }else{
            $quantity = $item->input['routetime_quantity']/60;
         }

         $rt_ticket = new self();
         $rand = rand(1, 9999);
         $input = [
            'tasks_id'      => $rand,
            'tickets_id'    => $ticket->getID(),
            'entities_id'   => $ticket->getEntityID(),
            'routetime'     => $quantity,
            'users_id'      => Session::getLoginUserID(),
         ];

         if ($item->input['routetime_quantity']/60 != 0){
            if ($rt_ticket->add($input)) { // ajout de ds valeur dans la bdd

               $table = self::getTable();
               $idticket = $ticket->getID();

               $result = $DB->query("SELECT glpi_tickettasks.id FROM glpi_tickettasks INNER JOIN $table
               ON glpi_tickettasks.date = $table.date_creation WHERE $table.tasks_id = $rand AND glpi_tickettasks.tickets_id = $idticket")->fetch_object();

                  if(!$DB->query("UPDATE $table SET tasks_id=$result->id WHERE tasks_id=$rand") || empty($result->id)){
                     $DB->query("DELETE FROM $table WHERE tasks_id=$rand");
                        Session::addMessageAfterRedirect(
                           __("Erreur lors de l'ajout du temps trajet", 'rt'),
                           true,
                           ERROR
                        );
                  }else{
                     $time = str_replace(":", "h", gmdate("H:i",$quantity*60));     
                     Session::addMessageAfterRedirect(
                        __('Temps de trajet ajouté : ' . $time . '-> Tâche : ' . $result->id, 'rt'),
                        true,
                        INFO
                     );
                  }
            }else{
               Session::addMessageAfterRedirect(
                  __("Erreur lors de l'ajout du temps trajet", 'rt'),
                  true,
                  ERROR
               );
            }
         }  
      }
   }

   static function postShowItemRT($params){
      global $DB, $EntitieAddress, $timerOn;

      $item = $params['item'];
      if (!is_object($item) || !method_exists($item, 'getType')) {
         return;
      }
      
      // Affichage du temps de trajet sous forme de badge sur la tache.
      switch ($item->getType()) {
         case 'TicketTask':
            $task_id    = $item->getID();
            $table      = self::getTable();
            $result     = $DB->query("SELECT routetime FROM $table WHERE tasks_id = $task_id")->fetch_object();

            if(!empty($result->routetime)){
               $time = $result->routetime;
               $Times = $result->routetime;
            }else{
               $time = 0;
               $Times = 0;
            }

            //$time = str_replace(":", "h", gmdate("H:i",$time));
         
            // conversion du temps pour l'affichage
            $heures = floor($time / 60);
            $minutes_restantes = $time % 60;
            $total_route = $heures . 'h' . str_pad($minutes_restantes, 2, '0', STR_PAD_LEFT);

            $fa_icon = ($Times > 0 ? ' fa-car ' : '');
            // mise en varibale des éléments à afficher
            $icon = "<span class='badge text-wrap ms-1 d-none d-md-block' style='color:black'><i id='rt_faclock_{$task_id}' class='fa{$fa_icon}'></i> $total_route </span>";
         
            if ($Times > 0) { // affichage des éléments sous forme de badge en JS 
               $script = <<<JAVASCRIPT
                  $(document).ready(function() {
                     $("div[data-itemtype='TicketTask'][data-items-id='{$task_id}'] div.card-body div.timeline-header div.creator").append("{$icon}");
                  });
               JAVASCRIPT;
               echo Html::scriptBlock($script);
            }
         break;
      }    

      // Affichage des infos de l'entité.
      $ticketId   = $_GET['id'];
      if($EntitieAddress == 0 && $ticketId != 0){
         $EntitieAddress = 1;
         $result     = $DB->query("SELECT glpi_entities.id, address, postcode, town, country, comment FROM glpi_entities INNER JOIN glpi_tickets ON glpi_entities.id = glpi_tickets.entities_id WHERE glpi_tickets.id = $ticketId")->fetch_object();
         
            if (!empty($result->comment))$complement = $result->comment;
            if (!empty($complement)){
               $complement .= "<br>";
               $complement = preg_replace("# {2,}#"," ",preg_replace("#(\r\n|\n\r|\n|\r)#"," ",$complement));
            }

            $address = "";
               if (!empty($result->address))$address .= $result->address.", ";
               if (!empty($result->postcode))$address .= $result->postcode.", ";
               if (!empty($result->town))$address .= $result->town.", ";
               if (!empty($result->country))$address .= $result->country.", ";

            if (!empty($address)){
               $address = substr($address,0,-2);          
               $address = preg_replace("# {2,}#"," ",preg_replace("#(\r\n|\n\r|\n|\r)#"," ",$address)).'.';
            }

            // verification des variables + affichage des infos de l'entité avec création d'un liens de recherche, filtré en fonction de l'entité.
         if (!empty($complement) || !empty($address)){
           $value = "<td><a href=ticket.php?is_deleted=0&as_map=0&browse=0&criteria[0][link]=AND&criteria[0][field]=80&criteria[0][searchtype]=equals&criteria[0][value]=$result->id&itemtype=Ticket&start=0&_glpi_csrf_token=9c400ceeba45c9c3e88bb3587d75bf6ec81ca5a774ce761aa6b71e3a84db751c&sort[]=19&order[]=DESC'> $complement $address </a></td>"; 
           $entitie = "<span class='glpi-badge form-field row col-12 d-flex align-items-center mb-2' style='margin-top:3px'> $value </span>";
               $script = <<<JAVASCRIPT
                  $(document).ready(function() {
                     $("div.form-field.row.col-12.d-flex.align-items-center.mb-2").append("{$entitie}");
                  });
               JAVASCRIPT;
            echo Html::scriptBlock($script);
         }
         
         // Affichage du temps total du ticket
         $config = new PluginRtConfig();
         if ($config->fields['showtime'] == 1){
            $result_total_task   = $DB->query("SELECT SUM(actiontime) as TotalTask from glpi_tickettasks WHERE tickets_id = $ticketId")->fetch_object();
            if(!empty($result_total_task->TotalTask)){ // recupération du temps total des taches

               $result_total_min_task = $result_total_task->TotalTask/60;
               $heures = floor($result_total_min_task / 60);
               $minutes_restantes = $result_total_min_task % 60;
               $result_total_hour_task = $heures . 'h' . str_pad($minutes_restantes, 2, '0', STR_PAD_LEFT);
               $result_total_task = $result_total_hour_task .' | '. $result_total_min_task .' min';

            }else{
               $result_total_task = 'Aucune durée';
            }
            
            $result_total_trajet = $DB->query("SELECT SUM(routetime) as TotalTrajet from glpi_plugin_rt_tickets WHERE tickets_id = $ticketId")->fetch_object();
            if(!empty($result_total_trajet->TotalTrajet)){// recupération du temps total des trajet

               $result_total_min_trajet = $result_total_trajet->TotalTrajet;
               $heures = floor($result_total_min_trajet / 60);
               $minutes_restantes = $result_total_min_trajet % 60;
               $result_total_hour_task = $heures . 'h' . str_pad($minutes_restantes, 2, '0', STR_PAD_LEFT);
               $result_total_trajet = $result_total_hour_task .' | '. $result_total_min_trajet .' min';

            }else{
               $result_total_trajet = 'Aucune durée';
            }

            $tableau = "<table class='table table-bordered'><tr><th scope='col'>Durée des tâches</th><th scope='col'>Durée des trajets</th></tr><tr><td>$result_total_task</td><td>$result_total_trajet</td></tr></table>";
            $entitie = "<span class='entity-badge form-field row col-12 d-flex align-items-center mb-2' style='margin-top:3px'> $tableau </span>";

            //affichage du tableau 
            $script = <<<JAVASCRIPT
                  $(document).ready(function() {
                     $("div.form-field.row.col-12.d-flex.align-items-center.mb-2").append("{$entitie}");
                  });
               JAVASCRIPT;
            echo Html::scriptBlock($script);
         }
      }

      if (empty($ticketId)){

      /**
       * @param $title
      * @param $text
      */
         echo'<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">';
         echo'<div class="modal-dialog">';
            echo'<div class="modal-content">';
               echo'<div class="modal-header">';
                  echo"<h5 class='modal-title' id='exampleModalLabel'>Ajout d'un demandeur</h5>";
                  echo'<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
               echo'</div>';
                  echo'<div class="modal-body">';
                  ?><style> /*Style du modale et du tableau */
                        .modal-dialog { 
                           max-width: 700px; 
                           margin: 1.75rem auto; 
                        }
                        .table td, .table td { 
                           border: none !important;
                        }
                  </style><?php 
                     //echo "<form id='monFormulaire' method=\"post\" name=\"formReport\">";
                        echo '<div class="table-responsive">';
                           echo "<table class='table'>"; 
                           // Entity
                              echo "<tr id='bar'>";
                                 echo "<td class='table-secondary'>";
                                    echo '';
                                 echo "</td>";

                                 echo "<td>";
                                       echo "<label for='name'>Entité de l'utilisateur</label><br>";

                                          Dropdown::show('Entity', [
                                             'name' => 'add_user_for_entities_id',
                                             'width'  => '80%',
                                             //'value' => $this->fields["entities_id"],
                                          ]);

                                          /* $querytask = "SELECT id, completename, name FROM glpi_entities ORDER BY id ASC;";
                                             $resulttask = $DB->query($querytask);
                                             $name = array();
                                    
                                             while ($data = $DB->fetchArray($resulttask)) {
                                                array_push($name, $data['name']);
                                             }

                                             Dropdown::showFromArray(
                                                'entity',
                                                $name,
                                                [
                                                   'value' => 0,
                                                ]
                                             ); */

                                       //$entity_id = $_POST['entities_id'];                                      
                                    echo "</select>";
                                 echo "</td>";
                              echo "</tr>";

                           // Nom / Prénom
                              echo "<tr id='bar'>";
                                 echo "<td class='table-secondary'>";
                                    echo "";
                                 echo "</td>";

                                 echo "<td>";
                                       echo '<label for="nom">Nom de famille</label><br>';
                                    echo '<input type="text" id="lastname" name="nom" placeholder="Nom" required>';
                                 echo "</td>";

                                 echo "<td>";
                                       echo '<label for="prenom">Prénom</label><br>';
                                    echo '<input type="text" id="firstname" name="prenom" placeholder="Prénom" required>';
                                 echo "</td>";
                              echo "</tr>";

                           // Téléphone / email
                              echo "<tr>";
                                 echo "<td class='table-secondary'>";
                                    echo '';
                                 echo "</td>"; 

                                 echo "<td>";
                                       echo '<label for="phone">Numéro de téléphone</label><br>';
                                    echo '<input type="tel" id="phone" name="phone" placeholder="Téléphone">';
                                 echo "</td>";
 
                                 echo "<td>";
                                       echo '<label for="email">Courriels</label><br>';
                                    echo "<input type='mail' id='mail' name='email' required style='widtd: 850px;' placeholder='Courriels'>";
                                 echo "</td>";
                              echo "</tr>";
                     
                           //Bouton validation
                              /*echo "<tr>";
                                 echo "<td class='table-secondary'>";
                                    echo '';
                                 echo "</td>";

                                 echo "<td>";
                                    echo "<input type='submit' name='add_cri' id='sig-submitBtn' value='Ajouter' class='submit'>";
                                 echo "</td>";
                              echo "</tr>";*/
                           echo "</table>"; 
                        echo "</div>";
                     //Html::closeForm(); 
                     echo '<div id="resultat"></div>';
                  echo '</div>';
               echo '<div class="modal-footer">';
                  echo '<button id="submit" class="btn btn-primary">Envoyer</button>';
                  echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>';
               echo '</div>';
            echo '</div>';
         echo '</div>';
         echo '</div>';

         $entitie = "<div class='d-grid gap-2 d-md-block'><button type='button' style='border: 1px solid;' class='btn-sm btn-outline-secondary' data-bs-toggle='modal' data-bs-target='#exampleModal'><i class='fas fa-plus'></i> Ajouter un demandeur</button></div>";
         $script = <<<JAVASCRIPT
            // Affichage du bouton ajouter un demandeur 
               $(document).ready(function() {
                     $("div.accordion-body.accordion-actors.row.m-0.mt-n2").append("{$entitie}");
               });
            //--------------------------------------

            //Action lors de l'ajout du demandeur
               document.getElementById('submit').addEventListener('click', function() {
                  var lastname = document.getElementById('lastname').value;
                  var firstname = document.getElementById('firstname').value;
                  var mail = document.getElementById('mail').value;
                  var phone = document.getElementById('phone').value;

                  var id_element_select = document.querySelector('[name="add_user_for_entities_id"]').id;
                     var entity_id = document.getElementById(id_element_select).value;

                  //alert("Nom : " + lastname + " / Prénom : " + firstname + " / Mail : " + mail + " / Phone : " + phone + " / ID Entity : " + entity_id);      
                  $.ajax({
                     type: "GET",
                     url: "http://localhost/glpi/plugins/rt/inc/traitement.php?lastname=" + lastname + "&firstname=" + firstname + "&mail=" + mail + "&phone=" + phone + "&entity_id=" + entity_id,
                     success: function(rep){
                        alert(rep);
                     },
                     error: function(err){
                        alert(err);
                     }
                  }); 
               });
            //--------------------------------------
         JAVASCRIPT;
         echo Html::scriptBlock($script);
      }
   }

   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'       => 881,
         'table'    => self::getTable(),
         'field'    => 'date_creation',
         'name'     => __("Date d'ajout", 'rt'),
         'datatype' => 'date',
      ];

      $tab[] = [
         'id'       => 882,
         'table'    => self::getTable(),
         'field'    => 'routetime',
         'name'     => __('Temps du trajet en minute(s)', 'rt'),
         'datatype' => 'number',
         'min'      => 1,
         'max'      => 1000000,
         'step'     => 1,
         'toadd'    => [0 => __('-----')],
      ];
      
      return $tab;
   }

   /**
    * Install all necessary table for the plugin
    *
    * @return boolean True if success
    */
   static function install(Migration $migration) { // fonction intsllation de la table en BDD
      global $DB;

      $default_charset = DBConnection::getDefaultCharset();
      $default_collation = DBConnection::getDefaultCollation();
      $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

      $table = self::getTable();

      if (!$DB->tableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int {$default_key_sign} NOT NULL auto_increment,
                     `tasks_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                     `tickets_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                     `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                     `date_creation` timestamp NULL DEFAULT NULL,
                     `routetime` int NOT NULL DEFAULT '0',
                     `users_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                     PRIMARY KEY (`id`),
                     UNIQUE KEY (`tasks_id`),
                     KEY `tickets_id` (`tickets_id`),
                     KEY `entities_id` (`entities_id`),
                     KEY `date_creation` (`date_creation`),
                     KEY `routetime` (`routetime`),
                     KEY `users_id` (`users_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
         $DB->query($query) or die($DB->error());
      } else {

         // Fix #1 in 1.0.1 : change tinyint to int for tickets_id
         $migration->changeField($table, 'tickets_id', 'tickets_id', "int {$default_key_sign} NOT NULL DEFAULT 0");

         // Change tinyint to int
         $migration->changeField(
            $table,
            'entities_id',
            'entities_id',
            "int {$default_key_sign} NOT NULL DEFAULT 0"
         );
         $migration->changeField($table, 'users_id', 'users_id', "int {$default_key_sign} NOT NULL DEFAULT 0");

         //execute the whole migration
         $migration->executeMigration();
      }
   }

   /**
    * Uninstall previously installed table of the plugin
    *
    * @return boolean True if success
    */
   static function uninstall(Migration $migration) {

      $table = self::getTable();
      $migration->dropTable($table);
   }
}
