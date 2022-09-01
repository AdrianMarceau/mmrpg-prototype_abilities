<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(2, 135, -15, 10, $this_robot->print_name().' hurls '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(0, 0, 0),
            'success' => array(1, 0, 0, 10, 'The '.$this_ability->print_name().' ripped right into the target!'),
            'failure' => array(0, -150, 0, -10, 'The '.$this_ability->print_name().' went right past them&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(1, 0, 0, 10, 'What?! The '.$this_ability->print_name().' only made the target stronger!'),
            'failure' => array(0, -150, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
            $energy_damage_amount = $this_ability->ability_damage;
            $trigger_options = array('apply_target_attachment_damage_breakers' => false);
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, true, $trigger_options);

        // If this attack returns and strikes a second time (as long as first didn't KO)
        if ($this_ability->ability_results['this_result'] != 'failure'
            && $target_robot->robot_status != 'disabled'){

            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(25, 30, 0),
                'success' => array(0, -50, 60, 10, 'There it is! The '.$this_ability->print_name().' struck again!'),
                'failure' => array(1, 150, 0, -10, 'Oh... The second hit went right past them&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'kickback' => array(-5, 0, 0),
                'frame' => 'taunt',
                'success' => array(0, 100, 0, 10, 'Oh no! Not again!'),
                'failure' => array(1, 150, 0, -10, 'Oh! The second hit missed!')
                ));
            $energy_damage_amount = $this_ability->ability_damage;
            $trigger_options = array('apply_target_attachment_damage_breakers' => false);
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, true, $trigger_options);

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
