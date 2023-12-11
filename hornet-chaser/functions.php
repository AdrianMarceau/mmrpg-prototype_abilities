<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Collect session token for later
        $session_token = rpg_game::session_token();

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(3, 120, 0, 10, $this_robot->print_name().' releases a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Check to see if the target is holding an item
        $target_has_item = !empty($target_robot->robot_item) ? true : false;
        $old_item_token = false;
        $removed_target_item = false;
        if ($target_has_item
            && !$target_robot->has_immunity($this_ability->ability_type)
            && !$target_robot->has_immunity($this_ability->ability_type2)){

            // Collect the item token
            $old_item_token = $target_robot->robot_item;

            // Define this ability's attachment token
            $temp_rotate_amount = 25;
            $item_attachment_token = 'item_'.$old_item_token;
            $item_attachment_info = array(
                'class' => 'item',
                'sticky' => true,
                'attachment_token' => $item_attachment_token,
                'item_token' => $old_item_token,
                'item_frame' => 0,
                'item_frame_animate' => array(0),
                'item_frame_offset' => array('x' => 0, 'y' => 60, 'z' => 20),
                'item_frame_styles' => 'opacity: 0.75; transform: rotate('.$temp_rotate_amount.'deg); -webkit-transform: rotate('.$temp_rotate_amount.'deg); -moz-transform: rotate('.$temp_rotate_amount.'deg); '
                );

             // Remove the item from the target robot and update w/ attachment info
            $old_item = rpg_game::get_item($this_battle, $target_player, $target_robot, array('item_token' => $old_item_token));
            $old_item->update_session();
            $target_robot->robot_attachments[$item_attachment_token] = $item_attachment_info;
            $target_robot->robot_item = '';
            $target_robot->update_session();
            $removed_target_item = true;

        }

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'modifiers' => true,
            'kickback' => array(10, 0, 0),
            'success' => array(4, -55, 0, 10, 'The '.$this_ability->print_name().' hit the target!'),
            'failure' => array(4, -75, 0, -10, 'The '.$this_ability->print_name().' missed the target&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'modifiers' => true,
            'frame' => 'taunt',
            'kickback' => array(10, 0, 0),
            'success' => array(4, -35, 0, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
            'failure' => array(4, -75, 0, -10, 'The '.$this_ability->print_name().' missed the target&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false, 'apply_stat_modifiers' => true);
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false, $trigger_options);

        // Remove the visual icon attachment from the target
        if ($removed_target_item){

            // Remove the item attachment from view
            unset($target_robot->robot_attachments[$item_attachment_token]);
            $target_robot->update_session();

            // If the target robot was disabled by the attack, steal the item
            if ($target_robot->robot_status == 'disabled'
                || $target_robot->robot_energy <= 0){

                // If the target robot was the player, we gotta update the session
                if ($target_player->player_side == 'left'
                    && empty($this_battle->flags['player_battle'])
                    && empty($this_battle->flags['challenge_battle'])){
                    $ptoken = $target_player->player_token;
                    $rtoken = $target_robot->robot_token;
                    if (!empty($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_item'])){
                        $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_item'] = '';
                    }
                }

                // Make sure the target looks disabled right now
                $target_robot->robot_frame = 'disabled';
                $target_robot->update_session();

                // Define this ability's attachment token in case we use it
                $ability_attachment_token = 'ability_'.$this_ability->ability_token;
                $ability_attachment_info = array(
                    'class' => 'ability',
                    'attachment_token' => $ability_attachment_token,
                    'ability_token' => $this_ability->ability_token,
                    'ability_frame' => 3,
                    'ability_frame_animate' => array(3),
                    'ability_frame_offset' => array('x' => 160, 'y' => 30, 'z' => 21),
                    'ability_frame_styles' => 'transform: scaleX(-1); -moz-transform: scaleX(-1); -webkit-transform: scaleX(-1); '
                    );

                // Attach a sprite of this ability to the user returning from its trip
                $this_robot->robot_attachments[$ability_attachment_token] = $ability_attachment_info;
                $this_robot->update_session();

                 // Make a duplicate of the target's item for the user to show it being taken
                $new_item_token = $old_item_token;
                $new_item = rpg_game::get_item($this_battle, $this_player, $this_robot, array('item_token' => $new_item_token));
                $new_item->update_session();

                // If the target was a consumable like a pellet, capsule, or tank - consume it!
                $temp_item_regex = '/^(super|energy|weapon|attack|defense|speed)-(pellet|capsule|tank)$/';
                if (preg_match($temp_item_regex, $old_item_token)
                    || in_array($old_item_token, array('yashichi'))){

                    // Break down the token into two parts, stat and size
                    if (strstr($old_item_token, '-')){ list($item_stat, $item_size) = explode('-', $old_item_token); }
                    else { $item_stat = $old_item_token; $item_size = ''; }
                    if ($item_stat == 'weapon'){ $item_stat .= 's'; }
                    //$this_battle->events_create(false, false, 'DEBUG', 'consumable item '.$old_item_token.' collected! <br /> $item_stat = '.$item_stat.' | $item_size = '.$item_size);

                    // Update the ability's target options and trigger
                    $temp_rotate_amount = 45;
                    $new_item->target_options_update(array(
                        'frame' => 'taunt',
                        'success' => array(0, 145, 10, 20,
                            'The '.$this_ability->print_name().' stole '.$target_robot->print_name().'\'s held item!'.
                            '<br /> The '.$old_item->print_name().' was consumed by '.$this_robot->print_name().'!'
                            )
                        ));
                    $this_robot->trigger_target($this_robot, $new_item, array('prevent_default_text' => true));

                    // If we're dealing with life or weapon energy-based consumables
                    if ($item_stat == 'yashichi'
                        || $item_stat == 'energy'
                        || $item_stat == 'weapons'){

                        // Define which stat(s) we're boosting and by how much
                        $stat_boost_amount = 0;
                        $stat_boost_tokens = array();
                        if ($item_stat == 'yashichi'){ $stat_boost_tokens = array('energy', 'weappons'); }
                        else { $stat_boost_tokens = array($item_stat); }
                        if ($item_size == 'pellet'){ $stat_boost_amount = 25; }
                        elseif ($item_size == 'capsule'){ $stat_boost_amount = 50; }
                        elseif ($item_stat == 'yashichi' || $item_size == 'tank'){ $stat_boost_amount = 100; }

                        // If we're dealing with an energy-based consumable
                        if (in_array('energy', $stat_boost_tokens)){

                            $old_item->recovery_options_update(array(
                                'kind' => 'energy',
                                'percent' => true,
                                'modifiers' => false,
                                'frame' => 'taunt',
                                'success' => array(9, 0, 0, -9999, $this_robot->print_name().'\'s life energy was restored!'),
                                'failure' => array(9, 0, 0, -9999, $this_robot->print_name().'\'s life energy was not affected...')
                                ));
                            $energy_recovery_amount = ceil($this_robot->robot_base_energy * ($stat_boost_amount / 100));
                            $this_robot->trigger_recovery($this_robot, $old_item, $energy_recovery_amount);

                        }

                        // If we're dealing with a weapons-based consumable
                        if (in_array('weapons', $stat_boost_tokens)){

                            // Increase this robot's life energy stat
                            $old_item->recovery_options_update(array(
                                'kind' => 'weapons',
                                'percent' => true,
                                'modifiers' => false,
                                'frame' => 'taunt',
                                'success' => array(9, 0, 0, -9999, $this_robot->print_name().'\'s weapon energy was restored!'),
                                'failure' => array(9, 0, 0, -9999, $this_robot->print_name().'\'s weapon energy was not affected...')
                                ));
                            $weapons_recovery_amount = ceil($this_robot->robot_base_weapons * ($stat_boost_amount / 100));
                            $this_robot->trigger_recovery($this_robot, $old_item, $weapons_recovery_amount);

                        }

                    }
                    // Otherwise for all other stat-based consumables
                    else {

                        // Define the stat(s) this item will boost and how much
                        $stat_boost_amount = 0;
                        $stat_boost_tokens = array();
                        if ($item_stat == 'super'){ $stat_boost_amount = $item_size == 'capsule' ? 2 : 1; }
                        else { $stat_boost_amount = $item_size == 'capsule' ? 3 : 2; }
                        if ($item_stat == 'attack' || $item_stat == 'super'){ $stat_boost_tokens[] = 'attack'; }
                        if ($item_stat == 'defense' || $item_stat == 'super'){ $stat_boost_tokens[] = 'defense'; }
                        if ($item_stat == 'speed' || $item_stat == 'super'){ $stat_boost_tokens[] = 'speed'; }
                        //$this_battle->events_create(false, false, 'DEBUG', 'it was a basic stat item! <br /> $stat_boost_amount = '.$stat_boost_amount.' | $stat_boost_tokens = '.implode(',', $stat_boost_tokens));

                        // Loop through and boost relevant stats as if this item was consumed
                        if (!empty($stat_boost_tokens)){
                            foreach ($stat_boost_tokens AS $stat_token){
                                // Call the global stat boost function with customized options
                                rpg_ability::ability_function_stat_boost($this_robot, $stat_token, $stat_boost_amount, $this_ability);
                            }
                        }

                    }

                }
                // Otherwise, if holdable and the user does NOT have a held item already
                elseif (empty($this_robot->robot_item)){

                    // Update the ability's target options and trigger
                    $temp_rotate_amount = 45;
                    $new_item->target_options_update(array(
                        'frame' => 'taunt',
                        'success' => array(0, 145, 10, 20,
                            'The '.$this_ability->print_name().' stole '.$target_robot->print_name().'\'s held item!'.
                            '<br /> The '.$old_item->print_name().' was returned to '.$this_robot->print_name().'!'
                            )
                        ));
                    $this_robot->trigger_target($this_robot, $new_item, array('prevent_default_text' => true));

                    // Give the cloned item to the user of the ability
                    $this_robot->robot_item = $new_item_token;
                    $this_robot->update_session();

                    // If the target robot was the player, we gotta update the session
                    if ($this_player->player_side == 'left'
                        && empty($this_battle->flags['player_battle'])
                        && empty($this_battle->flags['challenge_battle'])){
                        $ptoken = $this_player->player_token;
                        $rtoken = $this_robot->robot_token;
                        if (!empty($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken])){
                            $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_item'] = $new_item_token;
                        }
                    }

                    // Adjust the position of the ability attachment and show it moving before removing
                    $ability_attachment_info['ability_frame_offset'] = array('x' => -90, 'y' => 30, 'z' => -21);
                    $this_robot->robot_attachments[$ability_attachment_token] = $ability_attachment_info;
                    $this_robot->update_session();
                    $this_battle->events_create(false, false, '', '');

                }
                // Otherwise, if already has an item, do nothing
                else {

                    // Do nothing further

                }

                // Remove the ability attachment from view and give the item to the user
                unset($this_robot->robot_attachments[$ability_attachment_token]);
                $this_robot->update_session();

            }
            // Otherwise, put the item back and place and continue
            else {

                // Re-attach the item to the target robot
                $target_robot->robot_item = $old_item_token;
                $target_robot->update_session();

            }
        }

        // Now that all the damage has been dealt, allow the player to check for disabled
        $target_player->check_robots_disabled($this_player, $this_robot);

        // Return true on success
        return true;

    }
);
?>
