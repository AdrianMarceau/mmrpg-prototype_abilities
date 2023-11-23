<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_info = array(
            'class' => 'ability',
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_base_image,
            'ability_frame' => 2,
            'ability_frame_animate' => array(2, 3),
            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => 100),
            'ability_frame_classes' => ' '
            );

        // Change the image to the full-screen rain effect
        $this_ability->ability_image = $this_ability->ability_base_image;
        $this_ability->ability_frame_classes = '';
        $this_ability->update_session();

        // Target the opposing robot
        $this_battle->queue_sound_effect('ice-sound');
        $this_battle->queue_sound_effect(array('name' => 'ice-sound', 'delay' => 200));
        $this_battle->queue_sound_effect(array('name' => 'ice-sound', 'delay' => 400));
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 0, 100, 10, $this_robot->print_name().' summons a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true, 'prevent_stats_text' => true));

        // Change the image to the full-screen rain effect
        $this_ability->ability_image = $this_ability->ability_base_image.'-2';
        $this_ability->ability_frame_classes = 'sprite_fullscreen ';
        $this_ability->update_session();

        // Define a quick function for inflicting a weakness on a given target
        $prefixes = array();
        $prefixes[] = 'Brrrr, that\'s cold!';
        $prefixes[] = 'Wow, what a chill!';
        $prefixes[] = 'Achoo!';
        $prefixes[] = 'Wow! So icy!';
        $prefixes[] = 'Frigid!';
        $prefixes[] = 'Way too cold!';
        $prefixes[] = 'Popsicle!';
        shuffle($prefixes);
        $inflict_weakness_to_type = function($target_robot, $type)
            use ($this_battle, $this_robot, $this_ability, &$prefixes){
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
            // Print a message showing that this effect is taking place
            $prefix = !empty($prefixes) ? array_shift($prefixes).' ' : '';
            $target_robot->set_frame('defend');
            $target_robot->set_frame_styles('filter: sepia(1) saturate(2) hue-rotate(180deg); ');
            $this_battle->queue_sound_effect('debuff-received');
            $this_battle->events_create($target_robot, false, $this_robot->robot_name.'\'s '.$this_ability->ability_name,
                $prefix.$target_robot->print_name().' feels <em>super</em> cold! <br />'.
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

        // -- DAMAGE TARGETS -- //

        // Define a variable to keep track of which targets to inflict weaknesses on
        $inflict_weaknesses_on_targets = array();

        // Inflict damage on the opposing robot
        $this_battle->queue_sound_effect(array('name' => 'ice-sound', 'volume' => 0.3));
        $num_hits_counter = 0;
        $this_robot->set_frame('throw');
        $target_robot->set_attachment($this_attachment_token.'_fx', $this_attachment_info);
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'modifiers' => true,
            'kickback' => array(5, 0, 0),
            'success' => array(0, -5, 0, 99, 'The hailstorm battered the target with ice!'),
            'failure' => array(0, -5, 0, -10,'The '. $this_ability->print_name().' missed the first target&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'modifiers' => true,
            'frame' => 'taunt',
            'kickback' => array(5, 0, 0),
            'success' => array(0, -5, 0, 9, 'The hailstorm was absorbed by the target!'),
            'failure' => array(0, -5, 0, 9, 'The '.$this_ability->print_name().' had no effect on the first target&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false, 'apply_stat_modifiers' => true);
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false, $trigger_options);
        $target_robot->unset_attachment($this_attachment_token.'_fx');
        $num_hits_counter++;

        // Inflict a weakness if the ability was successful and actually did damage
        if ($target_robot->robot_status != 'disabled'
            && $this_ability->ability_results['this_result'] != 'failure'
            && !empty($this_ability->ability_results['this_amount'])){
            $inflict_weaknesses_on_targets[] = $target_robot;
        }

        // Loop through the target's benched robots, inflicting damage to each
        $backup_target_robots_active = $target_player->values['robots_active'];
        foreach ($backup_target_robots_active AS $key => $info){
            if ($info['robot_id'] == $target_robot->robot_id){ continue; }
            $this_battle->queue_sound_effect(array('name' => 'ice-sound', 'volume' => 0.3));
            $this_robot->set_frame($num_hits_counter % 2 === 0 ? 'defend' : 'taunt');
            $temp_target_robot = rpg_game::get_robot($this_battle, $target_player, $info);
            $temp_target_robot->set_attachment($this_attachment_token.'_fx', $this_attachment_info);
            $this_ability->ability_results_reset();
            $temp_positive_word = rpg_battle::random_positive_word();
            $temp_negative_word = rpg_battle::random_negative_word();
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'modifiers' => true,
                'kickback' => array(5, 0, 0),
                'success' => array(($key % 2), -5, 0, 99, ($target_player->player_side === 'right' ? $temp_positive_word : $temp_negative_word).' The attack hit another robot!'),
                'failure' => array(($key % 2), -5, 0, 99, 'The attack had no effect on '.$temp_target_robot->print_name().'&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'modifiers' => true,
                'frame' => 'taunt',
                'kickback' => array(5, 0, 0),
                'success' => array(($key % 2), -5, 0, 9, ($target_player->player_side === 'right' ? $temp_negative_word : $temp_positive_word).' The attack was absorbed by the target!'),
                'failure' => array(($key % 2), -5, 0, 9, 'The attack had no effect on '.$temp_target_robot->print_name().'&hellip;')
                ));
            $energy_damage_amount = $this_ability->ability_damage;
            $temp_target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false, $trigger_options);
            $temp_target_robot->unset_attachment($this_attachment_token.'_fx');
            $num_hits_counter++;

            // Inflict a weakness if the ability was successful and actually did damage
            if ($target_robot->robot_status != 'disabled'
                && $this_ability->ability_results['this_result'] != 'failure'
                && !empty($this_ability->ability_results['this_amount'])){
                $inflict_weaknesses_on_targets[] = $temp_target_robot;
            }

        }

        // Return the user to their base frame
        $this_robot->set_frame('base');

        // REMOVE ATTACHMENTS
        if (true){

            // Attach this ability to all robots on this player's side of the field
            $backup_robots_active = $this_player->values['robots_active'];
            $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
            if ($backup_robots_active_count > 0){
                $this_key = 0;
                foreach ($backup_robots_active AS $key => $info){
                    if ($info['robot_id'] == $this_robot->robot_id){ continue; }
                    $info2 = array('robot_id' => $info['robot_id'], 'robot_token' => $info['robot_token']);
                    $temp_this_robot = rpg_game::get_robot($this_battle, $this_player, $info2);
                    $temp_this_robot->robot_frame = 'base';
                    unset($temp_this_robot->robot_attachments[$this_attachment_token]);
                    $temp_this_robot->update_session();
                    $this_key++;
                }
            }

        }

        // Now that all the damage has been dealt, allow the player to check for disabled
        $target_player->check_robots_disabled($this_player, $this_robot);

        // If any robots survived, make sure we inflict weaknesses on them
        foreach ($inflict_weaknesses_on_targets AS $temp_target_robot){
            if ($temp_target_robot->robot_status === 'disabled'){ continue; }
            $inflict_weakness_to_type($temp_target_robot, $this_ability->ability_type);
        }

        // Change the image to the full-screen rain effect
        $this_ability->ability_image = $this_ability->ability_base_image;
        $this_ability->ability_frame_classes = '';
        $this_ability->update_session();

        // Return true on success
        return true;

        }
);
?>
