<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Change this ability's image based on the holding robot's type
        $this_ability->ability_image = $this_ability->ability_base_image;
        if ($this_robot->robot_token == 'acid-man'
            && $this_robot->robot_image != $this_robot->robot_token){
            $alt = trim(str_replace($this_robot->robot_token, '', $this_robot->robot_image), '_');
            if ($alt == 'alt'){ $this_ability->ability_image .= '-2'; }
            elseif ($alt == 'alt2'){ $this_ability->ability_image .= '-3'; }
            elseif ($alt == 'alt9'){ $this_ability->ability_image .= '-4'; }
        }
        $this_ability->update_session();

        // Predefine attachment create and destroy text for later
        $this_create_text = ($target_robot->print_name().' found '.$target_robot->get_pronoun('reflexive').' in a puddle of corrosive fluid!<br /> '.
            $target_robot->print_name().' will take damage at the end of each turn!'
            );
        $this_refresh_text = ('The '.$this_ability->print_name().' extended the corrosive fluid\'s duration!<br /> '.
            'This robot will continue taking damage at the end of each turn!'
            );

        // Define this ability's attachment token
        $static_attachment_key = $target_robot->get_static_attachment_key();
        $static_attachment_duration = 9;
        $this_attachment_info = rpg_ability::get_static_attachment($this_ability, 'acid-glob', $static_attachment_key, $static_attachment_duration);
        $this_attachment_token = $this_attachment_info['attachment_token'];

        // Target the opposing robot
        $this_battle->queue_sound_effect(array('name' => 'wobble-sound', 'volume' => 0.4));
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 125, -5, 10, $this_robot->print_name().' fires an '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'percent' => true,
            'modifiers' => true,
            'kickback' => array(5, 0, 0),
            'success' => array(0, -5, -5, 10, 'The '.$this_ability->print_name().' melts through the target!'),
            'failure' => array(0, -10, -5, -10,'The '. $this_ability->print_name().' missed '.$target_robot->print_name().'&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'percent' => true,
            'modifiers' => true,
            'frame' => 'taunt',
            'kickback' => array(5, 0, 0),
            'success' => array(0, -5, -5, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
            'failure' => array(0, -10, -5, -10, 'The '.$this_ability->print_name().' missed '.$target_robot->print_name().'&hellip;')
            ));
        $energy_damage_amount = ceil($target_robot->robot_base_energy * ($this_ability->ability_damage / 100));
        $trigger_options = $this_attachment_info['attachment_repeat']['options'];
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, true, $trigger_options);

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
                    $this_ability->target_options_update(array(
                        'frame' => 'defend',
                        'success' => array(9, -5, 5, -10, $this_create_text)
                    ));
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
                    if ($target_robot->robot_status != 'disabled'){
                        $this_ability->target_options_update(array(
                            'frame' => 'defend',
                            'success' => array(9, -5, 5, -10, $this_refresh_text)
                            ));
                        $target_robot->trigger_target($target_robot, $this_ability);
                    }
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
        $this_ability->ability_image = $this_ability->ability_base_image;
        if ($this_robot->robot_token == 'acid-man'
            && $this_robot->robot_image != $this_robot->robot_token){
            $alt = trim(str_replace($this_robot->robot_token, '', $this_robot->robot_image), '_');
            if ($alt == 'alt'){ $this_ability->ability_image .= '-2'; }
            elseif ($alt == 'alt2'){ $this_ability->ability_image .= '-3'; }
            elseif ($alt == 'alt9'){ $this_ability->ability_image .= '-4'; }
        }
        $this_ability->update_session();

        // Power up this ability if the user is holding onto the related shield/barrier
        $temp_new_energy = $this_ability->ability_base_energy;
        $temp_new_damage = $this_ability->ability_base_damage;
        foreach ($this_robot->robot_attachments AS $this_attachment_token => $this_attachment_info){
            if (strstr('ability_acid-barrier', $this_attachment_token)){
                $temp_new_energy = 0;
                $temp_new_damage *= 2;
                break;
            }
        }
        $this_ability->set_damage($temp_new_damage);
        $this_ability->set_energy($temp_new_energy);

        // Return true on success
        return true;

        },
    'static_attachment_function_acid-glob' => function($objects, $static_attachment_key, $this_attachment_duration = 99){

        // Extract all objects and config into the current scope
        extract($objects);
        
        // Generate the static attachment info using provided config
        $existing_attachments = isset($this_battle->battle_attachments[$static_attachment_key]) ? count($this_battle->battle_attachments[$static_attachment_key]) : 0;
        $this_ability_token = $this_ability->ability_token;
        $this_attachment_token = 'ability_'.$this_ability_token.'_'.$this_attachment->attachment_token.'_'.$static_attachment_key;
        $this_attachment_image = !empty($this_ability->ability_image) ? $this_ability->ability_image : $this_ability_token;
        $this_attachment_destroy_text = 'The corrosive <span class="ability_name ability_type ability_type_water">Acid Glob</span> below {this_robot} faded away... ';
        $this_attachment_repeat_text = 'The corrosive <span class="ability_name ability_type ability_type_water">Acid Glob</span> melted through {this_robot}\'s armor! ';
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
                'type' => 'water',
                'type2' => '',
                'energy' => 8,
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
            'ability_frame_animate' => array(2, 3),
            'ability_frame_offset' => array(
                'x' => (5 + ($existing_attachments * 2)),
                'y' => (0 + ($existing_attachments * 1)),
                'z' => (10 - $existing_attachments)
                )
            );

        // Return true on success
        return $this_attachment_info;

    }
);
?>
