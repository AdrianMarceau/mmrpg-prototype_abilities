<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_battle->queue_sound_effect(array('name' => 'hyper-blast-sound', 'volume' => 1.0));
        $this_battle->queue_sound_effect(array('name' => 'hyper-blast-sound', 'volume' => 0.8, 'delay' => 40));
        $this_battle->queue_sound_effect(array('name' => 'hyper-blast-sound', 'volume' => 0.6, 'delay' => 80));
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'kickback' => array(-10, 0, 0),
            'success' => array(0, 90, 5, 5, $this_robot->print_name().' releases a '.$this_ability->print_name().'!', 2)
            ));
        $trigger_options = array();
        $this_robot->trigger_target($target_robot, $this_ability, $trigger_options);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(30, 0, 0),
            'rates' => array('auto', 'auto', $this_ability->ability_recovery2),
            'success' => array(2, -120, 5, 10, 'The '.$this_ability->print_name().' crashes into the target!', 2),
            'failure' => array(2, -140, 0, -10, 'The '.$this_ability->print_name().' missed the target&hellip;', 2)
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(15, 0, 0),
            'rates' => array('auto', 'auto', $this_ability->ability_recovery2),
            'success' => array(2, -120, 5, 10, 'The '.$this_ability->print_name().' was absorbed by the target!', 2),
            'failure' => array(2, -140, 0, -10, 'The '.$this_ability->print_name().' missed the target&hellip;', 2)
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
