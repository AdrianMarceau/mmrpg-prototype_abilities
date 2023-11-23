<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 100, 0, 10, $this_robot->print_name().' throws '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'sticky' => true,
            'kickback' => array(5, 0, 0),
            'success' => array(1, 0, 0, 10, 'The '.$this_ability->print_name().' burned through target!'),
            'failure' => array(1, 0, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'sticky' => true,
            'frame' => 'taunt',
            'kickback' => array(5, 0, 0),
            'success' => array(0, 0, 0, 10, 'The '.$this_ability->print_name().' simmered the target!'),
            'failure' => array(0, 0, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
        $first_strike_success = $this_ability->ability_results['this_result'] === 'success' ? true : false;

        // Initiate a second strike as long as first didn't KO the target
        if ($target_robot->robot_status != 'disabled'){
            
            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'sticky' => true,
                'kickback' => array(25, 15, 0),
                'success' => array(2, 0, 0, 10, ($first_strike_success
					? 'And there\'s the second hit!'
                    : '...but it flared up for a second hit!'
                    )),
                'failure' => array(2, 0, 0, -10, ($first_strike_success
					? 'Oh! The second hit missed!'
                    : 'Oh! The second hit missed too!'
					))
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'sticky' => true,
                'kickback' => array(10, 5, 0),
                'frame' => 'taunt',
                'success' => array(2, 0, 0, 10, ($first_strike_success
					? 'And there it goes again!'
                    : '...but it flared up again and was enjoyed by the target!!'
                    )),
                'failure' => array(2, 0, 0, -10, ($first_strike_success
					? 'Oh! The second hit missed!'
					: 'Oh! The second hit missed too!'
					))
                ));
            $energy_damage_amount = $this_ability->ability_damage2;
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        }

        // Return true on success
        return true;

    }
);
?>
