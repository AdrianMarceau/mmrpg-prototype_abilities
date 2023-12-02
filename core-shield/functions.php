<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Collect the first and second shield type, if applicable
        $first_shield_type = $this_ability->ability_type;
        $second_shield_type = false;
        if (!empty($this_ability->ability_type2)
            && $this_ability->ability_type2 !== 'shield'){
            $second_shield_type = $this_ability->ability_type2;
        }

        // Define a function to use for applying core shields to each individual robot
        $apply_core_shield_to_robot = function($current_target_key, $current_target_robot)
            use ($this_battle, $this_player, $this_robot, $this_ability,
                $first_shield_type, $second_shield_type){

                // Generate the first static attachment info for the robot's elemental Core Shield
                $existing_shields = !empty($current_target_robot->robot_attachments) ? substr_count(implode('|', array_keys($current_target_robot->robot_attachments)), 'ability_core-shield_') : 0;
                $shield_attachment_info = rpg_ability::get_static_core_shield($first_shield_type, 3, $existing_shields);
                $shield_attachment_token = $shield_attachment_info['attachment_token'];
                $shield_attachment = new rpg_ability($this_battle, $this_player, $this_robot, $shield_attachment_info);
                $shield_attachment = new rpg_ability($this_battle, $this_player, $current_target_robot, $shield_attachment_info);

                // If applicable, generate the second static attachment info for the robot's elemental Core Shield
                if (!empty($second_shield_type)){
                    $shield2_attachment_info = rpg_ability::get_static_core_shield($second_shield_type, 3, ($existing_shields + 1));
                    $shield2_attachment_token = $shield2_attachment_info['attachment_token'];
                    $shield2_attachment = new rpg_ability($this_battle, $this_player, $this_robot, $shield2_attachment_info);
                    $shield2_attachment = new rpg_ability($this_battle, $this_player, $current_target_robot, $shield2_attachment_info);
                }

                // If his is the first application of the shield, we should show the summon message too
                if ($current_target_key === 0){

                    // Attach temporarily core shields to the user for animation only
                    $this_robot->set_attachment($shield_attachment_token.'_temp', $shield_attachment_info);
                    if (!empty($second_shield_type)){ $this_robot->set_attachment($shield2_attachment_token.'_temp', $shield2_attachment_info); }

                    // Show the summoning event for the one or two shields right now
                    $this_battle->queue_sound_effect('charge-sound');
                    $this_ability->target_options_update(array(
                        'frame' => 'summon',
                        'success' => array(0, -9999, -9999, -9999,
                            $this_robot->print_name().' uses the '.$this_ability->print_name().' technique!'
                            )
                        ));
                    $this_robot->trigger_target($this_robot, $this_ability, array('prevent_default_text' => true));

                    // Remove temporary core shields from the user for animation only
                    $this_robot->unset_attachment($shield_attachment_token.'_temp');
                    if (!empty($second_shield_type)){ $this_robot->unset_attachment($shield2_attachment_token.'_temp'); }

                }

                // Now add the REAL core shields to the target (user or otherwise) and leave 'em
                $current_target_robot->set_attachment($shield_attachment_token, $shield_attachment_info);
                if (!empty($second_shield_type)){ $current_target_robot->set_attachment($shield2_attachment_token, $shield2_attachment_info); }

                // Show the summoning event for the one or two shields right now
                $this_battle->queue_sound_effect('small-buff-received');
                $secondary_text = (preg_match('/^(a|e|i|o|u)/i', $first_shield_type) ? 'an' : 'a').' '.rpg_type::print_span($first_shield_type).'-type shield';
                if (!empty($second_shield_type)){ $secondary_text = rpg_type::print_span($first_shield_type).' and '.rpg_type::print_span($second_shield_type).'-type shields'; }
                $this_ability->target_options_update(array(
                    'frame' => 'taunt',
                    'success' => array(0, -9999, -9999, -9999,
                        'The '.$this_ability->print_name().' generated '.$secondary_text.'! <br /> '.
                        $current_target_robot->print_name().' is now protected from the element'.(!empty($second_shield_type) ? 's' : '').'!'
                        )
                    ));
                $current_target_robot->trigger_target($current_target_robot, $this_ability, array('prevent_default_text' => true));



            };

        // Collect a list of this player's active robots so we can loop through 'em
        $this_robots_active = $this_player->get_robots_active();
        foreach ($this_robots_active AS $current_target_key => $current_target_robot){

            // Adjust the target if it is the user themselves so we don't make duplicates
            if ($current_target_robot->robot_id === $this_robot->robot_id){ $current_target_robot = $this_robot; }

            // Apply the core shield to the current target robot
            $apply_core_shield_to_robot($current_target_key, $current_target_robot);

        }

        // Return true on success
        return true;

        },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Collect this robots core and item types
        $ability_base_type = !empty($this_ability->ability_base_type) ? $this_ability->ability_base_type : '';
        $robot_core_type = !empty($this_robot->robot_core) ? $this_robot->robot_core : '';
        $robot_item_type = !empty($this_robot->robot_item) && strstr($this_robot->robot_item, '-core') ? str_replace('-core', '', $this_robot->robot_item) : '';

        // Define the types for this ability
        $ability_types = array();
        $ability_types[] = $ability_base_type;
        if (!empty($robot_core_type) && $robot_core_type != 'copy' && !in_array($robot_core_type, $ability_types)){ $ability_types[] = $robot_core_type; }
        if (!empty($robot_item_type) && $robot_item_type != 'copy' && !in_array($robot_item_type, $ability_types)){ $ability_types[] = $robot_item_type; }
        $ability_types = array_unique($ability_types);
        $ability_types = array_reverse($ability_types);
        $ability_types = array_slice($ability_types, 0, 2);

        // Collect this robot's primary type and change its image if necessary
        $this_ability->set_image($this_ability->ability_token.'_'.$ability_types[0]);
        $this_ability->set_type($ability_types[0]);
        if (!empty($ability_types[1])){ $this_ability->set_type2($ability_types[1]); }
        else { $this_ability->set_type2(''); }

        // Return true on success
        return true;

        }
);
?>
