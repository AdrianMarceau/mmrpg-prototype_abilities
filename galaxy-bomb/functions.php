<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Predefine attachment create and destroy text for later
        $this_create_text = ($target_robot->print_name().' found '.$target_robot->get_pronoun('reflexive').' in front of a '.rpg_type::print_span('space_explode', 'Black Hole').'!<br /> '.
            $target_robot->print_name().' will take damage at the end of each turn!'
            );
        $this_refresh_text = ($this_robot->print_name().' refreshed the '.rpg_type::print_span('space_explode', 'Black Hole').' behind '.$target_robot->print_name().'!<br /> '.
            'That position on the field will continue to take end-of-turn damage!'
            );
        $this_destroy_text = ($this_robot->print_name().' sent out a new '.rpg_type::print_span('space_explode', 'Black Hole').' toward '.$target_robot->print_name().'!<br /> '.
            'Oh wow! The two '.rpg_type::print_span('space_explode', 'Black Holes').' cancelled each other out!'
            );

        // Define this ability's attachment token
        $static_attachment_key = $target_robot->get_static_attachment_key();
        $static_attachment_duration = 5;
        $this_attachment_info = rpg_ability::get_static_attachment($this_ability, 'black-hole', $static_attachment_key, $static_attachment_duration);
        $this_attachment_token = $this_attachment_info['attachment_token'];

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 120, 0, 10, $this_robot->print_name().' fires the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Attach the ability to the target if not disabled
        if ($this_ability->ability_results['this_result'] != 'failure'){

            // If the ability flag was not set, attach the hazard to the target position
            if (!isset($this_battle->battle_attachments[$static_attachment_key][$this_attachment_token])){

                // Attach this ability attachment to the robot using it
                $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token] = $this_attachment_info;
                $this_battle->update_session();

                // Target this robot's self
                if ($target_robot->robot_status != 'disabled'){
                    $this_robot->robot_frame = 'base';
                    $this_robot->update_session();
                    $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(0, -9999, -9999, -9999, $this_create_text)));
                    $target_robot->trigger_target($target_robot, $this_ability);
                }

            }
            // Else if the ability flag was set, this black hole cancels out the other one and removes it!
            else {

                // Collect the attachment from the robot to back up its info
                $this_attachment_info = $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token];
                if (empty($this_attachment_info['attachment_duration'])
                    || $this_attachment_info['attachment_duration'] < $static_attachment_duration){
                    $this_attachment_info['attachment_duration'] = 0;
                    $this_battle->set_attachment($static_attachment_key, $this_attachment_token, $this_attachment_info);
                }
                if ($target_robot->robot_status != 'disabled'){
                    $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(0, -9999, -9999, -9999, $this_destroy_text)));
                    $target_robot->trigger_target($target_robot, $this_ability);
                }
                $this_battle->unset_attachment($static_attachment_key, $this_attachment_token);
                $this_battle->events_create($this_robot, $target_robot, '', '', array(
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $target_robot->player->player_side,
                    'event_flag_camera_focus' => $target_robot->robot_position,
                    'event_flag_camera_depth' => $target_robot->robot_key
                    ));

            }

        }

        // Either way, update this ability's settings to prevent recovery
        $this_ability->damage_options_update($this_attachment_info['attachment_destroy'], true);
        $this_ability->recovery_options_update($this_attachment_info['attachment_destroy'], true);
        $this_ability->update_session();

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

        },
    'static_attachment_function_black-hole' => function($objects, $static_attachment_key, $this_attachment_duration = 99){

        // Extract all objects and config into the current scope
        extract($objects);
        
        // Generate the static attachment info using provided config
        $existing_attachments = isset($this_battle->battle_attachments[$static_attachment_key]) ? count($this_battle->battle_attachments[$static_attachment_key]) : 0;
        $this_ability_token = $this_ability->ability_token;
        $this_attachment_token = 'ability_'.$this_ability_token.'_'.$this_attachment->attachment_token.'_'.$static_attachment_key;
        $this_attachment_image = $this_ability_token;
        $this_attachment_destroy_text = 'The crushing <span class="ability_name ability_type ability_type_space_explode">Black Hole</span> behind {this_robot} faded away... ';
        $this_attachment_repeat_text = 'The <span class="ability_name ability_type ability_type_space_explode">Black Hole</span> behind {this_robot} exerted its power! ';
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability_token,
            'ability_image' => $this_attachment_image,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $this_attachment_duration,
            'attachment_energy' => 0,
            'attachment_energy_base_percent' => $this_ability->ability_damage,
            'attachment_sticky' => true,
            'attachment_destroy' => array(
                'trigger' => 'special',
                'kind' => '',
                'type' => '',
                'percent' => true,
                'modifiers' => false,
                'frame' => 'defend',
                'rates' => array(100, 0, 0),
                'success' => array(9, -9999, -9999, 10, $this_attachment_destroy_text),
                'failure' => array(9, -9999, -9999, 10, $this_attachment_destroy_text)
                ),
            'attachment_repeat' => array(
                'kind' => 'energy',
                'trigger' => 'damage',
                'type' => 'space',
                'type2' => 'explode',
                'energy' => 10,
                'percent' => true,
                'modifiers' => true,
                'frame' => 'damage',
                'rates' => array(100, 0, 0),
                'success' => array(1, -5, 5, -10, $this_attachment_repeat_text),
                'failure' => array(1, -5, 5, -99, $this_attachment_repeat_text),
                'options' => array(
                    'apply_modifiers' => true,
                    'apply_type_modifiers' => true,
                    'apply_core_modifiers' => true,
                    'apply_field_modifiers' => true,
                    'apply_stat_modifiers' => false,
                    'apply_position_modifiers' => false,
                    'referred_damage' => true,
                    'referred_damage_id' => 0,
                    'referred_damage_stats' => array()
                    )
                ),
            'ability_frame' => 1,
            'ability_frame_animate' => array(1, 2, 3, 4, 5, 6, 7, 8, 9),
            'ability_frame_offset' => array(
                'x' => (-5 + ($existing_attachments * 6)),
                'y' => (5 + $existing_attachments),
                'z' => (-10 - $existing_attachments)
                )
            );

        // Return true on success
        return $this_attachment_info;

    }
);
?>
