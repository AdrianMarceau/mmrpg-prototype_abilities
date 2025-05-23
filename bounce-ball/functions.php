<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Count the number of active robots on the target's side of the  field
        $target_robot_ids = array();
        $target_robots_active = $target_player->values['robots_active'];
        $target_robots_active_count = $target_player->counters['robots_active'];
        $get_random_target_robot = function($robot_id = 0) use($this_battle, $target_player, &$target_robot_ids){
            $robot_info = array();
            $active_robot_keys = array_keys($target_player->values['robots_active']);
            shuffle($active_robot_keys);
            foreach ($active_robot_keys AS $key_key => $robot_key){
                $robot_info = $target_player->values['robots_active'][$robot_key];
                if (!empty($robot_id) && $robot_info['robot_id'] !== $robot_id){ continue; }
                $robot_id = $robot_info['robot_id'];
                $random_target_robot = rpg_game::get_robot($this_battle, $target_player, $robot_info);
                if (!in_array($robot_info['robot_id'], $target_robot_ids)){ $target_robot_ids[] = $robot_id; }
                return $random_target_robot;
                }
            };

        // Collect three random targets, with the first always being active (repeats allowed)
        $target_robot_1 = $get_random_target_robot($target_robot->robot_id);
        $target_robot_2 = $get_random_target_robot();
        $target_robot_3 = $get_random_target_robot();
        

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'throw',
            'success' => array(0, 200, 20, 10, $this_robot->print_name().' throws out the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability, array('prevent_stats_text' => true));

        // Put the user in a summon frame for the duration of the attack
        $this_robot->set_frame('summon');

        // Inflict damage on the first opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(30, 10, 0),
            'success' => array(1, -50, 10, 10, 'The '.$this_ability->print_name().' struck the target!'),
            'failure' => array(1, -95, 0, -10, 'The '.$this_ability->print_name().' just went and bounced away&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(5, 0, 0),
            'success' => array(1, -35, 0, 10, 'The '.$this_ability->print_name().'\'s recoil was completely absorbed!'),
            'failure' => array(1, -95, 0, -10, 'The '.$this_ability->print_name().' just went and bounced away&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false);
        $target_robot_1->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false, $trigger_options);

        // Inflict damage on the second opposing robot if they're not disabled
        if ($target_robot_2->robot_status !== 'disabled'){

            // Define the success/failure text variables
            $success_text = '';
            $failure_text = '';

            // Adjust damage/recovery text based on results
            if ($this_ability->ability_results['total_strikes'] == 1){ $success_text = 'Another ball hit!'; }
            if ($this_ability->ability_results['total_misses'] == 1){ $failure_text = 'Another ball missed!'; }

            // Attempt to trigger damage to the target robot again
            $this_ability->ability_results_reset();
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(-60, 0, 0),
                'success' => array(2, -35, 10, 10, $success_text),
                'failure' => array(2, -95, 0, -10, $failure_text)
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'kickback' => array(-5, 0, 0),
                'success' => array(1, 35, -10, 10, $success_text),
                'failure' => array(1, 95, 0, -10, $failure_text)
                ));
            $target_robot_2->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false, $trigger_options);

        }

        // Inflict damage on the third opposing robot if they're not disabled
        if ($target_robot_3->robot_status !== 'disabled'){

            // Adjust damage/recovery text based on results again
            if ($this_ability->ability_results['total_strikes'] == 1){ $success_text = 'Another ball hit!'; }
            elseif ($this_ability->ability_results['total_strikes'] == 2){ $success_text = 'The third ball hit!'; }
            if ($this_ability->ability_results['total_misses'] == 1){ $failure_text = 'Another ball missed!'; }
            elseif ($this_ability->ability_results['total_misses'] == 2){ $failure_text = 'The third ball missed!'; }

            // Attempt to trigger damage to the target robot a third time
            $this_ability->ability_results_reset();
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(60, 0, 0),
                'success' => array(3, -20, 30, 10, $success_text),
                'failure' => array(3, 95, 0, -10, $failure_text)
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'kickback' => array(5, 0, 0),
                'success' => array(1, 0, -10, 10, $success_text),
                'failure' => array(1, -95, 0, -10, $failure_text)
                ));
            $target_robot_3->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

        }

        // Return the user to their base frame now that we're done
        $this_robot->set_frame('base');

        // Loop through all robots on the target side and disable any that need it
        $target_robots_active = $target_player->get_robots();
        foreach ($target_robots_active AS $key => $robot){
            if ($robot->robot_id == $target_robot->robot_id){ $temp_target_robot = $target_robot; }
            else { $temp_target_robot = $robot; }
            if (($temp_target_robot->robot_energy < 1 || $temp_target_robot->robot_status == 'disabled')
                && empty($temp_target_robot->flags['apply_disabled_state'])){
                $temp_target_robot->trigger_disabled($this_robot);
            }
        }

        // Return true on success
        return true;

    },

);
?>
