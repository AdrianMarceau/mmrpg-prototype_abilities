<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token.'_'.$target_robot->robot_id;
        $this_attachment_info = array(
            'class' => 'ability',
            'ability_token' => $this_ability->ability_token,
            'ability_image' => 'ability',
            'attachment_duration' => 2,
            'attachment_switch_disabled' => true,
            'ability_frame' => 9,
            'ability_frame_animate' => array(9),
            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => 0),
            'attachment_destroy' => false,
            );
        
        // Check to see if this ability will be successful
        $ability_success = true;
        $ability_failure_reason = '';
        if ($this_robot->robot_speed <= 0){ 
            $ability_success = false; 
            $ability_failure_reason = 'speed-break'; 
        } elseif ($target_robot->has_immunity($this_ability->ability_type)
            || $target_robot->has_immunity($this_ability->ability_type2)){ 
            $ability_success = false; 
            $ability_failure_reason = 'has-immunity'; 
        }

        // If the ability was successful, we should show the other robot being lifted
        $fx_attachment_token = false;
        if ($ability_success){
            $fx_attachment_token = 'ability_effects_'.$this_ability->ability_token.'_lift';
            $fx_attachment_info = array(
                'class' => 'ability',
                'attachment_token' => $fx_attachment_token,
                'sticky' => true,
                'ability_token' => $this_ability->ability_token,
                'ability_image' => $this_ability->ability_image,
                'ability_frame' => 0,
                'ability_frame_animate' => array(0),
                'ability_frame_offset' => array('x' => 20, 'y' => -40, 'z' => -1),
                //'ability_frame_classes' => '',
                'ability_frame_styles' => 'transform: rotate(-90deg); '
                );
            $target_robot->set_frame('damage');
            $target_robot->set_frame_offset('y', 40);
            $target_robot->set_frame_offset('x', 80);
            $target_robot->set_frame_styles('transform: rotate(-45deg);');
            $target_robot->set_attachment($fx_attachment_token, $fx_attachment_info);
        }

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'kickback' => array(10, 0, 0),
            'success' => array(0, -30, 0, -10, $this_robot->print_name().' prepares for the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // If the ability was successful, we should show the other robot being lifted
        if (!empty($fx_attachment_token)){
            $target_robot->set_frame('');
            $target_robot->set_frame_offset('y', 0);
            $target_robot->set_frame_offset('x', 0);
            $target_robot->set_frame_styles('');
            $target_robot->unset_attachment($fx_attachment_token);
        }

        // Ensure this robot is not prevented from attacking by speed break
        if ($ability_success){

            // Attach this ability attachment to the robot using it
            $target_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
            $target_robot->update_session();

            // Check to see which keys are already being used
            $available_keys = array(0, 1, 2, 3, 4, 5, 6, 7);
            $target_robots_active = $target_player->get_robots_active();
            foreach ($target_robots_active AS $key => $robot){
                if ($robot->robot_id == $target_robot->robot_id){ $temp_target_robot = $target_robot; }
                else { $temp_target_robot = $robot; }
                $key_pos = array_search($temp_target_robot->robot_key, $available_keys);
                if ($key_pos != -1){
                    unset($available_keys[$key_pos]);
                    $available_keys = array_values($available_keys);
                }
            }

            // IF THERE ARE OTHER ROBOTS we can throw to the bench
            if ($target_player->counters['robots_active'] > 1){

                // Inflict damage on the opposing robot
                $this_robot->robot_frame = 'throw';
                $this_robot->update_session();
                $target_robot->robot_position = 'bench';
                $target_robot->robot_key = !empty($available_keys) ? array_shift($available_keys) : 0;
                $target_robot->update_session();
                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'damage',
                    'kickback' => array(0, 0, 0),
                    'success' => array(0, -65, 0, 10, $target_robot->print_name().' is thrown to the bench!'),
                    'failure' => array(0, -85, 0, -10, $target_robot->print_name().' is thrown to the bench!')
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'kickback' => array(0, 0, 0),
                    'success' => array(0, -65, 0, 10, $target_robot->print_name().' is thrown to the bench!'),
                    'failure' => array(0, -85, 0, -10, $target_robot->print_name().' is thrown to the bench!')
                    ));
                $energy_damage_amount = $this_ability->ability_damage;
                $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
                $this_robot->robot_frame = 'throw';
                $this_robot->update_session();

                // Clear the action queue to allow the player to pick a new ability
                $this_battle->actions_empty();

                // Automatically append an action if on autopilot
                if ($target_player->player_autopilot == true){

                    // If the target robot was not destroyed by the hit, append a switch
                    //if ($target_robot->robot_energy > 0 && $target_robot->robot_status != 'disabled'){
                    if (true){

                        // Default the switch target to the existing robot
                        $switch_target_token = $target_robot->robot_id.'_'.$target_robot->robot_token;
                        $switch_target_token_backup = $switch_target_token;

                        // Randomly select a target for the opponent that isn't the same as the current
                        if ($target_player->counters['robots_active'] > 1){
                            $switch_robots_active = $target_player->values['robots_active'];
                            shuffle($switch_robots_active);
                            foreach ($switch_robots_active AS $key => $robot){
                                $new_switch_target_token = $robot['robot_id'].'_'.$robot['robot_token'];
                                if ($robot['robot_energy'] > 0 && $new_switch_target_token != $switch_target_token_backup){
                                    $switch_target_token = $new_switch_target_token;
                                    break;
                                }
                            }
                        }

                        // Trigger a switch on the opponent immediately
                        $this_battle->actions_prepend(
                            $target_player,
                            $target_robot,
                            $this_player,
                            $this_robot,
                            'switch',
                            $switch_target_token
                            );

                    }

                }
                // Otherwise if the player only has one robot anyway
                elseif ($target_player->counters['robots_active'] == 1){

                    // Remove this ability attachment from the robot using it
                    unset($target_robot->robot_attachments[$this_attachment_token]);
                    $target_robot->update_session();

                    // Pull the robot back into play automatically
                    $target_robot->robot_position = 'active';
                    $target_robot->robot_frame = 'defend';
                    $target_robot->update_session();

                }
                // Otherwise, clear the action queue and continue
                else {

                    // Do nothing?

                }

                // Trigger the disabled function if necessary
                if ($target_robot->robot_energy == 0 || $target_robot->robot_status == 'disabled'){
                    $target_robot->trigger_disabled($this_robot);
                }


            }
            // Otherwise we simply throw (but not to bench)
            else {

                // Inflict damage on the opposing robot
                $this_robot->set_frame('throw');
                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'damage',
                    'kickback' => array(40, 10, 0),
                    'success' => array(0, -65, 0, 10, $target_robot->print_name().' is thrown across the field!'),
                    'failure' => array(0, -85, 0, -10, $target_robot->print_name().' is thrown across the field!')
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'kickback' => array(40, 10, 0),
                    'success' => array(0, -65, 0, 10, $target_robot->print_name().' is thrown across the field!'),
                    'failure' => array(0, -85, 0, -10, $target_robot->print_name().' is thrown across the field!')
                    ));
                $energy_damage_amount = $this_ability->ability_damage;
                $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, true);
                $this_robot->reset_frame();

            }

        }
        // Otherwise, if the robot is in speed break
        else {

            // Target the opposing robot
            $failure_subtext = '';
            if ($ability_failure_reason === 'speed-break'){
                $this_ability->target_options_update(array(
                    'frame' => 'throw',
                    'success' => array(0, 0, 0, 10, '...but speed-break prevents '.$this_robot->print_name().' from getting a lock on the target!')
                    ));
                $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));
            } elseif ($ability_failure_reason === 'has-immunity'){
                $this_robot->set_frame('throw');
                $this_ability->target_options_update(array(
                    'frame' => 'throw',
                    'success' => array(0, 0, 0, 10, '...but '.$target_robot->print_name().' is immune to the attack!')
                    ));
                $target_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));
                $this_robot->reset_frame();
            } else {            
                $this_ability->target_options_update(array(
                    'frame' => 'defend',
                    'success' => array(0, 0, 0, 10, '...but the attack failed!')
                    ));
                $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));
            }

        }

        // Return true on success
        return true;

    }
);
?>
