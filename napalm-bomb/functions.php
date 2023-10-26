<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's first attachment token
        $explosion_attachment_token = 'ability_'.$this_ability->ability_token.'_explosion';
        $explosion_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 4,
            'ability_frame_animate' => array(4,5),
            'ability_frame_offset' => array('x' => 30, 'y' => 0, 'z' => 10),
            'attachment_token' => $explosion_attachment_token
            );

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

        // Function to handle the repeated logic of dropping a bomb on a target
        $this_ability_damage = $this_ability->ability_damage;
        $handle_strike = function($target_robot, $strike_num) use (
            &$this_robot, &$this_ability, &$this_ability_damage,
            $explosion_attachment_token, $explosion_attachment_info
            ){

            // Skip this robot if it's already disabled
            if ($target_robot->robot_status === 'disabled'){ return false; }

            // Pregenerate the success and failure text for this iteration
            if ($strike_num === 1){
                $damage_success_text = 'The '.$this_ability->print_name().'\'s explosion ravaged the target!';
                $damage_failure_text = 'The '.$this_ability->print_name().'\'s explosion just missed the target...';
                $recovery_success_text = 'The '.$this_ability->print_name().'\'s explosion was absorbed by the target!';
                $recovery_failure_text = 'The '.$this_ability->print_name().'\'s explosion had no effect on the target...';
            } else {
                $damage_success_text = ($strike_num > 1) ? 'Another one of the bombs hit!' : 'One of the bombs hit!';
                $damage_failure_text = ($strike_num > 1) ? 'Another one of the bombs missed!' : 'One of the bombs missed!';
                $recovery_success_text = ($strike_num > 1) ? 'Another one of the bombs was absorbed!' : 'One of the bombs was absorbed!';
                $recovery_failure_text = ($strike_num > 1) ? 'Another one of the bombs missed!' : 'One of the bombs missed!';
            }

            // Update the damage and recovery options for this iteration
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array((($strike_num % 2) * 2 - 1) * 10, 0, 0),
                'success' => array(2 + ($strike_num % 2), 35 * (($strike_num % 2) * 2 - 1), 0, 10, $damage_success_text),
                'failure' => array(2 + ($strike_num % 2), 95 * (($strike_num % 2) * 2 - 1), 0, -10, $damage_failure_text)
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'kickback' => array((($strike_num % 2) * 2 - 1) * 5, 0, 0),
                'success' => array(2 + ($strike_num % 2), 35 * (($strike_num % 2) * 2 - 1), 0, 10, $recovery_success_text),
                'failure' => array(2 + ($strike_num % 2), 95 * (($strike_num % 2) * 2 - 1), 0, -10, $recovery_failure_text)
                ));

            // Define the amount and attempt to trigger damage to the target robot
            $energy_damage_amount = $this_ability->ability_damage;
            $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false);
            $target_robot->set_attachment($explosion_attachment_token, $explosion_attachment_info);
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false, $trigger_options);
            if ($this_ability->ability_results['this_result'] != 'failure'){ $this_ability_damage += 1; }
            $target_robot->unset_attachment($explosion_attachment_token);
            $this_ability->reset_all();

        };

        // Calculate how much WE is required for repeated attacks
        $weapon_energy_required = $this_robot->calculate_weapon_energy($this_ability, $this_ability->ability_energy, $temp_ability_energy_mods);

        // Target the opposing robot
        if ($this_robot->robot_weapons >= $weapon_energy_required * 3){
            $this_ability->target_options_update(array(
                'frame' => 'throw',
                'kickback' => array(-10, 0, 0),
                'success' => array(0, 10, 80, 10, $this_robot->print_name().' releases an array of '.$this_ability->print_name(true).'!')
                ));
        } elseif ($this_robot->robot_weapons >= $weapon_energy_required * 2){
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'kickback' => array(-10, 0, 0),
                'success' => array(0, 10, 180, 10, $this_robot->print_name().' releases a duo of '.$this_ability->print_name(true).'!')
                ));
        } else {
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'kickback' => array(-10, 0, 0),
                'success' => array(1, 10, 140, 10, $this_robot->print_name().' releases a '.$this_ability->print_name().'!')
                ));
        }
        $this_robot->trigger_target($target_robot, $this_ability, array('prevent_stats_text' => true));

        // Continue triggering the attack until target disabled OR user runs out of weapon energy
        $strike_num = 0;
        $num_available_targets = $target_player->counters['robots_active'];
        while ($strike_num === 0 || $this_robot->robot_weapons >= $weapon_energy_required){

            // Decrement required weapon energy from this robot or break if not enough
            if ($strike_num > 0){
                $new_weapons_amount = $this_robot->robot_weapons - $weapon_energy_required;
                if ($new_weapons_amount < 0){ break; }
                $this_robot->set_weapons($new_weapons_amount);
            }

            // Increment the strike number so we can keep track
            $strike_num += 1;

            // Put the user in a throw frame for the duration of the attack
            $this_robot->set_frame('throw');

            // Inflict damage on the opposing robot, whichever one it is
            if ($strike_num === 1){
                $handle_strike($target_robot, $strike_num);
            } else {
                $num_available_targets = $target_player->counters['robots_active'];
                $temp_target_robot = $get_random_target_robot();
                if (empty($temp_target_robot) && !empty($num_available_targets)){ continue; }
                elseif (empty($temp_target_robot) && empty($num_available_targets)){ break; }
                $handle_strike($temp_target_robot, $strike_num);
            }

        }


        // Return the user to their base frame now that we're done
        $this_robot->set_frame('base');

        // Now that all the damage has been dealt, allow the player to check for disabled
        $target_player->check_robots_disabled($this_player, $this_robot);

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Update the ability image if the user is in their alt image
        $alt_image_triggers = array('napalm-man' => 2);
        if (isset($alt_image_triggers[$this_robot->robot_image])){ $ability_image = $this_ability->ability_token.'-'.$alt_image_triggers[$this_robot->robot_image]; }
        else { $ability_image = $this_ability->ability_base_image; }
        $this_ability->set_image($ability_image);

        // Return true on success
        return true;

        }
);
?>
