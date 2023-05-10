<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's first attachment token
        $splash_attachment_token = 'ability_'.$this_ability->ability_token.'_splash';
        $splash_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 4,
            'ability_frame_animate' => array(4),
            'ability_frame_offset' => array('x' => 20, 'y' => 0, 'z' => 10),
            'attachment_token' => $splash_attachment_token
        );

        // Define the ability's Y-offset given the user
        $x_offset_values = array(140, 150, 140, 130);
        $x_offset = $x_offset_values[0];
        $y_offset_values = array(0, -25, 0, 25);
        $y_offset = $y_offset_values[0];
        
        // Use a different attacking frame for the robot depending on who is using the ability
        $target_frame = 'throw';
        if ($this_robot->robot_token === 'yamato-man'){ $target_frame = 'defend'; }

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => $target_frame,
            'success' => array(0, $x_offset, $y_offset, 10, $this_robot->print_name().' releases a '.$this_ability->print_name().'!'),
        ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(20, 0, 0),
            'success' => array(1, 15, $y_offset, 10, 'The '.$this_ability->print_name().' pierced through the target!'),
            'failure' => array(0, -65, $y_offset, -10, 'The '.$this_ability->print_name().' missed&hellip;')
        ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'frame' => 'taunt',
            'kickback' => array(10, 0, 0),
            'success' => array(1, 15, $y_offset, 10, 'The '.$this_ability->print_name().' pierced through the target!'),
            'failure' => array(0, -65, $y_offset, -10, 'The '.$this_ability->print_name().' missed&hellip;')
        ));
        $energy_damage_amount = $this_ability->ability_damage;
        $trigger_options = array('apply_target_attachment_damage_breakers' => false);
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, true, $trigger_options);

        // Calculate how much WE is required for repeated attacks
        $weapon_energy_required = $this_robot->calculate_weapon_energy($this_ability, $this_ability->ability_energy, $temp_ability_energy_mods);

        // Continue triggering the attack until target disabled OR user runs out of weapon energy
        $loop_key = 0;
        while ($target_robot->robot_status != 'disabled'
               && $this_robot->robot_weapons >= $weapon_energy_required){
            
            // Immediately increment loop as we've already shot once
            $loop_key++; 
            
            // Tweak the offsets before each hit
            $y_offset_key = $loop_key % count($y_offset_values);
            $x_offset_key = $loop_key % count($x_offset_values);
            $y_offset = $y_offset_values[$y_offset_key];
            $x_offset = $x_offset_values[$x_offset_key];

            // Decrement required weapon energy from this robot
            $this_robot->robot_weapons -= $weapon_energy_required;
            if ($this_robot->robot_weapons < 0){ $this_robot->robot_weapons = 0; }
            $this_robot->update_session();

            // Target the opposing robot
            $this_ability->target_options_update(array(
                'frame' => $target_frame,
                'success' => array(0, $x_offset, $y_offset, 10, $this_robot->print_name().' releases another '.$this_ability->print_name().'!'),
            ));
            $this_robot->trigger_target($target_robot, $this_ability);

            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(20, 0, 0),
                'success' => array(1, 15, $y_offset, 10, 'The '.$this_ability->print_name().' pierced through the target!'),
                'failure' => array(0, -65, $y_offset, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'frame' => 'taunt',
                'kickback' => array(10, 0, 0),
                'success' => array(1, 15, $y_offset, 10, 'The '.$this_ability->print_name().' pierced through the target!'),
                'failure' => array(0, -65, $y_offset, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
            $energy_damage_amount = $this_ability->ability_damage;
            $trigger_options = array('apply_target_attachment_damage_breakers' => false);
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, true, $trigger_options);

        }

        // Check to see if there's a Super Block at this position
        $static_ability_token = 'super-arm';
        if ($target_robot->robot_position == 'active'){ $static_key = $target_player->player_side.'-active'; }
        else { $static_key = $target_player->player_side.'-bench-'.$target_robot->robot_key; }
        $static_attachment_token = 'ability_'.$static_ability_token.'_'.$static_key;
        if (!empty($this_battle->battle_attachments[$static_key][$static_attachment_token])){
            $static_attachment_info = $this_battle->battle_attachments[$static_key][$static_attachment_token];
            if ($this_ability->ability_results['this_result'] != 'failure'){ $static_attachment_info['attachment_duration'] = 0; }
            else { $static_attachment_info['ability_frame_styles'] = ''; }
            $this_battle->battle_attachments[$static_key][$static_attachment_token] = $static_attachment_info;
            $this_battle->update_session();
        }

        // Reset the offset and move the user back to their position
        $this_robot->set_frame('base');
        $this_robot->set_frame_offset('x', 0);

        // Disable the target if this ability brought them to zero
        if ($target_robot->robot_energy <= 0){ $target_robot->trigger_disabled($this_robot); }

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If the user has Extended Range, allow bench targeting
        if ($this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Return true on success
        return true;

    }
);
?>
