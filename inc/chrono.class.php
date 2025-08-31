<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}
$config = new PluginRtConfig();

if (!empty($_GET['_target'])){
    $VerifURL = substr($_GET['_target'], -15, null); // recupération URL

    if ($VerifURL == 'ticket.form.php'){ // Vérification de la page pour afficher le timer uniquement dans ticket.form.php
        ?><!-- ---------- timer JS---------- -->
            <script language="JavaScript">
                var timerID = 0
                function chrono(){
                    end = new Date()
                    diff = end - start
                    diff = new Date(diff)

                    var sec = diff.getSeconds()
                    var min = diff.getMinutes()
                    var hr = diff.getHours()-1

                    if (min < 10){
                        min = "0" + min
                    }
                    if (sec < 10){
                        sec = "0" + sec
                    }

                    document.getElementById("chronotime").innerHTML = hr + ":" + min + ":" + sec
                    timerID = setTimeout("chrono()", 10)
                }
            </script>

            <?php if($config->fields['showPlayPauseButton'] == 0 && $config->fields['showactivatetimer'] == 1){ ?>
                <script language="JavaScript">
                    function chronoStart(){
                        start = new Date()
                        chrono()
                    }
                </script>
            <?php } ?>

            <?php if($config->fields['showPlayPauseButton'] == 1 || $config->fields['showactivatetimer'] == 0){ ?>
                <script language="JavaScript">
                    function chronoStart(){
                    document.chronoForm.startstop.onclick = chronoStop
                    document.chronoForm.reset.onclick = chronoReset
                    start = new Date()
                    chrono()
                    }
                    function chronoContinue(){
                    document.chronoForm.startstop.onclick = chronoStop
                    document.chronoForm.reset.onclick = chronoReset
                    start = new Date()-diff
                    start = new Date(start)
                    chrono()
                    }
                    function chronoReset(){
                    document.getElementById("chronotime").innerHTML = "0:00:00"
                    start = new Date()
                    }
                    function chronoStopReset(){
                    document.getElementById("chronotime").innerHTML = "0:00:00"
                    document.chronoForm.startstop.onclick = chronoStart
                    }
                    function chronoStop(){
                    document.chronoForm.startstop.onclick = chronoContinue
                    document.chronoForm.reset.onclick = chronoStopReset
                    clearTimeout(timerID)
                    }
                </script>
            <?php } ?>
        <!-- ---------- timer ---------- --><?php

        class PluginRtChrono extends CommonDBTM
        {
            static private $_instance = null;
            public static $timerOn = 0;

            static function postShowItemChrono(){
                global $DB, $timerOn;
                $config = new PluginRtConfig();

                ?>
                <style>
                    .TimerBadge {
                        display: inline-block;
                        flex-wrap: wrap;
                        justify-content: center;
                        align-items: center;
                        background: <?php echo $config->fields['showBackgroundTimer']; ?> !important;
                        color: <?php echo $config->fields['showColorTimer']; ?> !important;
                        padding: calc(0.25rem - 1px) 0.25rem;
                        border: 1px solid transparent;
                        border-radius: 4px;
                        font-size: 0.7rem;
                    }
                    .chrono{
                        font-size: 12px;
                        margin-right: 5px;
                    }
                </style>
                <?php

                if($config->fields['showtimer'] == 1 && $timerOn == 0){// timer
                    $timerOn = 1;
                    if($config->fields['showPlayPauseButton'] == 1 || $config->fields['showactivatetimer'] == 0){
                        if ($config->fields['showcolorbutton'] == 0){
                            $Chrono = "<form name='chronoForm'><input type='button' style='background-color: white; border: none; margin-right: 5px;' class='fa-solid fa-play fa-pause' name='startstop' value='&#xf04b &#xf04c' onClick='chronoStart()'/><input type='button' style='background-color: white; border: none;' class='fas fa-sync-alt' name='reset' value='&#xf2f1'/></form>";
                        }else{
                            $Chrono = "<form name='chronoForm'><input type='button' style='background-color: #262626; color: #f5f7fb; border: none; margin-right: 5px;' class='fa-solid fa-play fa-pause' name='startstop' value='&#xf04b &#xf04c' onClick='chronoStart()'/><input type='button' style='background-color: #262626; color: #f5f7fb; border: none;' class='fas fa-sync-alt' name='reset' value='&#xf2f1'/></form>";
                        }
                    }

                    if($config->fields['showactivatetimer'] == 1){
                        $bgColor = $config->fields['showBackgroundTimer'];
                        $textColor = $config->fields['showColorTimer'];

                        $script = <<<JAVASCRIPT
                            $(document).ready(function() {
                                const chrono = $("<span>", {
                                    class: "input-group-text TimerBadge pe-2",
                                    style: "background: {$bgColor}; color: {$textColor}; display: flex; align-items: center;",
                                    html: `
                                        <div class='d-flex align-items-center'>
                                            <span class='chrono me-2' id='chronotime'>0:00:00</span>
                                            {$Chrono}
                                        </div>
                                    `
                                });

                                const searchGroup = $(".navbar form .input-group.input-group-flat");

                                if (searchGroup.length) {
                                    searchGroup.prepend(chrono);
                                }

                                if (typeof chronoStart === 'function') {
                                    chronoStart();
                                }
                            });
                        JAVASCRIPT;
                        echo Html::scriptBlock($script);
                    }else{
                        $script = <<<JAVASCRIPT
                            $(document).ready(function() {
                            $("div.navigationheader.justify-content-sm-between").append("<div class='TimerBadge'><span class='chrono' id='chronotime'>0:00:00</span>{$Chrono}</div>");
                            });
                        JAVASCRIPT;
                        echo Html::scriptBlock($script);
                    }
                }
            }
        }
    }
}
