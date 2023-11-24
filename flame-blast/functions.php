<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 100, 0, 10, $this_robot->print_name().' throws '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Define a function for checking if the target has chain attachment and returning it if so
        $static_ability_token = 'oil-shooter';
        $static_ability_object_token = 'crude-oil';
        $static_ability_attachment_token = 'ability_'.$static_ability_token.'_'.$static_ability_object_token;
        $has_chain_attachment = function($target_robot, &$chain_attachment = array()) use (
                $this_battle, $this_player, $this_robot,
                $static_ability_token, $static_ability_object_token, $static_ability_attachment_token
                ){
            $static_key = $target_robot->get_static_attachment_key();
            $static_attachment_token = $static_ability_attachment_token.'_'.$static_key;
            if (!empty($this_battle->battle_attachments[$static_key][$static_attachment_token])){
                $chain_attachment['key'] = $static_key;
                $chain_attachment['token'] = $static_attachment_token;
                $chain_attachment['attachment'] = &$this_battle->battle_attachments[$static_key][$static_attachment_token];
                return true;
                } else {
                return false;
                }
            };

        // Define a function for applying the visual effect to a given chain attachment
        $apply_chain_attachment_effect = function(&$chain_attachment) use (
                $this_battle, $this_player, $this_robot,
                $static_ability_token, $static_ability_object_token, $static_ability_attachment_token
                ){
            if (!isset($chain_attachment['attachment']['ability_frame_styles'])){ $chain_attachment['attachment']['ability_frame_styles'] = ''; }
            $chain_attachment['attachment']['ability_frame_styles'] .= 'filter: brightness(5) sepia(1) saturate(2) hue-rotate(335deg); ';
            $this_battle->set_attachment($chain_attachment['key'], $chain_attachment['token'], $chain_attachment['attachment']);
            };

        // Define a function for applying updating the attachment weakness to everything for a given chain attachment
        $apply_chain_attachment_weakness = function(&$chain_attachment) use (
                $this_battle, $this_player, $this_robot,
                $static_ability_token, $static_ability_object_token, $static_ability_attachment_token
                ){
            if (!isset($chain_attachment['attachment']['attachment_weaknesses'])){ $chain_attachment['attachment']['attachment_weaknesses'] = array(); }
            $chain_attachment['attachment']['attachment_weaknesses'][] = '*';
            $this_battle->set_attachment($chain_attachment['key'], $chain_attachment['token'], $chain_attachment['attachment']);
            };

        // Define a function for straight-up removing a given attachment from the target
        $remove_chain_attachment = function(&$chain_attachment) use (
                $this_battle, $this_player, $this_robot,
                $static_ability_token, $static_ability_object_token, $static_ability_attachment_token
                ){
            $this_battle->unset_attachment($chain_attachment['key'], $chain_attachment['token']);
            };

        // Inflict damage on the opposing robot
        $target_has_chain_attachment = $has_chain_attachment($target_robot, $target_chain_attachment);
        if ($target_has_chain_attachment){ $apply_chain_attachment_effect($target_chain_attachment); }
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'sticky' => true,
            'kickback' => array(5, 0, 0),
            'success' => array(1, 0, 0, 10, 'The '.$this_ability->print_name().' burned through target!'),
            'failure' => array(1, 0, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'sticky' => true,
            'frame' => 'taunt',
            'kickback' => array(5, 0, 0),
            'success' => array(0, 0, 0, 10, 'The '.$this_ability->print_name().' simmered the target!'),
            'failure' => array(0, 0, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
        $first_strike_success = $this_ability->ability_results['this_result'] === 'success' ? true : false;
        $target_is_disabled = empty($target_robot->robot_energy) || $target_robot->robot_status === 'disabled' ? true : false;

        // Now we make sure the attachment automatically disappears after the next attack
        // or immediately if the target has already been disabled by the blast
        if ($target_has_chain_attachment){
            if (!$target_is_disabled){ $apply_chain_attachment_weakness($target_chain_attachment); }
            else { $remove_chain_attachment($target_chain_attachment); }
        }

        // Initiate a second strike as long as first didn't KO the target
        if (!$target_is_disabled){
            
            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'sticky' => true,
                'kickback' => array(25, 15, 0),
                'success' => array(2, 0, 0, 10, ($first_strike_success
					? 'And there\'s the second hit!'
                    : '...but it flared up for a second hit!'
                    )),
                'failure' => array(2, 0, 0, -10, ($first_strike_success
					? 'Oh! The second hit missed!'
                    : 'Oh! The second hit missed too!'
					))
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'sticky' => true,
                'kickback' => array(10, 5, 0),
                'frame' => 'taunt',
                'success' => array(2, 0, 0, 10, ($first_strike_success
					? 'And there it goes again!'
                    : '...but it flared up again and was enjoyed by the target!!'
                    )),
                'failure' => array(2, 0, 0, -10, ($first_strike_success
					? 'Oh! The second hit missed!'
					: 'Oh! The second hit missed too!'
					))
                ));
            $energy_damage_amount = $this_ability->ability_damage2;
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
            $target_is_disabled = empty($target_robot->robot_energy) || $target_robot->robot_status === 'disabled' ? true : false;

        }

        // Regardless of what happened, make sure we remove the chain attachment if possible
        if ($target_has_chain_attachment){ $remove_chain_attachment($target_chain_attachment); }

        // If the target did have a chain attachment though, it's time to loop through and see
        // who else we can hit with this attack by spreading the fire to them one-by-one
        $target_robots_active = $target_player->get_robots_active();
        if (!empty($target_robots_active)
            && $target_has_chain_attachment){
            //error_log('target had chain attachment');
            foreach ($target_robots_active AS $key => $robot){
                if ($robot->robot_id === $target_robot->robot_id){ continue; }
                //error_log('checking if '.$robot->robot_string.' has chain attachment beneath them');
                $robot_has_chain_attachment = $has_chain_attachment($robot, $robot_chain_attachment);
                if ($robot_has_chain_attachment){
                    //error_log('found chain attachment beneath '.$robot->robot_string);
                    $apply_chain_attachment_effect($robot_chain_attachment);
                    // Apply the first round of damage to the target and check if they've been disabled
                    $this_ability->damage_options_update(array(
                        'kind' => 'energy',
                        'sticky' => true,
                        'kickback' => array(5, 0, 0),
                        'success' => array(1, 0, 0, 10, 'The '.$this_ability->print_name().' burned through target!'),
                        'failure' => array(1, 0, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
                        ));
                    $this_ability->recovery_options_update(array(
                        'kind' => 'energy',
                        'sticky' => true,
                        'frame' => 'taunt',
                        'kickback' => array(5, 0, 0),
                        'success' => array(0, 0, 0, 10, 'The '.$this_ability->print_name().' simmered the target!'),
                        'failure' => array(0, 0, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
                        ));
                    $energy_damage_amount = $this_ability->ability_damage;
                    $robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
                    $robot_is_disabled = empty($robot->robot_energy) || $robot->robot_status === 'disabled' ? true : false;
                    if (!$robot_is_disabled){ $apply_chain_attachment_weakness($robot_chain_attachment); }
                    else { $remove_chain_attachment($robot_chain_attachment); }
                    // If not disabled, apply the second round of damage to the target again
                    if (!$robot_is_disabled){
                        $this_ability->damage_options_update(array(
                            'kind' => 'energy',
                            'sticky' => true,
                            'kickback' => array(25, 15, 0),
                            'success' => array(2, 0, 0, 10, 'And there\'s the second hit!'),
                            'failure' => array(2, 0, 0, -10, 'Oh! The second hit missed!')
                            ));
                        $this_ability->recovery_options_update(array(
                            'kind' => 'energy',
                            'sticky' => true,
                            'kickback' => array(10, 5, 0),
                            'frame' => 'taunt',
                            'success' => array(2, 0, 0, 10, 'And there it goes again!'),
                            'failure' => array(2, 0, 0, -10, 'Oh! The second hit missed!')
                            ));
                        $energy_damage_amount = $this_ability->ability_damage2;
                        $robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
                        $robot_is_disabled = empty($robot->robot_energy) || $robot->robot_status === 'disabled' ? true : false;
                    }
                    $remove_chain_attachment($robot_chain_attachment);
                }

            }
        }

        // Now that all the damage has been dealt, allow the player to check for disabled
        $target_player->check_robots_disabled($this_player, $this_robot);

        // Return true on success
        return true;

    }
);
?>
