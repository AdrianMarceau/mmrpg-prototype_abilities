<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Update the ability's target options and trigger
        $this_battle->queue_sound_effect('hyper-summon-sound');
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(4, 45, -4, 15, $this_robot->print_name().' uses '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Predefine attachment create and destroy text for later
        $this_create_text = ($this_robot->print_name().' protected '.$this_robot->get_pronoun('reflexive').' with a '.rpg_type::print_span('space', 'Lunar Memory').'!<br /> '.
            'The entity blocks all damage <em>once</em> before fading! '
            );
        $this_refresh_text = ($this_robot->print_name().' refreshed the protective '.rpg_type::print_span('space', 'Lunar Memory').'!<br /> '.
            'The entity blocks all damage <em>once</em> before fading! '
            );

        // Define this ability's attachment token and info
        $static_attachment_key = $this_robot->get_static_attachment_key();
        $this_attachment_info = rpg_ability::get_static_attachment($this_ability, 'lunar-memory', $static_attachment_key);
        $this_attachment_token = $this_attachment_info['attachment_token'];

        // Create the attachment object for this ability
        $this_attachment = rpg_game::get_ability($this_battle, $this_player, $this_robot, $this_attachment_info);

        // Check to see if the attachment has already been generated
        $attachment_already_exists = $this_battle->has_attachment($static_attachment_key, $this_attachment_token);

        // If the attachment does not exist yet, we must generate it now
        if (!$attachment_already_exists){

            // Save this attachment to the battle field at the current position
            //$this_battle->events_create(false, false, 'debug', 'onabilityfunction');
            $this_battle->set_attachment($static_attachment_key, $this_attachment_token, $this_attachment_info);

            // Target this robot's self and trigger the appropriate text
            $this_ability->target_options_update(array('frame' => 'summon', 'success' => array(0, -9999, -9999, -9999, $this_create_text)));
            $this_robot->trigger_target($this_robot, $this_ability);

        }
        // Otherwise, if attachment already exists, we can release it toward the target for damage
        else {

            // Update the ability's target options and trigger
            $this_battle->queue_sound_effect('no-effect');
            $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(0, -9999, -9999, -9999, '...but a memory is already active!')));
            $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));
            return;

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

        // Define this ability's attachment token and info
        $static_attachment_key = $this_robot->get_static_attachment_key();
        $this_attachment_token = rpg_ability::get_static_attachment_token($this_ability, 'lunar-memory', $static_attachment_key);

        // Check to see if the attachment has already been generated
        $attachment_already_exists = $this_battle->has_attachment($static_attachment_key, $this_attachment_token);

        // If the attachment already exists, reduce the weapon energy to zero
        if ($attachment_already_exists){ $this_ability->set_energy(0); }
        // Otherwise, return the weapon energy back to default
        else { $this_ability->reset_energy(); }

        // Return true on success
        return true;

    },
    'static_attachment_function_lunar-memory' => function($objects, $static_attachment_key = 0, $this_attachment_duration = 99){

        // Extract all objects and config into the current scope
        extract($objects);

        // Generate the static attachment info using provided config
        $existing_attachments = isset($this_battle->battle_attachments[$static_attachment_key]) ? count($this_battle->battle_attachments[$static_attachment_key]) : 0;
        $effect_multiplier = 1 - ($this_ability->ability_recovery2 / 100);
        $this_ability_token = $this_ability->ability_token;
        $this_attachment_token = 'ability_'.$this_ability_token.'_'.$this_attachment->attachment_token.'_'.$static_attachment_key;
        $this_attachment_image = $this_ability_token;
        $this_attachment_destroy_text = 'The '.rpg_type::print_span('space', 'Lunar Memory').' in front of {this_robot} faded away... ';
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability_token,
            'ability_image' => $this_attachment_image,
            'attachment_sticky' => true,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $this_attachment_duration,
            'attachment_damage_input_breaker' => $effect_multiplier,
            'attachment_weaknesses' => array('*'),
            'attachment_weaknesses_trigger' => 'target',
            'attachment_destroy' => array(
                'trigger' => 'special',
                'kind' => '',
                'type' => '',
                'percent' => true,
                'modifiers' => false,
                'frame' => 'defend',
                'rates' => array(100, 0, 0),
                'success' => array(5, 40, -4, 15, $this_attachment_destroy_text),
                'failure' => array(5, 40, -4, 15, $this_attachment_destroy_text)
                ),
            'ability_frame' => 0,
            'ability_frame_animate' => array(0,1,2,3,0,1,2,3,0,1,2,3,0,1,2,3,4),
            'ability_frame_offset' => array('x' => 45, 'y' => -4, 'z' => 15)
            );

        // Return true on success
        return $this_attachment_info;

    }
);
?>
