<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);
        
        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 100, 2, 10, $this_robot->print_name().' discharges a mighty '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(1, 50, 0, 10, 'The '.$this_ability->print_name().' struck the helpless target!'),
            'failure' => array(0, -160, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(5, 0, 0),
            'success' => array(1, -140, 0, 10, 'Uh oh... The '.$this_ability->print_name().' energized the target!'),
            'failure' => array(0, -160, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

        // Check to see if we're there are MULTI BENCHED robots to target
        if ($target_player->counters['robots_positions']['bench'] >= 2){

            // Collect a list of benched robots from the target
            $temp_target_robots = rpg_game::find_robots(array(
                'player_id' => $target_player->player_id,
                'robot_position' => 'bench',
                'robot_status' => 'active'
                ));

            // Sort the robots by key (very important!)
            usort($temp_target_robots, function($a, $b){
                if ($a->robot_key < $b->robot_key){ return -1; }
                elseif ($a->robot_key > $b->robot_key){ return 1; }
                else { return 0; }
                });

            // Select the first target from the top of the list
            $temp_first_target_robot = $temp_target_robots[0];

            // Deal damage to the first target robot immediately
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(10, 0, 0),
                'success' => array(3, 15, -15, 10, 'Another hit! The '.$this_ability->print_name().' zapped the target!'),
                'failure' => array(3, -60, -15, 10, 'The '.$this_ability->print_name().' missed the target&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'kickback' => array(10, 0, 0),
                'success' => array(3, 15, -15, 10, 'The '.$this_ability->print_name().' energized the target!'),
                'failure' => array(3, -65, -15, 10, 'The '.$this_ability->print_name().' missed the target&hellip;')
                ));
            $energy_damage_amount = $this_ability->ability_damage2;
            $temp_first_target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
            if ($this_ability->ability_results['this_result'] != 'failure'){ $num_hits_counter++; }

            // Select the last target from the bottom of the list
            $temp_second_target_robot = $temp_target_robots[count($temp_target_robots) - 1];

            // Deal damage to the second target robot if not disabled
            if ($temp_second_target_robot->robot_energy > 0){
                $this_ability->ability_results_reset();
                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'kickback' => array(-5, 0, 0),
                    'success' => array(2, -15, -15, 10, 'Once again! The '.$this_ability->print_name().' shocked another target!'),
                    'failure' => array(2, -75, -15, 10, 'The '.$this_ability->print_name().' missed the target&hellip;')
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'kickback' => array(-5, 0, 0),
                    'success' => array(2, -15, -15, 10, 'The '.$this_ability->print_name().' energized the target!'),
                    'failure' => array(2, -75, -15, 10, 'The '.$this_ability->print_name().' missed the target&hellip;')
                    ));
                $energy_damage_amount = $this_ability->ability_damage2;
                $temp_second_target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
                if ($this_ability->ability_results['this_result'] != 'failure'){ $num_hits_counter++; }
            }

        }

        // Return true on success
        return true;

    }
);
?>
