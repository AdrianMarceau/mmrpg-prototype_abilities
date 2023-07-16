<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_battle->queue_sound_effect('bubble-sound');
        $this_ability->target_options_update(array(
            'frame' => 'throw',
            'success' => array(0, 85, 35, 10, $this_robot->print_name().' thows a '.$this_ability->print_name().'!'),
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_battle->queue_sound_effect('explode-sound');
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(20, 0, 0),
            'success' => array(2, -10, -10, 10, 'The '.$this_ability->print_name().' burst on contact!'),
            'failure' => array(1, -65, -10, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'frame' => 'taunt',
            'kickback' => array(10, 0, 0),
            'success' => array(2, -10, -10, 10, 'The '.$this_ability->print_name().' burst on contact!'),
            'failure' => array(1, -65, -10, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Ensure the target is not disabled before apply a stat change
        if ($target_robot->robot_status != 'disabled'
            && $this_ability->ability_results['this_result'] != 'failure'
            && $target_robot->counters['attack_mods'] > 0){

            // Call the global stat break function with customized options
            rpg_ability::ability_function_stat_reset($target_robot, 'attack', $this_ability, array(
                'initiator_robot' => $this_robot
                ));

        }

        // Return true on success
        return true;

    }
);
?>
