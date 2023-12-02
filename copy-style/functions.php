<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Collect session token for later
        $session_token = rpg_game::session_token();

        // Check if this ability is already charged
        $is_transformed = !empty($this_robot->robot_persona) ? true : false;

        // Define the frames based on current character
        $temp_ability_frames = array('target' => 0, 'damage' => 1, 'summon' => 2);
        $temp_transform_styles = 'filter: sepia(1) saturate(2) hue-rotate(230deg) brightness(0.8) contrast(2); ';
        if ($this_robot->robot_token == 'mega-man'){
            $temp_ability_frames = array('target' => 0, 'damage' => 1, 'summon' => 2);
            $temp_transform_styles = 'filter: sepia(1) saturate(2) hue-rotate(-170deg) brightness(0.8) contrast(2); ';
        }
        elseif ($this_robot->robot_token == 'bass'){
            $temp_ability_frames = array('target' => 3, 'damage' => 4, 'summon' => 5);
            $temp_transform_styles = 'filter: sepia(1) saturate(4) hue-rotate(-38deg) brightness(0.8) contrast(2); ';
        }
        elseif ($this_robot->robot_token == 'proto-man'){
            $temp_ability_frames = array('target' => 6, 'damage' => 7, 'summon' => 8);
            $temp_transform_styles = 'filter: sepia(1) saturate(2) hue-rotate(-55deg) brightness(0.8) contrast(2); ';
        }

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_id' => $this_attachment_token.'_fx',
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => $temp_ability_frames['target'],
            'ability_frame_animate' => array($temp_ability_frames['target']),
            'ability_frame_offset' => array('x' => -10, 'y' => 35, 'z' => -10)
            );

        // Create an options object for this function and populate
        $options = rpg_game::new_options_object();
        $options->persona_token = '';
        $options->persona_image_token = '';
        $extra_objects = array('options' => $options);
        $extra_objects['this_ability'] = $this_ability;
        $extra_objects['this_player'] = $this_player;
        $extra_objects['this_robot'] = $this_robot;
        $extra_objects['target_player'] = $target_player;
        $extra_objects['target_robot'] = $target_robot;

        // If the user has NOT already transformed, we can COPY style now
        if (!$is_transformed){

            // Attach the ability to this robot
            $this_attachment = rpg_game::get_ability($this_battle, $this_player, $this_robot, $this_attachment_info);
            $this_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
            $this_robot->update_session();

            // Update the ability's target options and trigger
            $this_battle->queue_sound_effect('intense-growing-sound');
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array($temp_ability_frames['target'], 55, 35, -10, $this_robot->print_name().' uses the '.$this_ability->print_name().' technique!')
                ));
            $this_robot->trigger_target($target_robot, $this_ability);

            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(10, 0, 0),
                'success' => array($temp_ability_frames['damage'], -15, 45, -10, 'The '.$this_ability->print_name().' drains the target\'s power!'),
                'failure' => array($temp_ability_frames['damage'], -15, 45, -10, 'The '.$this_ability->print_name().' had no effect...')
                ));
            $target_robot->trigger_damage($this_robot, $this_ability, $this_ability->ability_damage, false);

            // Attach the ability to this robot
            $this_attachment_info['ability_frame'] = $temp_ability_frames['summon'];
            $this_attachment_info['ability_frame_animate'] = array($temp_ability_frames['summon']);
            $this_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
            $this_robot->update_session();

            // Define the list of robot's with dynamically shifting images so we ensure the base is copied
            $dynamic_image_personas = array(
                'trill' => array('alt', 'alt2', 'alt3'),
                'trille-bot' => array('alt', 'alt2', 'alt3'),
                'ballade' => array('alt')
                );

            // Check to ensure the ability was a success before continuing AND the user isn't holding incompatible item
            $copy_style_success = false;
            if ($this_ability->ability_results['this_result'] != 'failure'
                && !empty($this_ability->ability_results['this_amount'])
                && empty($target_robot->robot_energy)){

                // Ensure the target robot's persona can be copied
                $current_persona = !empty($this_robot->robot_persona) ? $this_robot->robot_persona : $this_robot->robot_token;
                if ($this_robot->robot_token !== $target_robot->robot_token
                    && $current_persona !== $target_robot->robot_token){

                    // Collect the target's token as the persona as well as their current image
                    $persona_token = $target_robot->robot_token;
                    $persona_image_token = $target_robot->robot_image;

                    // Collect the target persona's index info as well as a backup of our original info
                    $persona_robot_info = rpg_robot::get_index_info($persona_token);
                    $persona_robot_name_span = rpg_type::print_span('x', $persona_robot_info['robot_name']);
                    $original_robot_info = rpg_robot::get_index_info($this_robot->robot_token);
                    $original_robot_name_span = rpg_type::print_span('x', $original_robot_info['robot_name']);

                    // If this is a special alt-changing robot, we need to make sure we remove the "_alt1234" suffix
                    if (strstr($persona_image_token, '_')){
                        //error_log('persona_image_token has alt: '.$persona_image_token);
                        $frags = explode('_', $persona_image_token);
                        //error_log('$frags = '.print_r($frags, true));
                        //error_log('check for $dynamic_image_personas['.$persona_token.']...');
                        if (!empty($dynamic_image_personas[$persona_token])){
                            //error_log('dynamic image alt found!');
                            $alts = $dynamic_image_personas[$persona_token];
                            //error_log('$alts = '.print_r($alts, true));
                            if (in_array($frags[1], $alts)){ $persona_image_token = $frags[0]; }
                            //error_log('new $persona_image_token = '.print_r($persona_image_token, true));
                        } elseif ($persona_robot_info['robot_core'] === 'copy'
                            || $persona_robot_info['robot_core2'] === 'copy'){
                            //error_log('copy image alt found!');
                            $persona_image_token = $frags[0];
                            //error_log('new $persona_image_token = '.print_r($persona_image_token, true));
                        }
                    }

                    // Update the robot's persona in the current battle state
                    //error_log('final $persona_token = '.print_r($persona_token, true));
                    //error_log('final $persona_image_token = '.print_r($persona_image_token, true));
                    $this_robot->set_persona($persona_token);
                    $this_robot->set_persona_image($persona_image_token);

                    // If this was a human player, make sure we update the player's session with the new persona
                    if ($this_player->player_side == 'left'
                        && empty($this_battle->flags['player_battle'])
                        && empty($this_battle->flags['challenge_battle'])){
                        $ptoken = $this_player->player_token;
                        $rtoken = $this_robot->robot_token;
                        if (!empty($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken])){
                            $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_persona'] = $persona_token;
                            $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_persona_image'] = $persona_image_token;
                        }
                    }

                    // If this is a human player, increment the summon counter for this persona
                    if ($this_player->player_side === 'left'){
                        if (!isset($_SESSION[$session_token]['values']['robot_database'][$persona_token])){ $_SESSION[$session_token]['values']['robot_database'][$persona_token] = array('robot_token' => $persona_token); }
                        if (empty($_SESSION[$session_token]['values']['robot_database'][$persona_token]['robot_summoned'])){ $_SESSION[$session_token]['values']['robot_database'][$persona_token]['robot_summoned'] = 0; }
                        $this_persona_summoned_counter = $_SESSION[$session_token]['values']['robot_database'][$persona_token]['robot_summoned'] + 1;
                        $_SESSION[$session_token]['values']['robot_database'][$persona_token]['robot_summoned'] = $this_persona_summoned_counter;
                    }
                    // Else if the target is the human player, increment their encounter counter for this persona
                    elseif ($target_player->player_side === 'left'){
                        if (!isset($_SESSION[$session_token]['values']['robot_database'][$persona_token])){ $_SESSION[$session_token]['values']['robot_database'][$persona_token] = array('robot_token' => $persona_token); }
                        if (empty($_SESSION[$session_token]['values']['robot_database'][$persona_token]['robot_encountered'])){ $_SESSION[$session_token]['values']['robot_database'][$persona_token]['robot_encountered'] = 0; }
                        $this_persona_encountered_counter = $_SESSION[$session_token]['values']['robot_database'][$persona_token]['robot_encountered'] + 1;
                        $_SESSION[$session_token]['values']['robot_database'][$persona_token]['robot_encountered'] = $this_persona_encountered_counter;
                    }

                    // Update relevant stats back to what they should be for the new persona
                    if (true){

                        // Save the initial damage and remaining energy to reapply later
                        $initial_energy = $this_robot->robot_energy;
                        $initial_energy_base = $this_robot->robot_base_energy;
                        $initial_energy_percent = $this_robot->robot_energy / $this_robot->robot_base_energy;
                        $inflicted_damage_amount = $this_ability->ability_results['this_amount'];

                        // Save the initial weapon energy remaining so we can reapply later
                        $initial_weapons = $this_robot->robot_weapons;
                        $initial_weapons_base = $this_robot->robot_base_weapons;

                        // Define a new name for this persona so it's clear that it's a transformation
                        $cross_letter = ucfirst(substr($original_robot_info['robot_token'], 0, 1));
                        $persona_name = $cross_letter.'× '.$persona_robot_info['robot_name']; // options: X ✗ ×
                        $this_robot->set_name($persona_name);
                        $this_robot->set_base_name($persona_name);

                        // List out the fields we want to copy verbaitm
                        $clone_fields = array(
                            'robot_number', 'robot_game', 'robot_gender',
                            'robot_core', 'robot_core2', 'robot_field', 'robot_field2',
                            'robot_image', 'robot_image_size',
                            'robot_description', 'robot_description2', 'robot_quotes',
                            'robot_weaknesses', 'robot_resistances', 'robot_affinities', 'robot_immunities',
                            'robot_skill', 'robot_skill_name', 'robot_skill_description', 'robot_skill_description2', 'robot_skill_parameters',
                            );
                        // Loop through and simply copy over the easy ones to the current robotinfo array
                        foreach ($clone_fields AS $clone_field){
                            if (!isset($persona_robot_info[$clone_field])){ continue; }
                            $func_name = str_replace('robot_', 'set_', $clone_field);
                            $func_base_name = str_replace('robot_', 'set_base_', $clone_field);
                            $clone_value = $persona_robot_info[$clone_field];
                            $this_robot->$clone_field = $clone_value;
                            if (method_exists($this_robot, $func_name)){ $this_robot->$func_name($clone_value); }
                            if (method_exists($this_robot, $func_base_name)){ $this_robot->$func_base_name($clone_value); }
                        }

                        // Now let's overwrite the persona image if a specific one has been supplied
                        $image_value = !empty($persona_image_token) ? $persona_image_token : $persona_token;
                        $this_robot->set_image($image_value);
                        $this_robot->set_base_image($image_value);

                        // Create an array to hold the stats we will copy over
                        $stats_to_copy_values = array();

                        // Now let's copy over the stats either directly or relatively depending on class
                        $stats_to_copy = array('energy', 'attack', 'defense', 'speed');
                        if ($original_robot_info['robot_class'] === $persona_robot_info['robot_class']){
                            // Copy the stats over 1-to-1 because the persona is of the same class
                            foreach ($stats_to_copy AS $stat_to_copy){
                                if (empty($persona_robot_info['robot_'.$stat_to_copy])){ continue; }
                                $copy_value = $persona_robot_info['robot_'.$stat_to_copy];
                                $stats_to_copy_values[$stat_to_copy] = $copy_value;
                            }
                        } else {
                            // The persona is of a different class, so calculate base-stat-total
                            // for current and then use that to pull relative values from the target persona
                            $old_base_stat_total = 0;
                            $persona_base_stat_total = 0;
                            foreach ($stats_to_copy AS $stat_to_copy){
                                if (empty($original_robot_info['robot_'.$stat_to_copy])){ continue; }
                                $old_base_stat_total += $original_robot_info['robot_'.$stat_to_copy];
                            }
                            foreach ($stats_to_copy AS $stat_to_copy){
                                if (empty($persona_robot_info['robot_'.$stat_to_copy])){ continue; }
                                $persona_base_stat_total += $persona_robot_info['robot_'.$stat_to_copy];
                            }
                            // Calculate stat ratios for the new robot then apply them to the old BST
                            foreach ($stats_to_copy as $stat_to_copy) {
                                if (empty($persona_robot_info['robot_'.$stat_to_copy])){ continue; }
                                $persona_stat_ratio = $persona_robot_info['robot_' . $stat_to_copy] / $persona_base_stat_total;
                                $copy_value = ($old_base_stat_total * $persona_stat_ratio);
                                if ($stat_to_copy === 'energy'){
                                    $stats_to_copy_values[$stat_to_copy] = ceil($copy_value);
                                } else {
                                    $stats_to_copy_values[$stat_to_copy] = round($copy_value);
                                }
                            }
                        }

                        // Preset energy to max before any calculations are (re) done
                        $this_robot->set_energy($initial_energy_base);
                        $this_robot->set_base_energy($initial_energy_base);

                        // Apply the calculated stats to the robot object
                        foreach ($stats_to_copy_values AS $stat_to_copy => $copy_value){
                            $stat_prop_name = 'robot_'.$stat_to_copy;
                            $stat_prop_base_name = 'robot_base_'.$stat_to_copy;
                            $stat_prop_backup_name = $stat_prop_base_name.'_backup';
                            $func_name = 'set_'.$stat_to_copy;
                            $func_base_name = 'set_base_'.$stat_to_copy;
                            $this_robot->$stat_to_copy = $copy_value;
                            $this_robot->set_value($stat_prop_backup_name, $copy_value);
                            $this_robot->$func_name($copy_value);
                            $this_robot->$func_base_name($copy_value);
                        }
                        $base_stats_ref = $persona_robot_info;
                        $base_stats_ref['robot_energy'] = $stats_to_copy_values['energy'];
                        $base_stats_ref['robot_weapons'] = $original_robot_info['robot_weapons'];
                        $base_stats_ref['robot_attack'] = $stats_to_copy_values['attack'];
                        $base_stats_ref['robot_defense'] = $stats_to_copy_values['defense'];
                        $base_stats_ref['robot_speed'] = $stats_to_copy_values['speed'];
                        $this_robot->apply_stat_bonuses(true, $base_stats_ref);
                        $this_robot->robot_reload();

                        // Reapply the initial energy percentage to the newly adjusted value
                        $new_energy = ceil($this_robot->robot_base_energy * $initial_energy_percent);
                        if ($inflicted_damage_amount > 0){ $new_energy += $inflicted_damage_amount; }
                        if ($new_energy > $this_robot->robot_base_energy){ $new_energy = $this_robot->robot_base_energy; }
                        $this_robot->set_energy($new_energy);
                        $this_robot->set_weapons($initial_weapons);

                        // Pull a list of the user and the target's current abilities so we can parse them
                        $user_ability_list = $this_robot->get_abilities();
                        $target_ability_list = $target_robot->get_abilities();
                        //error_log('target_ability_list: '.print_r($target_ability_list, true));
                        //error_log('user_ability_list: '.print_r($user_ability_list, true));

                        // Find the position of copy-style in the user's list, and if it's NOT the last item, we do stuff
                        $max_list_size = MMRPG_SETTINGS_BATTLEABILITIES_PERROBOT_MAX;
                        $copy_style_position = array_search('copy-style', $user_ability_list);
                        if ($copy_style_position !== false && $copy_style_position < ($max_list_size - 1)){

                            // Create a new list of abilities given compatibility
                            $new_ability_list = array();
                            //error_log('$new_ability_list: '.print_r($new_ability_list, true));

                            // Populate the list with the user's existing abilities up to and including copy-style
                            for ($i = 0; $i <= $copy_style_position; $i++){
                                $new_ability_list[] = $user_ability_list[$i];
                                //error_log('+ add user ability '.$user_ability_list[$i].' to list');
                            }

                            // Now loop through the target's abilities and include any that aren't duplicates, are compatible, and are unlocked already
                            foreach ($target_ability_list AS $key => $token){
                                if ($token === 'copy-style'){ continue; }
                                if (in_array($token, $new_ability_list)){ continue; }
                                $compatible = rpg_robot::has_ability_compatibility($persona_robot_info, $token, $this_robot->robot_item);
                                $unlocked = mmrpg_prototype_ability_unlocked('', '', $token);
                                //error_log('check target ability '.$token.' / $compatible: '.$compatible.' / $unlocked: '.$unlocked);
                                if (!$compatible || !$unlocked){ continue; }
                                //error_log('+ add target ability '.$token.' to list');
                                $new_ability_list[] = $token;
                            }

                            // Make sure we don't allow the list to exceed the max size
                            $new_ability_list = array_unique($new_ability_list);
                            if (count($new_ability_list) > $max_list_size){ $new_ability_list = array_slice($new_ability_list, 0, $max_list_size); }
                            //error_log('$new_ability_list: '.print_r($new_ability_list, true));

                            // Update the user's ability list with the new list
                            $this_robot->set_abilities($new_ability_list);
                            $this_robot->set_base_abilities($new_ability_list);

                            // If this was a human player, make sure we update the player's session with the new persona
                            if ($this_player->player_side == 'left'
                                && empty($this_battle->flags['player_battle'])
                                && empty($this_battle->flags['challenge_battle'])){
                                $ptoken = $this_player->player_token;
                                $rtoken = $this_robot->robot_token;
                                $atokens = array();
                                foreach ($new_ability_list AS $token){ $atokens[$token] = array('ability_token' => $token); }
                                if (!empty($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken])){
                                    $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_abilities'] = $atokens;
                                }
                            }

                        }

                    }

                    // Print out a message showing that the effect has taken place
                    $this_robot->set_frame_styles($temp_transform_styles);
                    $this_battle->events_create($this_robot, false,
                        $original_robot_info['robot_name'].'\'s '.$this_ability->ability_name,
                        $original_robot_name_span.' emulated '.$target_robot->print_name_s().' persona! <br />'.
                        $original_robot_name_span.' style-changed into '.rpg_type::print_span(trim($this_robot->robot_core.'_'.$this_robot->robot_core2, '_'), $this_robot->robot_name).'!',
                        //$original_robot_name_span.' turned into '.(preg_match('/^(a|e|i|o|u)/i', $target_robot->robot_core) ? 'an' : 'a').' '.$target_robot->print_core().' type '.$this_robot->print_name().'!',
                        array(
                            'event_flag_camera_action' => true,
                            'event_flag_camera_side' => $this_robot->player->player_side,
                            'event_flag_camera_focus' => $this_robot->robot_position,
                            'event_flag_camera_depth' => $this_robot->robot_key
                            )
                        );
                    $this_robot->reset_frame_styles();

                    // Briefly show the robot in it's new outwithout without any special colouring
                    $this_battle->events_create($this_robot, false, '', '',
                        array(
                            'event_flag_camera_action' => true,
                            'event_flag_camera_side' => $this_robot->player->player_side,
                            'event_flag_camera_focus' => $this_robot->robot_position,
                            'event_flag_camera_depth' => $this_robot->robot_key
                            )
                        );

                    // Set the ability success flag to true
                    $copy_style_success = true;

                    // Create an options object for this function and populate
                    $options->persona_token = $persona_token;
                    $options->persona_image_token = $persona_image_token;

                    // Trigger this robot's custom function if one has been defined for this context
                    $this_robot->trigger_custom_function('rpg-ability_persona-change_after', $extra_objects);
                    if ($options->return_early){ return $options->return_value; }

                }

            }

            // Now that all the damage has been dealt, allow the player to check for disabled
            $target_player->check_robots_disabled($this_player, $this_robot);

            // Remove the temporary ability attachment from this robot
            $this_robot->unset_attachment($this_attachment_token);

            // If the ability was a failure, print out a message saying so
            if (!$copy_style_success){

                // Update the ability's target options and trigger
                $this_ability->target_options_update(array(
                    'frame' => 'defend',
                    'success' => array(9, 0, 0, 10, 'The target\'s persona could not be copied...')
                    ));
                $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));
                return;

            }

        }
        // Otherwise, if we are ALREADY transformed, we need to DROP style
        else {

            // Update the ability's target options and trigger
            $this_battle->queue_sound_effect('small-debuff-received');
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array($temp_ability_frames['target'], 55, 35, -10, $this_robot->print_name().' uses the '.$this_ability->print_name().' technique!')
                ));
            $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));

            // Briefly show the robot in the old outfit, glowing in their colour
            $this_robot->set_frame('summon');
            $this_robot->set_frame_styles($temp_transform_styles);
            $this_battle->events_create($this_robot, false, '', '',
                array(
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $this_robot->player->player_side,
                    'event_flag_camera_focus' => $this_robot->robot_position,
                    'event_flag_camera_depth' => $this_robot->robot_key
                    )
                );
            $this_robot->reset_frame();
            $this_robot->reset_frame_styles();

            $persona_robot_info = rpg_robot::get_index_info($this_robot->robot_persona);
            $persona_robot_name_span = rpg_type::print_span($this_robot->robot_core, $this_robot->robot_name);
            $original_robot_info = rpg_robot::get_index_info($this_robot->robot_token);
            $original_robot_name_span = rpg_type::print_span('x', $original_robot_info['robot_name']);

            // Clear the persona variables for the current robot
            $this_robot->set_persona('');
            $this_robot->set_persona_image('');

            // If a persona was copied or modified in any way, and this is a human, make sure we update the player's session
            if ($this_player->player_side == 'left'
                && empty($this_battle->flags['player_battle'])
                && empty($this_battle->flags['challenge_battle'])){
                $ptoken = $this_player->player_token;
                $rtoken = $this_robot->robot_token;
                if (!empty($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken])){
                    $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_persona'] = '';
                    $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_persona_image'] = '';
                }
            }

            // Reset relevant stats back to what they used to be before
            if (true){

                // Save the initial damage and remaining energy to reapply later
                $initial_energy = $this_robot->robot_energy;
                $initial_energy_base = $this_robot->robot_base_energy;
                $initial_energy_percent = $this_robot->robot_energy / $this_robot->robot_base_energy;
                $inflicted_damage_amount = $this_ability->ability_results['this_amount'];
                //error_log('$initial_energy_base = '.print_r($initial_energy_base, true));
                //error_log('$initial_energy = '.print_r($initial_energy, true));
                //error_log('$initial_energy_percent = '.print_r($initial_energy_percent, true));
                //error_log('$inflicted_damage_amount = '.print_r($inflicted_damage_amount, true));

                // List out the fields we want to reset verbaitm
                $reset_fields = array(
                    'robot_name', 'robot_number', 'robot_game', 'robot_gender',
                    'robot_core', 'robot_core2', 'robot_field', 'robot_field2',
                    'robot_image', 'robot_image_size',
                    'robot_description', 'robot_description2', 'robot_quotes',
                    'robot_weaknesses', 'robot_resistances', 'robot_affinities', 'robot_immunities',
                    'robot_skill', 'robot_skill_name', 'robot_skill_description', 'robot_skill_description2', 'robot_skill_parameters',
                    );

                // Loop through and reset each field to the original value
                foreach($reset_fields AS $reset_field){
                    if (!isset($original_robot_info[$reset_field])){ continue; }
                    $func_name = str_replace('robot_', 'set_', $reset_field);
                    $func_base_name = str_replace('robot_', 'set_base_', $reset_field);
                    $reset_value = $original_robot_info[$reset_field];
                    $this_robot->$reset_field = $reset_value;
                    if (method_exists($this_robot, $func_name)){ $this_robot->$func_name($reset_value); }
                    if (method_exists($this_robot, $func_base_name)){ $this_robot->$func_base_name($reset_value); }
                }

                // Loop through and reset stats to their original indexed values
                $stats_to_copy = array('energy', 'weapons', 'attack', 'defense', 'speed');
                foreach($stats_to_copy AS $stat_to_copy){
                    $reset_value = $original_robot_info['robot_'.$stat_to_copy];
                    $stat_prop_name = 'robot_'.$stat_to_copy;
                    $stat_prop_base_name = 'robot_base_'.$stat_to_copy;
                    $stat_prop_backup_name = $stat_prop_base_name.'_backup';
                    $func_name = 'set_'.$stat_to_copy;
                    $func_base_name = 'set_base_'.$stat_to_copy;
                    $this_robot->$stat_to_copy = $reset_value;
                    $this_robot->set_value($stat_prop_backup_name, $reset_value);
                    $this_robot->$func_name($reset_value);
                    $this_robot->$func_base_name($reset_value);
                }
                $base_stats_ref = $original_robot_info;
                $this_robot->apply_stat_bonuses(true, $base_stats_ref);
                $this_robot->robot_reload();

                // Reapply the initial energy percentage
                $new_energy = ceil($this_robot->robot_base_energy * $initial_energy_percent);
                $this_robot->set_energy($new_energy);
                //error_log('$new_energy = '.print_r($new_energy, true));

            }

            // Print out a message showing that the effect has taken place
            $this_battle->events_create($this_robot, false,
                $this_robot->robot_name.'\'s '.$this_ability->ability_name,
                $original_robot_name_span.' dropped the '.$persona_robot_name_span.' persona!',
                array(
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $this_robot->player->player_side,
                    'event_flag_camera_focus' => $this_robot->robot_position,
                    'event_flag_camera_depth' => $this_robot->robot_key
                    )
                );

            // Remove the temporary ability attachment from this robot
            $this_robot->unset_attachment($this_attachment_token);

            // Create an options object for this function and populate
            $options->persona_token = '';
            $options->persona_image_token = '';

            // Trigger this robot's custom function if one has been defined for this context
            $this_robot->trigger_custom_function('rpg-ability_persona-change_after', $extra_objects);
            if ($options->return_early){ return $options->return_value; }

        }

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Check if this ability is already charged
        $is_transformed = !empty($this_robot->robot_persona) ? true : false;

        // If the ability flag had already been set, reduce the weapon energy to zero
        if ($is_transformed){ $this_ability->set_energy(0); }
        // Otherwise, return the weapon energy back to default
        else { $this_ability->reset_energy(); }

        // If the ability is already charged, allow bench targeting
        if (!$is_transformed && $this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->set_target('auto'); }

        // If this ability is being already charged, we should put an indicator
        if ($is_transformed){
            $new_name = $this_ability->ability_base_name;
            $new_name = str_replace('Copy', 'Drop', $new_name);
            $this_ability->set_name($new_name);
            $this_ability->set_type('');
            $this_ability->set_damage(0);
            $this_ability->set_image($this_ability->ability_token.'-2');
        } else {
            $this_ability->reset_name();
            $this_ability->reset_type();
            $this_ability->reset_damage();
            $this_ability->set_image($this_ability->ability_token);
        }

        // Return true on success
        return true;

    }
);
?>
