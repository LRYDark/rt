<?php
include('../../../inc/includes.php');

Html::header_nocache();
Session::checkLoginUser();


switch ($_POST['action']) {//action bouton généré PDF formulaire ticket
   case 'showCriForm' :
      $PluginRtCri = new PluginRtCri();
      $params                  = $_POST["params"];
      $PluginRtCri->showForm($params["job"], ['modal' => "test"]);
      break;
}
