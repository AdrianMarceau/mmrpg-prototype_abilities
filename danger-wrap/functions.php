<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Set the default Y offset for the attachment
        $temp_y_offset = 0;

        // Update this ability's attachment frame offset
        $this_ability->ability_frame_offset['y'] = $temp_y_offset;
        $this_ability->update_session();

        // Define the base turn duration this attachment must charge (1 turn wait = duration 2)
        $attachment_duration = 2;

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token.'_danger-wrap';
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => false,
            'ability_id' => $this_ability->ability_id,
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 1,
            'ability_frame_animate' => array(1, 2, 3, 4, 5),
            'ability_frame_offset' => array('x' => 0, 'y' => $temp_y_offset, 'z' => 10),
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $attachment_duration,
            'attachment_weaknesses' => array('electric', 'cutter'),
            'attachment_energy' => 0,
            'attachment_create' => array(
                'kind' => 'energy',
                'trigger' => 'damage',
                'type' => '',
                'percent' => false,
                'frame' => 'defend',
                'success' => array(1, 0, $temp_y_offset, 10, 'The '.$this_ability->print_name().' attached itself to '.$this_robot->print_name().'!'),
                'failure' => array(1, 0, $temp_y_offset, -10, 'The '.$this_ability->print_name().' flew past the target&hellip;')
                ),
            'attachment_destroy' => array(
                'kind' => 'energy',
                'trigger' => 'damage',
                'type' => $this_ability->ability_type,
                'type2' => $this_ability->ability_type2,
                'energy' => $this_ability->ability_damage,
                'percent' => false,
                'modifiers' => true,
                'frame' => 'damage',
                'rates' => array(100, 0, 0),
                'success' => array(7, 0, $temp_y_offset, 10, 'The '.$this_ability->print_name().' suddenly exploded!'),
                'failure' => array(7, 0, $temp_y_offset, -10, 'The '.$this_ability->print_name().' fizzled and faded away&hellip;'),
                'options' => array(
                    'apply_modifiers' => true,
                    'apply_type_modifiers' => true,
                    'apply_core_modifiers' => true,
                    'apply_field_modifiers' => true,
                    'apply_stat_modifiers' => true,
                    'apply_position_modifiers' => false,
                    'referred_damage' => true,
                    'referred_damage_id' => $this_robot->robot_id,
                    'referred_damage_stats' => $this_robot->get_stats()
                    )
                )
            );

        // If the target does not already have this ability attached to them
        if (!isset($target_robot->robot_attachments[$this_attachment_token])){

            // Target the opposing robot (Frame 0: user starts generating bubble)
            $this_battle->queue_sound_effect('throw-sound');
            $this_ability->target_options_update(array(
                'frame' => 'shoot',
                'success' => array(0, 85, 0, 10, $this_robot->print_name().' fires a '.$this_ability->print_name().'!')
                ));
            $this_robot->trigger_target($target_robot, $this_ability);

            // Define the base energy damage amount for this ability attachment
            $this_attachment_info['attachment_energy'] = $this_ability->ability_damage;

            // Configure the attachment creation trigger
            $this_ability->damage_options_update($this_attachment_info['attachment_create']);
            $this_ability->update_session();

            // Attach this ability attachment to the robot using it
            $target_robot->robot_frame = (!$target_robot->has_immunity($this_ability->ability_type) && !$target_robot->has_immunity($this_ability->ability_type2)) ? 'damage' : 'defend';
            $target_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
            $target_robot->update_session();

            // Create the attachment event manually as no damage/recovery occurs immediately
            $this_battle->queue_sound_effect('beeping-sound');
            $temp_console_header = $this_robot->robot_name.'&#39;s '.$this_ability->ability_name;
            $temp_console_content = 'The '.$this_ability->print_name().' attached itself to '.$target_robot->print_name().'!<br />';
            if ($target_player->player_token != 'player'){ $temp_console_content .= $target_player->print_name().'&#39;s robot started ticking&hellip;'; }
            else { $temp_console_content .= 'The target robot started ticking&hellip;'; }
            $this_battle->events_create($target_robot, false, $temp_console_header, $temp_console_content, array(
                'console_show_target' => false,
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $target_robot->player->player_side,
                'event_flag_camera_focus' => $target_robot->robot_position,
                'event_flag_camera_depth' => $target_robot->robot_key
                ));
            $target_robot->robot_frame = 'base';
            $target_robot->update_session();

            // If the target robot has an IMMUNITY to either of the ability's types
            if ($target_robot->has_immunity($this_ability->ability_type)
                || $target_robot->has_immunity($this_ability->ability_type2)){

                // Remove this ability attachment to the robot using it
                $target_robot->robot_frame = 'taunt';
                unset($target_robot->robot_attachments[$this_attachment_token]);
                $target_robot->update_session();

                // Create the fizzle event manually
                $this_battle->queue_sound_effect('no-effect');
                $temp_console_header = $this_robot->robot_name.'&#39;s '.$this_ability->ability_name;
                $temp_console_content = $target_robot->print_name().'&#39;s immunity kicked in!<br />';
                $temp_console_content .= 'The '.$this_ability->print_name().' fizzled and faded away&hellip;';
                $this_battle->events_create($target_robot, false, $temp_console_header, $temp_console_content, array(
                    'console_show_target' => false,
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $target_robot->player->player_side,
                    'event_flag_camera_focus' => $target_robot->robot_position,
                    'event_flag_camera_depth' => $target_robot->robot_key
                    ));
                $target_robot->robot_frame = 'base';
                $target_robot->update_session();

            }
            // Else if the target robot has an AFFINITY to either of the ability's types
            elseif ($target_robot->has_affinity($this_ability->ability_type)
                || $target_robot->has_affinity($this_ability->ability_type2)){

                // Update this ability attachment so that it heals the target instead of damages them
                $target_robot->robot_attachments[$this_attachment_token]['attachment_destroy']['trigger'] = 'recovery';
                $target_robot->robot_attachments[$this_attachment_token]['attachment_destroy']['frame'] = 'taunt';
                $target_robot->update_session();

            }

        }
        // Otherwise, if this ability has already been attached to the target (Refresh)
        else {

            // Target the opposing robot (Frame 0: user starts generating bubble)
            $this_battle->queue_sound_effect('throw-sound');
            $this_ability->target_options_update(array(
                'frame' => 'shoot',
                'success' => array(0, 85, 0, 10, $this_robot->print_name().' fires another '.$this_ability->print_name().'!')
                ));
            $this_robot->trigger_target($target_robot, $this_ability);
            
            // Refresh the attachment duration on the target robot
            $target_robot->robot_attachments[$this_attachment_token]['attachment_duration'] = $this_attachment_info['attachment_duration'];
            $target_robot->update_session();
            
            // Create the refresh event manually
            $this_battle->queue_sound_effect('beeping-sound');
            $temp_console_header = $this_robot->robot_name.'&#39;s '.$this_ability->ability_name;
            $temp_console_content = 'The '.$this_ability->print_name().' attached itself to '.$target_robot->print_name().'!<br />';
            $temp_console_content .= 'The explosive bubble was refreshed!';
            $this_battle->events_create($target_robot, false, $temp_console_header, $temp_console_content, array(
                'console_show_target' => false,
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $target_robot->player->player_side,
                'event_flag_camera_focus' => $target_robot->robot_position,
                'event_flag_camera_depth' => $target_robot->robot_key
                ));

        }

        // Return true on success
        return true;

    }
);
?>
