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
            && empty($this_battle->flags['challenge_battle'])){

            // Define tokens for the item and its equipped counter
            $item_token = $this_robot->robot_item;
            $equipped_token = $item_token . '__equipped';

            // Calculate available inventory before unequipping for the battle message
            $inventory_count = !empty($_SESSION[$session_token]['values']['battle_items'][$item_token]) ? $_SESSION[$session_token]['values']['battle_items'][$item_token] : 0;
            $equipped_count_old = !empty($_SESSION[$session_token]['values']['battle_items'][$equipped_token]) ? $_SESSION[$session_token]['values']['battle_items'][$equipped_token] : 0;
            $available_count_old = max(0, $inventory_count - $equipped_count_old);

            // Decrement the equipped counter
            if (!isset($_SESSION[$session_token]['values']['battle_items'][$equipped_token])){
                $_SESSION[$session_token]['values']['battle_items'][$equipped_token] = 0;
            }
            $_SESSION[$session_token]['values']['battle_items'][$equipped_token] -= 1;

            // Ensure we don't drop below zero just in case
            if ($_SESSION[$session_token]['values']['battle_items'][$equipped_token] < 0){
                $_SESSION[$session_token]['values']['battle_items'][$equipped_token] = 0;
            }

            // Calculate available inventory after unequipping
            $equipped_count_new = $_SESSION[$session_token]['values']['battle_items'][$equipped_token];
            $available_count_new = max(0, $inventory_count - $equipped_count_new);

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
            $this_robot->set_base_item('');

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
            $this_battle->queue_sound_effect('get-item');
            $temp_rotate_amount = 45;
            //$old_item->set_name('Unequip Item');
            $old_item->set_frame_styles('opacity: 0.5; transform: rotate('.$temp_rotate_amount.'deg); -webkit-transform: rotate('.$temp_rotate_amount.'deg); -moz-transform: rotate('.$temp_rotate_amount.'deg); ');
            $old_item->target_options_update(array(
                'frame' => 'summon',
                'success' => array(0, -10, 40, 20,
                    $this_robot->print_name().' unequipped '.$this_robot->get_pronoun('possessive2').' held item!'.
                    '<br /> The '.$old_item->print_name().' was returned to the inventory! '.
                    '<span class="item_stat item_type item_type_none">'.$available_count_old.' <sup style="bottom: 2px;">&raquo;</sup> '.$available_count_new.'</span>'
                    )
                ));
            $this_robot->trigger_target($this_robot, $old_item, array('prevent_default_text' => true));
            //$old_item->reset_name();

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