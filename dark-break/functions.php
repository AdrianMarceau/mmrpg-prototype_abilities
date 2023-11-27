<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Check to see which stat is highest for this robot
        $best_stat = rpg_robot::get_best_stat($target_robot, true);

        // Target the opposing robot
        $this_battle->queue_sound_effect('summon-negative');
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 75, 0, 10, ucfirst($this_robot->get_pronoun('subject')).' triggers a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Call the global stat break function with customized options
        if ($best_stat === 'all'){ $stats = array('attack', 'defense', 'speed'); }
        else { $stats = array($best_stat); }
        foreach ($stats AS $stat){
            rpg_ability::ability_function_stat_break($target_robot, $stat, 2, $this_ability, array(
                'initiator_robot' => $this_robot
                ));
        }

        // Return true on success
        return true;

    }
);
?>
