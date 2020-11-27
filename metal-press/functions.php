<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Check to see if the ability has been summoned yet
        $summoned_flag_token = $this_ability->ability_token.'_summoned';
        if (!empty($this_robot->flags[$summoned_flag_token])){ $has_been_summoned = true; }
        else { $has_been_summoned = false; }

        // If this ability has not been summoned yet, do the action and then queue a conclusion move
        if (!$has_been_summoned){

            // Check to see if a Gemini Clone is attached and if it's active, then check to see if we can use it
            $has_gemini_clone = isset($this_robot->robot_attachments['ability_gemini-clone']) ? true : false;
            $required_weapon_energy = $this_robot->calculate_weapon_energy($this_ability);
            if ($has_gemini_clone && !$has_been_summoned){
                if ($this_robot->robot_weapons >= $required_weapon_energy){ $this_robot->set_weapons($this_robot->robot_weapons - $required_weapon_energy); }
                else { $has_gemini_clone = false; }
            }

            // If the robot was found to gave a Gemini Clone, set the appropriate flag value now
            if ($has_gemini_clone){ $this_robot->set_flag($summoned_flag_token.'_include_gemini_clone', true); }

            // Set the summoned flag on this robot and save
            $this_robot->flags[$summoned_flag_token] = true;
            $this_robot->update_session();

             // Target the opposing robot and show summoning text
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(0, 0, 150, 30, $this_robot->print_name().' summons the '.$this_ability->print_name().'!')
                ));
            $this_robot->trigger_target($target_robot, $this_ability);

            // If we have a clone present, let's summon another press
            if ($has_gemini_clone){
                $this_robot->unset_flag('robot_is_using_ability');
                $this_robot->set_flag('gemini-clone_is_using_ability', true);
                $this_ability->target_options_update(array(
                    'frame' => 'summon',
                    'success' => array(0, 0, 150, 30, $this_robot->print_name().' summons another '.$this_ability->print_name().'!')
                    ));
                $this_robot->trigger_target($target_robot, $this_ability);
                $this_robot->unset_flag('gemini-clone_is_using_ability');
                $this_robot->set_flag('robot_is_using_ability', true);
            }

            // Queue another use of this ability at the end of turn
            $this_battle->actions_append(
                $this_player,
                $this_robot,
                $target_player,
                $target_robot,
                'ability',
                $this_ability->ability_id.'_'.$this_ability->ability_token,
                true
                );

        }
        // The ability has already been summoned, so we can finish executing it now and deal damage
        else {

            // Check to see if a Gemini Clone is attached and if it's active, then check to see if we can use it
            $has_gemini_clone = isset($this_robot->robot_attachments['ability_gemini-clone']) ? true : false;
            if (empty($this_robot->flags[$summoned_flag_token.'_include_gemini_clone'])){ $has_gemini_clone = false; }
            $this_robot->unset_flag($summoned_flag_token.'_include_gemini_clone');

            // Remove the summoned flag from this robot
            $this_robot->unset_flag($summoned_flag_token);

            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(5, 25, 0),
                'success' => array(1, 0, -50, 30, 'The '.$this_ability->print_name().' crushed the target with spikes!'),
                'failure' => array(1, 0, -50, -10, 'The '.$this_ability->print_name().' somehow missed the target&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'kickback' => array(0, 15, 0),
                'success' => array(1, 0, -30, 30, 'The '.$this_ability->print_name().' crushed the target but...'),
                'failure' => array(1, 0, -30, -10, 'The '.$this_ability->print_name().' somehow missed the target&hellip;')
                ));
            $energy_damage_amount = $this_ability->ability_damage;
            $this_robot->robot_frame = 'throw';
            $this_robot->update_session();
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
            $this_robot->robot_frame = 'base';
            $this_robot->update_session();

            // Only lower the target's stats of the ability was successful
            if ($target_robot->robot_status != 'disabled'
                && $this_ability->ability_results['this_result'] != 'failure'){

                // Call the global stat break functions with customized options
                rpg_ability::ability_function_stat_break($target_robot, 'attack', 1);
                rpg_ability::ability_function_stat_break($target_robot, 'defense', 1);
                rpg_ability::ability_function_stat_break($target_robot, 'speed', 1);

            }

            // If the user has a Gemini Clone, we need to drop the press again
            if ($target_robot->robot_status != 'disabled'
                && $has_gemini_clone){

                // Reverse the using ability flags for the robot
                $this_robot->unset_flag('robot_is_using_ability');
                $this_robot->set_flag('gemini-clone_is_using_ability', true);

                // Check if we should use the "again" text for a second hit
                $success_again_text = $this_ability->ability_results['this_result'] != 'failure' ? ' again' : '';
                $failure_again_text = $this_ability->ability_results['this_result'] == 'failure' ? ' again' : '';

                // Inflict damage on the opposing robot
                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'kickback' => array(10, 15, 0),
                    'success' => array(1, 10, -40, 30, 'The second '.$this_ability->print_name().' crushed the target with spikes'.$success_again_text.'!'),
                    'failure' => array(1, 0, -40, -10, 'The second '.$this_ability->print_name().' somehow missed the target'.$failure_again_text.'...')
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'kickback' => array(5, 5, 0),
                    'success' => array(1, 10, -20, 30, 'The second '.$this_ability->print_name().' crushed the target'.$success_again_text.' but...'),
                    'failure' => array(1, 0, -20, -10, 'The second '.$this_ability->print_name().' somehow missed the target'.$failure_again_text.'...')
                    ));
                $energy_damage_amount = $this_ability->ability_damage;
                $this_robot->robot_frame = 'throw';
                $this_robot->update_session();
                $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
                $this_robot->robot_frame = 'base';
                $this_robot->update_session();

                // Reverse the using ability flags for the robot
                $this_robot->unset_flag('gemini-clone_is_using_ability');
                $this_robot->set_flag('robot_is_using_ability', true);

                // Only lower the target's stats of the ability was successful
                if ($target_robot->robot_status != 'disabled'
                    && $this_ability->ability_results['this_result'] != 'failure'){

                    // Call the global stat break functions with customized options
                    rpg_ability::ability_function_stat_break($target_robot, 'attack', 1);
                    rpg_ability::ability_function_stat_break($target_robot, 'defense', 1);
                    rpg_ability::ability_function_stat_break($target_robot, 'speed', 1);

                }

            }

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

        // If the ability has already been summoned earlier this turn, decrease WE to zero
        $summoned_flag_token = $this_ability->ability_token.'_summoned';
        if (!empty($this_robot->flags[$summoned_flag_token])){ $this_ability->set_energy(0); }
        else { $this_ability->reset_energy(); }

        // Return true on success
        return true;

        }
);
?>
