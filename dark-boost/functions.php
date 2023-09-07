<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Check to see which stat is highest for this robot
        $best_stat = rpg_robot::get_best_stat($this_robot, true);

        // Target the opposing robot
        $this_battle->queue_sound_effect('summon-positive');
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 75, 0, 10,
                $this_robot->print_name().' targets '.$this_robot->get_pronoun('reflexive').'! <br />'.
                $this_robot->get_pronoun('subject').' triggers a '.$this_ability->print_name().'!'
                )
            ));
        $this_robot->trigger_target($this_robot, $this_ability, array('prevent_default_text' => true));

        // Call the global stat break function with customized options
        rpg_ability::ability_function_stat_boost($this_robot, $best_stat, 2, $this_ability, array(
            'initiator_robot' => $this_robot
            ));

        // Return true on success
        return true;

    }
);
?>
