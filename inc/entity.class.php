<?php

/**
 * -------------------------------------------------------------------------
 * Credit plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Credit.
 *
 * Credit is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * Credit is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Credit. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @author    François Legastelois
 * @copyright Copyright (C) 2017-2023 by Credit plugin team.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/credit
 * -------------------------------------------------------------------------
 */

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;

class PluginRtEntity extends CommonDBTM
{
    public static $rightname = 'rt';

    public static function getTypeName($nb = 0)
    {
        return _sn('Temps passé (tâches / trajets)', 'Temps passé (tâches / trajets)', $nb, 'rt');
    }

    public static function getIcon()
    {
        return 'ti ti-clock-hour-4';
    }

    /**
     * Construit le tableau HTML "Durée des tâches / Durée des trajets"
     * à partir des mêmes requêtes et variables que ton code existant.
     * (actiontime en secondes, routetime en minutes)
     */
    public static function buildTimesTable(Ticket $ticket): string {
        global $DB;

        $ticketId = (int)$ticket->getField('id');
        $totals = $DB->doQuery(
            "SELECT
                (SELECT SUM(actiontime) FROM glpi_tickettasks WHERE tickets_id = $ticketId) AS TotalTask,
                (SELECT SUM(routetime) FROM glpi_plugin_rt_tickets WHERE tickets_id = $ticketId) AS TotalTrajet"
        )->fetch_object();

        $formatMinutesLabel = static function (?int $minutes): string {
            $minutes = (int) ($minutes ?? 0);
            if ($minutes <= 0) {
                return 'Aucune durée';
            }
            $h = (int) floor($minutes / 60);
            $m = $minutes % 60;
            return $h . 'h' . str_pad((string)$m, 2, '0', STR_PAD_LEFT) . ' | ' . $minutes . ' min';
        };

        // ----- Total tâches (actiontime en secondes -> minutes) -----
        $result_total_min_task = !empty($totals->TotalTask) ? (int) (((int)$totals->TotalTask) / 60) : 0;
        $result_total_task_label = $formatMinutesLabel($result_total_min_task);

        // ----- Total trajets (déjà en minutes) -----
        $result_total_min_trajet = !empty($totals->TotalTrajet) ? (int) $totals->TotalTrajet : 0;
        $result_total_trajet_label = $formatMinutesLabel($result_total_min_trajet);

        // ----- Tableau identique à ton rendu -----
        $tableau = "<table class='table table-bordered'><thead><tr>"
                    . "<th>Durée des tâches</th><th>Durée des trajets</th>"
                    . "</tr></thead><tbody><tr>"
                    . "<td>{$result_total_task_label}</td><td>{$result_total_trajet_label}</td>"
                    . "</tr></tbody></table>";

        return $tableau;
    }
}
