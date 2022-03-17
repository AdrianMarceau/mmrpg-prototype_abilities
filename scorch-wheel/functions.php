<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);
        
                // Update this ability's damage based on the user and target's speed
        if (!empty($target_robot->counters['speed_mods'])
            && $target_robot->counters['speed_mods'] < 0){
            $new_damage_amount = $this_ability->ability_base_damage + ($target_robot->counters['speed_mods'] * 5);
            $this_ability->set_name($this_ability->ability_base_name.' Δ');
            $this_ability->set_damage($new_damage_amount);
        } else {
            $this_ability->reset_name();
            $this_ability->reset_damage();
        }

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 75, 0, 10, $this_robot->print_name().' uses the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(1, -55, 0, 10, 'The '.$this_ability->print_name().' hit the target!'),
            'failure' => array(1, -75, 0, -10, 'The '.$this_ability->print_name().' missed the target&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(10, 0, 0),
            'success' => array(1, -35, 0, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
            'failure' => array(1, -75, 0, -10, 'The '.$this_ability->print_name().' missed the target&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $trigger_options = array('target_stat_substitution' => array('robot_attack' => 'robot_speed'));
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, true, $trigger_options);
        
        // Update this ability's damage based on the user and target's speed
        if (!empty($target_robot)){
            if (!empty($target_robot->counters['speed_mods'])
                && $target_robot->counters['speed_mods'] > 0){
                $new_damage_amount = $this_ability->ability_base_damage + ($target_robot->counters['speed_mods'] * 5);
                $this_ability->set_name($this_ability->ability_base_name.' Δ');
                $this_ability->set_damage($new_damage_amount);
            } else {
                $this_ability->reset_name();
                $this_ability->reset_damage();
            }
        }

        // Return true on success
        return true;

    }
);
?>
