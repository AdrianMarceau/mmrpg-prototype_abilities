<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_battle->queue_sound_effect('cannon-sound');
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 300, 0, 10, $this_robot->print_name().' fires the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(0, 15, 0),
            'success' => array(1, 0, 30, 10, 'The '.$this_ability->print_name_s().' shot burned the target!'),
            'failure' => array(1, 0, 0, -10, 'The '.$this_ability->print_name().' missed the target&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(1, 0, 30, 10, 'The '.$this_ability->print_name_s().' shot fueled the target!'),
            'failure' => array(1, 0, 0, -10, 'The '.$this_ability->print_name().' was ignored by the target&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $this_robot->robot_frame = 'defend';
        $this_robot->update_session();
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
        $this_robot->robot_frame = 'base';
        $this_robot->update_session();

        // Only lower the target's stat of the ability was successful
        if ($this_ability->ability_results['this_result'] != 'failure'){
            // Call the global stat break function with customized options
            rpg_ability::ability_function_stat_break($target_robot, 'defense', 1, $this_ability, array(
                'initiator_robot' => $this_robot
                ));
        }

        // Return true on success
        return true;

        }
);
?>
