<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // -- ABILITY SETUP -- //

        // Define the list of negative and positive attachments/hazards that this ability removes
        $hazard_object_tokens = array(
            'oil-shooter_crude-oil',
            'bubble-spray_foamy-bubbles',
            'ice-breath_frozen-foothold',
            'galaxy-bomb_black-hole',
            'disco-fever_disco-ball',
            'thunder-wool_woolly-cloud',
            'acid-glob_acid-glob',
            'gravity-hold_gravity-well',
            'remote-mine_remote-mine',
            'crash-bomber_crash-bomb',
            'chain-blast_chain-bomb',
            );
        $beneficial_object_tokens = array(
            'super-arm_super-block',
            'skull-barrier_skull-barrier',
            'plant-barrier_plant-barrier',
            'lunar-memory_lunar-memory',
            'core-shield',
            );
        $object_fx_tokens = array(
            'chain-blast_chain-bomb' => 'chain-blast_fx'
            );

        // Combine the negative and positive tokens into one array
        $combined_object_tokens = array_merge($hazard_object_tokens, $beneficial_object_tokens);
        //error_log('combined object tokens: '.print_r($combined_object_tokens, true));

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_fx_token = $this_attachment_token.'_fx';
        $this_attachment_info = array(
            'class' => 'ability',
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_base_image,
            'ability_frame' => 2,
            'ability_frame_animate' => array(2, 3),
            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => -10),
            'ability_frame_classes' => ' '
            );

        // Define and attach this ability's blackout attachment
        $this_blackout_token = 'ability_'.$this_ability->ability_token.'_blackout';
        $this_blackout_info = array(
            'class' => 'ability',
            'ability_id' => $this_ability->ability_id.'_fx',
            'ability_token' => $this_ability->ability_token,
            'ability_image' => '_effects/rain-overlay',
            'ability_frame' => 0,
            'ability_frame_animate' => array(0,1),
            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => -100),
            'ability_frame_classes' => 'sprite_fullscreen ',
            'ability_frame_styles' => 'filter: brightness(2) saturate(0); transform: scaleY(-1); background-color: rgba(255, 255, 255, 0.2); '
            );
        $this_blackout = rpg_game::get_ability($this_battle, $this_player, $this_robot, $this_blackout_info);

        // Define a function for looping through both player's robots and set them into their damage frames
        $bulk_apply_frames = function($player, $frame = '', $offset = 0, $exception = ''){
            $robots = $player->get_robots_active();
            foreach ($robots AS $robot){
                if ($robot->robot_id === $exception){ continue; }
                if (!empty($frame)){
                    $robot->set_frame($frame);
                    $robot->set_frame_offset('x', $offset);
                    $robot->set_frame_styles('filter: saturate(0.5) brightness(0.6); ');
                    } else {
                    $robot->reset_frame();
                    $robot->reset_frame_offset();
                    $robot->reset_frame_styles();
                    }
                }
            };

        // Define a function for bulk-applying a given attachment provided player, token, and info
        $bulk_apply_attachments = function($player, $filter, $token, $info = array(), $exception = ''){
            if (empty($filter) || !is_array($filter)){ $filter = false; }
            $robots = $player->get_robots_active();
            foreach ($robots AS $robot){
                if (!empty($filter) && !in_array($robot->robot_id, $filter)){ continue; }
                if (!empty($info)){
                    $robot->set_attachment($token, $info);
                    } else {
                    $robot->unset_attachment($token);
                    }
                }
            };

        // Define a function for bulk-checking if specific types of attachments exist on the battlefield and documenting them
        $attachment_objects_found = 0;
        $attachment_objects_found_robots = array();
        $attachment_objects_found_index = array();
        $bulk_find_object_attachments = function($player) use (
                $this_battle, $combined_object_tokens, $object_fx_tokens,
                &$attachment_objects_found, &$attachment_objects_found_robots, &$attachment_objects_found_index
                ){
            // Collect the list of robots for this player
            $robots = $player->get_robots_active();
            foreach ($robots AS $robot){
                // First we search the attachments on the robot themselves
                foreach ($robot->robot_attachments AS $attachment_token => $attachment_info){
                    $attachment_token_clean = preg_replace('/^ability_([-_a-z0-9]+)_([-a-z0-9]+)$/', '$1', $attachment_token);
                    $attachment_token_context = preg_replace('/^ability_([-_a-z0-9]+)_([-a-z0-9]+)$/', '$2', $attachment_token);
                    //error_log('checking '.$attachment_token.' from '.$robot->robot_string.' | '.$attachment_token_clean.' | '.$attachment_token_context);
                    if (!in_array($attachment_token_clean, $combined_object_tokens)){ continue; }
                    $attachment_fx_token = false;
                    if (isset($object_fx_tokens[$attachment_token_clean])){
                        $attachment_fx_token = str_replace(
                            $attachment_token_clean,
                            $object_fx_tokens[$attachment_token_clean],
                            $attachment_token
                            );
                        }
                    $attachment_objects_found++;
                    $attachment_objects_found_robots[] = $robot->robot_id;
                    $attachment_objects_found_index[] = array(
                        'kind' => 'robot',
                        'robot' => $robot,
                        'token' => $attachment_token,
                        'fx_token' => $attachment_fx_token,
                        );
                    //error_log('found '.$attachment_token.' from '.$robot->robot_string.' | count: '.$attachment_objects_found);
                    }
                // And then we search for any battlefield attachments at their current position
                if (!empty($this_battle->battle_attachments)){
                    foreach ($this_battle->battle_attachments AS $side_position => $battle_attachments){
                        if (!strstr($side_position, $player->player_side.'-')){ continue; }
                        $attachment_token_regex = '/^ability_([-_a-z0-9]+)_([-a-z0-9]+)$/';
                        foreach ($battle_attachments AS $attachment_token => $attachment_info){
                            $attachment_token_clean = preg_replace($attachment_token_regex, '$1', $attachment_token);
                            $attachment_token_context = preg_replace($attachment_token_regex, '$2', $attachment_token);
                            //error_log('checking '.$attachment_token.' from '.$robot->robot_string.' | '.$attachment_token_clean.' | '.$attachment_token_context);
                            if (!in_array($attachment_token_clean, $combined_object_tokens)){ continue; }
                            $attachment_fx_token = false;
                            if (isset($object_fx_tokens[$attachment_token_clean])){
                                $attachment_fx_token = str_replace(
                                    $attachment_token_clean,
                                    $object_fx_tokens[$attachment_token_clean],
                                    $attachment_token
                                    );
                                }
                            if (!strstr($side_position, 'bench-')){  $key = 0; list($side, $position) = explode('-', $attachment_token_context);  }
                            else { list($side, $position, $key) = explode('-', $attachment_token_context); $key = (int)($key);  }
                            if ($position !== $robot->robot_position){ continue; }
                            if ($key !== $robot->robot_key){ continue; }
                            $attachment_objects_found++;
                            $attachment_objects_found_robots[] = $robot->robot_id;
                            $attachment_objects_found_index[] = array(
                                'kind' => 'battle',
                                'robot' => $robot,
                                'key' => $side_position,
                                'token' => $attachment_token,
                                'fx_token' => $attachment_fx_token,
                                );
                            //error_log('found '.$attachment_token.' from '.$robot->robot_string.' | count: '.$attachment_objects_found);
                            }
                        }
                    }
                }
            };

        // Define a function that loops through found attachments and visually lifts them in the air slightly
        $bulk_lift_object_attachments = function($objects_index) use ($this_battle){
            if (empty($objects_index) || !is_array($objects_index)){ return; }
            $lift_attachment = function(&$attachment){
                $attachment['ability_frame_offset']['y'] += 100;
                $attachment['ability_frame_offset']['z'] += 50;
                $attachment['ability_frame_styles'] = (isset($attachment['ability_frame_styles']) ? $attachment['ability_frame_styles'].' ' : '').'opacity: 0.7; ';
                };
            foreach ($objects_index AS $key => $info){
                extract($info);
                if ($kind === 'robot'){
                    $attachment = $robot->get_attachment($token);
                    $lift_attachment($attachment);
                    $robot->set_attachment($token, $attachment);
                    if (!empty($fx_token)){
                        $attachment = $robot->get_attachment($fx_token);
                        $lift_attachment($attachment);
                        $robot->set_attachment($fx_token, $attachment);
                        }
                    } elseif ($kind === 'battle'){
                    $attachment = $this_battle->get_attachment($key, $token);
                    $lift_attachment($attachment);
                    $this_battle->set_attachment($key, $token, $attachment);
                    if (!empty($fx_token)){
                        $attachment = $this_battle->get_attachment($key, $fx_token);
                        $lift_attachment($attachment);
                        $this_battle->set_attachment($key, $fx_token, $attachment);
                        }
                    }
                }
            };

        // Define a function that loops through found attachments and removes them from the battlefield or robot they're attached to
        $bulk_remove_object_attachments = function($objects_index) use ($this_battle){
            if (empty($objects_index) || !is_array($objects_index)){ return; }
            foreach ($objects_index AS $key => $info){
                extract($info);
                if ($kind === 'robot'){
                    $robot->unset_attachment($token);
                    if (!empty($fx_token)){ $robot->unset_attachment($fx_token); }
                    } elseif ($kind === 'battle'){
                    $this_battle->unset_attachment($key, $token);
                    if (!empty($fx_token)){ $this_battle->unset_attachment($key, $fx_token); }
                    }
                }
            };


        // -- SUMMON THE STORM -- //

        // Target the opposing robot
        $this_battle->queue_sound_effect('ice-sound');
        $this_battle->queue_sound_effect(array('name' => 'blowing-sound', 'delay' => 200));
        $this_battle->queue_sound_effect(array('name' => 'blowing-sound', 'delay' => 400));
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 0, 100, 10, $this_robot->print_name().' summons the full force of the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true, 'prevent_stats_text' => true));

        // Change the image to the full-screen rain effect
        $this_ability->set_image($this_ability->ability_base_image.'-2');
        $this_ability->set_frame_classes('sprite_fullscreen ');


        // -- REMOVE FIELD ATTACHMENTS -- //

        // Search for all relevant object attachments on the battlefield
        $bulk_find_object_attachments($this_player);
        $bulk_find_object_attachments($target_player);
        //error_log('$attachment_objects_found: '.print_r($attachment_objects_found, true));
        //error_log('$attachment_objects_found_robots: '.print_r($attachment_objects_found_robots, true));
        //error_log('$attachment_objects_found_index: '.print_r($attachment_objects_found_index, true));

        // Apply the blackout effect to the field using this robot as anchor
        $this_robot->set_attachment($this_blackout_token, $this_blackout_info);

        // Lift the attachments in the air slightly to show that they are being removed
        $bulk_lift_object_attachments($attachment_objects_found_index);

        // Generate a blank frame to show the effects we've applied above
        $this_battle->queue_sound_effect('whirlwind-sound');
        $this_robot->set_frame('summon');
        $bulk_apply_frames($this_player, 'damage', -10, $this_robot->robot_id);
        $bulk_apply_frames($target_player, 'damage', -10);
        $bulk_apply_attachments($this_player, $attachment_objects_found_robots, $this_attachment_fx_token, $this_attachment_info);
        $bulk_apply_attachments($target_player, $attachment_objects_found_robots, $this_attachment_fx_token, $this_attachment_info);
        $this_battle->events_create(false, false, '', '', array(
            'event_flag_camera_action' => false
            ));

        // Now loop through again and actually remove the attachments from both sides
        $bulk_remove_object_attachments($attachment_objects_found_index);

        // Generate a blank frame to show the effect if any field hazards have been removed
        $bulk_apply_frames($this_player, 'defend', -5, $this_robot->robot_id);
        $bulk_apply_frames($target_player, 'defend', -5);
        $bulk_apply_attachments($this_player, null, $this_attachment_fx_token);
        $bulk_apply_attachments($target_player, null, $this_attachment_fx_token);
        if (!empty($attachment_objects_found)){
            $this_battle->queue_sound_effect('shields-down');
            $header = $this_robot->robot_name.'\'s '.$this_ability->ability_name;
            $body = 'The '.$this_ability->print_name().' removed '.($attachment_objects_found === 1 ? 'a' : $attachment_objects_found).' field '.($attachment_objects_found == 1 ? 'hazard' : 'hazards').'!';
            //$body = 'The '.$this_ability->print_name().' removed all hazards from the battlefield!';
            $this_robot->set_frame('taunt');
            $this_battle->events_create($this_robot, false, $header, $body, array(
                'event_flag_camera_action' => false,
                //'event_flag_camera_action' => true,
                //'event_flag_camera_side' => $this_robot->player->player_side,
                //'event_flag_camera_focus' => $this_robot->robot_position,
                //'event_flag_camera_depth' => $target_robot->robot_key,
                ));
        }

        // Loop through both player's robots and set them into their damage frames
        $bulk_apply_frames($this_player, '');
        $bulk_apply_frames($target_player, '');


        // -- DAMAGE TARGETS -- //

        // Correct the attachment frame offset for the actual attack
        $this_attachment_info['ability_frame_offset']['z'] = 20;

        // Inflict damage on the opposing robot
        $this_battle->queue_sound_effect(array('name' => 'whirlwind-sound', 'volume' => 0.3));
        $num_hits_counter = 0;
        $this_robot->set_frame('throw');
        $target_robot->set_attachment($this_attachment_fx_token, $this_attachment_info);
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'modifiers' => true,
            'kickback' => array(5, 0, 0),
            'success' => array(0, -5, 0, 99, 'Hold on tight! The '. $this_ability->print_name().' rocked the target'.(!empty($attachment_objects_removed) ? ' too' : '').'!'),
            'failure' => array(0, -5, 0, -10,'The '. $this_ability->print_name().' blew right past them'.(!empty($attachment_objects_removed) ? ' though' : '').'&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'modifiers' => true,
            'frame' => 'taunt',
            'kickback' => array(5, 0, 0),
            'success' => array(0, -5, 0, 9, 'The winds were absorbed by the target'.(!empty($attachment_objects_removed) ? ' though' : '').'!'),
            'failure' => array(0, -5, 0, 9, 'The '.$this_ability->print_name().' had no effect on the first target'.(!empty($attachment_objects_removed) ? ' though' : '').'&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false, 'apply_stat_modifiers' => true);
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false, $trigger_options);
        $target_robot->unset_attachment($this_attachment_fx_token);
        $num_hits_counter++;

        // Loop through the target's benched robots, inflicting damage to each
        $backup_target_robots_active = $target_player->values['robots_active'];
        foreach ($backup_target_robots_active AS $key => $info){
            if ($info['robot_id'] == $target_robot->robot_id){ continue; }
            $this_battle->queue_sound_effect(array('name' => 'ice-sound', 'volume' => 0.3));
            $this_robot->set_frame($num_hits_counter % 2 === 0 ? 'defend' : 'taunt');
            $temp_target_robot = rpg_game::get_robot($this_battle, $target_player, $info);
            $temp_target_robot->set_attachment($this_attachment_fx_token, $this_attachment_info);
            $this_ability->ability_results_reset();
            $temp_positive_word = rpg_battle::random_positive_word();
            $temp_negative_word = rpg_battle::random_negative_word();
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'modifiers' => true,
                'kickback' => array(5, 0, 0),
                'success' => array(($key % 2), -5, 0, 99, ($target_player->player_side === 'right' ? $temp_positive_word : $temp_negative_word).' The gusts struck another robot!'),
                'failure' => array(($key % 2), -5, 0, 99, 'The attack had no effect on '.$temp_target_robot->print_name().'&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'modifiers' => true,
                'frame' => 'taunt',
                'kickback' => array(5, 0, 0),
                'success' => array(($key % 2), -5, 0, 9, ($target_player->player_side === 'right' ? $temp_negative_word : $temp_positive_word).' The attack was absorbed by the target!'),
                'failure' => array(($key % 2), -5, 0, 9, 'The attack had no effect on '.$temp_target_robot->print_name().'&hellip;')
                ));
            $energy_damage_amount = $this_ability->ability_damage;
            $temp_target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false, $trigger_options);
            $temp_target_robot->unset_attachment($this_attachment_fx_token);
            $temp_target_robot->unset_attachment($this_attachment_token);
            $num_hits_counter++;
        }

        // Return the user to their base frame
        $this_robot->reset_frame();
        $this_robot->reset_frame_classes();
        $this_robot->reset_frame_styles();
        $this_robot->unset_attachment($this_blackout_token);

        // Reset the ability image back to base values
        $this_ability->reset_image();
        $this_ability->reset_frame_classes();
        $this_ability->reset_frame_styles();

        // Now that all the damage has been dealt, allow the player to check for disabled
        $target_player->check_robots_disabled($this_player, $this_robot);

        // Return true on success
        return true;

        }
);
?>
