<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);
        
        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'throw',
            'success' => array(0, 100, 10, 10, $this_robot->print_name().' throws the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(1, -140, 0, 10, 'The '.$this_ability->print_name().'\s blades cut through the target!'),
            'failure' => array(2, -160, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(5, 0, 0),
            'success' => array(1, -140, 0, 10, 'The '.$this_ability->print_name().'\'s wind invigorated the target!'),
            'failure' => array(2, -160, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
                        // Only lower the target's stat of the ability was successful
        if ($this_ability->ability_results['this_result'] != 'failure'){
            // Call the global stat break function with customized options
            rpg_ability::ability_function_stat_break($target_robot, 'attack', 1, $this_ability, array(
                'initiator_robot' => $this_robot
                ));
        }

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
                'success' => array(3, 15, -15, 10, 'The '.$this_ability->print_name().' zapped the target!'),
                'failure' => array(1, -60, -15, 10, 'The '.$this_ability->print_name().' missed the target&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'kickback' => array(10, 0, 0),
                'success' => array(3, 15, -15, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
                'failure' => array(1, -65, -15, 10, 'The '.$this_ability->print_name().' missed the target&hellip;')
                ));
            $energy_damage_amount = $this_ability->ability_damage;
            $temp_first_target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
            
            // Only lower the target's stat of the ability was successful
            if ($this_ability->ability_results['this_result'] != 'failure'){
                // Call the global stat break function with customized options
                rpg_ability::ability_function_stat_break($target_robot, 'defense', 1, $this_ability, array(
                    'initiator_robot' => $this_robot
                    ));
            }
            if ($this_ability->ability_results['this_result'] != 'failure'){ 
                $num_hits_counter++; 
            }

            // Select the last target from the bottom of the list
            $temp_second_target_robot = $temp_target_robots[count($temp_target_robots) - 1];

            // Deal damage to the second target robot if not disabled
            if ($temp_second_target_robot->robot_energy > 0){
                $this_ability->ability_results_reset();
                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'kickback' => array(-5, 0, 0),
                    'success' => array(4, -15, -15, -10, 'The '.$this_ability->print_name().' zapped the target'.($num_hits_counter > 0 ? ' again' : '').'!'),
                    'failure' => array(1, -75, -15, -10, 'The '.$this_ability->print_name().' missed the target&hellip;')
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'kickback' => array(-5, 0, 0),
                    'success' => array(4, -15, -15, -10, 'The '.$this_ability->print_name().' was absorbed by the target'.($num_hits_counter > 0 ? ' again' : '').'!'),
                    'failure' => array(1, -75, -15, -10, 'The '.$this_ability->print_name().' missed the target&hellip;')
                    ));
                $energy_damage_amount = $this_ability->ability_damage;
                $temp_second_target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
                                // Only lower the target's stat of the ability was successful
        if ($this_ability->ability_results['this_result'] != 'failure'){
            // Call the global stat break function with customized options
            rpg_ability::ability_function_stat_break($target_robot, 'speed', 1, $this_ability, array(
                'initiator_robot' => $this_robot
                ));
        }
                if ($this_ability->ability_results['this_result'] != 'failure'){ $num_hits_counter++; }
            }

        }
        // Otherwise ability will automatically target ACTIVE robot or LONE BENCHED robot
        else {

            // Define the temp target robot for the ability
            if ($target_player->counters['robots_positions']['bench'] == 1){
                $temp_target_robot = rpg_game::find_robot(array(
                    'player_id' => $target_player->player_id,
                    'robot_position' => 'bench',
                    'robot_status' => 'active'
                    ));
            } else {
                $temp_target_robot = $target_robot;
            }

            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(10, 0, 0),
                'success' => array(3, 15, -10, 10, 'The '.$this_ability->print_name().' zapped the target!'),
                'failure' => array(1, -60, -10, 10, 'The '.$this_ability->print_name().' missed the target&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'kickback' => array(10, 0, 0),
                'success' => array(3, 15, -15, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
                'failure' => array(1, -65, -15, 10, 'The '.$this_ability->print_name().' missed the target&hellip;')
                ));
            $energy_damage_amount = $this_ability->ability_damage;
            $temp_target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
            if ($this_ability->ability_results['this_result'] != 'failure'){ $num_hits_counter++; }

            // Inflict damage again if target not disabled
            if ($temp_target_robot->robot_status != 'disabled'
                && $temp_target_robot->robot_energy > 0){
                $this_ability->ability_results_reset();
                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'kickback' => array(-5, 0, 0),
                    'success' => array(4, -15, -15, -10, 'The '.$this_ability->print_name().' zapped the target'.($num_hits_counter > 0 ? ' again' : '').'!'),
                    'failure' => array(1, -75, -15, -10, 'The '.$this_ability->print_name().' missed the target&hellip;')
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'kickback' => array(-5, 0, 0),
                    'success' => array(4, -15, -15, -10, 'The '.$this_ability->print_name().' was absorbed by the target'.($num_hits_counter > 0 ? ' again' : '').'!'),
                    'failure' => array(1, -75, -15, -10, 'The '.$this_ability->print_name().' missed the target&hellip;')
                    ));
                $energy_damage_amount = $this_ability->ability_damage;
                $temp_target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
                if ($this_ability->ability_results['this_result'] != 'failure'){ $num_hits_counter++; }
            }

        }

        // Return true on success
        return true;

    }
);
?>
