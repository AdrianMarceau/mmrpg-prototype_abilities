<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Lock the user into the shoot frame
        $this_robot->set_frame('shoot');

        // Target the opposing robot and launch the fireball (Frame 0: Wind-up)
        $this_battle->queue_sound_effect('fireball-sound');
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'kickback' => array(20, 0, 0),
            'success' => array(0, 90, 60, 10, $this_robot->print_name().' fires the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Check to see if there's a Super Block at this position
        $static_attachment_info = array();
        $static_ability_token = 'super-arm';
        $static_ability_object_token = 'super-block';
        $static_key = $target_robot->get_static_attachment_key();
        $static_attachment_token = 'ability_'.$static_ability_token.'_'.$static_ability_object_token.'_'.$static_key;
        if (!empty($this_battle->battle_attachments[$static_key][$static_attachment_token])){
            $static_attachment_info = $this_battle->battle_attachments[$static_key][$static_attachment_token];
            if (!isset($static_attachment_info['ability_frame_styles'])){ $static_attachment_info['ability_frame_styles'] = ''; }
            $static_attachment_info['attachment_weaknesses'][] = '*';
            $static_attachment_info['ability_frame_styles'] .= 'opacity: 0.5; ';
            $static_attachment_info['ability_frame_styles'] .= 'transform: translate('.($target_player->player_side == 'left' ? '-30%' : '30%').', 0); ';
            
            // Safely update the battle attachment using the modern setter
            $this_battle->set_attachment($static_key, $static_attachment_token, $static_attachment_info);
        }

        // Inflict damage on the opposing robot (Frame 1: Impact)
        $this_battle->queue_sound_effect('blast-sound');
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(25, 0, 0),
            'success' => array(1, 0, 0, 10, 'The '.$this_ability->print_name().' crashed down on the target!'),
            'failure' => array(1, 0, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(15, 0, 0),
            'success' => array(1, 0, 0, 10, 'The '.$this_ability->print_name().' warmed the target!'),
            'failure' => array(1, 0, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        
        // Trigger damage but defer the disabled check so the follow-through can render
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

        // Check to see if there's still a Super Block at this position, and completely destroy it
        if (!empty($this_battle->battle_attachments[$static_key][$static_attachment_token])){
            $static_attachment_info = $this_battle->battle_attachments[$static_key][$static_attachment_token];
            if ($this_ability->ability_results['this_result'] != 'failure'){ $static_attachment_info['attachment_duration'] = 0; }
            else { $static_attachment_info['ability_frame_styles'] = ''; }
            
            // Safely update the battle attachment using the modern setter
            $this_battle->set_attachment($static_key, $static_attachment_token, $static_attachment_info);
        }

        // Attach Frame 2 behind the target to show the dragon flying past
        $this_attachment_token_fx = 'ability_'.$this_ability->ability_token.'_fx';
        $this_attachment_info_fx = array(
            'class' => 'ability',
            'attachment_token' => $this_attachment_token_fx,
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 2,
            'ability_frame_animate' => array(2),
            'ability_frame_offset' => array('x' => -150, 'y' => 0, 'z' => 10)
            );
            
        $this_event_options = array(
            'event_flag_camera_action' => true,
            'event_flag_camera_side' => $target_robot->player->player_side,
            'event_flag_camera_focus' => $target_robot->robot_position,
            'event_flag_camera_depth' => $target_robot->robot_key,
            'event_flag_camera_offset' => 0,
            );

        // Display the blank event for the follow-through visual, then unset it
        $target_robot->set_attachment($this_attachment_token_fx, $this_attachment_info_fx);
        $this_battle->events_create(false, false, '', '', $this_event_options);
        $target_robot->unset_attachment($this_attachment_token_fx);

        // Return the user to their base frame
        $this_robot->set_frame('base');
        
        // Now that the visual sequence is completely finished, run the disabled check
        $target_player->check_robots_disabled($this_player, $this_robot);

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If the user has Extended Range, allow bench targeting
        if ($this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Return true on success
        return true;

    }
);
?>
