<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // DEBUG DEBUG DEBUG
        $context = $this_battle->values['context'];
        //error_log('$context = '.print_r($context, true));
        //error_log('$context = '.json_encode($context, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT));

        // Check early to see if this ability should fail for some reason
        $star_support_enabled = true;
        if (count($this_player->player_robots) >= MMRPG_SETTINGS_BATTLEROBOTS_PERSIDE_MAX){ $star_support_enabled = false; }
        if (!empty($this_player->flags['star_support_summoned'])){ $star_support_enabled = false; }
        if (!empty($this_battle->flags['star_support_summoned'])){ $star_support_enabled = false; }

        // Collect the battle context to decide if this is endgame battle or not
        $star_support_is_endgame = false;
        $context = $this_battle->values['context'];
        if ($context['player'] === 'dr-cossack'
            && $context['chapter'] === 5
            && $context['round'] === 2){
            $star_support_is_endgame = true;
        }

        // As long as this ability is enabled, we should place the crest on the field
        $static_attachment_key = $this_robot->get_static_attachment_key();
        $static_attachment_token = $static_attachment_key.'_ability_'.$this_ability->ability_token;
        if ($star_support_enabled){

            // Define this ability's attachment token
            $static_attachment_info = array(
                'class' => 'ability',
                'sticky' => true,
                'ability_id' => $this_ability->ability_id,
                'ability_token' => $this_ability->ability_token,
                'ability_image' => $this_ability->ability_token,
                'ability_frame' => 1,
                'ability_frame_animate' => array(1,2),
                'ability_frame_offset' => array('x' => -10, 'y' => 60, 'z' => 10),
                'ability_frame_classes' => ' ',
                'ability_frame_styles' => ' '
                );

            // Add the ability crest attachment
            $this_robot->set_frame('summon');
            $this_battle->set_attachment($static_attachment_key, $static_attachment_token, $static_attachment_info);

        }

        // If we're using this outside of the endgame we can display the usage prompt
        if ($star_support_enabled
            && !$star_support_is_endgame){

            // Update the ability's target options and trigger
            $this_ability->set_class('master');
            $this_battle->queue_sound_effect('summon-sound');
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(0, 0, 0, -10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')
                ));
            $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));

        }

        // Show a difference entrance message if this is engame or not
        if ($star_support_enabled){

            // Print a message showing that this effect is taking place
            $event_trigger_options = array(
                'this_ability' => $this_ability,
                'canvas_show_this_ability_overlay' => false,
                'canvas_show_this_ability_underlay' => false,
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $this_robot->player->player_side,
                'event_flag_camera_focus' => $this_robot->robot_position,
                'event_flag_camera_depth' => $this_robot->robot_key,
                'event_flag_camera_offset' => 0
                );
            $this_player->set_frame('base2');
            $this_robot->set_frame('summon');
            $this_battle->queue_sound_effect('cosmic-sound');
            $this_battle->queue_sound_effect(array('name' => 'cosmic-sound', 'delay' => 200));
            $this_battle->queue_sound_effect(array('name' => 'cosmic-sound', 'delay' => 600));
            $this_battle->events_create($this_robot, false, $this_player->player_name.'\'s '.$this_ability->ability_name,
                $this_player->print_name().' senses something coming...',
                $event_trigger_options
                );
            $this_player->set_frame('base');
            $this_robot->set_frame('defend');
            $event_trigger_options['event_flag_camera_offset'] += 1;
            $this_battle->events_create($this_robot, false, '', '');
            $this_player->set_frame('base2');
            $this_robot->set_frame('base');
            $event_trigger_options['event_flag_camera_offset'] += 2;
            $this_battle->events_create($this_robot, false, '', '');
            $this_player->reset_frame();
            $this_robot->reset_frame();

        }

        // Only continue with the ability if player has less than 8 robots and any other criteria
        if ($star_support_enabled){

            // Clearly define that this is, in fact, an ability that summons Duo
            $this_master_token = 'duo';
            $this_master_info = rpg_robot::get_index_info($this_master_token);

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

            // Generate the new robot and add it to this player's team
            $this_master_key = $temp_summoner_key;
            $this_master_image = $this_master_info['robot_image'];
            $this_master_id = rpg_game::unique_robot_id($this_player->player_id, $this_master_info['robot_id'], ($this_player->counters['robots_total'] + 1));
            $this_master_id_token = $this_master_id.'_'.$this_master_info['robot_token'];

            // Define the base mecha info with position, level, and base rewards
            $this_master_info['robot_id'] = $this_master_id;
            $this_master_info['robot_key'] = $temp_summoner_key;
            $this_master_info['robot_token'] = $this_master_token;
            $this_master_info['robot_image'] = $this_master_image;
            $this_master_info['robot_position'] = 'active';
            $this_master_info['robot_core'] = 'space';
            $this_master_info['robot_core2'] = 'copy';
            $this_master_info['robot_item'] = '';
            $this_master_info['robot_level'] = 100;
            $this_master_info['robot_experience'] = 0;
            //$this_master_info['robot_weapons'] = $this_robot->robot_base_weapons;
            //$this_master_info['robot_base_weapons'] = $this_robot->robot_base_weapons;
            //$this_master_info['values']['robot_rewards']['robot_energy'] = !empty($this_robot->values['robot_rewards']['robot_energy']) ? $this_robot->values['robot_rewards']['robot_energy'] : 0;
            //$this_master_info['values']['robot_rewards']['robot_attack'] = !empty($this_robot->values['robot_rewards']['robot_attack']) ? $this_robot->values['robot_rewards']['robot_attack'] : 0;
            //$this_master_info['values']['robot_rewards']['robot_defense'] = !empty($this_robot->values['robot_rewards']['robot_defense']) ? $this_robot->values['robot_rewards']['robot_defense'] : 0;
            //$this_master_info['values']['robot_rewards']['robot_speed'] = !empty($this_robot->values['robot_rewards']['robot_speed']) ? $this_robot->values['robot_rewards']['robot_speed'] : 0;
            //$this_master_info['counters']['energy_mods'] = !empty($this_robot->counters['energy_mods']) ? $this_robot->counters['energy_mods'] : 0;
            //$this_master_info['counters']['attack_mods'] = !empty($this_robot->counters['attack_mods']) ? $this_robot->counters['attack_mods'] : 0;
            //$this_master_info['counters']['defense_mods'] = !empty($this_robot->counters['defense_mods']) ? $this_robot->counters['defense_mods'] : 0;
            //$this_master_info['counters']['speed_mods'] = !empty($this_robot->counters['speed_mods']) ? $this_robot->counters['speed_mods'] : 0;

            // Decide which abilities this master should have, let's start fresh
            $mmrpg_index_abilities = rpg_ability::get_index(true);
            $master_ability_list = array();

            // If this is the endgame, we need to manually define abilities right now
            if ($star_support_is_endgame){

                // Assign an item that makes sense for the uno-reverse card we're pulling
                $this_master_info['robot_item'] = 'xtreme-module';

                // Define abilities relevant for an endgame summon character
                $master_ability_list = array(
                    'energy-fist', 'star-crash', 'astro-crush', 'core-laser',
                    'item-swap', 'quick-strike', 'hard-knuckle', 'atomic-crasher',
                    );

                // Make sure this robot starts with a few boosts of its own
                $this_master_info['counters']['attack_mods'] = 5;
                $this_master_info['counters']['defense_mods'] = 5;
                $this_master_info['counters']['speed_mods'] = 5;

            }
            // Otherwise, we should assign a procedurally determined selection of abilities
            else {

                // Assign an item based on some kind of logic we think of
                $this_master_info['robot_item'] = 'fortune-module';

                // This master always gets it's signature ability/abilities
                if (!empty($this_master_info['robot_rewards']['abilities'])){
                    foreach ($this_master_info['robot_rewards']['abilities'] AS $key => $ability){
                        if (isset($ability['level']) && $this_master_info['robot_level'] < $ability['level']){ continue; }
                        if (in_array($ability['token'], $master_ability_list)){ continue; }
                        if (!isset($mmrpg_index_abilities[$ability['token']])){ continue; }
                        $master_ability_list[] = $ability['token'];
                    }
                }

                // Collect the core list of abilities we'll be pulling from
                $mmrpg_core_abilities = array(
                    rpg_ability::get_tier_one_abilities(),
                    rpg_ability::get_tier_two_abilities(),
                    rpg_ability::get_tier_three_abilities()
                    );
                //error_log('$mmrpg_core_abilities = '.print_r($mmrpg_core_abilities, true));

                // Collect the current starforce for this player to determine abilities
                $player_starforce = rpg_game::starforce_unlocked();
                //error_log('$player_starforce = '.print_r($player_starforce, true));

                // Collect the current abilities for this player to determine what's allowed
                mmrpg_prototype_abilities_unlocked(false, false, $unlocked_player_abilities);
                //error_log('$unlocked_player_abilities = '.print_r($unlocked_player_abilities, true));

                // Pull types one at a time until we have enough abilities
                $player_ability_influence = $player_starforce;
                while (count($master_ability_list) < MMRPG_SETTINGS_BATTLEABILITIES_PERROBOT_MAX){
                    //error_log('$player_ability_influence = '.print_r($player_ability_influence, true));
                    $next_ability_type = $this_battle->weighted_chance(array_keys($player_ability_influence), array_values($player_ability_influence));
                    $next_ability_power = $player_ability_influence[$next_ability_type];
                    unset($player_ability_influence[$next_ability_type]);
                    //error_log('$next_ability_type = '.$next_ability_type);
                    //error_log('$next_ability_power = '.$next_ability_power);
                    $next_ability_pool = $mmrpg_core_abilities[0];
                    if ($next_ability_power >= 25){ $next_ability_pool = array_merge($next_ability_pool, $mmrpg_core_abilities[1]); }
                    if ($next_ability_power >= 50){ $next_ability_pool = array_merge($next_ability_pool, $mmrpg_core_abilities[2]); }
                    shuffle($next_ability_pool);
                    foreach ($next_ability_pool AS $next_ability_token){
                        if (in_array($next_ability_token, $master_ability_list)){ continue; }
                        if (!in_array($next_ability_token, $unlocked_player_abilities)){ continue; }
                        $next_ability_info = $mmrpg_index_abilities[$next_ability_token];
                        if ($next_ability_type !== $next_ability_info['ability_type'] && $next_ability_type !== $next_ability_info['ability_type2']){ continue; }
                        $master_ability_list[] = $next_ability_token;
                        break;
                    }
                    if (empty($player_ability_influence)){ break; }
                }

                // Predefine zero value stat mods for this master to start
                $turns_passed = $this_battle->counters['battle_turn'] - 1;
                $power_level = min((1 + $turns_passed), 6);
                $mod_boost = $power_level - 1;
                //error_log('$turns_passed = '.$turns_passed);
                //error_log('$power_level = '.$power_level);
                //error_log('$mod_boost = '.$mod_boost);
                $this_master_info['counters']['attack_mods'] = $mod_boost;
                $this_master_info['counters']['defense_mods'] = $mod_boost;
                $this_master_info['counters']['speed_mods'] = $mod_boost;

            }

            // Crop if there are too many abilities as this still needs to be playable
            if (count($master_ability_list) > MMRPG_SETTINGS_BATTLEABILITIES_PERROBOT_MAX){
                $master_ability_list = array_slice($master_ability_list, 0, MMRPG_SETTINGS_BATTLEABILITIES_PERROBOT_MAX);
            }

            // Imprint the generated abilities onto the mecha's final info array
            //error_log('$master_ability_list for '.$this_master_token.' = '.print_r($master_ability_list, true));
            $this_master_info['robot_abilities'] = $master_ability_list;

            // Now that we're set everything up, we can create the new mecha object and apply flags
            $this_new_robot = rpg_game::get_robot($this_battle, $this_player, $this_master_info);
            $this_new_robot->apply_stat_bonuses();
            $this_new_robot_abilities = array();
            foreach ($this_new_robot->robot_abilities AS $this_key2 => $this_token){
                $temp_abilityinfo = array('ability_token' => $this_token);
                $this_new_robot_abilities[$this_key2] = rpg_game::get_ability($this_battle, $this_player, $this_new_robot, $temp_abilityinfo);
            }
            $this_new_robot->set_flag('ability_startup', true);
            $this_new_robot->update_session();
            $this_master_info = $this_new_robot->export_array();
            $this_player->load_robot($this_master_info, $this_player->counters['robots_total']);
            $this_player->update_session();

            // Remove the attachment from the field to show it has done its job summoning
            $this_battle->unset_attachment($static_attachment_key, $static_attachment_token);

            // Automatically trigger a switch action to the new star support robot
            $this_robot->set_key($temp_next_key);
            $this_battle->actions_trigger($this_player, $this_robot, $target_player, $target_robot, 'switch', $this_master_id_token);
            $this_robot->set_frame('base');

            // Collect reference to the new robot so we can use it
            $this_new_robot->robot_reload();

            // Show an extra frame right after switchin with a fun sound effect
            $event_trigger_options = array(
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $this_new_robot->player->player_side,
                'event_flag_camera_focus' => $this_new_robot->robot_position,
                'event_flag_camera_depth' => $this_new_robot->robot_key,
                'event_flag_camera_offset' => 0
                );
            $this_new_robot->set_frame('summon');
            $this_battle->queue_sound_effect('level-up');
            $this_battle->queue_sound_effect('boss-teleport-in');
            $event_trigger_options['event_flag_camera_offset'] += 1;
            $this_battle->events_create(false, false, '', '', $event_trigger_options);
            $this_new_robot->set_frame('defend');
            $event_trigger_options['event_flag_camera_offset'] += 1;
            $this_battle->events_create(false, false, '', '');
            $this_new_robot->reset_frame();

            // Automatically trigger an ability action from the new star support robot
            $this_new_robot->robot_reload();
            $this_new_robot_ability = $this_new_robot_abilities[0];
            $this_new_robot_ability_action = $this_new_robot_ability->ability_id.'_'.$this_new_robot_ability->ability_token;
            $this_battle->actions_append(
                $this_player,
                $this_new_robot,
                $target_player,
                $target_robot,
                'ability',
                $this_new_robot_ability_action,
                true
                );

            // Assuming this is the human player, we should reset their star support cooldown
            if ($this_player->player_side === 'left'){ rpg_prototype::reset_star_support_cooldown(); }

            // Update the summon flag now that we're done with it
            $this_player->set_flag('star_support_summoned', true);
            $this_battle->set_flag('star_support_summoned', true);

            // If this is a human player, increment the summon counter for this mecha
            if ($this_player->player_side === 'left'){
                $session_token = rpg_game::session_token();
                if (!isset($_SESSION[$session_token]['values']['robot_database'][$this_master_token])){ $_SESSION[$session_token]['values']['robot_database'][$this_master_token] = array('robot_token' => $this_master_token); }
                if (empty($_SESSION[$session_token]['values']['robot_database'][$this_master_token]['robot_summoned'])){ $_SESSION[$session_token]['values']['robot_database'][$this_master_token]['robot_summoned'] = 0; }
                $this_master_summoned_counter = $_SESSION[$session_token]['values']['robot_database'][$this_master_token]['robot_summoned'] + 1;
                $_SESSION[$session_token]['values']['robot_database'][$this_master_token]['robot_summoned'] = $this_master_summoned_counter;
            }

        }
        // Otherwise print a nothing happened message
        else {

            // Update the ability's target options and trigger
            $trigger_text = '...but nothing happened.';
            $trigger_options = array('prevent_default_text' => true);
            if ($star_support_is_endgame
                && !$star_support_enabled){
                $trigger_text = '';
                $trigger_options['canvas_show_this_ability'] = false;
            }
            $this_ability->target_options_update(array(
                'frame' => 'defend',
                'success' => array(0, 0, 0, 10, $trigger_text)
                ));
            $this_robot->trigger_target($target_robot, $this_ability, $trigger_options);

        }

        // Regardless of what happens we gotta remove the attachment from the field
        if ($star_support_enabled){

            // Add the ability crest attachment
            $this_robot->reset_frame();
            $this_battle->unset_attachment($static_attachment_key, $static_attachment_token);

        }

        // Return true on success
        return true;

        }
);
?>
