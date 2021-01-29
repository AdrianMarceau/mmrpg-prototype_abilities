<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Update the ability's target options and trigger
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 0, 0, 10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));

        // Only continue with the ability if player has less than 8 robots
        if (count($this_player->player_robots) < MMRPG_SETTINGS_BATTLEROBOTS_PERSIDE_MAX){

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

            // Check to see if this robot has summoned a mecha during this battle already
            if (!isset($this_robot->counters['ability_mecha_support'])){ $this_robot->set_counter('ability_mecha_support', 0); }

            // If this robot has a support mecha defined, use it directly
            if (!empty($this_robot->robot_support)){

                // Collect the mecha token from the support field directly
                $this_mecha_token = $this_robot->robot_support;
                $this_mecha_name_token = preg_replace('/-([1-3]+)$/i', '', $this_mecha_token);

            }
            // Otherwise we need to auto-generate based on core and environment
            else {

                // Check if this robot is a Copy Core or Elemental Core (skip if Neutral)
                $this_field_mechas = array();
                if (!empty($this_robot->robot_core)){
                    if ($this_robot->robot_core == 'copy'){
                        // Collect the current robots available for this current field
                        $this_field_mechas = !empty($this_battle->battle_field->field_mechas) ? $this_battle->battle_field->field_mechas : array();
                    } else {
                        $this_field_token = false;
                        if (!empty($this_robot->robot_field) && $this_robot->robot_field !== 'field'){ $this_field_token = $this_robot->robot_field; }
                        elseif (!empty($this_robot->robot_field2) && $this_robot->robot_field2 !== 'field'){ $this_field_token = $this_robot->robot_field2; }
                        if ($this_field_token){
                            $this_field_info = rpg_field::get_index_info($this_field_token);
                            if (!empty($this_field_info['field_mechas'])){ $this_field_mechas = $this_field_info['field_mechas']; }
                        }
                    }
                }

                // If no mechas were defined, default to the Met
                if (empty($this_field_mechas)){
                    $this_field_mechas[] = 'met';
                }

                // Based on the number of summons this battle, decide which in rotation to use
                $this_mecha_count = count($this_field_mechas);
                $temp_summon_pos = $this_robot->counters['ability_mecha_support'] + 1;
                if ($this_mecha_count == 1){ $temp_summon_pos = 1; }
                elseif ($temp_summon_pos > $this_mecha_count){
                    $temp_summon_pos = $temp_summon_pos % $this_mecha_count;
                    if ($temp_summon_pos < 1){ $temp_summon_pos = $this_mecha_count; }
                }
                $temp_summon_key = $temp_summon_pos - 1;
                $this_mecha_token = $this_field_mechas[$temp_summon_key];
                $this_mecha_name_token = preg_replace('/-([1-3]+)$/i', '', $this_mecha_token);

            }

            // Collect database info for this mecha
            $this_mecha_info = rpg_robot::get_index_info($this_mecha_token);

            // If this is a human player, increment the summon counter for this mecha
            if ($this_player->player_side === 'left'){
                if (!isset($_SESSION['GAME']['values']['robot_database'][$this_mecha_token])){ $_SESSION['GAME']['values']['robot_database'][$this_mecha_token] = array('robot_token' => $this_mecha_token); }
                if (empty($_SESSION['GAME']['values']['robot_database'][$this_mecha_token]['robot_summoned'])){ $_SESSION['GAME']['values']['robot_database'][$this_mecha_token]['robot_summoned'] = 0; }
                if (empty($_SESSION['GAME']['values']['robot_database'][$this_mecha_token]['robot_encountered'])){ $_SESSION['GAME']['values']['robot_database'][$this_mecha_token]['robot_encountered'] = 0; }
                $this_mecha_summoned_counter = $_SESSION['GAME']['values']['robot_database'][$this_mecha_token]['robot_summoned'] + 1;
                $this_mecha_encountered_counter = $_SESSION['GAME']['values']['robot_database'][$this_mecha_token]['robot_encountered'] + 1;
                $_SESSION['GAME']['values']['robot_database'][$this_mecha_token]['robot_summoned'] = $this_mecha_summoned_counter;
                $_SESSION['GAME']['values']['robot_database'][$this_mecha_token]['robot_encountered'] = $this_mecha_encountered_counter;
            }

            // Update the summon flag now that we're done with it
            $this_robot->inc_counter('ability_mecha_support');

            // Update or create the counter for num mechas summoned by this player then use it to determine the letter
            $this_letter_options = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
            if (!isset($this_player->counters['player_mechas'][$this_mecha_name_token])){ $this_player->set_counter('player_mechas', $this_mecha_name_token, 0); }
            else { $this_player->inc_counter('player_mechas', $this_mecha_name_token); }
            $this_mecha_letter = $this_letter_options[$this_player->counters['player_mechas'][$this_mecha_name_token]];

            // If this mecha has alt images, make sure we select the next in line
            $this_mecha_image = $this_mecha_token;
            if (!empty($this_mecha_info['robot_image_alts'])){
                $alt_images = array();
                $alt_images[] = array('token' => '', 'name' => $this_mecha_info['robot_name'], 'summons' => 0);
                $alt_images = array_merge($alt_images, $this_mecha_info['robot_image_alts']);
                $alt_key = $this_robot->counters['ability_mecha_support'] - 1;
                $max_alt_key = count($alt_images) - 1;
                if ($alt_key > $max_alt_key){ $alt_key = (($alt_key + 1) % ($max_alt_key + 1)) - 1; }
                $alt_token = $alt_images[$alt_key]['token'];
                $this_mecha_image = $this_mecha_token.( !empty($alt_token) ? '_'.$alt_token : '' );
            }

            // Generate the new robot and add it to this player's team
            $this_mecha_key = $temp_summoner_key; //$this_player->counters['robots_active'] + $this_player->counters['robots_disabled'] + 1;
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
            $this_mecha_info['robot_key'] = $temp_summoner_key;
            $this_mecha_info['robot_position'] = 'active';
            $this_mecha_info['robot_name'] .= ' '.$this_mecha_letter;
            $this_mecha_info['robot_image'] = $this_mecha_image;
            $this_mecha_info['robot_item'] = '';
            $this_mecha_info['robot_experience'] = 0;
            $this_mecha_info['robot_level'] = $this_robot_level;
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

            // Give this mecha any abilities from the summoner they're compatible with
            foreach ($this_robot->robot_abilities AS $key => $extra_ability){
                if ($extra_ability == 'mecha-support'){ continue; }
                if (rpg_robot::has_ability_compatibility($this_mecha_token, $extra_ability, $this_mecha_info['robot_item'])){
                    $this_mecha_info['robot_abilities'][] = $extra_ability;
                }
            }

            // Crop if there are too many abilities
            if (count($this_mecha_info['robot_abilities']) > 8){
                $this_mecha_info['robot_abilities'] = array_slice($this_mecha_info['robot_abilities'], 0, 8);
            }

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

            // Automatically trigger a switch action to the new mecha support robot
            $this_robot->set_key($temp_next_key);
            $this_battle->actions_trigger($this_player, $this_robot, $target_player, $target_robot, 'switch', $this_mecha_id_token);
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
            $this_ability->target_options_update(array(
                'frame' => 'defend',
                'success' => array(0, 0, 0, 10, '&hellip;but nothing happened.')
                ));
            $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));

        }

        // Return true on success
        return true;

        }
);
?>
