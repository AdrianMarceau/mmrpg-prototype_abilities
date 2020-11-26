<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Collect session token for later
        $session_token = rpg_game::session_token();

        // Define the frames based on current character
        $temp_ability_frames = array('target' => 0, 'damage' => 1, 'summon' => 2);
        if ($this_robot->robot_token == 'mega-man'){ $temp_ability_frames = array('target' => 0, 'damage' => 1, 'summon' => 2); }
        elseif ($this_robot->robot_token == 'bass'){ $temp_ability_frames = array('target' => 3, 'damage' => 4, 'summon' => 5); }
        elseif ($this_robot->robot_token == 'proto-man'){ $temp_ability_frames = array('target' => 6, 'damage' => 7, 'summon' => 8); }

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

        // Attach the ability to this robot
        $this_attachment = rpg_game::get_ability($this_battle, $this_player, $this_robot, $this_attachment_info);
        $this_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
        $this_robot->update_session();

        // Update the ability's target options and trigger
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

        // Check to ensure the ability was a success before continuing AND the user isn't holding incompatible item
        $copy_soul_success = false;
        if ($this_ability->ability_results['this_result'] != 'failure'){

            // Ensure the target robot has a core type to draw from
            if (!empty($target_robot->robot_core)){

                // Collect the core type to be copied
                $current_core_type = $this_robot->robot_core;
                $new_core_type = $target_robot->robot_core != $current_core_type ? $target_robot->robot_core : '';
                if (empty($new_core_type) && !empty($target_robot->robot_core2)){ $new_core_type = $target_robot->robot_core2 != $current_core_type ? $target_robot->robot_core2 : ''; }

                // If the new core type was not empty and was from a valid source
                if (!empty($new_core_type)
                    && $new_core_type != 'empty'){

                    // Create the item object to trigger data loading (mostly for display)
                    $new_item_info = array('item_token' => $new_core_type.'-core');
                    $this_new_item = rpg_game::get_item($this_battle, $this_player, $this_robot, $new_item_info);

                    // Set the success frames for the player and robot
                    $this_robot->set_frame('taunt');
                    $this_player->set_frame('victory');

                    // If the user isn't holding an item (or the item is already a core), we give CORE ITEM + CORE SHIELD
                    if ((empty($this_robot->robot_item) || preg_match('/-core$/i', $this_robot->robot_item))){

                        // Collect index data for the user robot
                        $this_robot_index = rpg_robot::get_index_info($this_robot->robot_token);

                        // If this robot isn't holding an item, generate a new core
                        $new_item_generated = false;
                        $existing_item_transformed = false;
                        $core_shield_refreshed = false;
                        if (empty($this_robot->robot_item)
                            || (preg_match('/-core$/i', $this_robot->robot_item)
                                && $this_robot->robot_item != $new_core_type.'-core')){

                            // Update the core type for the robot
                            if (empty($this_robot->robot_item)){ $new_item_generated = true; }
                            else { $existing_item_transformed = true; }
                            $this_robot->set_item($new_core_type.'-core');

                        } else {

                            // We'll simply refresh the core shield
                            $core_shield_refreshed = true;

                        }

                        // If the user created a new core or shifted an existing one, apply the auto core shield
                        if ($new_item_generated
                            || $existing_item_transformed
                            || $core_shield_refreshed){
                            $existing_shields = !empty($this_robot->robot_attachments) ? substr_count(implode('|', array_keys($this_robot->robot_attachments)), 'ability_core-shield_') : 0;
                            $shield_info = rpg_ability::get_static_core_shield($new_core_type, 3, $existing_shields);
                            $shield_token = $shield_info['attachment_token'];
                            $shield_duration = $shield_info['attachment_duration'];
                            if (!isset($this_robot->robot_attachments[$shield_token])){ $this_robot->robot_attachments[$shield_token] = $shield_info; }
                            else { $this_robot->robot_attachments[$shield_token]['attachment_duration'] += $shield_duration; }
                        }

                        // Create an event displaying the new copied element
                        $event_header = $this_new_item->item_name.' Copied';
                        $event_body = $this_ability->print_name().' converts the collected elemental energy&hellip;<br />';
                        if ($new_item_generated){ $event_body .= $this_robot->print_name().' generated a new '.$this_new_item->print_name().' and '.rpg_type::print_span($new_core_type, 'Core Shield').'!'; }
                        elseif ($existing_item_transformed){ $event_body .= $this_robot->print_name().'\'s held core transformed! A new '.rpg_type::print_span($new_core_type, 'Core Shield').' was created too!'; }
                        elseif ($core_shield_refreshed){ $event_body .= $this_robot->print_name().'\'s protection from '.rpg_type::print_span($new_core_type).' type damage was extended!'; }
                        $event_options = array();
                        $event_options['console_show_target'] = false;
                        $event_options['this_item'] = $this_new_item;
                        $event_options['this_item_image'] = 'icon';
                        $event_options['console_show_this_robot'] = false;
                        $event_options['canvas_show_this_item'] = false;
                        $event_options['console_show_this_item'] = true;
                        $this_battle->events_create($this_robot, $target_robot, $event_header, $event_body, $event_options);
                        $copy_soul_success = true;

                        // If a core was generated or modified, we need to add update the user's item in the session
                        if (($new_item_generated || $existing_item_transformed)
                            && $this_player->player_side == 'left'
                            && empty($this_battle->flags['player_battle'])
                            && empty($this_battle->flags['challenge_battle'])){
                            $ptoken = $this_player->player_token;
                            $rtoken = $this_robot->robot_token;
                            $itoken = $new_core_type.'-core';
                            if (!empty($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken])){
                                $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_item'] = $itoken;
                            }
                        }

                    }
                    // Otherwise, if the user is already holding an item, we give the shield only
                    elseif (!empty($this_robot->robot_item)){

                        // If the user created a new core or shifted an existing one, apply the auto core shield
                        $existing_shields = !empty($this_robot->robot_attachments) ? substr_count(implode('|', array_keys($this_robot->robot_attachments)), 'ability_core-shield_') : 0;
                        $shield_info = rpg_ability::get_static_core_shield($new_core_type, 3, $existing_shields);
                        $shield_token = $shield_info['attachment_token'];
                        $shield_duration = $shield_info['attachment_duration'];
                        $shield_exists = false;
                        if (!isset($this_robot->robot_attachments[$shield_token])){ $this_robot->robot_attachments[$shield_token] = $shield_info; }
                        else { $this_robot->robot_attachments[$shield_token]['attachment_duration'] += $shield_duration; $shield_exists = true; }

                        // Create an event displaying the new copied element
                        $this_new_item->set_name('Core Shield');
                        $event_header = $this_new_item->item_name.' Copied';
                        $event_body = $this_ability->print_name().' converts the target\'s elemental energy&hellip; <br />';
                        if (!$shield_exists){ $event_body .= $this_robot->print_name().' generated a new '.rpg_type::print_span($new_core_type, 'Core Shield').'!'; }
                        else { $event_body .= $this_robot->print_name().'\'s protection from '.rpg_type::print_span($new_core_type).' type damage was extended!'; }
                        $event_options = array();
                        $event_options['console_show_target'] = false;
                        $event_options['this_item'] = $this_new_item;
                        $event_options['this_item_image'] = 'icon';
                        $event_options['console_show_this_robot'] = false;
                        $event_options['canvas_show_this_item'] = false;
                        $event_options['console_show_this_item'] = true;
                        $this_battle->events_create($this_robot, $target_robot, $event_header, $event_body, $event_options);
                        $this_new_item->reset_name();
                        $copy_soul_success = true;

                    }

                }

            }

        }

        // Check the target robot and disable if necessary
        if (($target_robot->robot_energy < 1 || $target_robot->robot_status == 'disabled')
            && empty($target_robot->flags['apply_disabled_state'])){
            $target_robot->trigger_disabled($this_robot);
        }

        // Remove the temporary ability attachment from this robot
        unset($this_robot->robot_attachments[$this_attachment_token]);
        $this_robot->update_session();

        // If the ability was a failure, print out a message saying so
        if (!$copy_soul_success){

            // Update the ability's target options and trigger
            $this_ability->target_options_update(array(
                'frame' => 'defend',
                'success' => array(9, 0, 0, 10, 'The target\'s core could not be copied...')
                ));
            $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));
            return;

        }

        // Return true on success
        return true;

        }
);
?>
