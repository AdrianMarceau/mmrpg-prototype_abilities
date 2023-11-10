<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);
        
        // Use a different attacking frame for the robot depending on who is using the ability
        $target_frame = 'summon';
        if ($this_robot->robot_token === 'sword-man'){ $target_frame = 'taunt'; }

        // Target the opposing robot
        $this_battle->queue_sound_effect('flame-sound');
        $this_battle->queue_sound_effect(array('name' => 'flame-sound', 'delay' => 100));
        $this_battle->queue_sound_effect(array('name' => 'flame-sound', 'delay' => 200));
        $this_ability->target_options_update(array(
            'frame' => $target_frame,
            'success' => array(0, 50, 0, 10, $this_robot->print_name().' readies their stance...')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);
        
        // Target the opposing robot
        $this_battle->queue_sound_effect('flame-sound');
        $this_battle->queue_sound_effect(array('name' => 'hyper-slide-sound', 'delay' => 100));
        $this_robot->set_frame_offset('z', 50);
        $this_ability->target_options_update(array(
            'frame' => 'throw',
            'kickback' => array(180, 0, 0),
            'success' => array(1, 50, 0, 10, $this_robot->print_name().' tears through the air with '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);
        $this_robot->reset_frame_offset('z');

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(2, -55, 0, 10, 'The '.$this_ability->print_name().' slashed right through the target!'),
            'failure' => array(2, -75, 0, -10, 'The '.$this_ability->print_name().' was off the mark&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(10, 0, 0),
            'success' => array(2, -35, 0, 10, 'The '.$this_ability->print_name().' only refreshed the target'),
            'failure' => array(2, -75, 0, -10, 'The '.$this_ability->print_name().' was off the mark&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
        
        // Ensure the target is not disabled before apply a stat change
        if ($target_robot->robot_status != 'disabled'
            && $this_ability->ability_results['this_result'] != 'failure'){

            // Check all three stats to see if they should be reversed
            $check_stats = array('attack', 'defense', 'speed');
            foreach ($check_stats AS $stat){
                if ($target_robot->counters[$stat.'_mods'] > 0){
                    // Call the global stat break function with customized options
                    rpg_ability::ability_function_stat_reset($target_robot, $stat, $this_ability, array(
                        'initiator_robot' => $this_robot
                        ));
                }
            }

        }

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
