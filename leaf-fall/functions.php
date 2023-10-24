<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Count the number of active robots on the target's side of the  field
        $target_robot_ids = array();
        $target_robots_active = $target_player->values['robots_active'];
        $target_robots_active_count = $target_player->counters['robots_active'];
        $get_random_target_robot = function($robot_id = 0) use($this_battle, $target_player, &$target_robot_ids){
            $robot_info = array();
            $active_robot_keys = array_keys($target_player->values['robots_active']);
            shuffle($active_robot_keys);
            foreach ($active_robot_keys AS $key_key => $robot_key){
                $robot_info = $target_player->values['robots_active'][$robot_key];
                if (!empty($robot_id) && $robot_info['robot_id'] !== $robot_id){ continue; }
                $robot_id = $robot_info['robot_id'];
                $random_target_robot = rpg_game::get_robot($this_battle, $target_player, $robot_info);
                if (!in_array($robot_info['robot_id'], $target_robot_ids)){ $target_robot_ids[] = $robot_id; }
                return $random_target_robot;
                }
            };

        // Function to handle the repeated logic
        $this_ability_damage = $this_ability->ability_damage;
        $handle_strike = function($target_robot, $strike_num) use (&$this_robot, &$this_ability, &$this_ability_damage) {
            if ($target_robot->robot_status !== 'disabled') {
                // Pregenerate the success and failure text for this iteration
                if ($strike_num === 1){
                    $damage_success_text = 'The '.$this_ability->print_name().'\'s leaves slice through the target!';
                    $damage_failure_text = 'The '.$this_ability->print_name().'\'s leaves just missed the target&hellip;';
                    $recovery_success_text = 'The '.$this_ability->print_name().'\'s leaves were absorbed by the target!';
                    $recovery_failure_text = 'The '.$this_ability->print_name().'\'s leaves just missed the target&hellip;';
                } else {
                    $damage_success_text = ($strike_num > 1) ? 'Another one of the leaves hit!' : 'One of the leaves hit!';
                    $damage_failure_text = ($strike_num > 1) ? 'Another one of the leaves missed!' : 'One of the leaves missed!';
                    $recovery_success_text = ($strike_num > 1) ? 'Another one of the leaves was absorbed!' : 'One of the leaves was absorbed!';
                    $recovery_failure_text = ($strike_num > 1) ? 'Another one of the leaves missed!' : 'One of the leaves missed!';
                }
                // Update the damage and recovery options for this iteration
                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'kickback' => array((($strike_num % 2) * 2 - 1) * 10, 0, 0),
                    'success' => array(2 + ($strike_num % 2), 35 * (($strike_num % 2) * 2 - 1), 0, 10, $damage_success_text),
                    'failure' => array(2 + ($strike_num % 2), 95 * (($strike_num % 2) * 2 - 1), 0, -10, $damage_failure_text)
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'kickback' => array((($strike_num % 2) * 2 - 1) * 5, 0, 0),
                    'success' => array(2 + ($strike_num % 2), 35 * (($strike_num % 2) * 2 - 1), 0, 10, $recovery_success_text),
                    'failure' => array(2 + ($strike_num % 2), 95 * (($strike_num % 2) * 2 - 1), 0, -10, $recovery_failure_text)
                    ));

                // Define the amount and attempt to trigger damage to the target robot
                $energy_damage_amount = $this_ability->ability_damage;
                $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false);
                $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false, $trigger_options);
                if ($this_ability->ability_results['this_result'] != 'failure'){ $this_ability_damage += 1; }
            }
        };

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 10, 140, 10, $this_robot->print_name().' summons a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability, array('prevent_stats_text' => true));

        // Put the user in a throw frame for the duration of the attack
        $this_robot->set_frame('throw');

        // Loop through five times and inflict damage on the robots one-by-one
        for ($i = 0; $i < 5; $i++){
            $strike_num = $i + 1;
            if ($i === 0){
                $handle_strike($target_robot, $strike_num);
            } else {
                $temp_target_robot = $get_random_target_robot();
                if (empty($temp_target_robot)){ break; }
                $handle_strike($temp_target_robot, $strike_num);
            }
        }

        // Return the user to their base frame now that we're done
        $this_robot->set_frame('base');

        // Now that all the damage has been dealt, allow the player to check for disabled
        $target_player->check_robots_disabled($this_player, $this_robot);

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Update the ability image if the user is in their alt image
        $alt_image_triggers = array('wood-man_alt' => 2, 'wood-man_alt2' => 3, 'wood-man_alt9' => 4);
        if (isset($alt_image_triggers[$this_robot->robot_image])){ $ability_image = $this_ability->ability_token.'-'.$alt_image_triggers[$this_robot->robot_image]; }
        else { $ability_image = $this_ability->ability_base_image; }
        $this_ability->set_image($ability_image);

        // Return true on success
        return true;

        }
);
?>
