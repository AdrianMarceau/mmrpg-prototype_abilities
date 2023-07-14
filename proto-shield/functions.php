<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Collect the target robot and correct for bugs
        if ($target_robot->player->player_id == $this_robot->player->player_id){ $temp_ally_robot = $target_robot; }
        else { $temp_ally_robot = $this_robot; }

        // Define the base attachment duration
        $base_attachment_duration = 6;
        $base_attachment_multiplier = 0.5;

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token.'_'.$temp_ally_robot->robot_id;
        $this_attachment_info = array(
            'class' => 'ability',
            //'ability_id' => $this_ability->ability_id.'_'.$temp_ally_robot->robot_id,
            'ability_token' => $this_ability->ability_token,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $base_attachment_duration,
            'attachment_damage_input_breaker' => $base_attachment_multiplier,
            'attachment_create' => array(
                'trigger' => 'special',
                'kind' => '',
                'percent' => true,
                'frame' => 'taunt',
                'rates' => array(100, 0, 0),
                'success' => array(0, 34, -10, 18, 'The '.$this_ability->print_name().' hovers in front of '.$temp_ally_robot->print_name().'!<br /> '.$temp_ally_robot->print_name().'&#39;s defenses were bolstered!'),
                'failure' => array(0, 34, -10, 18, 'The '.$this_ability->print_name().' hovers in front of '.$temp_ally_robot->print_name().'!<br /> '.$temp_ally_robot->print_name().'&#39;s defenses were bolstered!')
                ),
            'attachment_destroy' => array(
                'trigger' => 'special',
                'kind' => '',
                'type' => '',
                'percent' => true,
                'modifiers' => false,
                'frame' => 'defend',
                'rates' => array(100, 0, 0),
                'success' => array(2, -2, 0, -10,  'The '.$this_ability->print_name().' faded away!<br /> '.$temp_ally_robot->print_name().'&#39;s defenses returned to normal!'),
                'failure' => array(2, -2, 0, -10, 'The '.$this_ability->print_name().' faded away!<br /> '.$temp_ally_robot->print_name().'&#39;s defenses returned to normal!')
                ),
                'ability_frame' => 0,
                'ability_frame_animate' => array(0, 1, 2, 1),
                'ability_frame_offset' => array('x' => 34, 'y' => -10, 'z' => 18)
            );

        // Create the attachment object for this ability
        $this_attachment = new rpg_ability($this_battle, $this_player, $temp_ally_robot, $this_attachment_info);

        // If the ability flag was not set, attach the ability to the target
        if (!isset($temp_ally_robot->robot_attachments[$this_attachment_token])){

            // Target this robot's self
            $this_battle->queue_sound_effect(array('name' => 'spawn-sound', 'volume' => 0.5));
            $this_battle->queue_sound_effect('summon-positive');
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(0, 50, 0, 18, $this_robot->print_name().' summons a '.$this_ability->print_name().'!')
                ));
            $this_robot->trigger_target($this_robot, $this_ability);

            // If this robot is targetting itself
            if ($this_robot->robot_id == $temp_ally_robot->robot_id){

                // Target this robot's self
                $this_battle->queue_sound_effect('small-buff-received');
                $this_ability->target_options_update($this_attachment_info['attachment_create']);
                $this_robot->trigger_target($this_robot, $this_ability);

                // Attach this ability attachment to the robot using it
                $this_attachment_info['ability_frame_animate'] = array(2, 1, 0, 1);
                $this_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
                $this_robot->update_session();

            }
            // Otherwise if targetting a team mate
            else {

                // Target this robot's self
                $this_battle->queue_sound_effect('small-buff-received');
                $this_robot->robot_frame = 'base';
                $this_robot->update_session();
                $this_attachment->target_options_update($this_attachment_info['attachment_create']);
                $temp_ally_robot->trigger_target($temp_ally_robot, $this_attachment);

                // Attach this ability attachment to the robot using it
                $this_attachment_info['ability_frame_animate'] = array(0, 1, 2, 1);
                $temp_ally_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
                $temp_ally_robot->update_session();

            }

        }
        // Else if the ability flag was set, reinforce the shield by one more duration point
        else {

            // If this robot is targetting itself
            if ($this_robot->robot_id == $temp_ally_robot->robot_id){

                // Collect the attachment from the robot to back up its info
                $this_attachment_info = $this_robot->robot_attachments[$this_attachment_token];
                $this_attachment_info['attachment_duration'] = $base_attachment_duration;
                $this_attachment_info['attachment_damage_input_breaker'] = $this_attachment_info['attachment_damage_input_breaker'] * $base_attachment_multiplier;
                $this_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
                $this_robot->update_session();

                // Target the opposing robot
                $this_battle->queue_sound_effect('summon-positive');
                $this_battle->queue_sound_effect('small-buff-received');
                $this_ability->target_options_update(array(
                    'frame' => 'summon',
                    'success' => array(9, 85, -10, -10, $this_robot->print_name().' refreshed the '.$this_ability->print_name().'!<br /> The duration of the shield\'s protection has been extended!')
                    ));
                $this_robot->trigger_target($this_robot, $this_ability);

            }
            // Otherwise if targetting a team mate
            else {

                // Collect the attachment from the robot to back up its info
                $this_attachment_info = $temp_ally_robot->robot_attachments[$this_attachment_token];
                $this_attachment_info['attachment_duration'] = $base_attachment_duration;
                $this_attachment_info['attachment_damage_input_breaker'] = $this_attachment_info['attachment_damage_input_breaker'] * $base_attachment_multiplier;
                $temp_ally_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
                $temp_ally_robot->update_session();

                // Target the opposing robot
                $this_battle->queue_sound_effect('summon-positive');
                $this_battle->queue_sound_effect('small-buff-received');
                $this_attachment->target_options_update(array(
                    'frame' => 'summon',
                    'success' => array(9, 85, -10, -10, $this_robot->print_name().' refreshed the '.$this_ability->print_name().' in front of '.$temp_ally_robot->print_name().'!<br /> The duration of the shield\'s protection has been extended!')
                    ));
                $this_robot->trigger_target($this_robot, $this_attachment);

            }

        }

        // Either way, update this ability's settings to prevent recovery
        $this_attachment->damage_options_update($this_attachment_info['attachment_destroy'], true);
        $this_attachment->recovery_options_update($this_attachment_info['attachment_destroy'], true);
        $this_attachment->update_session();

        // Return true on success
        return true;

    }
);
?>
