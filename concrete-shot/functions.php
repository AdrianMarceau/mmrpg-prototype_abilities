<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Predefine attachment create and refresh text for later
        $this_create_text = ($target_robot->print_name().' found '.$target_robot->get_pronoun('reflexive').' trapped inside a '.rpg_type::print_span('earth', 'Concrete Block').'!<br /> '.
            $target_robot->print_name().' is prevented from switching!'
            );
        $this_refresh_text = ($this_robot->print_name().' refreshed the '.rpg_type::print_span('earth', 'Concrete Block').' trapping '.$target_robot->print_name().'!<br /> '.
            $target_robot->print_name().' is still prevented from switching!'
            );

        // Define this ability's attachment token
        $static_attachment_key = $target_robot->get_static_attachment_key();
        $static_attachment_duration = 2;
        $this_attachment_info = rpg_ability::get_static_attachment($this_ability, 'concrete-block', $static_attachment_key, $static_attachment_duration);
        $this_attachment_token = $this_attachment_info['attachment_token'];

        // Target the opposing robot (Frame 0: Blob Wind-up)
        $this_battle->queue_sound_effect('blob-sound');
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 120, 0, 10, $this_robot->print_name().' fires a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot (Frame 1: Impact)
        $this_battle->queue_sound_effect('smack-sound');
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(15, 0, 0),
            'success' => array(1, -20, 0, 10, 'The '.$this_ability->print_name().' smashed into the target!'),
            'failure' => array(1, -60, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(1, -20, 0, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
            'failure' => array(1, -60, 0, -10, 'The '.$this_ability->print_name().' had no effect&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Attach the concrete block to the target if the attack didn't fail
        if ($this_ability->ability_results['this_result'] != 'failure'){

            // If the ability flag was not set, attach the hazard to the target position
            if (!isset($this_battle->battle_attachments[$static_attachment_key][$this_attachment_token])){

                // Attach this ability attachment to the target's field position
                $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token] = $this_attachment_info;
                $this_battle->update_session();

                // Target this robot's self to show the trapped message
                if ($target_robot->robot_status != 'disabled'){
                    $this_robot->robot_frame = 'base';
                    $this_robot->update_session();
                    $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(2, -9999, -9999, -9999, $this_create_text)));
                    $target_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));
                }

            }
            // Else if the ability flag was set, reinforce the hazard by resetting its duration
            else {

                // Collect the attachment from the robot to back up its info
                $this_attachment_info = $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token];
                if (empty($this_attachment_info['attachment_duration'])
                    || $this_attachment_info['attachment_duration'] < $static_attachment_duration){
                    $this_attachment_info['attachment_duration'] = $static_attachment_duration;
                    $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token] = $this_attachment_info;
                    $this_battle->update_session();
                }
                
                // Show the refresh message
                if ($target_robot->robot_status != 'disabled'){
                    $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(2, -9999, -9999, -9999, $this_refresh_text)));
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
    'static_attachment_function_concrete-block' => function($objects, $static_attachment_key, $this_attachment_duration = 99){

        // Extract all objects and config into the current scope
        extract($objects);
        
        // Generate the static attachment info using provided config
        $existing_attachments = isset($this_battle->battle_attachments[$static_attachment_key]) ? count($this_battle->battle_attachments[$static_attachment_key]) : 0;
        $this_ability_token = $this_ability->ability_token;
        $this_attachment_token = 'ability_'.$this_ability_token.'_'.$this_attachment->attachment_token.'_'.$static_attachment_key;
        $this_attachment_image = $this_ability_token;
        $this_attachment_destroy_text = 'The <span class="ability_name ability_type ability_type_earth">Concrete Block</span> trapping {this_robot} crumbled away... ';
        
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability_token,
            'ability_image' => $this_attachment_image,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $this_attachment_duration,
            'attachment_sticky' => true,
            'attachment_switch_disabled' => true,
            'attachment_weaknesses' => array('impact', 'explode'),
            'attachment_weaknesses_trigger' => 'either',
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
            'ability_frame' => 2, // Solid Block
            'ability_frame_animate' => array(2), 
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
