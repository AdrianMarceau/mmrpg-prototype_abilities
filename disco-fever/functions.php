<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's overlay effect token
        $this_overlay_token = 'effect_'.$this_ability->ability_token.'_'.$target_robot->robot_id;
        $this_overlay_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => 'ability',
            'ability_image' => '_effects/black-overlay',
            'ability_frame' => 0,
            'ability_frame_animate' => array(0, 1),
            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => -20),
            'ability_frame_classes' => 'sprite_fullscreen ',
            'attachment_token' => $this_overlay_token
            );

        // Create the attachment object for this ability
        $this_overlay = rpg_game::get_ability($this_battle, $target_player, $target_robot, $this_overlay_info);

        // Add the black background overlay attachment
        $target_robot->robot_attachments[$this_overlay_token] = $this_overlay_info;
        $target_robot->update_session();

        // Predefine attachment create and destroy text for later
        $this_create_text = ($target_robot->print_name().' found '.$target_robot->get_pronoun('reflexive').' behind a spinning '.rpg_type::print_span('laser', 'Disco Ball').'!<br /> '.
            $target_robot->print_name().'\'s damage output has been compromised!'
            );
        $this_refresh_text = ('The '.rpg_type::print_span('laser', 'Disco Ball').' in front of '.$target_robot->print_name().' keeps spinning!<br /> '.
            ucfirst($target_robot->get_pronoun('possessive2')).' damage output is still compromised!'
            );

        // If there's a hazard on this side already, we're gonna remove it first
        $attachment_was_moved = false;
        $static_attachment_key = $this_robot->get_static_attachment_key();
        $static_attachment_duration = 6;
        $this_attachment_info = rpg_ability::get_static_attachment($this_ability, 'disco-ball', $static_attachment_key, $static_attachment_duration);
        $this_attachment_token = $this_attachment_info['attachment_token'];
        if (isset($this_battle->battle_attachments[$static_attachment_key][$this_attachment_token])){
            $static_attachment_duration = $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token]['attachment_duration'];
            unset($this_battle->battle_attachments[$static_attachment_key][$this_attachment_token]);
            $this_battle->update_session();
            $attachment_was_moved = true;
        }

        // Define this ability's attachment token
        $static_attachment_key = $target_robot->get_static_attachment_key();
        $this_attachment_info = rpg_ability::get_static_disco_ball($static_attachment_key, $static_attachment_duration);
        $this_attachment_token = $this_attachment_info['attachment_token'];

        // Update the attachment image if a special robot is using it
        $static_attachment_image = in_array($this_robot->robot_image, array('disco_alt', 'disco_alt3', 'disco_alt5')) ? $this_ability->ability_token.'-2' : $this_ability->ability_image;
        $this_attachment_info['ability_image'] = $static_attachment_image;

        // Create the attachment object for this ability
        $this_attachment = rpg_game::get_ability($this_battle, $target_player, $target_robot, $this_attachment_info);

        // If the ability flag was not set, attach the hazard to the target position
        if (!isset($this_battle->battle_attachments[$static_attachment_key][$this_attachment_token])){

            // Target this robot's self
            $trigger_options = array();
            $trigger_options['event_flag_sound_effects'] = array(
                array('name' => 'get-weird-item', 'volume' => 1.5)
                );
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(0, -10, 0, -18, $this_robot->print_name().(!$attachment_was_moved ? ' started a ' : ' moved the ').$this_ability->print_name().'!')
                ));
            $this_robot->trigger_target($this_robot, $this_ability, $trigger_options);

            // Attach this ability attachment to the robot using it
            $this_attachment_info['ability_frame_animate'] = array(0, 1, 2, 1);
            $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token] = $this_attachment_info;
            $this_battle->update_session();

            // Target this robot's self
            $trigger_options = array();
            $trigger_options['prevent_default_text'] = true;
            $trigger_options['event_flag_sound_effects'] = array(
                array('name' => 'full-screen-down', 'volume' => 1.5)
                );
            $this_robot->robot_frame = 'base';
            $this_robot->update_session();
            $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(0, -9999, -9999, -9999, $this_create_text)));
            $target_robot->trigger_target($target_robot, $this_ability, $trigger_options);

        }
        // Else if the ability flag was set, reinforce the fever by one more duration point
        else {

            // Target this robot's self
            $trigger_options = array();
            $trigger_options['prevent_default_text'] = true;
            $trigger_options['event_flag_sound_effects'] = array(
                array('name' => 'full-screen-down', 'volume' => 1.5)
                );
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(0, -10, 0, -18, $this_robot->print_name().' continued the '.$this_ability->print_name().'!')
                ));
            $this_robot->trigger_target($this_robot, $this_ability, $trigger_options);

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

        // Add the black background overlay attachment
        unset($target_robot->robot_attachments[$this_overlay_token]);
        $target_robot->update_session();

        // Either way, update this ability's settings to prevent recovery
        $this_attachment->damage_options_update($this_attachment_info['attachment_destroy'], true);
        $this_attachment->recovery_options_update($this_attachment_info['attachment_destroy'], true);
        $this_attachment->update_session();

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If the user has Extended Range, allow bench targeting
        if ($this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Update the ability image if the user is in their alt image
        $alt_image_triggers = array('disco_alt', 'disco_alt3', 'disco_alt5');
        if (in_array($this_robot->robot_image, $alt_image_triggers)){ $this_ability->set_image($this_ability->ability_token.'-2'); }

        // Return true on success
        return true;

        },
    'static_attachment_function_disco-ball' => function($objects, $static_attachment_key, $this_attachment_duration = 99){

        // Extract all objects and config into the current scope
        extract($objects);
        
        // Generate the static attachment info using provided config
        $existing_attachments = isset($this_battle->battle_attachments[$static_attachment_key]) ? count($this_battle->battle_attachments[$static_attachment_key]) : 0;
        $this_ability_token = $this_ability->ability_token;
        $this_attachment_token = 'ability_'.$this_ability_token.'_'.$this_attachment->attachment_token.'_'.$static_attachment_key;
        $this_attachment_image = $this_ability_token;
        $this_attachment_destroy_text = 'The spinning <span class="ability_name ability_type ability_type_laser">Disco Ball</span> in front of {this_robot} faded away... ';
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability_token,
            'ability_image' => $this_attachment_image,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $this_attachment_duration,
            'attachment_sticky' => true,
            'attachment_damage_output_breaker' => 0.5,
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
            'ability_frame' => 0,
            'ability_frame_animate' => array(0, 1, 2, 1),
            'ability_frame_offset' => array(
                'x' => (70 + ($existing_attachments * 10)),
                'y' => (10),
                'z' => (20 + $existing_attachments)
                )
            );   

        // Return true on success
        return $this_attachment_info;

    }
);
?>
