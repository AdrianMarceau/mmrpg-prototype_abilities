<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Update the ability's target options and trigger
        $this_battle->queue_sound_effect('bubble-sound');
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 120, 0, 10, $this_robot->print_name().' generates a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(2, -60, 0, 10, 'The '.$this_ability->print_name().' crashed into the target!'),
            'failure' => array(1, -125, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(2, -60, 0, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
            'failure' => array(1, -125, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // If the ability was a success and this robot's life energy is less than full
        if ($target_robot->robot_status !== 'disabled'
            && ($this_ability->ability_results['this_result'] === 'failure'
                || empty($this_ability->ability_results['this_amount']))){

            // Define the attachment token and information for this ability's effect
            $this_attachment_token = 'ability_'.$this_ability->ability_token.'_fx';
            $this_attachment_info = array(
                'class' => 'ability',
                'attachment_token' => $this_attachment_token,
                'ability_token' => $this_ability->ability_token,
                'ability_image' => $this_ability->ability_base_image,
                'ability_frame' => 3,
                'ability_frame_animate' => array(3, 4, 5),
                'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => 10),
                'ability_frame_styles' => 'transform: scaleX(-1); ',
                'ability_frame_classes' => ' '
                );
            $this_event_options = array(
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $target_robot->player->player_side,
                'event_flag_camera_focus' => $target_robot->robot_position,
                'event_flag_camera_depth' => $target_robot->robot_key,
                'event_flag_camera_offset' => 0,
                );

            // Show the message about the ability bouncing back again (effect1)
            $this_attachment_info['ability_frame'] = 3;
            $this_attachment_info['ability_frame_offset']['x'] = -250;
            $this_attachment_info['ability_frame_styles'] = 'transform: scaleX(-1); ';
            $target_robot->set_attachment($this_attachment_token, $this_attachment_info);
            $this_battle->events_create(false, false, '', '', $this_event_options);

            // Show the message about the ability bouncing back again (effect2)
            $this_battle->queue_sound_effect('bubble-sound');
            $this_attachment_info['ability_frame'] = 4;
            $this_attachment_info['ability_frame_offset']['x'] = -125;
            $this_attachment_info['ability_frame_styles'] = ' ';
            $target_robot->set_attachment($this_attachment_token, $this_attachment_info);
            $this_battle->events_create(false, false, '', '', $this_event_options);

            // Inflict damage on the opposing robot again
            $target_robot->unset_attachment($this_attachment_token);
            $target_robot->set_frame_offset('x', 60);
            $this_ability->set_frame_styles('transform: scaleX(-1); ');
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(15, 0, 0),
                'rates' => array(90, 0, 'auto'),
                'success' => array(5, 60, 0, -10, 'The '.$this_ability->print_name().' crashed into the target this time!'),
                'failure' => array(3, 90, 0, -10, 'The '.$this_ability->print_name().' missed the target again!')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'kickback' => array(5, 0, 0),
                'rates' => array(90, 0, 'auto'),
                'success' => array(5, 60, 0, -10, 'The '.$this_ability->print_name().' was absorbed by the target this!'),
                'failure' => array(3, 90, 0, -10, 'The '.$this_ability->print_name().' missed the target again!')
                ));
            $energy_damage_amount = $this_ability->ability_damage * 2;
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
            $target_robot->reset_frame_offset();
            $this_ability->reset_frame_styles();

        }

        // Return true on success
        return true;

    }
);
?>
