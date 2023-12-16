<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // -- ABILITY SETUP -- //

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_wind_fx_token = $this_attachment_token.'_fx-wind';
        $this_attachment_wind_fx_info = array(
            'class' => 'ability',
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_base_image,
            'ability_frame' => 0,
            'ability_frame_animate' => array(0, 1),
            'ability_frame_offset' => array('x' => 0, 'y' => 5, 'z' => -10),
            'ability_frame_classes' => ' '
            );
        $this_attachment_ice_fx_token = $this_attachment_token.'_fx-ice';
        $this_attachment_ice_fx_info = array(
            'class' => 'ability',
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_base_image,
            'ability_frame' => 2,
            'ability_frame_animate' => array(2),
            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => 10),
            'ability_frame_classes' => ' '
            );

        // Define a function for looping through both player's robots and set them into their damage frames
        $bulk_apply_frames = function($player, $filter = array(), $frame = '', $offset = 0){
            $robots = $player->get_robots_active();
            foreach ($robots AS $robot){
                if (!empty($filter) && !in_array($robot->robot_id, $filter)){ continue; }
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
        $bulk_apply_attachments = function($player, $filter = array(), $token, $info = array()){
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


        // -- SUMMON THE STORM -- //

        // Target the opposing robot
        $this_battle->queue_sound_effect('ice-sound');
        $this_battle->queue_sound_effect(array('name' => 'blowing-sound', 'delay' => 100));
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(3, 0, 0, -10, $this_robot->print_name().' summons a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability, array(
            'prevent_default_text' => true,
            'prevent_stats_text' => true
            ));


        // -- GENERATE VISUAL EFFECTS -- //

        // Search for all relevant object attachments on the battlefield
        $target_robots_active = $target_player->get_robots_active();
        $ability_target_robot_ids = array($target_robot->robot_id);
        if ($this_robot->counters['attack_mods'] >= MMRPG_SETTINGS_STATS_MOD_MAX){
            foreach ($target_robots_active AS $robot){
                if (in_array($robot->robot_id, $ability_target_robot_ids)){ continue; }
                $ability_target_robot_ids[] = $robot->robot_id;
            }
        }
        //error_log('$ability_target_robot_ids: '.print_r($ability_target_robot_ids, true));

        // Define the event options for the camera
        $animate_event_options = array('event_flag_camera_action' => false);
        if (count($ability_target_robot_ids) === 1){
            $animate_event_options = array(
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $target_robot->player->player_side,
                'event_flag_camera_focus' => $target_robot->robot_position,
                'event_flag_camera_depth' => $target_robot->robot_key
                );
        }

        // Generate a blank frame to show the effects we've applied above
        $this_robot->set_frame('summon');
        $this_battle->queue_sound_effect('whirlwind-sound');
        $bulk_apply_frames($target_player, $ability_target_robot_ids, 'damage', -10);
        $bulk_apply_attachments($target_player, $ability_target_robot_ids, $this_attachment_wind_fx_token, $this_attachment_wind_fx_info);
        $this_battle->events_create(false, false, '', '', $animate_event_options);
        $this_battle->queue_sound_effect('ice-sound');
        $bulk_apply_frames($target_player, $ability_target_robot_ids, 'damage', -10);
        $bulk_apply_attachments($target_player, $ability_target_robot_ids, $this_attachment_ice_fx_token, $this_attachment_ice_fx_info);
        $this_battle->events_create(false, false, '', '', $animate_event_options);

        // Reset the robots on both sides back to their base frames and remove the attachments
        $this_robot->reset_frame();
        $bulk_apply_frames($target_player, $ability_target_robot_ids, '');
        $bulk_apply_attachments($target_player, $ability_target_robot_ids, $this_attachment_wind_fx_token);


        // -- DAMAGE TARGETS -- //

        // Loop through the target's robots and deal damage to any that have been filtered
        $this_attachment_ice_fx_info['ability_frame'] = 3;
        $this_attachment_ice_fx_info['ability_frame_animate'] = array(3);
        foreach ($target_robots_active AS $robot){
            if (!in_array($robot->robot_id, $ability_target_robot_ids)){ continue; }
            $this_battle->queue_sound_effect('ice-sound');
            $robot->set_attachment($this_attachment_ice_fx_token, $this_attachment_ice_fx_info);
            $this_ability->ability_results_reset();
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'modifiers' => true,
                'kickback' => array(5, 0, 0),
                'success' => array(9, -5, 0, 99, 'The '.$this_ability->print_name().' freeze dries the target!'),
                'failure' => array(9, -5, 0, 99, 'The attack had no effect on '.$robot->print_name().'&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'modifiers' => true,
                'frame' => 'taunt',
                'kickback' => array(5, 0, 0),
                'success' => array(9, -5, 0, 9, 'The '.$this_ability->print_name().'\'s ice was enjoyed by the target!'),
                'failure' => array(9, -5, 0, 9, 'The attack had no effect on '.$robot->print_name().'&hellip;')
                ));
            $energy_damage_amount = $this_ability->ability_damage;
            $robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
            $robot->unset_attachment($this_attachment_ice_fx_token);
            }

        // Now that all the damage has been dealt, allow the player to check for disabled
        $target_player->check_robots_disabled($this_player, $this_robot);

        // Return true on success
        return true;

        }
);
?>
