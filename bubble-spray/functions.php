<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Predefine attachment create and destroy text for later
        $this_create_text = ($target_robot->print_name().' found '.$target_robot->get_pronoun('reflexive').' in a mound of '.rpg_type::print_span('water', 'Foamy Bubbles').'!<br /> '.
            'That position on the field is vulnerable to '.
            rpg_type::print_span('electric').' and '.rpg_type::print_span('freeze').' '.
            'types now!'
            );
        $this_refresh_text = ($this_robot->print_name().' refreshed the mound of '.rpg_type::print_span('water', 'Foamy Bubbles').' below '.$target_robot->print_name().'!<br /> '.
            'That position on the field is still vulnerable to '.
            rpg_type::print_span('electric').' and '.rpg_type::print_span('freeze').' '.
            'types!'
            );

        // Define this ability's attachment token
        $static_attachment_key = $target_robot->get_static_attachment_key();
        $static_attachment_duration = 6;
        //$this_attachment_info = rpg_ability::get_static_foamy_bubbles($static_attachment_key, $static_attachment_duration);
        $this_attachment_info = rpg_ability::get_static_attachment($this_ability->ability_token, 'foamy-bubbles', $static_attachment_key, $static_attachment_duration);
        $this_attachment_token = $this_attachment_info['attachment_token'];

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 120, 5, 10, $this_robot->print_name().' fires the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Apply or re-apply this attachment to the battle field, regardless of the ability's damage/recovery
        $attachment_already_exists = isset($this_battle->battle_attachments[$static_attachment_key][$this_attachment_token]) ? true : false;
        $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token] = $this_attachment_info;
        $this_battle->update_session();

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(5, 0, 0),
            'success' => array(1, 5, -10, 10, 'The '.$this_ability->print_name().' surrounded into the target!'),
            'failure' => array(1, -65, -10, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(5, 0, 0),
            'success' => array(1, 5, -10, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
            'failure' => array(1, -65, -10, -10, 'The '.$this_ability->print_name().' had no effect&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // If the target was not disabled, show the message for the attachment
        if ($target_robot->robot_status != 'disabled'){
            if (!$attachment_already_exists){
                $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(0, -9999, -9999, -9999, $this_create_text)));
                $target_robot->trigger_target($target_robot, $this_ability);
            } else {
                $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(0, -9999, -9999, -9999, $this_refresh_text)));
                $target_robot->trigger_target($target_robot, $this_ability);
            }
        }

        // Return true on success
        return true;

        },
    'static_attachment_function_foamy-bubbles' => function($objects, $static_attachment_key, $this_attachment_duration = 99){

        // Extract all objects and config into the current scope
        extract($objects);
        
        // Generate the static attachment info using provided config
        $existing_attachments = isset($this_battle->battle_attachments[$static_attachment_key]) ? count($this_battle->battle_attachments[$static_attachment_key]) : 0;
        $this_ability_token = $this_ability->ability_token;
        $this_attachment_token = 'ability_'.$this_ability_token.'_'.$this_attachment->attachment_token.'_'.$static_attachment_key;
        $this_attachment_image = $this_ability_token;
				$this_attachment_destroy_text = 'The mound of <span class="ability_name ability_type ability_type_water">Foamy Bubbles</span> below {this_robot} faded away... ';
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability_token,
            'ability_image' => $this_attachment_image,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $this_attachment_duration,
            'attachment_sticky' => true,
            'attachment_damage_input_booster_electric' => 2.0,
            'attachment_damage_input_booster_freeze' => 2.0,
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
            'ability_frame_animate' => array(2, 3),
            'ability_frame_offset' => array(
                'x' => (0 - ($existing_attachments * 4)),
                'y' => (-5),
                'z' => (6 + $existing_attachments)
                )
            ); 

        // Return true on success
        return $this_attachment_info;

    }
);
?>
