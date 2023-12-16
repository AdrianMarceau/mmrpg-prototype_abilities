<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Predefine attachment create and destroy text for later
        $this_create_text = ($target_robot->print_name().' found '.$target_robot->get_pronoun('reflexive').' standing on '.rpg_type::print_span('freeze', 'Frozen Spikes').'!<br /> '.
            $target_robot->print_name().' will take damage at the end of each turn!'
            );
        $this_refresh_text = ($this_robot->print_name().' re-chilled the '.rpg_type::print_span('freeze', 'Frozen Spikes').' below '.$target_robot->print_name_s().' feet!<br /> '.
            $target_robot->print_name().' will continue taking damage at the end of each turn!'
            );

        // Define this ability's attachment token
        $static_attachment_key = $target_robot->get_static_attachment_key();
        $static_attachment_duration = 12;
        $this_attachment_info = rpg_ability::get_static_attachment($this_ability, 'frozen-spikes', $static_attachment_key, $static_attachment_duration);
        $this_attachment_token = $this_attachment_info['attachment_token'];

        // Target the opposing robot
        $this_battle->queue_sound_effect('blowing-sound');
        $this_ability->target_options_update(array(
            'frame' => $this_robot->robot_token == 'chill-man' ? 'taunt' : 'shoot',
            'success' => array(0, 110, 0, 10, $this_robot->print_name().' uses the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_battle->queue_sound_effect('ice-sound');
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(5, 0, 0),
            'success' => array(1, 5, -10, 10, 'The '.$this_ability->print_name().' pierced into the target!'),
            'failure' => array(0, -65, -10, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(1, 5, -10, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
            'failure' => array(0, -65, -10, -10, 'The '.$this_ability->print_name().' had no effect&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Attach the ability to the target if not disabled
        if ($this_ability->ability_results['this_result'] != 'failure'){
            $this_battle->queue_sound_effect('ice-sound');

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
                    $target_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));
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
                    $target_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));
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

        // If the user has Extended Range, allow bench targeting
        if ($this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Return true on success
        return true;

        },
    'static_attachment_function_frozen-spikes' => function($objects, $static_attachment_key, $this_attachment_duration = 99){

        // Extract all objects and config into the current scope
        extract($objects);

        // Generate the static attachment info using provided config
        $existing_attachments = isset($this_battle->battle_attachments[$static_attachment_key]) ? count($this_battle->battle_attachments[$static_attachment_key]) : 0;
        $this_ability_token = $this_ability->ability_token;
        $this_attachment_token = 'ability_'.$this_ability_token.'_'.$this_attachment->attachment_token.'_'.$static_attachment_key;
        $this_attachment_image = $this_ability_token;
        $this_attachment_destroy_text = 'The <span class="ability_name ability_type ability_type_freeze">Frozen Spikes</span> below {this_robot} faded away... ';
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability_token,
            'ability_image' => $this_attachment_image,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $this_attachment_duration,
            'attachment_sticky' => true,
            'attachment_weaknesses' => array('flame', 'laser'),
            'attachment_weaknesses_trigger' => 'either',
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
                'type' => 'water',
                'type2' => '',
                'energy' => 6,
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
            'ability_frame' => 2,
            'ability_frame_animate' => array(2, 3, 2, 4),
            'ability_frame_offset' => array(
                'x' => (0 + ($existing_attachments * 8)),
                'y' => (-5 + $existing_attachments),
                'z' => (8 + $existing_attachments)
                )
            );

        // Return true on success
        return $this_attachment_info;

    }
);
?>
