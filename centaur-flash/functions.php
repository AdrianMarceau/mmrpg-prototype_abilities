<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Create an options object for this function and populate
        $options = rpg_game::new_options_object();
        $extra_objects = array('options' => $options);
        $extra_objects['this_ability'] = $this_ability;

        // Target the opposing robot
        $this_battle->queue_sound_effect('cosmic-sound');
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 0, 60, 10, $this_robot->print_name().' triggers the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));

        // Define and attach this ability's blackout attachment
        $this_blackout_token = 'ability_'.$this_ability->ability_token.'_blackout';
        $this_blackout_info = array(
            'class' => 'ability',
            'ability_id' => $this_ability->ability_id.'_fx',
            'ability_token' => $this_ability->ability_token,
            'ability_image' => '_effects/black-overlay',
            'ability_frame' => 0,
            'ability_frame_animate' => array(0),
            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => -100),
            'ability_frame_classes' => 'sprite_fullscreen ',
            'ability_frame_styles' => 'opacity: 1.0; filter: alpha(opacity=100); '
            );
        $this_blackout = rpg_game::get_ability($this_battle, $this_player, $this_robot, $this_blackout_info);
        $this_robot->set_attachment($this_blackout_token, $this_blackout_info);

        // As long as this effect is enabled, we should place the sparkle on the field
        $static_attachment_key = $this_robot->get_static_attachment_key();
        $static_attachment_token = $static_attachment_key.'_ability_'.$this_ability->ability_token;
        $static_flash_attachment_token = $static_attachment_token.'_flash';
        $static_flash_attachment_info = array(
            'class' => 'ability',
            'attachment_token' => $static_attachment_token,
            'attachment_duration' => 3,
            'ability_id' => $this_ability->ability_id,
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_token.'-2',
            'ability_frame' => 0,
            'ability_frame_animate' => array(0,1),
            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => -100),
            'ability_frame_classes' => 'sprite_fullscreen ',
            'ability_frame_styles' => 'opacity: 1.0; filter: alpha(opacity=100); ',
            );
        $this_battle->set_attachment($static_attachment_key, $static_flash_attachment_token, $static_flash_attachment_info);

        // Define a function for looping through both player's robots and set them into their damage frames
        $bulk_apply_frames = function($player, $frame = '', $offset = 0, $exception = ''){
            $robots = $player->get_robots_active();
            foreach ($robots AS $robot){
                if ($robot->robot_id === $exception){ continue; }
                if (!empty($frame)){
                    $robot->set_frame($frame);
                    $robot->set_frame_offset('x', $offset);
                    $robot->set_frame_styles('filter: sepia(1) saturate(3) hue-rotate(60deg); ');
                    } else {
                    $robot->reset_frame();
                    $robot->reset_frame_offset();
                    $robot->reset_frame_styles();
                    }
                }
            };

        // Generate a blank frame to show the effect
        $this_battle->queue_sound_effect('shining-sound');
        $this_robot->set_frame('taunt');
        $bulk_apply_frames($this_player, 'damage', -10, $this_robot->robot_id);
        $bulk_apply_frames($target_player, 'damage', -10);
        $this_battle->events_create(false, false, '', '', array(
            'event_flag_camera_action' => false
            ));

        // Generate a blank frame to show the effect
        $this_battle->queue_sound_effect('hyper-stomp-sound');
        $this_robot->set_frame('defend');
        $bulk_apply_frames($this_player, 'defend', -5, $this_robot->robot_id);
        $bulk_apply_frames($target_player, 'defend', -5);
        $this_battle->events_create(false, false, '', '', array(
            'event_flag_camera_action' => true,
            'event_flag_camera_side' => $this_robot->player->player_side,
            'event_flag_camera_focus' => $this_robot->robot_position,
            'event_flag_camera_depth' => $target_robot->robot_key,
            ));

        // Loop through both player's robots and set them into their damage frames
        $bulk_apply_frames($this_player, '');
        $bulk_apply_frames($target_player, '');

        // Now we should loop through and actually disable all skills on the battlefield
        $bulk_disable_skills = function($player, $other_player) use ($this_robot, $extra_objects){
            $robots = $player->get_robots_active();
            foreach ($robots AS $robot){
                // Trigger this robot's custom function if one has been defined for this context
                $extra_objects['this_player'] = $player;
                $extra_objects['this_robot'] = $robot;
                $extra_objects['target_player'] = $other_player;
                $extra_objects['target_robot'] = $this_robot;
                $robot->trigger_custom_function('rpg-skill_disable-skill_before', $extra_objects);
                $robot->set_counter('skill_disabled', 3);
                }
            };
        $bulk_disable_skills($this_player, $target_player);
        $bulk_disable_skills($target_player, $this_player);

        // Generate a blank frame to show the effect
        $this_battle->queue_sound_effect('hyper-stomp-sound');
        $this_robot->set_frame('defend');
        $header = $this_robot->robot_name.'\'s '.$this_ability->ability_name;
        $body = 'The '.$this_ability->print_name().' disabled all skills on the battlefield!';
        $this_battle->events_create($this_robot, false, $header, $body, array(
            'event_flag_camera_action' => true,
            'event_flag_camera_side' => $target_robot->player->player_side,
            'event_flag_camera_focus' => $target_robot->robot_position,
            'event_flag_camera_depth' => $target_robot->robot_key,
            ));
        $this_robot->reset_frame();

        // Remove the blackout attachment from the robot
        $this_robot->unset_attachment($this_blackout_token);

        // Return true on success
        return true;

    }
);
?>
