<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Lock the user into the slide frame and shift them across the field
        $this_robot->set_frame('slide');
        $this_robot->set_frame_offset('x', 40);

        // Target the opposing robot and dash towards them (Frame 0: Wind-up)
        $this_battle->queue_sound_effect('dash-sound');
        $this_ability->target_options_update(array(
            'frame' => 'slide',
            'success' => array(0, 55, 0, 10, $this_robot->print_name().' uses the '.$this_ability->print_name().'!')
        ));
        $this_robot->trigger_target($target_robot, $this_ability);
        
        // Shift the user's slide forward a bit so it looks like they're moving
        $this_robot->set_frame_offset('x', 140);

        // Inflict damage on the opposing robot (Frame 1: Impact)
        $this_battle->queue_sound_effect('smack-sound');
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(20, 0, 0),
            'success' => array(1, 55, 0, 10, 'The '.$this_ability->print_name().' struck the target!'),
            'failure' => array(0, 55, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
        ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(10, 0, 0),
            'success' => array(1, 55, 0, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
            'failure' => array(0, 55, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
        ));
        $energy_damage_amount = $this_ability->ability_damage;
        
        // Trigger damage but defer the disabled check so we can move the user back first
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

        // Reset the offset and move the user back to their original position
        $this_robot->set_frame('base');
        $this_robot->set_frame_offset('x', 0);

        // Trigger an attack break if the ability was successful
        if ($target_robot->robot_status != 'disabled'
            && $this_ability->ability_results['this_result'] != 'failure'){

            // Call the global stat break function with customized options
            rpg_ability::ability_function_stat_break($target_robot, 'attack', 1, $this_ability, array(
                'initiator_robot' => $this_robot
            ));

        }

        // Now that the user is safely back in their base position, run the disabled check
        $target_player->check_robots_disabled($this_player, $this_robot);

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If the user has Extended Range, allow bench targeting
        if ($this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Return true on success
        return true;

    }
);
?>
