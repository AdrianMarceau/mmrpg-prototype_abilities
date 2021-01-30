<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Collect session token for later
        $session_token = rpg_game::session_token();

        // If this robot is holding an item (and we're allowed), return to inventory
        if ($this_player->player_side == 'left'
            && !empty($this_robot->robot_item)
            && empty($this_battle->flags['player_battle'])
            && empty($this_battle->flags['challenge_battle'])
            && (empty($_SESSION[$session_token]['values']['battle_items'][$this_robot->robot_item])
                || $_SESSION[$session_token]['values']['battle_items'][$this_robot->robot_item] < MMRPG_SETTINGS_ITEMS_MAXQUANTITY)){

            // Create inventory slot if not exists yet then add one of these items to it
            if (empty($_SESSION[$session_token]['values']['battle_items'][$this_robot->robot_item])){ $_SESSION[$session_token]['values']['battle_items'][$this_robot->robot_item] = 0; }
            $temp_item_quantity_old = $_SESSION[$session_token]['values']['battle_items'][$this_robot->robot_item];
            $_SESSION[$session_token]['values']['battle_items'][$this_robot->robot_item] += 1;
            $temp_item_quantity_new = $_SESSION[$session_token]['values']['battle_items'][$this_robot->robot_item];

            // Remove the item from this robot in the session
            $ptoken = $this_player->player_token;
            $rtoken = $this_robot->robot_token;
            $rid = $this_robot->robot_id;
            if (!empty($_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_item'])){
                $_SESSION[$session_token]['values']['battle_settings'][$ptoken]['player_robots'][$rtoken]['robot_item'] = '';
            }
            if (!empty($_SESSION['ROBOTS_PRELOAD'][$this_battle->battle_token][$rid.'_'.$rtoken]['robot_item'])){
                $_SESSION['ROBOTS_PRELOAD'][$this_battle->battle_token][$rid.'_'.$rtoken]['robot_item'] = '';
            }

            // Remove the item from the robot and create an object out of it
            $old_item_token = $this_robot->robot_item;
            $old_item = rpg_game::get_item($this_battle, $this_player, $this_robot, array('item_token' => $old_item_token));
            $this_robot->set_item('');

            // If the old item happened to be a robot core, we may need to destroy a core shield
            if (strstr($old_item_token, '-core')){
                //$this_battle->events_create(false, false, 'debug', 'The knocked-off item was a robot core!');
                $lost_core_type = preg_replace('/-core$/', '', $old_item_token);
                $possible_attachment_token = 'ability_core-shield_'.$lost_core_type;
                if (!empty($this_robot->robot_attachments[$possible_attachment_token])){
                    $this_robot->unset_attachment($possible_attachment_token);
                }
            }

            // Update the ability's target options and trigger
            $temp_rotate_amount = 45;
            //$old_item->set_name('Unequip Item');
            $old_item->set_frame_styles('opacity: 0.5; transform: rotate('.$temp_rotate_amount.'deg); -webkit-transform: rotate('.$temp_rotate_amount.'deg); -moz-transform: rotate('.$temp_rotate_amount.'deg); ');
            $old_item->target_options_update(array(
                'frame' => 'summon',
                'success' => array(0, -10, 40, 20,
                    $this_robot->print_name().' unequipped '.$this_robot->get_pronoun('possessive2').' held item!'.
                    '<br /> The '.$old_item->print_name().' was added to the inventory! '.
                    '<span class="item_stat item_type item_type_none">'.$temp_item_quantity_old.' <sup style="bottom: 2px;">&raquo;</sup> '.$temp_item_quantity_new.'</span>'
                    )
                ));
            $this_robot->trigger_target($this_robot, $old_item, array('prevent_default_text' => true));
            //$old_item->reset_name();

            // If the item we added to the inventory was a shard, we may need to generate a new core
            if (strstr($old_item_token, '-shard')){
                //error_log('returned '.$old_item_token.' to inventory');
                $type_token = str_replace('-shard', '', $old_item_token);
                $shard_token = $type_token.'-shard';
                $shard_name = ucfirst($type_token).' Shard';
                $core_token = $type_token.'-core';
                $core_name = ucfirst($type_token).' Core';
                $num_shards = mmrpg_prototype_get_battle_item_count($shard_token);
                $num_cores = mmrpg_prototype_get_battle_item_count($core_token);
                //error_log('$num_'.$type_token.'_shards = '.$num_shards);
                //error_log('$num_'.$type_token.'cores = '.$num_cores);
                if ($num_shards >= MMRPG_SETTINGS_SHARDS_MAXQUANTITY){
                    $cores_generated = 0;
                    while ($num_shards >= MMRPG_SETTINGS_SHARDS_MAXQUANTITY){
                        //error_log('create new '.$type_token.' core from '.$type_token.' shards...');
                        mmrpg_prototype_dec_battle_item_count($shard_token, MMRPG_SETTINGS_SHARDS_MAXQUANTITY);
                        mmrpg_prototype_inc_battle_item_count($core_token, 1);
                        $cores_generated += 1;
                        $num_shards = mmrpg_prototype_get_battle_item_count($shard_token);
                        $num_cores = mmrpg_prototype_get_battle_item_count($core_token);
                        //error_log('$num_'.$type_token.'shards = '.$num_shards);
                        //error_log('$num_'.$type_token.'cores = '.$num_cores);
                        // Create the temporary item object for event creation using above parameters
                        $item_index_info = rpg_item::get_index_info($core_token);
                        $item_core_info = array('item_token' => $core_token, 'item_name' => $core_name, 'item_type' => $type_token);
                        $item_core_info['item_id'] = rpg_game::unique_item_id($this_robot->robot_id, $item_index_info['item_id']);
                        $item_core_info['item_token'] = $core_token;
                        $temp_core = rpg_game::get_item($this_battle, $this_player, $this_robot, $item_core_info);
                        $temp_core->set_name($item_core_info['item_name']);
                        $temp_core->set_image($item_core_info['item_token']);
                        // Collect or define the item variables
                        $temp_type_name = !empty($temp_core->item_type) ? ucfirst($temp_core->item_type) : 'Neutral';
                        $temp_core_colour = !empty($temp_core->item_type) ? $temp_core->item_type : 'none';
                        // Display the robot reward message markup
                        $all_shards_merged = empty($num_shards) ? true : false;
                        $event_header = $core_name.' Item Fusion';
                        $event_body = ($all_shards_merged ? 'The other' : 'Some of the other').' <span class="item_name item_type item_type_'.$type_token.'">'.$temp_type_name.' Shards</span> from the inventory started glowing&hellip;<br /> ';
                        $event_body .= rpg_battle::random_positive_word().' The glowing shards fused to create a new '.$temp_core->print_name().'! ';
                        $event_body .= ' <span class="item_stat item_type item_type_none">'.($num_cores - 1).' <sup style="bottom: 2px;">&raquo;</sup> '.($num_cores).'</span>';
                        $event_options = array();
                        $event_options['console_show_target'] = false;
                        $event_options['this_header_float'] = $this_player->player_side;
                        $event_options['this_body_float'] = $this_player->player_side;
                        $event_options['this_item'] = $temp_core;
                        $event_options['this_item_image'] = 'icon';
                        $event_options['console_show_this_player'] = false;
                        $event_options['console_show_this_robot'] = false;
                        $event_options['console_show_this_item'] = true;
                        $event_options['canvas_show_this_item'] = true;
                        $this_player->set_frame($cores_generated % 2 == 0 ? 'taunt' : 'victory');
                        $this_robot->set_frame($cores_generated % 2 == 0 ? 'taunt' : 'defend');
                        $temp_core->set_frame('base');
                        $temp_core->set_frame_offset(array('x' => 80, 'y' => 0, 'z' => 10));
                        $this_battle->events_create($this_robot, false, $event_header, $event_body, $event_options);
                    }
                }
            }

        }
        // Otherwise this action will always fail (and should have been disabled elsewhere anyway)
        else {

            // Print the failure message for the ability
            $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(9, 0, 0, -999, 'But nothing happened...')));
            $this_robot->trigger_target($this_robot, $this_ability, array('prevent_default_text' => true));

        }

        // Return true on success
        return true;

        }
);
?>