<?php
use Glpi\Application\View\TemplateRenderer;

class PluginRtTicketConfig extends CommonDBTM
{
    public static $rightname = 'plugin_rt_ticketconfig';

    const TICKET_TAB  = 1024;
    const TICKET_FORM = 2048;

    public static function getTypeName($nb = 0)
    {
        return _sn('Temps passé (tâches / trajets)', 'Temps passé (tâches / trajets)', $nb, 'rt');
    }

    public static function getIcon()
    {
        return 'ti ti-clock-hour-4';
    }

    /**
     * Force use of the main RT table instead of an unused *_ticketconfigs table.
     */
    public static function getTable($classname = null)
    {
        return 'glpi_plugin_rt_tickets';
    }

    /**
     * Show default credit option ticket
     *
     * @param Ticket $ticket
     * @param bool $embed_in_ticket_form
     */
    public static function showForTicket(Ticket $ticket, bool $embed_in_ticket_form = false)
    {
        if(Session::haveRight("plugin_rt_rt", READ)){
            global $DB;

            //load ticket configuration
            $ticket_config = new PluginRtTicketConfig();
            $table = self::getTable();

            if (!$ticket->isNewItem() && $DB->tableExists($table)) {
                $ticket_config->getFromDBByCrit(["tickets_id" => $ticket->getID()]);
            } else {
                $ticket_config->getEmpty();
            }

            if ($embed_in_ticket_form) {
                $uncollapsed = (importArrayFromDB(Config::getSafeConfig()['itil_layout'])['items']['plugin-rt-ticket-config'] ?? 'true') == 'true';
            } else {
                $form_url = self::getFormUrl();
            }
                TemplateRenderer::getInstance()->display('@rt/tickets/config.html.twig', [
                    'embed_in_ticket_form' => $embed_in_ticket_form,
                    'uncollapsed'          => $uncollapsed ?? false,
                    'type_name'            => self::getTypeName(),
                    'entity_id'            => $ticket->getEntityID(),
                    'ticket'               => $ticket,
                    'ticket_config'        => $ticket_config,
                    'form_url'             => $form_url ?? '',
                    // >>> NOUVEAU : HTML du tableau de temps, prêt à être rendu dans Twig
                    'times_table_html'     => PluginRtEntity::buildTimesTable($ticket),
                ]);
        }
    }
}
