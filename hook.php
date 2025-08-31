<?php

function plugin_rt_item_update($item)
{
    PluginRtTicket::updateroutetime($item);
    if (isset($item->input['items_id'])) {
        $ticket_id = $item->input['items_id'];
    }
}

function plugin_rt_install() { // fonction installation du plugin

   $migration = new Migration(PLUGIN_RT_VERSION);

   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginRt' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'install')) {
            $classname::install($migration);
         }
      }
   }
   $migration->executeMigration();
   return true;
}

function plugin_rt_uninstall() { // fonction desintallation du plugin

   $migration = new Migration(PLUGIN_RT_VERSION);

   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginRt' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'uninstall')) {
            $classname::uninstall($migration);
         }
      }
   }

   $migration->executeMigration();
   return true;
}



