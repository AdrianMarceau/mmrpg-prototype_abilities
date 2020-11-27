<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define the attachment Y offset based on the target
        $temp_y_offset = 5;
        $temp_attach_frames = array(1,2);
        if ($target_robot->robot_token == 'met'){ $temp_attach_frames = array(1,2); $temp_y_offset = -25; }
        elseif ($target_robot->robot_token == 'mega-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 9; }
        elseif ($target_robot->robot_token == 'proto-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 14; }
        elseif ($target_robot->robot_token == 'bass'){ $temp_attach_frames = array(1,2); $temp_y_offset = 12; }
        elseif ($target_robot->robot_token == 'roll'){ $temp_attach_frames = array(1,2); $temp_y_offset = 14; }
        elseif ($target_robot->robot_token == 'disco'){ $temp_attach_frames = array(1,2); $temp_y_offset = 18; }
        elseif ($target_robot->robot_token == 'rhythm'){ $temp_attach_frames = array(1,2); $temp_y_offset = 12; }
        elseif ($target_robot->robot_token == 'duo'){ $temp_attach_frames = array(1,2); $temp_y_offset = 12; }
        elseif ($target_robot->robot_token == 'cut-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 14; }
        elseif ($target_robot->robot_token == 'guts-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 24; }
        elseif ($target_robot->robot_token == 'ice-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 10; }
        elseif ($target_robot->robot_token == 'bomb-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 10; }
        elseif ($target_robot->robot_token == 'fire-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 12; }
        elseif ($target_robot->robot_token == 'elec-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 12; }
        elseif ($target_robot->robot_token == 'time-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 12; }
        elseif ($target_robot->robot_token == 'oil-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 12; }
        elseif ($target_robot->robot_token == 'metal-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 12; }
        elseif ($target_robot->robot_token == 'air-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 32; }
        elseif ($target_robot->robot_token == 'bubble-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 14; }
        elseif ($target_robot->robot_token == 'quick-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 16; }
        elseif ($target_robot->robot_token == 'crash-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 14; }
        elseif ($target_robot->robot_token == 'flash-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 14; }
        elseif ($target_robot->robot_token == 'heat-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 10; }
        elseif ($target_robot->robot_token == 'wood-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 26; }
        elseif ($target_robot->robot_token == 'needle-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 30; }
        elseif ($target_robot->robot_token == 'magnet-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 10; }
        elseif ($target_robot->robot_token == 'gemini-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 10; }
        elseif ($target_robot->robot_token == 'hard-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 45; }
        elseif ($target_robot->robot_token == 'top-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 14; }
        elseif ($target_robot->robot_token == 'snake-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 30; }
        elseif ($target_robot->robot_token == 'spark-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 20; }
        elseif ($target_robot->robot_token == 'shadow-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 18; }
        elseif ($target_robot->robot_token == 'bright-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 45; }
        elseif ($target_robot->robot_token == 'toad-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 35; }
        elseif ($target_robot->robot_token == 'drill-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 25; }
        elseif ($target_robot->robot_token == 'pharaoh-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 15; }
        elseif ($target_robot->robot_token == 'ring-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 15; }
        elseif ($target_robot->robot_token == 'dust-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 30; }
        elseif ($target_robot->robot_token == 'dive-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 45; }
        elseif ($target_robot->robot_token == 'skull-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 15; }
        elseif ($target_robot->robot_token == 'gravity-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 30; }
        elseif ($target_robot->robot_token == 'stone-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 50; }
        elseif ($target_robot->robot_token == 'wave-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 10; }
        elseif ($target_robot->robot_token == 'gyro-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 20; }
        elseif ($target_robot->robot_token == 'star-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 15; }
        elseif ($target_robot->robot_token == 'charge-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 10; }
        elseif ($target_robot->robot_token == 'napalm-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 16; }
        elseif ($target_robot->robot_token == 'crystal-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 25; }
        elseif ($target_robot->robot_token == 'blizzard-man'){ $temp_attach_frames = array(1,2); $temp_y_offset = 45; }
        else { $temp_y_offset = 5; }

        // Update this ability's attachment frame offset
        $this_ability->ability_frame_offset['y'] = $temp_y_offset;
        $this_ability->update_session();

        // Define the base turn duration this attachment must charge
        $attachment_duration = 2;

        // If the user has Quick Charge, cut charge time in half
        if ($this_robot->has_attribute('quick-charge')){ $attachment_duration = 1; }

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
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
            $this_ability->target_options_update(array(
                'frame' => 'shoot',
                'success' => array(0, 95, 0, 10, $this_robot->print_name().' fires a '.$this_ability->print_name().'!')
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
            $temp_console_header = $this_robot->robot_name.'&#39;s '.$this_ability->ability_name;
            $temp_console_content = 'The '.$this_ability->print_name().' attached itself to '.$target_robot->print_name().'!<br />';
            if ($target_player->player_token != 'player'){ $temp_console_content .= $target_player->print_name().'&#39;s robot started ticking&hellip;'; }
            else { $temp_console_content .= 'The target robot started ticking&hellip;'; }
            $this_battle->events_create($target_robot, false, $temp_console_header, $temp_console_content, array('console_show_target' => false));
            $target_robot->robot_frame = 'base';
            $target_robot->update_session();

            // If the target robot has an IMMUNITY to the ability
            if ($target_robot->has_immunity($this_ability->ability_type)){

                // Attach this ability attachment to the robot using it
                $target_robot->robot_frame = 'taunt';
                unset($target_robot->robot_attachments[$this_attachment_token]);
                $target_robot->update_session();

                // Create the attachment event manually as no damage/recovery occurs
                $temp_console_header = $this_robot->robot_name.'&#39;s '.$this_ability->ability_name;
                $temp_console_content = $target_robot->print_name().'&#39;s immunity kicked in!<br />';
                $temp_console_content .= 'The '.$this_ability->print_name().' fizzled and faded away&hellip;';
                $this_battle->events_create($target_robot, false, $temp_console_header, $temp_console_content, array('console_show_target' => false));
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
            $damage_trigger_options = $this_attachment_info['attachment_destroy']['options'];
            unset($damage_trigger_options['referred_damage'], $damage_trigger_options['referred_damage_id']);
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, true, $damage_trigger_options);

        }

        // Return true on success
        return true;

    }
);
?>
