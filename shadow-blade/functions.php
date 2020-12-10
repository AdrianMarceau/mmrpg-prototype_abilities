<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'throw',
            'success' => array(0, 120, 0, 10, $this_robot->print_name().' throws the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(1, -65, 0, 10, 'The '.$this_ability->print_name().' rips through the target!'),
            'failure' => array(1, -85, 0, -10, 'The '.$this_ability->print_name().' spun past the target&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(5, 0, 0),
            'success' => array(1, -65, 0, 10, 'The '.$this_ability->print_name().' rips through target!'),
            'failure' => array(1, -85, 0, -10, 'The '.$this_ability->print_name().' spun past the target&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Ensure the target is not disabled before apply a stat change
        if ($target_robot->robot_status != 'disabled'
            && $this_ability->ability_results['this_result'] != 'failure'){

            // Check to see which stat is highest for this robot
            $best_stat = rpg_robot::get_best_stat($target_robot);

            // Call the global stat break function with customized options
            rpg_ability::ability_function_stat_break($target_robot, $best_stat, 1, $this_ability, array(
                'initiator_robot' => $this_robot
                ));

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
