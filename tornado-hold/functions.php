<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Predefine attachment create and destroy text for later
        $this_create_text = ($target_robot->print_name().' found '.$target_robot->get_pronoun('reflexive').' trapped inside a '.rpg_type::print_span('wind', 'Tornado Hold').'!<br /> '.
            $target_robot->print_name().' is prevented from switching and will take damage!'
            );
        $this_refresh_text = ($this_robot->print_name().' refreshed the '.rpg_type::print_span('wind', 'Tornado Hold').' trapping '.$target_robot->print_name().'!<br /> '.
            $target_robot->print_name().' is still prevented from switching and taking damage!'
            );

        // Define this ability's attachment tokens
        $static_attachment_key = $target_robot->get_static_attachment_key();
        $static_attachment_duration = 3;
        
        $this_propeller_info = rpg_ability::get_static_attachment($this_ability, 'tornado-hold-propeller', $static_attachment_key, $static_attachment_duration);
        $this_propeller_token = $this_propeller_info['attachment_token'];
        
        $this_wind_info = rpg_ability::get_static_attachment($this_ability, 'tornado-hold-wind', $static_attachment_key, $static_attachment_duration);
        $this_wind_token = $this_wind_info['attachment_token'];

        // Target the opposing robot
        $this_battle->queue_sound_effect('blowing-sound');
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 120, -10, 10, $this_robot->print_name().' launches a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Attach the ability to the target if not disabled
        if ($this_ability->ability_results['this_result'] != 'failure'){
            $this_battle->queue_sound_effect('whirlwind-sound');

            // If the ability flag was not set, attach the hazard to the target position
            if (!isset($this_battle->battle_attachments[$static_attachment_key][$this_propeller_token])){

                // Attach both parts of the tornado hold to the field
                $this_battle->battle_attachments[$static_attachment_key][$this_propeller_token] = $this_propeller_info;
                $this_battle->battle_attachments[$static_attachment_key][$this_wind_token] = $this_wind_info;
                $this_battle->update_session();

                // Target this robot's self to display the trapped message
                if ($target_robot->robot_status != 'disabled'){
                    $this_robot->robot_frame = 'base';
                    $this_robot->update_session();
                    $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(0, -9999, -9999, -9999, $this_create_text)));
                    $target_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));
                }

            }
            // Else if the ability flag was set, reinforce the hazard by resetting its duration
            else {

                // Collect the propeller attachment from the robot to back up its info
                $this_propeller_info = $this_battle->battle_attachments[$static_attachment_key][$this_propeller_token];
                if (empty($this_propeller_info['attachment_duration'])
                    || $this_propeller_info['attachment_duration'] < $static_attachment_duration){
                    $this_propeller_info['attachment_duration'] = $static_attachment_duration;
                    $this_battle->battle_attachments[$static_attachment_key][$this_propeller_token] = $this_propeller_info;
                }
                
                // Collect the wind attachment from the robot to back up its info and update duration
                $this_wind_info = $this_battle->battle_attachments[$static_attachment_key][$this_wind_token];
                if (empty($this_wind_info['attachment_duration'])
                    || $this_wind_info['attachment_duration'] < $static_attachment_duration){
                    $this_wind_info['attachment_duration'] = $static_attachment_duration;
                    $this_battle->battle_attachments[$static_attachment_key][$this_wind_token] = $this_wind_info;
                }
                
                $this_battle->update_session();
                
                if ($target_robot->robot_status != 'disabled'){
                    $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(0, -9999, -9999, -9999, $this_refresh_text)));
                    $target_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));
                }

            }

        }

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
    'static_attachment_function_tornado-hold-propeller' => function($objects, $static_attachment_key, $this_attachment_duration = 99){

        // Extract all objects and config into the current scope
        extract($objects);
        
        // Generate the static attachment info using provided config
        $existing_attachments = isset($this_battle->battle_attachments[$static_attachment_key]) ? count($this_battle->battle_attachments[$static_attachment_key]) : 0;
        $this_ability_token = $this_ability->ability_token;
        $this_attachment_token = 'ability_'.$this_ability_token.'_'.$this_attachment->attachment_token.'_'.$static_attachment_key;
        $this_attachment_image = $this_ability_token;
        $this_attachment_destroy_text = 'The <span class="ability_name ability_type ability_type_wind">Tornado Hold</span> trapping {this_robot} faded away... ';
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability_token,
            'ability_image' => $this_attachment_image,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $this_attachment_duration,
            'attachment_sticky' => true,
            'attachment_switch_disabled' => true,
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
            'ability_frame' => 2,
            'ability_frame_animate' => array(2, 3, 4),
            'ability_frame_offset' => array(
                'x' => (0 + ($existing_attachments * 8)),
                'y' => (-5 + $existing_attachments),
                'z' => (10 + $existing_attachments)
                )
            );

        // Return true on success
        return $this_attachment_info;

    },
    'static_attachment_function_tornado-hold-wind' => function($objects, $static_attachment_key, $this_attachment_duration = 99){

        // Extract all objects and config into the current scope
        extract($objects);
        
        // Generate the static attachment info using provided config
        $existing_attachments = isset($this_battle->battle_attachments[$static_attachment_key]) ? count($this_battle->battle_attachments[$static_attachment_key]) : 0;
        $this_ability_token = $this_ability->ability_token;
        $this_attachment_token = 'ability_'.$this_ability_token.'_'.$this_attachment->attachment_token.'_'.$static_attachment_key;
        $this_attachment_image = $this_ability_token;
        $this_attachment_repeat_text = 'The <span class="ability_name ability_type ability_type_wind">Tornado Hold</span> battered {this_robot} with violent winds! ';
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
                'success' => array(9, -9999, -9999, -10, ''),
                'failure' => array(9, -9999, -9999, -10, '')
                ),
            'attachment_repeat' => array(
                'kind' => 'energy',
                'trigger' => 'damage',
                'type' => 'wind',
                'type2' => '',
                'energy' => 20,
                'percent' => true,
                'modifiers' => true,
                'frame' => 'damage',
                'rates' => array(100, 0, 0),
                'success' => array(5, -5, 5, -10, $this_attachment_repeat_text),
                'failure' => array(5, -5, 5, -99, $this_attachment_repeat_text),
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
            'ability_frame' => 5,
            'ability_frame_animate' => array(5, 6, 7),
            'ability_frame_offset' => array(
                'x' => (0 + ($existing_attachments * 8)),
                'y' => (20 + $existing_attachments),
                'z' => (-10 - $existing_attachments)
                )
            );

        // Return true on success
        return $this_attachment_info;

    }
);
?>
