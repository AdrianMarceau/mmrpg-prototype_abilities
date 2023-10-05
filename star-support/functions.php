<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // DEBUG DEBUG DEBUG
        $this_player->set_value('master_support_pending', 'duo');
        $this_player->set_flag('master_support_is_endgame', true);

        // Check early to see if this ability should fail for some reason
        $master_support_token = '';
        $master_support_enabled = true;
        $master_support_is_endgame = false;
        if (!empty($this_player->values['master_support_pending'])){ $master_support_token = $this_player->values['master_support_pending']; }
        elseif (!empty($this_player->values['master_support_synced'])){ $master_support_token = $this_player->values['master_support_synced']; }
        if (empty($master_support_token)){ $master_support_enabled = false; }
        if (count($this_player->player_robots) >= MMRPG_SETTINGS_BATTLEROBOTS_PERSIDE_MAX){ $master_support_enabled = false; }
        if (!empty($this_player->counters['master_support_triggered'])){ $master_support_enabled = false; }
        if (!empty($this_player->flags['master_support_is_endgame'])){ $master_support_is_endgame = true; }

        // Show a difference entrance message if this is engame or not
        if ($master_support_is_endgame
            && $master_support_enabled){
            // Print a message showing that this effect is taking place
            $this_player->set_frame('base2');
            $this_robot->set_frame('defend');
            $this_battle->queue_sound_effect('summon-sound');
            $this_battle->events_create($this_robot, false, $this_player->player_name.'\'s '.$this_ability->ability_name,
                $this_player->print_name().' senses something coming...',
                array(
                    'this_ability' => $this_ability,
                    'canvas_show_this_ability_overlay' => false,
                    'canvas_show_this_ability_underlay' => false,
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $this_robot->player->player_side,
                    'event_flag_camera_focus' => $this_robot->robot_position,
                    'event_flag_camera_depth' => $this_robot->robot_key
                    )
                );
            $this_player->reset_frame();
            $this_robot->reset_frame();
        } elseif (!$master_support_is_endgame){
            // Update the ability's target options and trigger
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(0, 0, 0, 10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')
                ));
            $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));
        }

        // Only continue with the ability if player has less than 8 robots and any other criteria
        if ($master_support_enabled){

            // Check to see what the next available key is
            $temp_next_key = 8;
            $temp_keys_used = array();
            $temp_this_robots = $this_player->get_robots();
            foreach ($temp_this_robots AS $k => $r){ $temp_keys_used[] = $r->robot_key; }
            for ($i = 0; $i <= 8; $i++){ if (!in_array($i, $temp_keys_used)){ $temp_next_key = $i; break; } }

            // Place the current robot back on the bench
            $temp_summoner_key = $this_robot->robot_key;
            $this_original_robot_id = $this_robot->robot_id;
            $this_robot->set_frame('taunt');
            $this_robot->set_position('bench');
            $this_player->set_frame('base');
            $this_player->set_value('current_robot', false);
            $this_player->set_value('current_robot_enter', false);

            // Collect the current robot level for this field
            $this_robot_level = !empty($this_robot->robot_level) ? $this_robot->robot_level : 1;
            $this_field_level = !empty($this_battle->battle_level) ? $this_battle->battle_level : 1;

            // Check to see if this player has summoned a master during this battle already
            if (!isset($this_player->counters['master_support_triggered'])){ $this_robot->set_counter('master_support_triggered', 0); }

            // Collect database info for this mecha
            $this_master_token = $master_support_token;
            $this_master_info = rpg_robot::get_index_info($this_master_token);

            // If this is a human player, increment the summon counter for this mecha
            if ($this_player->player_side === 'left'){
                if (!isset($_SESSION['GAME']['values']['robot_database'][$this_master_token])){ $_SESSION['GAME']['values']['robot_database'][$this_master_token] = array('robot_token' => $this_master_token); }
                if (empty($_SESSION['GAME']['values']['robot_database'][$this_master_token]['robot_summoned'])){ $_SESSION['GAME']['values']['robot_database'][$this_master_token]['robot_summoned'] = 0; }
                //if (empty($_SESSION['GAME']['values']['robot_database'][$this_master_token]['robot_encountered'])){ $_SESSION['GAME']['values']['robot_database'][$this_master_token]['robot_encountered'] = 0; }
                $this_master_summoned_counter = $_SESSION['GAME']['values']['robot_database'][$this_master_token]['robot_summoned'] + 1;
                //$this_master_encountered_counter = $_SESSION['GAME']['values']['robot_database'][$this_master_token]['robot_encountered'] + 1;
                $_SESSION['GAME']['values']['robot_database'][$this_master_token]['robot_summoned'] = $this_master_summoned_counter;
                //$_SESSION['GAME']['values']['robot_database'][$this_master_token]['robot_encountered'] = $this_master_encountered_counter;
            }

            // Update the summon flag now that we're done with it
            $this_player->inc_counter('master_support_triggered');

            // Generate the new robot and add it to this player's team
            $this_master_key = $temp_summoner_key;
            $this_master_image = $this_master_info['robot_image'];
            $this_master_id = rpg_game::unique_robot_id($this_player->player_id, $this_master_info['robot_id'], ($this_player->counters['robots_total'] + 1));
            $this_master_id_token = $this_master_id.'_'.$this_master_info['robot_token'];

            // Define the base mecha info with position, level, and base rewards
            $this_master_info['robot_id'] = $this_master_id;
            $this_master_info['robot_key'] = $temp_summoner_key;
            $this_master_info['robot_position'] = 'active';
            $this_master_info['robot_image'] = $this_master_image;
            $this_master_info['robot_item'] = '';
            $this_master_info['robot_experience'] = 0;
            $this_master_info['robot_level'] = $this_robot_level;
            //$this_master_info['robot_weapons'] = $this_robot->robot_base_weapons;
            //$this_master_info['robot_base_weapons'] = $this_robot->robot_base_weapons;
            $this_master_info['values']['robot_rewards']['robot_energy'] = !empty($this_robot->values['robot_rewards']['robot_energy']) ? $this_robot->values['robot_rewards']['robot_energy'] : 0;
            $this_master_info['values']['robot_rewards']['robot_attack'] = !empty($this_robot->values['robot_rewards']['robot_attack']) ? $this_robot->values['robot_rewards']['robot_attack'] : 0;
            $this_master_info['values']['robot_rewards']['robot_defense'] = !empty($this_robot->values['robot_rewards']['robot_defense']) ? $this_robot->values['robot_rewards']['robot_defense'] : 0;
            $this_master_info['values']['robot_rewards']['robot_speed'] = !empty($this_robot->values['robot_rewards']['robot_speed']) ? $this_robot->values['robot_rewards']['robot_speed'] : 0;
            $this_master_info['counters']['energy_mods'] = !empty($this_robot->counters['energy_mods']) ? $this_robot->counters['energy_mods'] : 0;
            $this_master_info['counters']['attack_mods'] = !empty($this_robot->counters['attack_mods']) ? $this_robot->counters['attack_mods'] : 0;
            $this_master_info['counters']['defense_mods'] = !empty($this_robot->counters['defense_mods']) ? $this_robot->counters['defense_mods'] : 0;
            $this_master_info['counters']['speed_mods'] = !empty($this_robot->counters['speed_mods']) ? $this_robot->counters['speed_mods'] : 0;

            // Decide which abilities this master should have, let's start fresh
            $mmrpg_index_abilities = rpg_ability::get_index(true);
            $master_hold_item = '';
            $master_ability_list = array();

            // If this is the endgame, we need to manually define abilities right now
            if ($master_support_is_endgame){

                // Define abilities based on the endgame summon character
                if ($master_support_token === 'duo'){
                    $this_master_info['robot_core2'] = 'copy';
                    $this_master_info['robot_item'] = 'reverse-module';
                    $master_ability_list = array(
                        'energy-fist', 'star-crash', 'astro-crush', 'core-laser',
                        'quick-strike', 'hard-knuckle', 'atomic-crasher', 'buster-charge',
                        );
                }

            }

            // Otherwise we can intelligently select abilities based on content
            if (!$master_support_is_endgame
                || empty($master_ability_list)) {

                // This master always gets it's signature ability/abilities
                if (!empty($this_master_info['robot_rewards']['abilities'])){
                    foreach ($this_master_info['robot_rewards']['abilities'] AS $key => $ability){
                        if (isset($ability['level']) && $this_robot->robot_level < $ability['level']){ continue; }
                        if (in_array($ability['token'], $master_ability_list)){ continue; }
                        if (!isset($mmrpg_index_abilities[$ability['token']])){ continue; }
                        $master_ability_list[] = $ability['token'];
                    }
                }

                // Define the base order for the support move types and the stats that can be altered
                $support_stat_order = array('attack', 'defense', 'speed');
                $support_kind_order = array('boost', 'break', 'swap');

                // Define how many rotations there should be given player number and mecha counters
                $rotations_required = 0;
                $rotations_required += $this_player->player_number > 0 ? ($this_player->player_number - 1) : 0;
                $rotations_required += $this_player->counters['master_support_triggered'] > 0 ? ($this_player->counters['master_support_triggered'] - 1) : 0;
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

                // Loop through and give this master up to three more abilities given above rotations
                $support_key = 0;
                for ($i = 1; $i <= 9; $i++){
                    if (count($master_ability_list) >= 4){ break; }
                    $allowed = true;
                    $support_ability_token = $support_stat_order[$support_key].'-'.$support_kind_order[$support_key];
                    if ($filter_unlocked_abilities !== false && !in_array($support_ability_token, $filter_unlocked_abilities)){ $allowed = false; }
                    if ($allowed){ $master_ability_list[] = $support_ability_token; }
                    if ($i % 3 === 0){ $rotate_support_kinds(); $support_key = 0; }
                    else { $support_key++; }
                }

                // Finally, give this mecha any abilities from the summoner they're compatible with
                foreach ($this_robot->robot_abilities AS $key => $extra_ability){
                    if ($extra_ability == 'mecha-support'){ continue; }
                    if (in_array($extra_ability, $master_ability_list)){ continue; }
                    if (rpg_robot::has_ability_compatibility($this_master_token, $extra_ability, $this_master_info['robot_item'])){
                        $master_ability_list[] = $extra_ability;
                    } else {
                    }
                }

            }

            // Crop if there are too many abilities
            if (count($master_ability_list) > 8){
                $master_ability_list = array_slice($master_ability_list, 0, 8);
            }

            // Imprint the generated abilities onto the mecha's final info array
            //error_log('$master_ability_list for '.$this_master_token.' = '.print_r($master_ability_list, true));
            $this_master_info['robot_abilities'] = $master_ability_list;

            // Now that we're set everything up, we can create the new mecha object and apply flags
            $temp_mecha = rpg_game::get_robot($this_battle, $this_player, $this_master_info);
            $temp_mecha->apply_stat_bonuses();
            $temp_mecha_abilities = array();
            foreach ($temp_mecha->robot_abilities AS $this_key2 => $this_token){
                $temp_abilityinfo = array('ability_token' => $this_token);
                $temp_mecha_abilities[$this_key2] = rpg_game::get_ability($this_battle, $this_player, $temp_mecha, $temp_abilityinfo);
            }
            $temp_mecha->set_flag('ability_startup', true);
            $temp_mecha->update_session();
            $this_master_info = $temp_mecha->export_array();
            $this_player->load_robot($this_master_info, $this_player->counters['robots_total']);
            $this_player->update_session();

            // Automatically trigger a switch action to the new mecha support robot
            $this_robot->set_key($temp_next_key);
            $this_battle->actions_trigger($this_player, $this_robot, $target_player, $target_robot, 'switch', $this_master_id_token);
            $this_robot->set_frame('base');

            // Automatically trigger an ability action from the new mecha support robot
            $temp_mecha->robot_reload();
            $temp_mecha_ability = $temp_mecha_abilities[0];
            $temp_mecha_ability_action = $temp_mecha_ability->ability_id.'_'.$temp_mecha_ability->ability_token;
            $this_battle->actions_append(
                $this_player,
                $temp_mecha,
                $target_player,
                $target_robot,
                'ability',
                $temp_mecha_ability_action,
                true
                );

        }
        // Otherwise print a nothing happened message
        else {

            // Update the ability's target options and trigger
            $trigger_text = '...but nothing happened.';
            $trigger_options = array('prevent_default_text' => true);
            if ($master_support_is_endgame
                && !$master_support_enabled){
                $trigger_text = '';
                $trigger_options['canvas_show_this_ability'] = false;
            }
            $this_ability->target_options_update(array(
                'frame' => 'defend',
                'success' => array(0, 0, 0, 10, $trigger_text)
                ));
            $this_robot->trigger_target($target_robot, $this_ability, $trigger_options);

        }

        // Return true on success
        return true;

        }
);
?>
