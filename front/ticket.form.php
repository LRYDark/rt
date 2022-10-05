<?php
use Glpi\Event;

include ('../../../inc/includes.php');

Session::haveRight("ticket", UPDATE);

$PluginRtTicket = new PluginRtTicket();
$ticket = new Ticket();

$input = [
    'tickets_id'                => $ticket->getID(),
    'entities_id'               => $ticket->getEntityID(),
    'routetime'                 => $_REQUEST['routetime_quantity'],
    'users_id'                  => Session::getLoginUserID(),
];
if ($PluginRtTicket->add($input)) {
    Session::addMessageAfterRedirect(
        __('Temps de trajet ajouté : ' . $item->input['routetime_quantity']/60 . ' Minute(s)', 'rt'),
        true,
        INFO
    );
    Html::back();
}

Html::displayErrorAndDie("lost");
