<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Update the ability's image in the session
        $this_sprite_sheet = $this_robot->robot_pseudo_token === 'cold-man' ? 2 : 1;
        $this_sprite_image = $this_ability->ability_token.($this_sprite_sheet > 1 ? '-'.$this_sprite_sheet : '');
        //error_log('$this_sprite_sheet: '.$this_sprite_sheet);
        //error_log('$this_sprite_image: '.$this_sprite_image);

        // Upper-case object name while being sensitive to of/the/a/etc.
        $this_object_name = 'ice wall';
        $this_object_name = ucwords($this_object_name);
        $this_object_name = str_replace(array(' A ', ' An ', ' Of ', ' The '), array(' a ', ' an ', ' of ', ' the '), $this_object_name);
        $this_object_name_span = rpg_type::print_span('freeze_impact', $this_object_name);

        // Define this ability's attachment token
        $static_attachment_key = $this_robot->get_static_attachment_key();
        $static_attachment_duration = 10;
        $this_attachment_info = rpg_ability::get_static_attachment($this_ability, 'ice-wall', $static_attachment_key, $static_attachment_duration);
        $this_attachment_token = $this_attachment_info['attachment_token'];
        $this_attachment_info['ability_image'] = $this_sprite_image;

        // Create the attachment object for this ability
        $this_attachment = rpg_game::get_ability($this_battle, $this_player, $this_robot, $this_attachment_info);
        $this_attachment->set_image($this_attachment_info['ability_image']);

        // Update the image of the actual ability so it matches
        $this_ability->set_image($this_attachment_info['ability_image']);

        // Check if this ability is already summoned to the field
        $is_summoned = isset($this_battle->battle_attachments[$static_attachment_key][$this_attachment_token]) ? true : false;

        // If the user has Quick Charge, auto-summon the ability
        if ($this_robot->has_attribute('quick-charge')){ $is_summoned = true; }

        // If the ability flag was not set, this ability begins charging
        if (!$is_summoned){

            // Attach this ability attachment to the battle field itself
            //$this_attachment_info['ability_frame_styles'] = 'opacity: 0.5; ';
            $this_attachment_info['ability_frame_styles'] = 'transform: scale(0.5) translate(0, 50%); ';
            $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token] = $this_attachment_info;
            $this_battle->update_session();

            // Target this robot's self
            $this_battle->queue_sound_effect('spawn-sound');
            $trigger_options = array();
            $trigger_options['prevent_default_text'] = true;
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(0, -9999, -9999, 0, $this_robot->print_name().' uses the '.$this_ability->print_name().' technique! ')
                ));
            $this_robot->trigger_target($target_robot, $this_ability, $trigger_options);

            // Attach this ability attachment to the battle field itself
            $this_attachment_info['ability_frame_styles'] = '';
            $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token] = $this_attachment_info;
            $this_battle->update_session();

            // Target this robot's self
            $this_battle->queue_sound_effect('smack-sound');
            $this_battle->queue_sound_effect(array('name' => 'ice-sound', 'delay' => 200));
            $trigger_options = array();
            $trigger_options['prevent_default_text'] = true;
            $this_ability->target_options_update(array(
                'frame' => 'defend',
                'success' => array(1, -9999, -9999, 0, 'The '.$this_ability->print_name().' raised '.
                    (preg_match('/^(a|e|i|o|u)/i', $this_object_name) ? 'an ' : 'a ').
                    $this_object_name_span.
                    ' as a shield!<br /> '.
                    'Damage from incoming attacks will be reduced!'
                    )
                ));
            $this_robot->trigger_target($target_robot, $this_ability, $trigger_options);

        }
        // Else if the ability flag was set, the block is thrown and the attachment goes away
        else {

            // Remove this ability attachment from the battle field itself
            unset($this_battle->battle_attachments[$static_attachment_key][$this_attachment_token]);
            $this_battle->update_session();

            // Target the opposing robot
            $this_battle->queue_sound_effect('swing-sound');
            $trigger_options = array();
            $this_ability->target_options_update(array(
                'frame' => 'throw',
                'kickback' => array(15, 0, 0),
                'success' => array(2, 100, 0, 10, $this_ability->print_name().' pushes the '.$this_object_name_span.'!')
                ));
            $this_robot->trigger_target($target_robot, $this_ability, $trigger_options);

            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(20, 0, 0),
                'success' => array(4, -30, 0, 10, 'The '.$this_object_name_span.' crashed into the target!'),
                'failure' => array(2, -90, 0, -10, 'The '.$this_object_name_span.' missed the target&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'kickback' => array(0, 0, 0),
                'success' => array(4, -30, 0, 10, 'The '.$this_object_name_span.' crashed into the target!'),
                'failure' => array(2, -90, 0, -10, 'The '.$this_object_name_span.' missed the target&hellip;')
                ));
            $energy_damage_amount = $this_ability->ability_damage;
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

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

        // Define this ability's attachment token
        $static_attachment_key = $this_robot->get_static_attachment_key();
        $this_ability_token = $this_ability->ability_token;
        $this_object_token = 'ice-wall';
        $this_attachment_token = 'ability_'.$this_ability_token.'_'.$this_object_token.'_'.$static_attachment_key;

        // Check if this ability is already summoned to the field
        $is_summoned = isset($this_battle->battle_attachments[$static_attachment_key][$this_attachment_token]) ? true : false;
        //error_log('$is_summoned = '.($is_summoned ? 'true' : 'false'));

        // Check if this ability has a true core-match
        $is_corematch = $this_robot->robot_core == $this_ability->ability_type ? true : false;
        //error_log('$is_corematch = '.($is_corematch ? 'true' : 'false'));

        // If the summon flag had already been set, reduce the weapon energy to zero
        if ($is_summoned){ $this_ability->set_energy(0); }
        // Otherwise, return the weapon energy back to default
        else { $this_ability->reset_energy(); }
        //error_log('$this_ability->ability_energy = '.print_r($this_ability->ability_energy, true));

        // If the user has Quick Charge, auto-charge the ability
        if ($this_robot->has_attribute('quick-charge')){ $is_summoned = true; }

        // If the summon flag had already been set, make sure the ability is slower
        if ($is_summoned){
            $this_ability->set_speed($this_ability->ability_base_speed * -1);
            $this_ability->set_speed2($this_ability->ability_base_speed2 * -1);
        }
        // Otherwise, return the weapon energy back to default
        else {
            $this_ability->reset_speed();
            $this_ability->reset_speed2();
        }
        //error_log('$this_ability->ability_speed = '.print_r($this_ability->ability_speed, true));
        //error_log('$this_ability->ability_speed2 = '.print_r($this_ability->ability_speed2, true));

        // If the ability is already summoned and is core-match or Target Module, allow bench targeting
        if ($is_summoned && ($is_corematch || $this_robot->has_attribute('extended-range'))){ $this_ability->set_target('select_target'); }
        else { $this_ability->set_target('auto'); }
        //error_log('$this_ability->ability_target = '.print_r($this_ability->ability_target, true));

        // Update the ability's image in the session
        $this_sprite_sheet = $this_robot->robot_pseudo_token === 'cold-man' ? 2 : 1;
        $this_ability->set_image($this_ability->ability_token.($this_sprite_sheet > 1 ? '-'.$this_sprite_sheet : ''));
        //error_log('$this_ability->ability_image = '.print_r($this_ability->ability_image, true));

        // Return true on success
        return true;

    },
    'static_attachment_function_ice-wall' => function($objects, $static_attachment_key, $this_attachment_duration = 99){

        // Extract all objects and config into the current scope
        extract($objects);

        // Collect details for this particular super block based on the current field
        $this_sprite_sheet = 1; //$this_robot->robot_pseudo_token === 'cold-man' ? 2 : 1;
        $this_object_token = 'ice-wall';
        $this_object_name = 'ice wall';

        $this_object_name = ucwords($this_object_name);
        $this_object_name = str_replace(array(' A ', ' An ', ' Of ', ' The '), array(' a ', ' an ', ' of ', ' the '), $this_object_name);
        $this_object_name_span = rpg_type::print_span('freeze_impact', $this_object_name);

        // Generate the static attachment info using provided config
        $existing_attachments = isset($this_battle->battle_attachments[$static_attachment_key]) ? count($this_battle->battle_attachments[$static_attachment_key]) : 0;
        $show_field_position = strstr($static_attachment_key, 'active') ? 'active' : 'bench';
        $show_block_behind = $show_field_position === 'active' && strstr($static_attachment_key, 'left') ? true : false;
        $this_ability_token = $this_ability->ability_token;
        $this_attachment_token = 'ability_'.$this_ability_token.'_'.$this_object_token.'_'.$static_attachment_key;
        $this_attachment_image = $this_ability_token.($this_sprite_sheet > 1 ? '-'.$this_sprite_sheet : '');
        $this_attachment_destroy_text = 'The protective '.$this_object_name_span.' in front of {this_robot} faded away... ';
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability_token,
            'ability_image' => $this_attachment_image,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $this_attachment_duration,
            'attachment_sticky' => true,
            'attachment_damage_input_breaker' => 0.50,
            'attachment_weaknesses' => array('electric', 'flame'),
            'attachment_weaknesses_trigger' => 'target',
            'attachment_destroy' => array(
                'trigger' => 'special',
                'kind' => '',
                'type' => '',
                'percent' => true,
                'modifiers' => false,
                'frame' => 'defend',
                'rates' => array(100, 0, 0),
                'success' => array(3, -9999, -9999, 10, $this_attachment_destroy_text),
                'failure' => array(3, -9999, -9999, 10, $this_attachment_destroy_text)
                ),
            'ability_frame' => 0,
            'ability_frame_animate' => array(0),
            'ability_frame_offset' => array(
                'x' => (($show_field_position === 'active' ? 55 : 45) + ($existing_attachments * 8)),
                'y' => ($show_block_behind ? 2 : -2),
                'z' => ($show_block_behind ? -20 : (2 + $existing_attachments))
                )
            );

        // Return true on success
        return $this_attachment_info;

    }
);
?>
