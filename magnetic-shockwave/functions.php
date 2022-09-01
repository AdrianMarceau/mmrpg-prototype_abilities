<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 50, 0, 10, $this_robot->print_name().' fires the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(1, 10, -5, -10, 'Fantastic! The '.$this_ability->print_name().' struck the target!'),
            'failure' => array(1, -50, -5, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(1, 0, -5, -10, 'Danger! The '.$this_ability->print_name().' just made the target stronger!'),
            'failure' => array(61, 0, -5, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
        

        // This attack will return and strike a second time
        if ($this_ability->ability_results['this_result'] != 'failure'
            && $target_robot->robot_status != 'disabled'){

            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(30, 0, 0),
                'success' => array(2, 30, -5, -10, 'Uncanny! The '.$this_ability->print_name().' struck once again!'),
                'failure' => array(2, 0, -5, -10, 'It missed!')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'kickback' => array(0, 0, 0),
                'frame' => 'taunt',
                'success' => array(2, 0, -5, -10, 'Danger! The '.$this_ability->print_name().' just made the target stronger!'),
                'failure' => array(2, 0, -5, -10, 'It missed!')
                ));
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
           

            // This attack will return and strike a third time
            if ($this_ability->ability_results['this_result'] != 'failure'
                && $target_robot->robot_energy != 'disabled'){

                // Inflict damage on the opposing robot
                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'kickback' => array(50, 0, 0),
                    'success' => array(1, 50, -5, -10, 'Here it comes! A hyper combo finish!'),
                    'failure' => array(1, 0, -5, -10, 'It missed!')
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'kickback' => array(0, 0, 0),
                    'success' => array(1, 0, -5, -10, 'Oh! Oh! Oh! Welcome to mahvel, baby!'),
                    'failure' => array(1, 0, -5, -10, 'It missed!')
                    ));
                $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
                

            }

        }

        // Return true on success
        return true;

    }
);
?>
