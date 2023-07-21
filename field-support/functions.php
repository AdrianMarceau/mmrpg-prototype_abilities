<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);
        $mmrpg_index_fields = rpg_field::get_index();
        $this_battle = rpg_battle::get_battle();
        $this_field = rpg_field::get_field();

        // Define field multipliers differently for Copy Core, Elemental Core, and Neutral Core
        $this_field_multipliers = array();
        if ($this_robot->robot_core === 'copy'){

            // Define the field multipliers for when a Copy Core robot uses the ability (multiply existing)
            $this_field_multipliers = !empty($this_field->field_multipliers) ? $this_field->field_multipliers : array();

        } elseif (empty($this_robot->robot_core)){

            // Define the field multipliers for when a Neutral Core robot uses the ability (remove existing)
            foreach ($this_field->field_multipliers AS $temp_type => $temp_multiplier){
                if ($temp_multiplier == 1){ continue; }
                $temp_new_multiplier = $temp_multiplier / ($temp_multiplier * $temp_multiplier);
                $this_field_multipliers[$temp_type] = $temp_new_multiplier;
            }

        } else {

            // Define the field multipliers for when an Elemental Core robot uses the ability (boost core, break weaknesses)
            $field_boost = 2.00;
            $field_break = !in_array($this_robot->robot_core, $this_robot->robot_weaknesses) ? 0.50 : 0.25;
            $this_field_multipliers[$this_robot->robot_core] = $field_boost;
            if (!empty($this_robot->robot_weaknesses)){
                foreach ($this_robot->robot_weaknesses AS $temp_type){
                    if ($temp_type === $this_robot->robot_core){ continue; }
                    $this_field_multipliers[$temp_type] = $field_break;
                }
            }

        }

        // Only continue with the ability if there are multipliers to apply
        if (!empty($this_field_multipliers)){

            // Target this robot's self
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(0, -9999, -9999, -10,
                    $this_robot->print_name().' uses '.$this_robot->get_pronoun('possessive2').' '.$this_ability->print_name().'!<br />'.
                    'The ability altered the conditions of the battle field&hellip;'
                    )
                ));
            $this_robot->trigger_target($this_robot, $this_ability, array('prevent_default_text' => true));

            // Loop through each of the field multipliers collected and apply them to the current conditions
            $temp_modifiers_applied = 0;
            asort($this_field_multipliers);
            $this_field_multipliers = array_reverse($this_field_multipliers);
            foreach ($this_field_multipliers AS $type_token => $type_multiplier){

                // Define the modify and boost parameters for this multiplier
                $type_name = ucfirst($type_token);
                if ($type_multiplier > 1){ $temp_modify_amount = 0.1; }
                elseif ($type_multiplier < 1){ $temp_modify_amount = -0.1; }
                else { $temp_modify_amount = 0; }

                // Only continue if there was a difference to boost
                if (!empty($temp_modify_amount)){

                    // Update the field multipliers accordingly
                    if (!isset($this_field->field_multipliers[$type_token])){ $this_field->field_multipliers[$type_token] = 1; }
                    $temp_first_amount = $this_field->field_multipliers[$type_token];
                    $this_field->field_multipliers[$type_token] = $this_field->field_multipliers[$type_token] * $type_multiplier;
                    if ($this_field->field_multipliers[$type_token] > MMRPG_SETTINGS_MULTIPLIER_MAX){ $this_field->field_multipliers[$type_token] = MMRPG_SETTINGS_MULTIPLIER_MAX; }
                    elseif ($this_field->field_multipliers[$type_token] < MMRPG_SETTINGS_MULTIPLIER_MIN){ $this_field->field_multipliers[$type_token] = MMRPG_SETTINGS_MULTIPLIER_MIN; }
                    // If the new amount was exactly one, remove it alltogether
                    $temp_new_amount = round($this_field->field_multipliers[$type_token], 1);
                    if ($temp_new_amount == 1){ unset($this_field->field_multipliers[$type_token]); }
                    else { $this_field->field_multipliers[$type_token] = $temp_new_amount; }

                    // Update the session with the new field changes
                    $this_field->update_session();

                    // Define the boost or lower percent
                    $temp_change_kind = '';
                    $temp_change_text = '';
                    $temp_change_text2 = '';
                    if ($temp_new_amount > $temp_first_amount){
                        $temp_change = $temp_new_amount - $temp_first_amount;
                        $temp_change_percent = round(($temp_change / $temp_first_amount) * 100);
                        $temp_change_kind = 'boost';
                        $temp_change_text = 'boosted';
                        $temp_change_text2 = 'intensified';
                        //$temp_change_alert = rpg_battle::random_positive_word();
                        $temp_change_alert = $this_player->player_side == 'left' ? rpg_battle::random_positive_word() : rpg_battle::random_negative_word();
                    } elseif ($temp_new_amount < $temp_first_amount){
                        $temp_change = $temp_first_amount - $temp_new_amount;
                        $temp_change_percent = round(($temp_change / $temp_first_amount) * 100);
                        $temp_change_kind = 'break';
                        $temp_change_text = 'reduced';
                        $temp_change_text2 = 'worsened';
                        //$temp_change_alert = rpg_battle::random_positive_word();
                        $temp_change_alert = $this_player->player_side == 'left' ? rpg_battle::random_positive_word() : rpg_battle::random_negative_word();
                    } else {
                        continue;
                    }

                    // Update this robot's frame to a taunt
                    $this_robot->robot_frame = $temp_modify_amount > 0 ? 'taunt' : 'defend';
                    $this_robot->update_session();

                    // CREATE ATTACHMENTS
                    if (true){

                        // Collect the elemental type arrow index
                        $kind = $temp_modify_amount < 0 ? 'break' : 'boost';
                        $this_arrow_index = rpg_prototype::type_arrow_image($kind, $type_token);
                        $this_types_index = rpg_type::get_index(true, false, true, true);

                        // Collect the type colours so we can use them w/ effects
                        $temp_boost_colour_dark = 'rgb('.implode(', ', $this_types_index[$type_token]['type_colour_dark']).')';
                        $temp_boost_colour_light = 'rgb('.implode(', ', $this_types_index[$type_token]['type_colour_light']).')';

                        // Define this ability's attachment token and effect parameters
                        $this_attachment_token = 'ability_effects_'.$this_ability->ability_token;
                        $this_attachment_info = array(
                            'class' => 'ability',
                            'attachment_token' => $this_attachment_token,
                            'ability_token' => $this_ability->ability_token,
                            'ability_image' => $this_arrow_index['image'],
                            'ability_frame' => $this_arrow_index['frame'],
                            'ability_frame_animate' => array($this_arrow_index['frame']),
                            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => -10)
                            );

                        // Define a separate attachment for the background to show a large colour overlay
                        $fx_attachment_token = 'ability_effects_field-support_overlay';
                        $fx_attachment_info = array(
                            'class' => 'ability',
                            'attachment_token' => $fx_attachment_token,
                            'sticky' => true,
                            'ability_token' => $this_ability->ability_token,
                            'ability_image' => '_effects/arrow-overlay_'.$kind.'-2',
                            'ability_frame' => 0,
                            'ability_frame_animate' => array(0),
                            'ability_frame_offset' => array('x' => -5, 'y' => 20, 'z' => -9999),
                            'ability_frame_classes' => 'sprite_fullscreen ',
                            'ability_frame_styles' => 'opacity: 0.6; filter: alpha(opacity=60); background-color: '.$temp_boost_colour_dark.'; '
                            );

                        // Attach this ability attachment to this robot temporarily
                        $this_robot->set_frame($temp_modify_amount > 0 ? 'taunt' : 'defend');
                        $this_robot->set_frame_styles('z-index: 9999; ');
                        $this_robot->set_attachment($this_attachment_token, $this_attachment_info);
                        $this_robot->set_attachment($fx_attachment_token, $fx_attachment_info);

                        // Attach this ability to all robots on this player's side of the field
                        $backup_robots_active = $this_player->values['robots_active'];
                        $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
                        if ($backup_robots_active_count > 0){
                            // Loop through the this's benched robots, inflicting les and less damage to each
                            $this_key = 0;
                            foreach ($backup_robots_active AS $key => $info){
                                if ($info['robot_id'] == $this_robot->robot_id){ continue; }
                                $temp_this_robot = rpg_game::get_robot($this_battle, $this_player, $info);
                                // Attach this ability attachment to the this robot temporarily
                                $temp_this_robot->set_frame($temp_modify_amount > 0 ? 'taunt' : 'defend');
                                $this_key++;
                            }
                        }

                        // Attach this ability to all robots on the target's side of the field
                        $backup_robots_active = $target_player->values['robots_active'];
                        $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
                        if ($backup_robots_active_count > 0){
                            // Loop through the target's benched robots, inflicting les and less damage to each
                            $target_key = 0;
                            foreach ($backup_robots_active AS $key => $info){
                                $temp_target_robot = rpg_game::get_robot($this_battle, $target_player, $info);
                                // Attach this ability attachment to the target robot temporarily
                                $temp_target_robot->set_frame($temp_modify_amount > 0 ? 'taunt' : 'defend');
                                $target_key++;
                            }
                        }

                    }

                    // Create the event to show this multiplier boost or lowering
                    $first_text = '';
                    if ($this_robot->robot_core === 'copy'){ $effect_text = '<span class="ability_name ability_type ability_type_'.$type_token.'">'.$type_name.' Effects</span> were '.$temp_change_text2.'!<br />'; }
                    elseif (!empty($this_robot->robot_core)){ $effect_text = '<span class="ability_name ability_type ability_type_'.$type_token.'">'.$type_name.' Effects</span> were '.$temp_change_text.' by '.$temp_change_percent.'%!<br />'; }
                    else { $effect_text = '<span class="ability_name ability_type ability_type_'.$type_token.'">'.$type_name.' Effects</span> returned to normal!<br />'; }
                    $this_battle->queue_sound_effect(array('name' => 'field-'.$temp_change_kind, 'volume' => 1.5));
                    $this_battle->events_create($this_robot, false, $this_field->field_name.' Multipliers',
                        //$temp_change_alert.' '.$effect_text.
                        $effect_text.
                        'The multiplier is now at <span class="ability_name ability_type ability_type_'.$type_name.'">'.$type_name.' x '.number_format($temp_new_amount, 1).'</span>!',
                        array(
                            'canvas_show_this_ability_overlay' => true,
                            'event_flag_camera_action' => true,
                            'event_flag_camera_side' => $this_robot->player->player_side,
                            'event_flag_camera_focus' => $this_robot->robot_position,
                            'event_flag_camera_depth' => $this_robot->robot_key
                            )
                        );

                    // DESTROY ATTACHMENTS
                    if (true){

                        // Remove this ability from all robots on this player's side of the field
                        $backup_robots_active = $this_player->values['robots_active'];
                        $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
                        if ($backup_robots_active_count > 0){
                            // Loop through the this's benched robots, inflicting les and less damage to each
                            $this_key = 0;
                            foreach ($backup_robots_active AS $key => $info){
                                if ($info['robot_id'] == $this_robot->robot_id){ continue; }
                                $temp_this_robot = rpg_game::get_robot($this_battle, $this_player, $info);
                                // Attach this ability attachment to the this robot temporarily
                                $temp_this_robot->reset_frame();
                                $this_key++;
                            }
                        }

                        // Remove this ability from all robots on the target's side of the field
                        $backup_robots_active = $target_player->values['robots_active'];
                        $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
                        if ($backup_robots_active_count > 0){
                            // Loop through the target's benched robots, inflicting les and less damage to each
                            $target_key = 0;
                            foreach ($backup_robots_active AS $key => $info){
                                if ($info['robot_id'] == $target_robot->robot_id){ continue; }
                                $temp_target_robot = rpg_game::get_robot($this_battle, $target_player, $info);
                                // Attach this ability attachment to the target robot temporarily
                                $temp_target_robot->reset_frame();
                                $target_key++;
                            }
                        }

                    }

                    // Remove this item attachment from this robot
                    $this_robot->reset_frame();
                    $this_robot->reset_frame_styles();
                    $this_robot->unset_attachment($this_attachment_token);
                    $this_robot->unset_attachment($fx_attachment_token);

                    // Update the field multiplier
                    $temp_modifiers_applied++;

                }

            }
            // Otherwise print a nothing happened message
            if (empty($temp_modifiers_applied)){

                // Update the ability's target options and trigger
                $this_ability->target_options_update(array(
                    'frame' => 'defend',
                    'success' => array(0, 0, 0, 10, '&hellip;but nothing happened.')
                    ));
                $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));

            }

            // Update this robot's frame to a base
            $this_robot->reset_frame();

        }
        // Otherwise print a nothing happened message
        else {

            // Target this robot's self
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(0, -9999, -9999, -10,
                    $this_robot->print_name().' uses '.$this_robot->get_pronoun('possessive2').' '.$this_ability->print_name().'!'
                    )
                ));
            $this_robot->trigger_target($this_robot, $this_ability, array('prevent_default_text' => true));

            // Target this robot's self
            $this_ability->target_options_update(array(
                'frame' => 'defend',
                'success' => array(0, -9999, -9999, -10,
                    '&hellip;but nothing happened!'
                    )
                ));
            $this_robot->trigger_target($this_robot, $this_ability, array('prevent_default_text' => true));

        }

        // Return true on success
        return true;

        }
);
?>
