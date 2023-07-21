<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Update this ability's damage based on the user and target's speed
        if (!empty($target_robot->counters['speed_mods'])
            && $target_robot->counters['speed_mods'] > 0){
            $new_damage_amount = $this_ability->ability_base_damage + ($target_robot->counters['speed_mods'] * 5);
            $this_ability->set_name($this_ability->ability_base_name.' Δ');
            $this_ability->set_damage($new_damage_amount);
        } else {
            $this_ability->reset_name();
            $this_ability->reset_damage();
        }

        // Target the opposing robot
        $this_battle->queue_sound_effect('flame-sound');
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 100, 0, 10, $this_robot->print_name().' unleashes a '.$this_ability->print_name().'!'),
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_battle->queue_sound_effect('flame-sound');
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(15, 0, 0),
            'success' => array(1, -75, 0, 10, 'The '.$this_ability->print_name().' burned through the target!'),
            'failure' => array(1, -100, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(1, -75, 0, 10, 'The '.$this_ability->print_name().' ignited the target!'),
            'failure' => array(1, -100, 0, -10, 'The '.$this_ability->print_name().' had no effect&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Return true on success
        return true;

        },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

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

        // If the user has Extended Range, allow bench targeting
        if ($this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Return true on success
        return true;

        }
);
?>
