<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot and ignite the burner (Wind-up, no lunge yet)
        $this_battle->queue_sound_effect('flame-sound');
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 80, 0, 10, $this_robot->print_name().' ignites the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Now move the user forward and lock them into the shoot frame for the continuous attack
        $this_robot->set_frame('shoot');
        $this_robot->set_frame_offset('x', 120);
        $this_robot->update_session();

        // Define the animation sequence (0, 1, 0, 2, repeat)
        $anim_sequence = array(0, 1, 0, 2);
        $anim_counter = 1; // Start at 1 since frame 0 was used for the wind-up

        // Determine current frame from sequence
        $current_frame = $anim_sequence[$anim_counter % count($anim_sequence)];

        // Inflict the first hit of damage (skipping the trigger_target cutaway to keep camera focused)
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(15, 0, 0),
            'success' => array($current_frame, 70, 0, 10, 'The '.$this_ability->print_name().' scorched the target!'),
            'failure' => array($current_frame, 70, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(10, 0, 0),
            'success' => array($current_frame, 70, 0, 10, 'The '.$this_ability->print_name().' warmed the target!'),
            'failure' => array($current_frame, 70, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        
        // Trigger damage and explicitly defer the disabled check so we can finish the animation
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
        
        // Increment the animation counter for the next hit
        $anim_counter++;

        // Calculate how much WE is required for repeated attacks
        $weapon_energy_required = $this_robot->calculate_weapon_energy($this_ability, $this_ability->ability_energy, $temp_ability_energy_mods);

        // Continue triggering the attack until target energy is zero OR user runs out of weapon energy
        while ($target_robot->robot_status != 'disabled' && $target_robot->robot_energy > 0 && $this_robot->robot_weapons >= $weapon_energy_required){

            // Decrement required weapon energy from this robot
            $this_robot->robot_weapons -= $weapon_energy_required;
            if ($this_robot->robot_weapons < 0){ $this_robot->robot_weapons = 0; }
            $this_robot->update_session();

            // Determine current frame from sequence
            $current_frame = $anim_sequence[$anim_counter % count($anim_sequence)];

            // Inflict damage on the opposing robot again
            $this_battle->queue_sound_effect('flame-sound');
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(15, 0, 0),
                'success' => array($current_frame, 70, 0, 10, 'The '.$this_ability->print_name().' burned the target!'),
                'failure' => array($current_frame, 70, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'kickback' => array(10, 0, 0),
                'success' => array($current_frame, 70, 0, 10, 'The '.$this_ability->print_name().' warmed the target!'),
                'failure' => array($current_frame, 70, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
                ));
                
            // Trigger damage and explicitly defer the disabled check again
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

            // Increment the animation counter for the next hit
            $anim_counter++;

        }

        // Reset the offset and move the user back to their original position
        $this_robot->set_frame('base');
        $this_robot->set_frame_offset('x', 0);
        $this_robot->update_session();

        // Now that all the damage has been dealt, allow the player to check for disabled
        $target_player->check_robots_disabled($this_player, $this_robot);

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
