<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Collect the stat boosts that this robot has available
        $stat_tokens = array('attack', 'defense', 'speed');
        $stat_values = array();
        foreach ($stat_tokens AS $key => $stat){
            $value = !empty($this_robot->counters[$stat.'_mods']) ? $this_robot->counters[$stat.'_mods'] : 0;
            $stat_values[$stat] = $value > 0 ? $value : 0;
        }
        $stat_boost_values = $stat_values;
        $stat_boosts_available = array_sum($stat_boost_values) > 0 ? true : false;
        $stat_boosts_allowed = $stat_boosts_available;

        // Loop through the boosts and make sure none of them are too high for the target
        foreach ($stat_boost_values AS $stat => $stat_value){

            // Ensure this stat boost does not exceed the maximum allowed
            $current_value = !empty($target_robot->counters[$stat.'_mods']) ? $target_robot->counters[$stat.'_mods'] : 0;
            if ($current_value + $stat_value > MMRPG_SETTINGS_STATS_MOD_MAX){
                $stat_value = MMRPG_SETTINGS_STATS_MOD_MAX - $current_value;
                $stat_boost_values[$stat] = $stat_value;
                $stat_boosts_allowed = $stat_boosts_allowed && array_sum($stat_boost_values) > 0 ? true : false;
            }

        }

        // Collect this side's attachment tokens and info for the later animations
        $this_static_attachment_key = $this_robot->get_static_attachment_key();
        $this_static_attachment_token = $this_static_attachment_key.'_ability_'.$this_ability->ability_token;
        $this_static_relay_attachment_token = $this_static_attachment_token.'_relay';
        $this_static_heart_attachment_token = $this_static_attachment_token.'_heart';

        // Collect the other side's attachment tokens and info for the later animations
        $target_static_attachment_key = $target_robot->get_static_attachment_key();
        $target_static_attachment_token = $target_static_attachment_key.'_ability_'.$this_ability->ability_token;
        $target_static_relay_attachment_token = $target_static_attachment_token.'_relay';
        $target_static_heart_attachment_token = $target_static_attachment_token.'_heart';

        // Define this ability's attachment token
        $static_relay_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_id' => $this_ability->ability_id,
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_token,
            'ability_frame' => 0,
            'ability_frame_animate' => array(0,1),
            'ability_frame_offset' => array('x' => 0, 'y' => 10, 'z' => -10),
            'ability_frame_classes' => ' ',
            'ability_frame_styles' => ' '
            );
        $static_heart_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_id' => $this_ability->ability_id,
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_token,
            'ability_frame' => 2,
            'ability_frame_animate' => array(2,3,4,5,6,7),
            'ability_frame_offset' => array('x' => 0, 'y' => 40, 'z' => 10),
            'ability_frame_classes' => ' ',
            'ability_frame_styles' => ' '
            );
        $static_heart_animation_frames = array(
            'attack' => array(2, 3),
            'defense' => array(4, 5),
            'speed' => array(6, 7)
            );

        // Add the static RELAY attachments to the opposite sides of the field
        if (true){
            $this_battle->set_attachment($this_static_attachment_key, $this_static_relay_attachment_token, $static_relay_attachment_info);
            $this_battle->set_attachment($target_static_attachment_key, $target_static_relay_attachment_token, $static_relay_attachment_info);
        }

        // Target this robot's self and show the display text
        $summon_text = $this_robot->robot_name.' uses the '.$this_ability->print_name().' ability!';
        if ($this_robot->robot_class === 'mecha'){ $summon_text = 'The '.$this_robot->print_name().' '.$this_ability->print_name().'!'; }
        $this_battle->queue_sound_effect('summon-positive');
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, -9999, -9999, -10, $summon_text)
            ));
        $this_robot->trigger_target($this_robot, $this_ability, array('prevent_default_text' => true));

        // Define the different affection thresholds for display text purposes
        $affection_thresholds = array(
            0 => 'remains hostile toward',
            1 => 'seems less hostile toward',
            2 => 'seems indifferent toward',
            3 => 'appears to have taken a liking to',
            4 => 'becomes a bit more friendly toward',
            5 => 'feels very friendly toward', // mecha recruitment here
            6 => 'is now quite fond of',
            7 => 'has become friends with',
            8 => 'is now best friends with',
            9 => 'has become infatuated with',
            10 => 'appears to have fallen in love with'
            );

        // Define the required values for recruitment and then check if we've reached it
        $target_affection_required = 5;
        //error_log('$target_affection_required = '.print_r($target_affection_required, true));

        // Ensure this robot has at least one stat boost else this ability does nothing
        if ($stat_boosts_available && $stat_boosts_allowed){

            // Otherwise, we can give these boosts to the target robot/mecha/whatever instead
            foreach ($stat_boost_values AS $stat => $stat_value){

                // Add the static HEART attachments to the opposite sides of the field
                if (true){
                    $static_heart_attachment_info['ability_frame'] = $static_heart_animation_frames[$stat][0];
                    $this_battle->set_attachment($this_static_attachment_key, $this_static_heart_attachment_token, $static_heart_attachment_info);
                    $this_battle->set_attachment($target_static_attachment_key, $target_static_heart_attachment_token, $static_heart_attachment_info);
                }

                // Boost the target's robot's stat by the calculated amount
                rpg_ability::ability_function_stat_boost($target_robot, $stat, $stat_value, $this_ability);

                // Increase the target robot's affection meter by an appropriate amount
                if (!isset($target_robot->counters['affection'])){ $target_robot->counters['affection'] = array(); }
                if (!isset($target_robot->counters['affection'][$this_robot->robot_token])){ $target_robot->counters['affection'][$this_robot->robot_token] = 0; }
                $target_robot->counters['affection'][$this_robot->robot_token] += $stat_value;

                // Remove the static HEART attachments from both sides of the field
                if (true){
                    $this_battle->unset_attachment($this_static_attachment_key, $this_static_heart_attachment_token);
                    $this_battle->unset_attachment($target_static_attachment_key, $target_static_heart_attachment_token);
                }

            }

            // Calculate the effective affection value given the target class
            $target_affection_value = $target_robot->counters['affection'][$this_robot->robot_token];
            if ($target_robot->robot_core === 'empty'){ $target_affection_value = $target_affection_value / 5; }
            elseif ($target_robot->robot_class === 'mecha'){ $target_affection_value = $target_affection_value / 2; }
            elseif ($target_robot->robot_class === 'master'){ $target_affection_value = $target_affection_value / 3; }
            elseif ($target_robot->robot_class === 'boss'){ $target_affection_value = $target_affection_value / 4; }
            //error_log('$target_robot->counters[\'affection\']['.$this_robot->robot_token.'] = '.print_r($target_robot->counters['affection'][$this_robot->robot_token], true));
            //error_log('$target_affection_value = '.print_r($target_affection_value, true));

            // Generate the appropriate affection text given the stat of things
            $target_name_prefix = $target_robot->robot_class === 'mecha' ? 'the ' : '';
            if ($target_robot->robot_core !== 'empty'){
                $target_heart_icon = 'heart';
                $target_affection_text = $affection_thresholds[0];
                foreach ($affection_thresholds AS $threshold => $threshold_text){
                    if ($target_affection_value >= $threshold){ $target_affection_text = $threshold_text; }
                }
            } else {
                $target_heart_icon = 'heart-broken';
                $target_affection_text = 'is not impressed by';
            }

            // Display a little message based on how friendly the target robot has become
            $this_battle->queue_sound_effect('small-buff-received');
            $this_robot->set_frame('taunt');
            $target_robot->set_frame('base2');
            $event_header = $this_robot->robot_name.' and '.$target_robot->robot_name;
            $event_body = ucfirst($target_name_prefix).$target_robot->print_name().' '.$target_affection_text.' '.$this_robot->print_name().'!';
            $event_body .= '<br /> <span style="font-size: 80%;">'.str_repeat('<i class="fa fas fa-'.$target_heart_icon.'"></i>', floor(min($target_affection_value, 25))).'</span>';
            $this_battle->events_create($target_robot, false, $event_header, $event_body, array(
                'console_show_this_player' => false,
                'console_show_target_player' => false,
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $target_robot->player->player_side,
                'event_flag_camera_focus' => $target_robot->robot_position,
                'event_flag_camera_depth' => $target_robot->robot_key
                ));
            $this_robot->reset_frame();

            // Define the list of robot's with dynamically shifting images so we ensure the base is copied
            $dynamic_image_mechas = array(
                'sniper-joe' => array('alt'),
                'trille-bot' => array('alt', 'alt2', 'alt3')
                );

            // Only check recruitment logic if the player using this ability is a human character
            if ($this_player->player_side === 'left'
                && !empty($target_affection_value)
                && $target_affection_value >= $target_affection_required){
                //error_log('affection value reached! check recruitment logic...');

                // Collect session token for saving
                $session_token = rpg_game::session_token();
                $ptoken = $this_player->player_token;
                $rtoken = $this_robot->robot_token;

                // If the target robot is a MECHA and has the necessary affection value we can recruit it
                if ($target_robot->robot_class === 'mecha'){

                    // If this is NOT an empty type mecha, we can recruit it NORMALLY with a friendly message
                    if ($target_robot->robot_core !== 'empty'){

                        // Add the target mecha support to this robot's battle settings for future use
                        //error_log('We can recruit the '.$target_robot->robot_token.' mecha now!');
                        $old_support_token = !empty($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_support']) ? $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_support'] : '';
                        $old_support_image_token = !empty($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_support_image']) ? $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_support_image'] : '';
                        $new_support_token = $target_robot->robot_token;
                        $new_support_image_token = $target_robot->robot_image !== $target_robot->robot_token ? $target_robot->robot_image : '';
                        if (strstr($target_robot->robot_image, '_')){
                            $frags = explode('_', $target_robot->robot_image);
                            //error_log('$frags = '.print_r($frags, true));
                            if (!empty($dynamic_image_mechas[$new_support_token])){
                                //error_log('dynamic image alt found!');
                                $alts = $dynamic_image_mechas[$new_support_token];
                                //error_log('$alts = '.print_r($alts, true));
                                if (in_array($frags[1], $alts)){ $new_support_image_token = $frags[0]; }
                                //error_log('new $new_support_image_token = '.print_r($new_support_image_token, true));
                            } elseif ($target_robot->robot_core === 'copy'
                                || $target_robot->robot_core2 === 'copy'){
                                //error_log('copy core found!');
                                $new_support_image_token = $frags[0];
                                //error_log('new $new_support_image_token = '.print_r($new_support_image_token, true));
                            }
                        }
                        $old_support_exists = !empty($old_support_token) ? true : false;
                        unset($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_support']);
                        unset($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_support_image']);
                        $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_support'] = $new_support_token;
                        if ($new_support_image_token){ $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_support_image'] = $new_support_image_token; }
                        $this_robot->set_support($new_support_token);
                        $this_robot->set_support_image($new_support_image_token);
                        //error_log('$old_support_token = '.print_r($old_support_token, true));
                        //error_log('$old_support_image_token = '.print_r($old_support_image_token, true));
                        //error_log('$new_support_token = '.print_r($new_support_token, true));
                        //error_log('$new_support_image_token = '.print_r($new_support_image_token, true));
                        //error_log('$old_support_exists = '.print_r($old_support_exists, true));
                        //error_log('$_SESSION//settings/'.$rtoken.' = '.print_r($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken], true));

                        // Display a little message showing that the mecha has been recruited
                        $this_battle->queue_sound_effect('get-weird-item');
                        $this_robot->set_frame('summon');
                        $target_robot->set_frame('victory');
                        $event_header = $this_robot->robot_name.' and '.$target_robot->robot_name;
                        $event_body = 'The '.$target_robot->print_name().' decided to join '.$this_robot->print_name().' on '.$this_robot->get_pronoun('possessive2').' journey!';
                        if ($old_support_exists
                            && ($old_support_token !== $new_support_token
                                || $old_support_image_token !== $new_support_image_token)){
                            $event_body .= '<br /> '.$this_robot->print_name().' waves goodbye to '.$this_robot->get_pronoun('possessive2').' previous support mecha!';
                        } else {
                            $event_body .= '<br /> '.$this_robot->print_name().' waves hello to '.$this_robot->get_pronoun('possessive2').' new support mecha!';
                        }
                        $event_body .= ' <i class="fas fa-hand-heart"></i>';
                        $this_battle->events_create($target_robot, false, $event_header, $event_body, array(
                            'console_show_this_player' => false,
                            'console_show_target_player' => false,
                            'event_flag_camera_action' => true,
                            'event_flag_camera_side' => $target_robot->player->player_side,
                            'event_flag_camera_focus' => $target_robot->robot_position,
                            'event_flag_camera_depth' => $target_robot->robot_key
                            ));
                        $this_robot->reset_frame();
                        //error_log('$target_robot [old] ('.$target_robot->robot_string.') robot frame is '.$target_robot->robot_frame.' on '.__LINE__.' of '.basename(__FILE__));

                        // Mark the mecha as friend and disabled so that the battle can end when everything else is done
                        $target_robot->set_flag('is_friendly', true);
                        $target_robot->set_flag('is_recruited', true);
                        $target_robot->set_status('disabled');
                        $target_robot->set_frame('victory');
                        $target_robot->set_frame_styles('display: none;');
                        $target_player->check_robots_disabled($this_player, $this_robot);
                        //error_log('$target_robot [old] ('.$target_robot->robot_string.') robot frame is '.$target_robot->robot_frame.' on '.__LINE__.' of '.basename(__FILE__));

                    }
                    // Otherwise, if this IS an EMPTY type mecha, we will instead sever any connections to prior support mecha
                    else {

                        // Remove any of this robot's preset support mecha as punishment for trying
                        //error_log('Trying to recruit the '.$target_robot->robot_token.' backfired!');
                        $old_support_token = !empty($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_support']) ? $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_support'] : '';
                        $old_support_image_token = !empty($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_support_image']) ? $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_support_image'] : '';
                        $old_support_exists = !empty($old_support_token) ? true : false;
                        unset($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_support']);
                        unset($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_support_image']);
                        $this_robot->set_support('');
                        $this_robot->set_support_image('');
                        //error_log('$old_support_token = '.print_r($old_support_token, true));
                        //error_log('$old_support_image_token = '.print_r($old_support_image_token, true));
                        //error_log('$old_support_exists = '.print_r($old_support_exists, true));
                        //error_log('$_SESSION//settings/'.$rtoken.' = '.print_r($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken], true));

                        // Display a little message showing that the mecha sent out bad vibes and severed the user's connection if applicable
                        $this_battle->queue_sound_effect('debuff-received');
                        $this_robot->set_frame('defend');
                        $target_robot->set_frame('summon');
                        $event_header = $this_robot->robot_name.' and '.$target_robot->robot_name;
                        $event_body = 'The '.$target_robot->print_name().' rejected '.$this_robot->print_name().'\'s attempt to recruit it!';
                        if ($old_support_exists){
                            $event_body .= '<br /> What the?! '.$this_robot->print_name().' lost '.$this_robot->get_pronoun('possessive2').' connection to '.$this_robot->get_pronoun('possessive2').' previous support mecha!';
                        } else {
                            $event_body .= '<br /> '.$target_robot->print_name().' is sending some pretty bad vibes right now!';
                        }
                        $event_body .= ' <i class="fas fa-heart-broken"></i>';
                        $this_battle->events_create($target_robot, false, $event_header, $event_body, array(
                            'console_show_this_player' => false,
                            'console_show_target_player' => false,
                            'event_flag_camera_action' => true,
                            'event_flag_camera_side' => $target_robot->player->player_side,
                            'event_flag_camera_focus' => $target_robot->robot_position,
                            'event_flag_camera_depth' => $target_robot->robot_key
                            ));
                        $this_robot->reset_frame();
                        //error_log('$target_robot [old] ('.$target_robot->robot_string.') robot frame is '.$target_robot->robot_frame.' on '.__LINE__.' of '.basename(__FILE__));

                    }


                }
                // Else if the target robot is a MASTER or a BOSS, we cannot really do anything with it (for now)
                else {

                    // Do nothing for now

                }

            }

            // Otherwise, we can give these boosts to the target robot/mecha/whatever instead
            foreach ($stat_boost_values AS $stat => $stat_value){

                // Break this robot's stat by the same amount
                rpg_ability::ability_function_stat_reset($this_robot, $stat, $this_ability, array(
                        'initiator_robot' => $this_robot
                        ));

            }

        } elseif (!$stat_boosts_available){

            // Generate an event to show nothing happened
            $this_battle->queue_sound_effect('no-effect');
            $this_robot->set_frame('defend');
            $event_header = $this_robot->robot_name.'\'s '.$this_ability->ability_name;
            $event_body = '...but '.$this_robot->print_name().' had nothing to give!';
            $this_battle->events_create($this_robot, false, $event_header, $event_body, array(
                'console_show_this_player' => false,
                'console_show_target_player' => false,
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $this_robot->player->player_side,
                'event_flag_camera_focus' => $this_robot->robot_position,
                'event_flag_camera_depth' => $this_robot->robot_key
                ));
            $this_robot->reset_frame();

        } elseif (!$stat_boosts_allowed){

            // Generate an event to show nothing happened
            $this_battle->queue_sound_effect('no-effect');
            $target_robot->set_frame('defend');
            $event_header = $this_robot->robot_name.'\'s '.$this_ability->ability_name;
            $event_body = '...but '.$target_robot->print_name().' was unaffected!';
            $this_battle->events_create($target_robot, false, $event_header, $event_body, array(
                'console_show_this_player' => false,
                'console_show_target_player' => false,
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $target_robot->player->player_side,
                'event_flag_camera_focus' => $target_robot->robot_position,
                'event_flag_camera_depth' => $target_robot->robot_key
                ));
            $target_robot->reset_frame();

        }

        // Remove the static RELAY attachments from both sides of the field
        if (true){
            $this_battle->unset_attachment($this_static_attachment_key, $this_static_relay_attachment_token);
            $this_battle->unset_attachment($target_static_attachment_key, $target_static_relay_attachment_token);
        }

        // Return true on success
        return true;

    }
);
?>
