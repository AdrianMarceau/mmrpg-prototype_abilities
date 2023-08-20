<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Check to see if the ability has been summoned yet
        $summoned_flag_token = $this_ability->ability_token.'_summoned';
        if (!empty($this_robot->flags[$summoned_flag_token])){ $has_been_summoned = true; }
        else { $has_been_summoned = false; }

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'attachment_token' => $this_attachment_token,
            'ability_id' => $this_ability->ability_id,
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 7,
            'ability_frame_animate' => array(7, 6, 5, 4, 3, 2, 1, 0),
            'ability_frame_offset' => array('x' => 60, 'y' => 0, 'z' => 28)
            );

        // Create the attachment object for this ability
        $this_attachment = rpg_game::get_ability($this_battle, $this_player, $this_robot, $this_attachment_info);

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

            // Target this robot's self
            $this_battle->queue_sound_effect(array('name' => 'spawn-sound', 'volume' => 0.5));
            $this_battle->queue_sound_effect('beeping-sound');
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(7,
                    $this_attachment_info['ability_frame_offset']['x'],
                    $this_attachment_info['ability_frame_offset']['y'],
                    $this_attachment_info['ability_frame_offset']['z'],
                    $this_robot->print_name().' generates a '.$this_ability->print_name().'! '.
                        '<br /> The '.$this_ability->print_name().' started rolling in place&hellip;'
                    )
                ));
            $this_robot->trigger_target($this_robot, $this_ability);

            // Attach this ability attachment to the robot using it
            $this_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
            $this_robot->update_session();

            // If we have a clone present, let's summon another ball
            if ($has_gemini_clone){

                // Create the cloned attachment with matching hologram styles
                $clone_attachment_token = $this_attachment_token.'_clone';
                $clone_attachment_info = $this_attachment_info;
                unset($clone_attachment_info['ability_id']);
                $clone_attachment_info['attachment_token'] = $clone_attachment_token;
                $clone_attachment_info['ability_frame_offset']['x'] -= 40;
                $clone_attachment_info['ability_frame_offset']['y'] -= 4;
                //$clone_attachment_info['ability_frame_styles'] = rpg_ability::get_css_filter_styles_for_gemini_clone();
                array_push($clone_attachment_info['ability_frame_animate'], array_shift($clone_attachment_info['ability_frame_animate']));
                $clone_attachment = rpg_game::get_ability($this_battle, $this_player, $this_robot, $clone_attachment_info);

                // Trigger the summon animation a second time and then attach the duplicate ball
                $this_battle->queue_sound_effect(array('name' => 'spawn-sound', 'volume' => 0.5));
                $this_battle->queue_sound_effect('beeping-sound');
                $this_robot->unset_flag('robot_is_using_ability');
                $this_robot->set_flag('gemini-clone_is_using_ability', true);
                $this_robot->set_attachment($clone_attachment_token, $clone_attachment_info);
                $this_ability->target_options_update(array(
                    'frame' => 'summon',
                    'success' => array(false, -9999, -9999, -9999,
                        $this_robot->print_name().' generates another '.$this_ability->print_name().'! '.
                            '<br /> The second '.$this_ability->print_name().' started rolling in place&hellip;'
                        )
                    ));
                $this_robot->trigger_target($this_robot, $this_ability);
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
            unset($this_robot->flags[$summoned_flag_token.'_include_gemini_clone']);

            // Remove the summoned flag from this robot
            $this_robot->unset_flag($summoned_flag_token);

            // Remove the attachment from the summoner
            $this_robot->unset_attachment($this_attachment_token);

            // Update this ability's target options and trigger
            $this_battle->queue_sound_effect('slide-sound');
            $this_battle->queue_sound_effect(array('name' => 'smack-sound', 'delay' => 100));
            $this_battle->queue_sound_effect(array('name' => 'bounce-sound', 'delay' => 100));
            $this_ability->target_options_update(array(
                'frame' => 'slide',
                'kickback' => array(60, 0, 0),
                'success' => array(8, 40, 0, 28, $this_robot->print_name().' kicks the '.$this_ability->print_name().' at the target!'),
                ));
            $this_robot->trigger_target($target_robot, $this_ability);

            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(24, 0, 0),
                'success' => array(9, -30, 0, 28, 'The '.$this_ability->print_name().' collided with the target!'),
                'failure' => array(9, -60, 0, -10, 'The '.$this_ability->print_name().' bounced past the target&hellip;')
                ));
            $energy_damage_amount = $this_ability->ability_damage;
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

            // If the ability was successful, loop through and remove recent core shield
            if ($this_ability->ability_results['this_result'] != 'failure'){
                if (!empty($target_robot->robot_attachments)){
                    $temp_attachment_tokens = array_keys($target_robot->robot_attachments);
                    $temp_attachment_tokens = array_reverse($temp_attachment_tokens);
                    $temp_shields_removed = 0;
                    foreach ($temp_attachment_tokens AS $temp_key => $temp_attachment_token){
                        $temp_attachment_info = $target_robot->robot_attachments[$temp_attachment_token];
                        if (strstr($temp_attachment_token, 'ability_core-shield_')){
                            // Collect the type for this core shield we're removing
                            list($ab, $at, $core_type) = explode('_', $temp_attachment_token);
                            // Update this field attachment with an opacity tweak before removing
                            if (!isset($temp_attachment_info['ability_frame_styles'])){ $temp_attachment_info['ability_frame_styles'] = ''; }
                            $temp_attachment_info['ability_frame_styles'] .= ' opacity: 0.5; ';
                            $target_robot->robot_attachments[$temp_attachment_token] = $temp_attachment_info;
                            $target_robot->update_session();
                            // Show a message about the attachment being removed
                            $target_robot->set_counter('item_disabled', 2);
                            $temp_ability_info = rpg_ability::get_index_info('core-shield');
                            $temp_ability_object = rpg_game::get_ability($this_battle, $target_player, $target_robot, $temp_ability_info);
                            $temp_remove_frame = $temp_shields_removed % 2 == 0 ? 'taunt' : 'defend';
                            $temp_remove_text = 'The attack disabled '.($temp_shields_removed >= 1 ? 'another' : 'their').' protective shield!<br /> ';
                            $temp_remove_text .= 'The '.rpg_type::print_span($core_type, $temp_ability_object->ability_name).' around '.$target_robot->print_name().' faded away!';
                            $temp_ability_object->target_options_update(array( 'frame' => $temp_remove_frame, 'success' => array(0, -9999, -9999, -9999, $temp_remove_text)));
                            $target_robot->trigger_target($target_robot, $temp_ability_object, array('prevent_default_text' => true, 'canvas_show_this_ability' => false));
                            $temp_shields_removed += 1;
                            // Remove this attachment from the robot
                            unset($target_robot->robot_attachments[$temp_attachment_token]);
                            $target_robot->counters['core-shield_cooldown_timer'] = 1;
                            $target_robot->update_session();
                        }
                    }
                }
            }

            // If a Gemini Clone is present and there's another ball, we need to kick that one too
            if ($has_gemini_clone){

                // Remove this ability attachment to the robot using it
                $clone_attachment_token = $this_attachment_token.'_clone';
                unset($this_robot->robot_attachments[$clone_attachment_token]);
                $this_robot->update_session();

                // We can only show the kick animation if the target is not disabled
                if ($target_robot->robot_status != 'disabled'){

                    // Reverse the using ability flags for the robot
                    $this_robot->unset_flag('robot_is_using_ability');
                    $this_robot->set_flag('gemini-clone_is_using_ability', true);

                    // Collect the existing clone attachment info from the game object
                    $clone_attachment = rpg_game::get_ability($this_battle, $this_player, $this_robot, array('attachment_token' => $clone_attachment_token));
                    $clone_css_styles = rpg_ability::get_css_filter_styles_for_gemini_clone();

                    // Update this ability's target options and trigger
                    //$this_ability->set_frame_styles($clone_css_styles);
                    $this_ability->target_options_update(array(
                        'frame' => 'slide',
                        'kickback' => array(60, 0, 0),
                        'success' => array(8, 120, 100, 28, $this_robot->print_name().' kicks the second  '.$this_ability->print_name().' at the target!'),
                        ));
                    $this_robot->trigger_target($target_robot, $this_ability);

                    // Inflict damage on the opposing robot
                    //$this_ability->set_frame_styles($clone_css_styles);
                    $this_ability->damage_options_update(array(
                        'kind' => 'energy',
                        'kickback' => array(24, 0, 0),
                        'success' => array(9, -30, 0, 28, 'The second '.$this_ability->print_name().' collided with the target!'),
                        'failure' => array(9, -60, 0, -10, 'The second '.$this_ability->print_name().' bounced past the target&hellip;')
                        ));
                    $energy_damage_amount = $this_ability->ability_damage;
                    $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
                    //$this_ability->set_frame_styles('');

                    // If the ability was successful, loop through and remove recent core shield
                    if ($this_ability->ability_results['this_result'] != 'failure'){
                        if (!empty($target_robot->robot_attachments)){
                            $temp_attachment_tokens = array_keys($target_robot->robot_attachments);
                            $temp_attachment_tokens = array_reverse($temp_attachment_tokens);
                            foreach ($temp_attachment_tokens AS $temp_key => $temp_attachment_token){
                                $temp_attachment_info = $target_robot->robot_attachments[$temp_attachment_token];
                                if (strstr($temp_attachment_token, 'ability_core-shield_')){
                                    $temp_attachment_info['attachment_duration'] = 0;
                                    $target_robot->robot_attachments[$temp_attachment_token] = $temp_attachment_info;
                                    $target_robot->update_session();
                                    break;
                                }
                            }
                        }
                    }

                    // Reverse the using ability flags for the robot
                    $this_robot->unset_flag('gemini-clone_is_using_ability');
                    $this_robot->set_flag('robot_is_using_ability', true);

                }

            }

        }

        // Return true on success
        return true;

        },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If the ability has already been summoned earlier this turn, decrease WE to zero
        $summoned_flag_token = $this_ability->ability_token.'_summoned';
        if (!empty($this_robot->flags[$summoned_flag_token])){ $this_ability->set_energy(0); }
        else { $this_ability->reset_energy(); }

        // Return true on success
        return true;

        }
);
?>
