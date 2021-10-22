<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);
     

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'throw',
            'success' => array(1, 100, 0, 10, $this_robot->print_name().' throws a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(0, 0, 5, 10, 'The '.$this_ability->print_name().' hit the target!'),
            'failure' => array(0, -50, 5, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(0, 0, 5, 10, 'The '.$this_ability->print_name().' was enjoyed by the target!'),
            'failure' => array(0, -50, 5, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // If this attack returns and strikes a second time (random chance)
        if ($this_ability->ability_results['this_result'] != 'failure'
            && $target_robot->robot_status != 'disabled'
            && $this_battle->critical_chance($this_ability->ability_accuracy)){

            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(20, 0, 0),
                'success' => array(1, -40, 10, 10, 'Oh! It hit again!'),
                'failure' => array(1, -90, 10, -10, '')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'kickback' => array(0, 0, 0),
                'frame' => 'taunt',
                'success' => array(1, -40, 10, 10, 'Oh no! Not again!'),
                'failure' => array(1, -90, 10, -10, '')
                ));
            //$energy_damage_amount = $energy_damage_amount + 1;
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

            // If this attack returns and strikes a third time (random chance)
            if ($this_ability->ability_results['this_result'] != 'failure'
                && $target_robot->robot_energy != 'disabled'
                && $this_battle->critical_chance($this_ability->ability_accuracy)){

                // Inflict damage on the opposing robot
                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'kickback' => array(30, 0, 0),
                    'success' => array(2, 10, 15, -10, 'Wow! A third hit!'),//'Wow! A third hit?!?'),
                    'failure' => array(2, 60, 15, -10, '')
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'kickback' => array(0, 0, 0),
                    'success' => array(2, 10, 15, -10, 'What? It recovered the target again?!'),//'Wow! A third hit?!?'),
                    'failure' => array(2, 60, 15, -10, '')
                    ));
                //$energy_damage_amount = $energy_damage_amount + 1;;
                $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

            }

        }

        // Return true on success
        return true;

    }
);
?>
