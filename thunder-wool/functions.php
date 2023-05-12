<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Change this ability's image based on the holding robot's type
        if ($this_robot->robot_core == 'electric'){ $this_ability->ability_image = $this_ability->ability_base_image; }
        else { $this_ability->ability_image = $this_ability->ability_base_image.'-2'; }
        $this_ability->update_session();

        // Predefine attachment create and destroy text for later
        $this_create_text = ($target_robot->print_name().' found '.$target_robot->get_pronoun('reflexive').' under a '.rpg_type::print_span('electric', 'Woolly Cloud').'!<br /> '.
            $target_robot->print_name().' will take damage at the end of each turn!'
            );
        $this_refresh_text = ($this_robot->print_name().' refreshed the '.rpg_type::print_span('electric', 'Woolly Cloud').' above '.$target_robot->print_name().'!<br /> '.
            'That position on the field will continue to take end-of-turn damage!'
            );

        // Define this ability's attachment token
        $static_attachment_key = $target_robot->get_static_attachment_key();
        $static_attachment_duration = 3;
        $this_attachment_info = rpg_ability::get_static_attachment($this_ability, 'woolly-cloud', $static_attachment_key, $static_attachment_duration);
        $this_attachment_token = $this_attachment_info['attachment_token'];

        // Update the attachment image if a special robot is using it
        $this_attachment_info['ability_image'] = $this_ability->ability_image;

        // Target the opposing robot
        if ($this_robot->robot_token == 'sheep-man'){
            $this_ability->target_options_update(array('frame' => 'summon', 'success' => array(1, 120, 0, 10, $this_robot->print_name().' releases '.$this_robot->get_pronoun('possessive2').' '.$this_ability->print_name().'!')));
        } else {
            $this_ability->target_options_update(array('frame' => 'shoot', 'success' => array(1, 120, -40, 10, $this_robot->print_name().' launches a '.$this_ability->print_name().'!')));
        }
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
            // Else if the ability flag was set, reinforce the hazard by one more duration point
            else {

                // Collect the attachment from the robot to back up its info
                $this_attachment_info = $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token];
                if (empty($this_attachment_info['attachment_duration'])
                    || $this_attachment_info['attachment_duration'] < $static_attachment_duration){
                    $this_attachment_info['attachment_duration'] = $static_attachment_duration;
                    $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token] = $this_attachment_info;
                    $this_battle->update_session();
                }
                if ($target_robot->robot_status != 'disabled'){
                    $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(0, -9999, -9999, -9999, $this_refresh_text)));
                    $target_robot->trigger_target($target_robot, $this_ability);
                }

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

        // Change this ability's image based on the holding robot's type
        if ($this_robot->robot_core == 'electric'){ $this_ability->ability_image = $this_ability->ability_base_image; }
        else { $this_ability->ability_image = $this_ability->ability_base_image.'-2'; }
        $this_ability->update_session();

        // Return true on success
        return true;

        },
    'static_attachment_function_woolly-cloud' => function($objects, $static_attachment_key, $this_attachment_duration = 99){

        // Extract all objects and config into the current scope
        extract($objects);
        
        // Generate the static attachment info using provided config
        $existing_attachments = isset($this_battle->battle_attachments[$static_attachment_key]) ? count($this_battle->battle_attachments[$static_attachment_key]) : 0;
        $this_ability_token = $this_ability->ability_token;
        $this_attachment_token = 'ability_'.$this_ability_token.'_'.$this_attachment->attachment_token.'_'.$static_attachment_key;
        $this_attachment_image = $this_ability_token;
        $this_attachment_destroy_text = 'The thunderous '.rpg_type::print_span('electric', 'Woolly Cloud').' above {this_robot} faded away... ';
        $this_attachment_repeat_text = 'The '.rpg_type::print_span('electric', 'Woolly Cloud').' above {this_robot} released a lightning bolt! ';
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
                'type' => 'electric',
                'type2' => '',
                'energy' => 15,
                'percent' => true,
                'modifiers' => true,
                'frame' => 'damage',
                'rates' => array(100, 0, 0),
                'success' => array(9, -5, 30, 10, $this_attachment_repeat_text),
                'failure' => array(9, -5, 30, 10, $this_attachment_repeat_text),
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
            'ability_frame' => 0,
            'ability_frame_animate' => array(0, 2, 4, 1, 3, 5),
            'ability_frame_offset' => array(
                'x' => (-5 + ($existing_attachments * 2)),
                'y' => (40 + ($existing_attachments * 1)),
                'z' => (-8 - $existing_attachments)
                )
            );    

        // Return true on success
        return $this_attachment_info;

    }
);
?>
