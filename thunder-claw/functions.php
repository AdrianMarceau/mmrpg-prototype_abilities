<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's visual tether attachment
        $this_attachment_token = 'ability_'.$this_ability->ability_token.'_tether';
        $this_attachment_info = array(
            'class' => 'ability',
            'ability_id' => $this_ability->ability_id,
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_image,
            'attachment_token' => $this_attachment_token,
            'ability_frame' => 1,
            'ability_frame_animate' => array(1),
            'ability_frame_offset' => array('x' => 80, 'y' => 0, 'z' => 10)
            );

        // Target the opposing robot and initialize the attack
        $this_battle->queue_sound_effect('electric-sound');
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 80, 0, 10, $this_robot->print_name().' extends the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Lock the user's frame to 'shoot' and attach the looping electricity visual
        $this_robot->set_frame('shoot');
        $this_robot->set_attachment($this_attachment_token, $this_attachment_info);

        // STEP 1: Drain Weapon Energy
        if ($target_robot->robot_energy > 0 && $target_robot->robot_status != 'disabled'){
            
            // Calculate 50% of the target's remaining weapon energy, rounded up
            $we_damage_amount = ceil($target_robot->robot_weapons * 0.5);

            if ($we_damage_amount > 0){
                
                // Play suction sound for the drain effect
                $this_battle->queue_sound_effect('suction-sound');
                
                // Inflict weapon energy damage on the target
                $this_ability->damage_options_update(array(
                    'kind' => 'weapons',
                    'percent' => false,
                    'modifiers' => false,
                    'kickback' => array(10, 0, 0),
                    'success' => array(2, 0, 0, 10, 'The '.$this_ability->print_name().' drained the target\'s weapon energy!'),
                    'failure' => array(2, 0, 0, -10, 'The target had no weapon energy to drain...')
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'weapons',
                    'percent' => false,
                    'modifiers' => false,
                    'frame' => 'taunt',
                    'kickback' => array(10, 0, 0),
                    'success' => array(2, 0, 0, 10, 'The '.$this_ability->print_name().' drained the target\'s weapon energy!'),
                    'failure' => array(2, 0, 0, -10, 'The target had no weapon energy to drain...')
                    ));
                
                // Trigger damage and extract the actual amount of WE drained from the target
                $we_damage_results = $target_robot->trigger_damage($this_robot, $this_ability, $we_damage_amount, false);
                $we_drained = isset($we_damage_results['this_amount']) ? $we_damage_results['this_amount'] : 0;

                // If WE was successfully drained, return it to the user
                if ($we_drained > 0 && $this_robot->robot_weapons < $this_robot->robot_base_weapons){
                    $this_ability->recovery_options_update(array(
                        'kind' => 'weapons',
                        'percent' => false,
                        'modifiers' => false,
                        'frame' => 'shoot',
                        'success' => array(0, 80, 0, 10, $this_robot->print_name().' absorbed the stolen energy!'),
                        'failure' => array(0, 80, 0, -10, $this_robot->print_name().' absorbed the stolen energy!')
                        ));
                    $this_robot->trigger_recovery($this_robot, $this_ability, $we_drained);
                }
            }
        }

        // STEP 2: Deal Life Energy Damage
        if ($target_robot->robot_energy > 0 && $target_robot->robot_status != 'disabled'){
            
            // Play electric shock sound for the damage impact
            $this_battle->queue_sound_effect('electric-sound');
            
            // Inflict life energy damage on the target
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'percent' => false,
                'modifiers' => true,
                'kickback' => array(20, 0, 0),
                'success' => array(2, 0, 0, 10, 'The '.$this_ability->print_name().' shocked the target!'),
                'failure' => array(2, 0, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'percent' => false,
                'modifiers' => true,
                'frame' => 'taunt',
                'kickback' => array(20, 0, 0),
                'success' => array(2, 0, 0, 10, 'The '.$this_ability->print_name().' healed the target!'),
                'failure' => array(2, 0, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
                ));
            $target_robot->trigger_damage($this_robot, $this_ability, $this_ability->ability_damage, false);
        }

        // Cleanup: Remove the visual tether attachment and return user to their base frame
        $this_robot->unset_attachment($this_attachment_token);
        $this_robot->set_frame('base');

        // Disable the target if this ability brought them to zero
        if ($target_robot->robot_energy <= 0){ $target_robot->trigger_disabled($this_robot); }

        // Return true on success
        return true;

    }
);
?>
