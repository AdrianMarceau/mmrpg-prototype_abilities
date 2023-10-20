<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Manually define offsets for each potential target
        // (to ensure it attaches to their heads)
        $temp_robot_offsets = [
            'met' => [0, -25, 0],
            'mega-man' => [0, 9, 0],
            'proto-man' => [0, 14, 0],
            'bass' => [0, 12, 0],
            'roll' => [0, 14, 0],
            'disco' => [0, 18, 0],
            'rhythm' => [0, 12, 0],
            'duo' => [0, 12, 0],
            'cut-man' => [0, 14, 0],
            'guts-man' => [0, 24, 0],
            'ice-man' => [0, 10, 0],
            'bomb-man' => [0, 10, 0],
            'fire-man' => [0, 12, 0],
            'elec-man' => [0, 12, 0],
            'time-man' => [0, 12, 0],
            'oil-man' => [0, 12, 0],
            'metal-man' => [0, 12, 0],
            'air-man' => [0, 32, 0],
            'bubble-man' => [0, 14, 0],
            'quick-man' => [0, 16, 0],
            'crash-man' => [0, 14, 0],
            'flash-man' => [0, 14, 0],
            'heat-man' => [0, 10, 0],
            'wood-man' => [0, 26, 0],
            'needle-man' => [0, 30, 0],
            'magnet-man' => [0, 10, 0],
            'gemini-man' => [0, 10, 0],
            'hard-man' => [0, 45, 0],
            'top-man' => [0, 14, 0],
            'snake-man' => [0, 30, 0],
            'spark-man' => [0, 20, 0],
            'shadow-man' => [0, 18, 0],
            'bright-man' => [0, 45, 0],
            'toad-man' => [0, 35, 0],
            'drill-man' => [0, 25, 0],
            'pharaoh-man' => [0, 15, 0],
            'ring-man' => [0, 15, 0],
            'dust-man' => [0, 30, 0],
            'dive-man' => [0, 45, 0],
            'skull-man' => [0, 15, 0],
            'gravity-man' => [0, 30, 0],
            'stone-man' => [0, 50, 0],
            'wave-man' => [0, 10, 0],
            'gyro-man' => [0, 20, 0],
            'star-man' => [0, 15, 0],
            'charge-man' => [0, 10, 0],
            'napalm-man' => [0, 16, 0],
            'crystal-man' => [0, 25, 0],
            'blizzard-man' => [0, 45, 0]
            ];

        // Set the default offset, but collect the proper one if it exists
        $temp_y_offset = 5;
        $temp_attach_frames = [1, 2];
        if (isset($temp_robot_offsets[$target_robot->robot_token])) {
            $temp_y_offset = $temp_robot_offsets[$target_robot->robot_token][1];
        }

        // Update this ability's attachment frame offset
        $this_ability->ability_frame_offset['y'] = $temp_y_offset;
        $this_ability->update_session();

        // Define the base turn duration this attachment must charge
        $attachment_duration = 2;

        // If the user has Quick Charge, cut charge time in half
        if ($this_robot->has_attribute('quick-charge')){ $attachment_duration = 1; }

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token.'_crash-bomb';
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => false,
            'ability_id' => $this_ability->ability_id,
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 1,
            'ability_frame_animate' => array(1, 2),
            'ability_frame_offset' => array('x' => 0, 'y' => $temp_y_offset, 'z' => 10),
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $attachment_duration + 1,
            'attachment_weaknesses' => array('electric', 'flame'),
            'attachment_energy' => 0,
            'attachment_create' => array(
                'kind' => 'energy',
                'trigger' => 'damage',
                'type' => '',
                'percent' => false,
                'frame' => 'defend',
                'success' => array(1, 2, $temp_y_offset, 10, 'The '.$this_ability->print_name().' attached itself to '.$this_robot->print_name().'!'),
                'failure' => array(4, 2, $temp_y_offset, -10, 'The '.$this_ability->print_name().' flew past the target&hellip;')
                ),
            'attachment_destroy' => array(
                'kind' => 'energy',
                'trigger' => 'damage',
                'type' => $this_ability->ability_type,
                'energy' => $this_ability->ability_damage,
                'percent' => false,
                'modifiers' => true,
                'frame' => 'damage',
                'rates' => array(100, 0, 0),
                'success' => array(3, 2, $temp_y_offset, 10, 'The '.$this_ability->print_name().' suddenly exploded!'),
                'failure' => array(0, 2, -9999, 0, 'The '.$this_ability->print_name().' fizzled and faded away&hellip;'),
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

            // Target the opposing robot
            $this_battle->queue_sound_effect('throw-sound');
            $this_ability->target_options_update(array(
                'frame' => 'shoot',
                'success' => array(0, 45, 0, 10, $this_robot->print_name().' fires a '.$this_ability->print_name().'!')
                ));
            $this_robot->trigger_target($target_robot, $this_ability);

            // Define the base energy damage amount for this ability attachment
            $this_attachment_info['attachment_energy'] = $this_ability->ability_damage;

            // Decrease this robot's speed stat if the attachment does not already exist
            $this_ability->damage_options_update($this_attachment_info['attachment_create']);
            $this_ability->update_session();

            // Attach this ability attachment to the robot using it
            $target_robot->robot_frame = !$target_robot->has_immunity($this_ability->ability_type) ? 'damage' : 'defend';
            $target_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
            $target_robot->update_session();

            // Create the attachment event manually as no damage/recovery occurs
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

            // If the target robot has an IMMUNITY to the ability
            if ($target_robot->has_immunity($this_ability->ability_type)){

                // Attach this ability attachment to the robot using it
                $target_robot->robot_frame = 'taunt';
                unset($target_robot->robot_attachments[$this_attachment_token]);
                $target_robot->update_session();

                // Create the attachment event manually as no damage/recovery occurs
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
            // Else if the target robot has an IMMUNITY to the ability
            elseif ($target_robot->has_affinity($this_ability->ability_type)){

                // Attach this ability attachment to the robot using it
                $target_robot->robot_attachments[$this_attachment_token]['attachment_destroy']['trigger'] = 'recovery';
                $target_robot->robot_attachments[$this_attachment_token]['attachment_destroy']['frame'] = 'taunt';
                $target_robot->update_session();

            }

        }
        // Otherwise, if this ability has already been attached ot the target
        else {

            // Target the opposing robot
            $this_battle->queue_sound_effect('beeping-sound');
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(0, 0, -9999, 0, $this_robot->print_name().' triggered the '.$this_ability->print_name().' early!')
                ));
            $this_robot->trigger_target($target_robot, $this_ability);

            // Collect the existing attachment info and update the generated one
            $this_attachment_info = $target_robot->robot_attachments[$this_attachment_token];

            // Attach this ability attachment to the robot using it
            unset($target_robot->robot_attachments[$this_attachment_token]);
            $target_robot->update_session();

            // Update this ability with the attachment destroy data
            $this_ability->damage_options_update($this_attachment_info['attachment_destroy']);
            $this_ability->update_session();

            // Collect the energy damage amount and cut it in half for triggering early
            $energy_damage_amount = round($this_attachment_info['attachment_energy'] * 0.5);

            // Now that we have the new amount, we can trigger the reduced damage
            $this_battle->queue_sound_effect('explode-sound');
            $damage_trigger_options = $this_attachment_info['attachment_destroy']['options'];
            unset($damage_trigger_options['referred_damage'], $damage_trigger_options['referred_damage_id']);
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, true, $damage_trigger_options);

        }

        // Return true on success
        return true;

    }
);
?>
