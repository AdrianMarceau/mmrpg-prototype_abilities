<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define a quick function for inflicting a weakness on a given target
        $inflict_weakness_to_type = function($target_robot, $type)
            use ($this_battle, $this_robot, $this_ability){
            // Return early if the typeis empty of they're already weak to it
            if (empty($type)){ return false; }
            if ($target_robot->has_weakness($type)){ return false; }
            // Inflict the weakness on the target by adding it to the appropriate lists
            $weaknesses = $target_robot->get_weaknesses();
            $weaknesses[] = $type;
            $target_robot->set_weaknesses($weaknesses);
            $target_robot->set_base_weaknesses($weaknesses);
            // If the target had a resistance to this type, make sure we remove it
            $resistances = $target_robot->get_resistances();
            if (in_array($type, $resistances)){
                $resistances = array_diff($resistances, array($type));
                $target_robot->set_resistances($resistances);
                $target_robot->set_base_resistances($resistances);
                }
            // If the target had an immunity to this type, make sure we remove it
            $immunities = $target_robot->get_immunities();
            if (in_array($type, $immunities)){
                $immunities = array_diff($immunities, array($type));
                $target_robot->set_immunities($immunities);
                $target_robot->set_base_immunities($immunities);
                }
            // If the target had an affinity to this type, make sure we remove it
            $affinities = $target_robot->get_affinities();
            if (in_array($type, $affinities)){
                $affinities = array_diff($affinities, array($type));
                $target_robot->set_affinities($affinities);
                $target_robot->set_base_affinities($affinities);
                }
            // Collect the type info so we can know its colour values
            $type_info = rpg_type::get_index_info($type);
            $type_hsl = cms_image::color_rgb2hsl(array_values($type_info['type_colour_dark']));
            $sepia_hue = 30;
            $type_matched_hue = ceil($type_hsl['h'] - $sepia_hue);
            // Print a message showing that this effect is taking place
            $prefix = '';
            $target_robot->set_frame('defend');
            $target_robot->set_frame_styles('filter: sepia(1) saturate(2) hue-rotate('.$type_matched_hue.'deg); ');
            $this_battle->queue_sound_effect('debuff-received');
            $this_battle->events_create($target_robot, false, $this_robot->robot_name.'\'s '.$this_ability->ability_name,
                $prefix.$target_robot->print_name().' feels <em>really</em> weird! <br />'.
                ucfirst($target_robot->get_pronoun('subject')).' suddenly found '.$target_robot->get_pronoun('reflexive').' weak to the '.rpg_type::print_span($type).' type!',
                array(
                    'this_ability' => $this_ability,
                    'canvas_show_this_ability_overlay' => false,
                    'canvas_show_this_ability_underlay' => false,
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $target_robot->player->player_side,
                    'event_flag_camera_focus' => $target_robot->robot_position,
                    'event_flag_camera_depth' => $target_robot->robot_key
                    )
                );
            $target_robot->reset_frame();
            $target_robot->reset_frame_styles();
            $this_battle->events_create($target_robot, false, '', '',
                array(
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $target_robot->player->player_side,
                    'event_flag_camera_focus' => $target_robot->robot_position,
                    'event_flag_camera_depth' => $target_robot->robot_key
                    )
                );
            };

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token.'_'.$target_robot->robot_id;
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 0,
            'ability_frame_animate' => array(0),
            'ability_frame_offset' => array('x' => -20, 'y' => 80, 'z' => -10)
            );

        // Target the opposing robot
        $this_battle->queue_sound_effect('zephyr-sound');
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 0, 60, -10, $this_robot->print_name().' prepares the '.$this_ability->print_name().' technique!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Target the opposing robot
        $this_battle->queue_sound_effect('hyper-slide-sound');
        $this_ability->target_options_update(array(
            'frame' => 'slide',
            'kickback' => array(120, 0, 0),
            'success' => array(1, -100, 0, -110, $this_robot->print_name().' lunges at the target!', 2)
            ));
        $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));

        // Move the user forward so it looks like their swining the weapon
        $this_robot->set_frame('defend');
        $this_robot->set_frame_offset('x', 120);
        $this_robot->set_frame_offset('z', 100);
        $this_robot->set_frame_styles('transform: scaleX(-1); -moz-transform: scaleX(-1); -webkit-transform: scaleX(-1); ');

        // Inflict damage on the opposing robot
        $this_robot->set_frame('throw');
        $this_battle->queue_sound_effect('ambush-sound');
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(15, 0, 0),
            'success' => array(1, -60, 10, -10, 'The '.$this_ability->print_name().' pierced the target!', 2),
            'failure' => array(1, -30, 10, -10, 'The '.$this_ability->print_name().' missed the target&hellip;', 2)
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(2, 0, 0),
            'success' => array(1, -60, 10, -10, 'The '.$this_ability->print_name().' nibbled at the target!', 2),
            'failure' => array(1, -30, 10, -10, 'The '.$this_ability->print_name().' missed the target&hellip;', 2)
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

        // Reset the offset and move the user back to their position
        $this_robot->reset_frame();
        $this_robot->reset_frame_offset();
        $this_robot->reset_frame_styles();

        // If this attack was successful, remove the target's held item from use (not permanently)
        if (!empty($this_robot->robot_base_core)
            && $this_ability->ability_results['this_result'] != 'failure'
            && !empty($this_ability->ability_results['this_amount'])
            && $target_robot->robot_energy > 0
            && $target_robot->robot_status != 'disabled'){

            // Inflict the target with a weakness to the user's own type
            $core_type = $this_robot->robot_base_core;
            $this_robot->set_frame('taunt');
            $inflict_weakness_to_type($target_robot, $core_type);
            $this_robot->reset_frame();

            // Also inflict the target with a little venom to make like-typed attacks do even more
            $this_attachment_token = 'ability_'.$this_ability->ability_token.'_venom';
            $this_attachment_multiplier = 1.5;
            if ($target_robot->has_attachment($this_attachment_token)){
                $this_attachment_info = $target_robot->get_attachment($this_attachment_token);
                $this_attachment_info['attachment_damage_input_booster_'.$core_type] += 0.5;
            } else {
                $this_attachment_info = array(
                    'class' => 'ability',
                    'ability_id' => $this_ability->ability_id,
                    'ability_token' => $this_ability->ability_token,
                    'ability_image' => false,
                    'attachment_token' => $this_attachment_token,
                    'attachment_damage_input_booster_'.$core_type => $this_attachment_multiplier
                    );
            }
            $target_robot->set_attachment($this_attachment_token, $this_attachment_info);

        }

        // Now that all the damage has been dealt, allow the player to check for disabled
        $target_player->check_robots_disabled($this_player, $this_robot);

        // Return true on success
        return true;

    }
);
?>
