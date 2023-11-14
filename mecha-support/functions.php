<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Check to see how many robots this player current has on their team
        $num_current_robots = count($this_player->player_robots);

        // Update the ability's target options and trigger
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 0, 0, 10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));

        // Check to see how many mecha support we're allowed to summon right now (1 max, but reduce if too many)
        $num_mechas_to_summon = 1;
        if (($num_current_robots + $num_mechas_to_summon) > MMRPG_SETTINGS_BATTLEROBOTS_PERSIDE_MAX){
            $num_mechas_to_summon = MMRPG_SETTINGS_BATTLEROBOTS_PERSIDE_MAX - $num_current_robots;
        }

        // Only continue with the ability if player has less than 8 robots
        if ($this_robot->robot_class !== 'mecha'
            && $num_mechas_to_summon > 0
            && ($num_current_robots < MMRPG_SETTINGS_BATTLEROBOTS_PERSIDE_MAX)
            ){

            // Collect session token for saving and refs to this player/robot tokens
            $session_token = rpg_game::session_token();
            $ptoken = $this_player->player_token;
            $rtoken = $this_robot->robot_token;

            // Check to see if this robot already has a saved support token in the session we can use
            //error_log('Check if this robot ('.$this_robot->robot_token.') has a recruited support mecha');
            //error_log('$this_robot->robot_support: '.$this_robot->robot_support);
            //error_log('$this_robot->robot_support_image: '.$this_robot->robot_support_image);

            // Place the current robot back on the bench
            $temp_summoner_key = $this_robot->robot_key;
            $this_original_robot_id = $this_robot->robot_id;
            $this_robot->set_frame('taunt');
            $this_player->set_frame('base');
            $this_player->set_value('current_robot', false);
            $this_player->set_value('current_robot_enter', false);

            // Collect the current robot level for this field
            $this_robot_level = !empty($this_robot->robot_level) ? $this_robot->robot_level : 1;
            $this_field_level = !empty($this_battle->battle_level) ? $this_battle->battle_level : 1;
            $this_mecha_token = 'met';
            $this_mecha_image_token = '';

            // Ensure required ability counters have been set before starting
            if (!isset($this_robot->counters['ability_mecha_support'])){ $this_robot->set_counter('ability_mecha_support', 0); }
            if (!isset($this_robot->counters['support_mechas_summoned'])){ $this_robot->set_counter('support_mechas_summoned', 0); }

            // Update the ability counter once for each use
            $this_robot->inc_counter('ability_mecha_support');

            // If this robot has a support mecha defined, use it directly
            if (!empty($this_robot->robot_support)){

                // Collect the mecha token from the support field directly
                $this_mecha_token = $this_robot->robot_support;
                $this_mecha_image_token = $this_robot->robot_support_image;

            }
            // Otherwise we need to auto-generate based on core and environment
            else {

                // Default to the Met if we are somehow unable to pull real results
                $this_mecha_token = 'met';
                $this_mecha_image_token = '';

            }

            // Collect database info for this mecha
            $this_mecha_index_info = rpg_robot::get_index_info($this_mecha_token);

            // Loop through and create mechas based on the number we're allowed to summon
            for ($new_mecha_key = 0; $new_mecha_key < $num_mechas_to_summon; $new_mecha_key++){

                // Collect a copy of the index info for this mecha
                $this_mecha_info = $this_mecha_index_info;

                // Check to see what the next available key is
                $temp_next_key = 8;
                $temp_keys_used = array();
                $temp_this_robots = $this_player->get_robots();
                foreach ($temp_this_robots AS $k => $r){ $temp_keys_used[] = $r->robot_key; }
                for ($i = 0; $i <= 8; $i++){ if (!in_array($i, $temp_keys_used)){ $temp_next_key = $i; break; } }

                // If this is a human player, increment the summon counter for this mecha
                if ($this_player->player_side === 'left'){
                    if (!isset($_SESSION['GAME']['values']['robot_database'][$this_mecha_token])){ $_SESSION['GAME']['values']['robot_database'][$this_mecha_token] = array('robot_token' => $this_mecha_token); }
                    if (empty($_SESSION['GAME']['values']['robot_database'][$this_mecha_token]['robot_summoned'])){ $_SESSION['GAME']['values']['robot_database'][$this_mecha_token]['robot_summoned'] = 0; }
                    $this_mecha_summoned_counter = $_SESSION['GAME']['values']['robot_database'][$this_mecha_token]['robot_summoned'] + $num_mechas_to_summon;
                    $_SESSION['GAME']['values']['robot_database'][$this_mecha_token]['robot_summoned'] = $this_mecha_summoned_counter;
                }

                // Update the summon flag now that we're done with it
                $this_robot->inc_counter('support_mechas_summoned');

                // Update or create the counter for num mechas summoned by this player then use it to determine the letter
                $this_letter_options = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
                if (!isset($this_player->counters['player_mechas'][$this_mecha_token])){ $this_player->set_counter('player_mechas', $this_mecha_token, 0); }
                else { $this_player->inc_counter('player_mechas', $this_mecha_token); }
                $this_mecha_letter = $this_letter_options[$this_player->counters['player_mechas'][$this_mecha_token]];

                // If this mecha has alt images, make sure we select the next in line
                $this_mecha_image = !empty($this_mecha_image_token) ? $this_mecha_image_token : $this_mecha_token;

                // Generate the new robot and add it to this player's team
                $this_mecha_key = $temp_next_key;
                $this_mecha_id = rpg_game::unique_robot_id($this_player->player_id, $this_mecha_info['robot_id'], ($this_player->counters['robots_total'] + 1));
                $this_mecha_id_token = $this_mecha_id.'_'.$this_mecha_info['robot_token'];
                $this_boost_abilities = array('attack-boost', 'defense-boost', 'speed-boost', 'energy-boost');
                $this_break_abilities = array('attack-break', 'defense-break', 'speed-break', 'energy-break');
                $this_mode_abilities = array('attack-mode', 'defense-mode', 'speed-mode', 'energy-mode');
                $this_swap_abilities = array('attack-swap', 'defense-swap', 'speed-swap', 'energy-swap');
                $this_extra_abilities = array_merge($this_boost_abilities, $this_break_abilities, $this_mode_abilities, $this_swap_abilities);
                shuffle($this_extra_abilities);

                // Define the base mecha info with position, level, and base rewards
                $this_mecha_info['robot_id'] = $this_mecha_id;
                $this_mecha_info['robot_key'] = $temp_next_key;
                $this_mecha_info['robot_token'] = $this_mecha_token;
                $this_mecha_info['robot_position'] = 'bench';
                $this_mecha_info['robot_image'] = $this_mecha_image;
                $this_mecha_info['robot_item'] = '';
                $this_mecha_info['robot_experience'] = 0;
                $this_mecha_info['robot_level'] = ceil($this_robot_level / 3);
                $this_mecha_info['robot_weapons'] = $this_robot->robot_base_weapons;
                $this_mecha_info['robot_base_weapons'] = $this_robot->robot_base_weapons;
                $this_mecha_info['values']['robot_rewards']['robot_energy'] = !empty($this_robot->values['robot_rewards']['robot_energy']) ? $this_robot->values['robot_rewards']['robot_energy'] : 0;
                $this_mecha_info['values']['robot_rewards']['robot_attack'] = !empty($this_robot->values['robot_rewards']['robot_attack']) ? $this_robot->values['robot_rewards']['robot_attack'] : 0;
                $this_mecha_info['values']['robot_rewards']['robot_defense'] = !empty($this_robot->values['robot_rewards']['robot_defense']) ? $this_robot->values['robot_rewards']['robot_defense'] : 0;
                $this_mecha_info['values']['robot_rewards']['robot_speed'] = !empty($this_robot->values['robot_rewards']['robot_speed']) ? $this_robot->values['robot_rewards']['robot_speed'] : 0;
                $this_mecha_info['counters']['energy_mods'] = !empty($this_robot->counters['energy_mods']) ? $this_robot->counters['energy_mods'] : 0;
                $this_mecha_info['counters']['attack_mods'] = !empty($this_robot->counters['attack_mods']) ? $this_robot->counters['attack_mods'] : 0;
                $this_mecha_info['counters']['defense_mods'] = !empty($this_robot->counters['defense_mods']) ? $this_robot->counters['defense_mods'] : 0;
                $this_mecha_info['counters']['speed_mods'] = !empty($this_robot->counters['speed_mods']) ? $this_robot->counters['speed_mods'] : 0;

                // Decide which abilities this mecha should have, let's start fresh
                $mecha_ability_list = array();

                // This mecha always gets it's signature ability/abilities
                $mmrpg_index_abilities = rpg_ability::get_index(true);
                if (!empty($this_mecha_info['robot_rewards']['abilities'])){
                    foreach ($this_mecha_info['robot_rewards']['abilities'] AS $key => $ability){
                        if (isset($ability['level']) && $this_robot->robot_level < $ability['level']){ continue; }
                        if (in_array($ability['token'], $mecha_ability_list)){ continue; }
                        if (!isset($mmrpg_index_abilities[$ability['token']])){ continue; }
                        $mecha_ability_list[] = $ability['token'];
                    }
                }

                // Define the base order for the support move types and the stats that can be altered
                $support_stat_order = array('attack', 'defense', 'speed');
                $support_kind_order = array('boost', 'break', 'swap');

                // Define how many rotations there should be given player number and mecha counters
                $rotations_required = 0;
                $rotations_required += $this_player->player_number > 0 ? ($this_player->player_number - 1) : 0;
                $rotations_required += $this_robot->counters['ability_mecha_support'] > 0 ? ($this_robot->counters['ability_mecha_support'] - 1) : 0;
                $rotate_support_kinds = function() use(&$support_kind_order){
                    $first_support = array_shift($support_kind_order);
                    array_push($support_kind_order, $first_support);
                    $support_kind_order = array_values($support_kind_order);
                    };

                // Rotate the order of the support moves kinds based on the above counter
                for ($i = 1; $i <= $rotations_required; $i++){ $rotate_support_kinds(); }

                // Collect a list of unlocked abilities for the player (if human) so we can prevent early-usage
                $filter_unlocked_abilities = false;
                if (intval($this_player->user_id) !== MMRPG_SETTINGS_TARGET_PLAYERID){
                    rpg_user::pull_unlocked_abilities($this_player->user_id, $filter_unlocked_abilities);
                }

                // Loop through and give this mecha up to three more abilities given above rotations
                $support_key = 0;
                for ($i = 1; $i <= 9; $i++){
                    if (count($mecha_ability_list) >= 4){ break; }
                    $allowed = true;
                    $support_ability_token = $support_stat_order[$support_key].'-'.$support_kind_order[$support_key];
                    if ($filter_unlocked_abilities !== false && !in_array($support_ability_token, $filter_unlocked_abilities)){ $allowed = false; }
                    if ($allowed){ $mecha_ability_list[] = $support_ability_token; }
                    if ($i % 3 === 0){ $rotate_support_kinds(); $support_key = 0; }
                    else { $support_key++; }
                }

                // Finally, give this mecha any abilities from the summoner they're compatible with
                foreach ($this_robot->robot_abilities AS $key => $extra_ability){
                    if ($extra_ability == 'mecha-support' || $extra_ability == 'mecha-party'){ continue; }
                    if (in_array($extra_ability, $mecha_ability_list)){ continue; }
                    if (rpg_robot::has_ability_compatibility($this_mecha_token, $extra_ability, $this_mecha_info['robot_item'])){
                        $mecha_ability_list[] = $extra_ability;
                    }
                }

                // Crop if there are too many abilities
                if (count($mecha_ability_list) > 8){
                    $mecha_ability_list = array_slice($mecha_ability_list, 0, 8);
                }

                // Imprint the generated abilities onto the mecha's final info array
                $this_mecha_info['robot_abilities'] = $mecha_ability_list;

                // Now that we're set everything up, we can create the new mecha object and apply flags
                $temp_mecha = rpg_game::get_robot($this_battle, $this_player, $this_mecha_info);
                $temp_mecha->apply_stat_bonuses();
                $temp_mecha_abilities = array();
                foreach ($temp_mecha->robot_abilities AS $this_key2 => $this_token){
                    $temp_abilityinfo = array('ability_token' => $this_token);
                    $temp_mecha_abilities[$this_key2] = rpg_game::get_ability($this_battle, $this_player, $temp_mecha, $temp_abilityinfo);
                }
                $temp_mecha->set_flag('ability_startup', true);
                $temp_mecha->update_session();
                $this_mecha_info = $temp_mecha->export_array();
                $this_player->load_robot($this_mecha_info, $this_player->counters['robots_total']);
                $this_player->update_session();

                /*
                // TODO:  In the future, we should implement some hue shifting for fun!
                // If this is a mecha beyond the first, we should modify it's appearance a bit
                $base_appearance = $temp_mecha->get_frame_styles();
                $shift_appearance = $this_robot->get_counter('ability_mecha_support');
                if ($shift_appearance > 1){
                    $new_hue = -1 * (($shift_appearance - 1) * 90);
                    $new_style = trim($base_appearance).' filter: hue-rotate('.$new_hue.'deg); ';
                    $temp_mecha->set_frame_styles($new_style);
                }
                */

                // Show an event of this mecha being summoned with the new robot in focus
                $this_robot->set_frame('taunt');
                $temp_mecha->set_frame($new_mecha_key % 2 === 0 ? 'taunt' : 'summon');
                $this_battle->queue_sound_effect('mecha-teleport-in');
                $event_header = $this_robot->robot_name.'\'s '.$this_ability->ability_name;
                $event_body = ucfirst(($new_mecha_key > 1 ? 'yet ' : '').($new_mecha_key === 0 ? 'a ' : 'another ')).$temp_mecha->print_name().' was summoned to the bench! <br />';
                if (isset($temp_mecha->robot_quotes['battle_start'])){
                    $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                    $this_replace = array($target_player->player_name, $target_robot->robot_name, $this_player->player_name, $temp_mecha->robot_name);
                    $event_body .= $temp_mecha->print_quote('battle_start', $this_find, $this_replace);
                }
                $this_battle->events_create($temp_mecha, false, $event_header, $event_body,
                    array(
                        'this_ability' => $this_ability,
                        'canvas_show_this_ability' => false,
                        'canvas_show_this_ability_overlay' => false,
                        'canvas_show_this_ability_underlay' => false,
                        'event_flag_camera_action' => true,
                        'event_flag_camera_side' => $temp_mecha->player->player_side,
                        'event_flag_camera_focus' => $temp_mecha->robot_position,
                        'event_flag_camera_depth' => $temp_mecha->robot_key
                        )
                    );
                $temp_mecha->reset_frame();
                $this_robot->reset_frame();

                // Now that the summon animation is done, we can append the letter if necessary
                if ($this_mecha_letter !== 'A'){
                    $new_name = $temp_mecha->robot_name.' '.$this_mecha_letter;
                    $temp_mecha->set_name($new_name);
                    $temp_mecha->set_base_name($new_name);
                }

            }

        }
        // Otherwise print a nothing happened message
        else {

            // Update the ability's target options and trigger
            $this_ability->target_options_update(array(
                'frame' => 'defend',
                'success' => array(0, 0, 0, 10, '...but nothing happened.')
                ));
            $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));

        }

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If the robot's mecha support has already been literally defined
        if (!empty($this_robot->robot_support)){ $this_robot->set_flag('mecha_support_defined', true); }

        // If this robot's mecha support familiar has not been defined yet
        if (empty($this_robot->flags['mecha_support_defined'])){
            $mecha_token = '';
            $mecha_image = '';
            if ($this_robot->robot_class !== 'mecha'){
                static $mecha_support_index;
                $include_custom = $this_robot->player->player_controller === 'human' ? true : false;
                if (empty($mecha_support_index)){ $mecha_support_index = mmrpg_prototype_mecha_support_index($include_custom); }
                $mecha_support_info = !empty($mecha_support_index[$this_robot->robot_token]) ? $mecha_support_index[$this_robot->robot_token] : array();
                if (!empty($mecha_support_info['custom'])){
                    $mecha_token = $mecha_support_info['custom']['token'];
                    $mecha_image = $mecha_support_info['custom']['image'];
                } elseif (!empty($mecha_support_info['default'])){
                    $mecha_token = $mecha_support_info['default'];
                    $mecha_image = '';
                }
                if ($mecha_token === 'local'){
                    $this_field_mechas = !empty($this_battle->battle_field->field_mechas) ? $this_battle->battle_field->field_mechas : array();
                    if (!empty($this_field_mechas)){ $mecha_token = $this_field_mechas[array_rand($this_field_mechas)]; }
                }
                if (empty($mecha_token)){ $mecha_token = 'met'; }
            } else {
                $mecha_token = $this_robot->robot_token;
            }
            $this_robot->set_support($mecha_token);
            $this_robot->set_support_image($mecha_image);
            $this_robot->set_flag('mecha_support_defined', true);
        }

        // Double this ability's energy cost for each time it's been used, exponentially
        $support_mechas_summoned = $this_robot->get_counter('support_mechas_summoned');
        $ability_energy_cost = $this_ability->ability_base_energy;
        if ($support_mechas_summoned > 0){ $ability_energy_cost += $this_ability->ability_base_energy * $support_mechas_summoned; }
        $this_ability->set_energy($ability_energy_cost);

        // Return true on success
        return true;

    }
);
?>
