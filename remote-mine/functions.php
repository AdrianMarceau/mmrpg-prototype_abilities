<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Predefine attachment create and destroy text for later
        $this_create_text = ($target_robot->print_name().' found '.$target_robot->get_pronoun('reflexive').' behind a ticking '.rpg_type::print_span('explode', 'Remote Mine').'!<br /> '.
            'The explosive starting building power...'
            );

        // Collect this ability's attachment token and info
        $static_attachment_key = $target_robot->get_static_attachment_key();
        $static_attachment_duration = 99;
        $static_attachment_created = $this_battle->counters['battle_turn'];
        $this_attachment_info = rpg_ability::get_static_attachment($this_ability, 'remote-mine', $static_attachment_key, $static_attachment_duration, $static_attachment_created);
        $this_attachment_token = $this_attachment_info['attachment_token'];

        // If the target does not already have a Remote Mine set, attach the hazard to the target position
        if (!isset($this_battle->battle_attachments[$static_attachment_key][$this_attachment_token])){

            // Update the attachment image if a special robot is using it
            $static_attachment_image = $this_ability->ability_image;
            $this_attachment_info['ability_image'] = $static_attachment_image;

            // Create the attachment object for this ability
            $this_attachment = rpg_game::get_ability($this_battle, $target_player, $target_robot, $this_attachment_info);

            // Generate the text for throwing the mine at the target
            $offset_x = $target_robot->robot_position === 'active' ? 160 : 210;
            $offset_y = $target_robot->robot_position === 'active' ? 40 : 50;
            $this_ability->target_options_update(array(
                'frame' => 'throw',
                'success' => array(5, $offset_x, $offset_y, 10,
                    $this_robot->print_name().' threw a '.$this_ability->print_name().' at the target!')
                ));
            $this_robot->trigger_target($this_robot, $this_ability);

            // Attach this ability attachment to the robot using it
            if ($this_battle->counters['battle_turn'] % 2 === 0){ $this_attachment_info['ability_frame_animate'] = array_reverse($this_attachment_info['ability_frame_animate']); }
            $this_battle->set_attachment($static_attachment_key, $this_attachment_token, $this_attachment_info);

            // Target this robot's self
            $this_robot->set_frame('base');
            $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(0, -9999, -9999, -9999, $this_create_text)));
            $target_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));

            // Either way, update this ability's settings to prevent recovery
            $this_attachment->damage_options_update($this_attachment_info['attachment_destroy'], true);
            $this_attachment->recovery_options_update($this_attachment_info['attachment_destroy'], true);

        }
        // Else if the ability flag was set, reinforce the fever by one more duration point
        else {

            // Define an inline function for detonating a mine given the static attachment key
            $detonate_remote_mine = function($actual_target_robot, $static_attachment_key, $this_attachment_token, $this_attachment_info, $show_trigger = true, $check_adjacent = 'both')
                use($objects, &$detonate_remote_mine) {

                // Extract all objects into the current scope
                extract($objects);

                // Generate the destroy text specific to the actual target robot
                $this_destroy_text = ('The '.rpg_type::print_span('explode', 'Remote Mine').' in front of '.$actual_target_robot->print_name().' exploded!');

                // Check to see how many mines are currently on the field
                $num_same_attachments = 0;
                if (!empty($this_battle->battle_attachments)){
                    foreach ($this_battle->battle_attachments AS $static_key => $static_attachments){
                        if (!strstr($static_key, $target_player->player_side.'-')){ continue; }
                        elseif (strstr(json_encode($static_attachments), '"'.$this_ability->ability_token.'"')){
                            $num_same_attachments++;
                        }
                    }
                }

                // Collect the frame offsets for the attachment
                $attachment_offsets = $this_attachment_info['ability_frame_offset'];
                $attachment_offsets['y'] -= 30;
                $this_attachment_info['ability_frame_offset'] = $attachment_offsets;
                $this_battle->set_attachment($static_attachment_key, $this_attachment_token, $this_attachment_info);

                // If we're supposed to show the trigger action, do it now
                if ($show_trigger){

                    // Shift the target's attachment into the deteonation frame beforehand
                    $this_attachment_info['ability_frame'] = 8;
                    $this_attachment_info['ability_frame_animate'] = array(8);
                    $this_battle->set_attachment($static_attachment_key, $this_attachment_token, $this_attachment_info);

                    // Generate text for triggering the mine's detonation
                    $is_multiple = $num_same_attachments > 1 ? true : false;
                    $this_ability->target_options_update(array(
                        'frame' => 'summon',
                        'success' => array(9, -9999, -9999, -9999,
                            $this_robot->print_name().' detonated '.($is_multiple ? 'one of ' : '').'the '.$this_ability->print_name($is_multiple).'!')
                        ));
                    $this_robot->trigger_target($this_robot, $this_ability);

                }

                // Shift the target's attachment into the deteonation frame beforehand
                $this_attachment_info['ability_frame'] = 9;
                $this_attachment_info['ability_frame_animate'] = array(9);
                $this_battle->set_attachment($static_attachment_key, $this_attachment_token, $this_attachment_info);

                // Detonate the mine and inflict damage on the opposing robot
                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'kickback' => array(0, 15, 0),
                    'success' => array(9, -9999, -9999, -9999, $this_destroy_text),
                    'failure' => array(9, -9999, -9999, -9999, substr($this_destroy_text, -1).', but...')
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'kickback' => array(0, 0, 0),
                    'success' => array(9, -9999, -9999, -9999, $this_destroy_text),
                    'failure' => array(9, -9999, -9999, -9999, substr($this_destroy_text, -1).', but...')
                    ));
                $turn_created = !empty($this_attachment_info['attachment_created']) ? $this_attachment_info['attachment_created'] : 0;
                $current_turn = $this_battle->counters['battle_turn'];
                $energy_damage_amount = ($current_turn - $turn_created) * $this_ability->ability_damage;
                $this_robot->set_frame('taunt');
                $actual_target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
                $this_robot->set_frame('base');

                // Unset the attachment as we'll be showing the final explosion the usual way
                $this_battle->unset_attachment($static_attachment_key, $this_attachment_token);

                // If the target robot was on the bench, check to see if there were adjacent mines to detonate
                if ($actual_target_robot->robot_position === 'bench'){

                    // Break apart the current static attachment key so we can determine adjecent positions
                    list($b_side, $b_position, $b_key) = explode('-', $static_attachment_key);

                    // Determine the attachment key and token for the positions BEFORE this one and check if exists
                    if ($check_adjacent === 'both' || $check_adjacent === 'before'){
                        $before_key = intval($b_key) + 1;
                        $before_static_attachment_key = $b_side.'-'.$b_position.'-'.$before_key;
                        $before_attachment_token = str_replace($static_attachment_key, $before_static_attachment_key, $this_attachment_token);
                        //error_log('CHECK BEFORE | key='.$before_static_attachment_key);
                        if (isset($this_battle->battle_attachments[$before_static_attachment_key][$before_attachment_token])){
                            $before_target_robot = $target_player->get_robot_by_key($before_key);
                            //error_log('BEFORE SUCCESS | robot='.$before_target_robot->robot_token.' | key='.$before_target_robot->robot_key);
                            if (!empty($before_target_robot) && $before_target_robot->robot_status !== 'disabled'){
                                $before_attachment_info = $this_battle->battle_attachments[$before_static_attachment_key][$before_attachment_token];
                                $detonate_remote_mine($before_target_robot, $before_static_attachment_key, $before_attachment_token, $before_attachment_info, false, 'before');
                            } else {
                                $this_battle->unset_attachment($before_static_attachment_key, $before_attachment_token);
                                $this_battle->events_create(false, false, '', '');
                            }
                        }
                    }

                    // Determine the attachment key and token for the position AFTER this one and check if exists
                    if ($check_adjacent === 'both' || $check_adjacent === 'after'){
                        $after_key = intval($b_key) - 1;
                        $after_static_attachment_key = $b_side.'-'.$b_position.'-'.$after_key;
                        $after_attachment_token = str_replace($static_attachment_key, $after_static_attachment_key, $this_attachment_token);
                        //error_log('CHECK AFTER | key='.$after_static_attachment_key);
                        if (isset($this_battle->battle_attachments[$after_static_attachment_key][$after_attachment_token])){
                            $after_target_robot = $target_player->get_robot_by_key($after_key);
                            //error_log('AFTER SUCCESS | robot='.$after_target_robot->robot_token.' | key='.$after_target_robot->robot_key);
                            if (!empty($after_target_robot) && $after_target_robot->robot_status !== 'disabled'){
                                $after_attachment_info = $this_battle->battle_attachments[$after_static_attachment_key][$after_attachment_token];
                                $detonate_remote_mine($after_target_robot, $after_static_attachment_key, $after_attachment_token, $after_attachment_info, false, 'after');
                            } else {
                                $this_battle->unset_attachment($after_static_attachment_key, $after_attachment_token);
                                $this_battle->events_create(false, false, '', '');
                            }
                        }
                    }

                }

            };

            // Overwrite default attachment info with the one on the target robot
            $this_attachment_info = array_merge($this_attachment_info, $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token]);

            // Detonate the mine directly in front of the selected target
            $detonate_remote_mine($target_robot, $static_attachment_key, $this_attachment_token, $this_attachment_info);

        }

        // Return true on success
        return true;

    },
    'static_attachment_function_remote-mine' => function($objects, $static_attachment_key, $this_attachment_duration = 99, $this_attachment_created = 0){

        // Extract all objects and config into the current scope
        extract($objects);

        // Generate the static attachment info using provided config
        $existing_attachments = isset($this_battle->battle_attachments[$static_attachment_key]) ? count($this_battle->battle_attachments[$static_attachment_key]) : 0;
        $this_ability_token = $this_ability->ability_token;
        $this_attachment_token = 'ability_'.$this_ability_token.'_'.$this_attachment->attachment_token.'_'.$static_attachment_key;
        $this_attachment_image = $this_ability_token;
        $this_attachment_destroy_text = 'The <span class="ability_name ability_type ability_type_explode">Remote Mine</span> below {this_robot} was defused... ';
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability_token,
            'ability_image' => $this_attachment_image,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $this_attachment_duration,
            'attachment_created' => $this_attachment_created,
            'attachment_sticky' => true,
            'attachment_weaknesses' => array('flame'),
            'attachment_weaknesses_trigger' => 'user',
            'attachment_destroy' => array(
                'trigger' => 'special',
                'kind' => '',
                'type' => '',
                'percent' => true,
                'modifiers' => false,
                'frame' => 'defend',
                'rates' => array(100, 0, 0),
                'success' => array(8, 0, -10, 10, $this_attachment_destroy_text),
                'failure' => array(8, 0, -10, 10, $this_attachment_destroy_text)
                ),
            'ability_frame' => 6,
            'ability_frame_animate' => array(6, 7),
            'ability_frame_offset' => array(
                'x' => (30 + ($existing_attachments * 8)),
                'y' => (-5),
                'z' => (6 + $existing_attachments)
                )
            );

        // Return true on success
        return $this_attachment_info;

    }
);
?>
