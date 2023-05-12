<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 100, 0, 10, $this_robot->print_name().' fires the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(2, 0, 5, 10, 'The '.$this_ability->print_name().' astonished the target!'),
            'failure' => array(6, -50, 5, -10, 'The '.$this_ability->print_name().' was a dud&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(2, 0, 5, 10, 'The '.$this_ability->print_name().' invigorated the target!'),
            'failure' => array(6, 0, 5, 10, 'The '.$this_ability->print_name().' was a dud&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
        

        // If this attack returns and strikes a second time (random chance)
        if ($this_ability->ability_results['this_result'] != 'failure'
            && $target_robot->robot_status != 'disabled'){

            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(20, 0, 0),
                'success' => array(4, 0, 5, 10, 'Oh! The '.$this_ability->print_name().' hit again!'),
                'failure' => array(6, 0, 5, 10, '')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'kickback' => array(0, 0, 0),
                'frame' => 'taunt',
                'success' => array(4, 0, 5, 10, 'Oh no! Not again!'),
                'failure' => array(6, 0, 5, 10, '')
                ));
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
           

            // If this attack returns and strikes a third time (random chance)
            if ($this_ability->ability_results['this_result'] != 'failure'
                && $target_robot->robot_energy != 'disabled'){

                // Inflict damage on the opposing robot
                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'kickback' => array(30, 0, 0),
                    'success' => array(5, 0, 5, 10, 'A third hit! Amazing!'),
                    'failure' => array(6, 0, 5, 10, '')
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'kickback' => array(0, 0, 0),
                    'success' => array(5, 0, 5, 10, 'What? The '.$this_ability->print_name().' recovered the target again!'),
                    'failure' => array(6, 0, 5, 10, '')
                    ));
                $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
                
                // Randomly trigger a defense break if the ability was successful
                if ($target_robot->robot_status != 'disabled'
                    && $this_ability->ability_results['this_result'] != 'failure'){

                    // Call the global stat break function with customized options
                    rpg_ability::ability_function_stat_break($target_robot, 'attack', 3, $this_ability, array(
                        'initiator_robot' => $this_robot
                        ));

                }

            }

        }

        // Return true on success
        return true;

    }
);
?>
