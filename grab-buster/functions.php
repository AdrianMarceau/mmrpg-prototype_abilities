<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'kickback' => array(-10, 0, 0),
            'success' => array(0, 150, 0, 10, $this_robot->print_name().' fires a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(1, -100, 0, 10, 'The '.$this_ability->print_name(true).' drained the target\'s energy!'),
            'failure' => array(1, -125, 0, -10, 'The '.$this_ability->print_name(true).' missed the target&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(10, 0, 0),
            'success' => array(1, -75, 0, 10, 'The '.$this_ability->print_name(true).' emboldened the target!'),
            'failure' => array(1, -100, 0, -10, 'The '.$this_ability->print_name(true).' missed the target&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Restore life energy if the ability was successful
        if ($this_robot->robot_energy < $this_robot->robot_base_energy
            && $this_ability->ability_results['this_result'] != 'failure'
            && $this_ability->ability_results['this_amount'] > 0){

            // Increase the target robot's energy stat
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'percent' => true,
                'kickback' => array(0, 0, 0),
                'success' => array(2, -5, -5, 10, $this_robot->print_name().'\'s life energy was restored!'),
                'failure' => array(2, 0, 0, -9999, '')
                ));
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'frame' => 'defend',
                'percent' => true,
                'kickback' => array(0, 0, 0),
                'success' => array(2, -5, -5, -10, $this_robot->print_name().'\'s life energy was lowered!'),
                'failure' => array(2, 0, 0, -9999, '')
                ));
            $energy_recovery_amount = ceil($this_ability->ability_results['this_amount'] * ($this_ability->ability_recovery2 / 100));
            if ($this_robot->robot_energy + $energy_recovery_amount > $this_robot->robot_base_energy){ $energy_recovery_amount = $this_robot->robot_base_energy - $this_robot->robot_energy; }
            $trigger_options = array('apply_modifiers' => false);
            $this_robot->trigger_recovery($this_robot, $this_ability, $energy_recovery_amount, true, $trigger_options);

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
