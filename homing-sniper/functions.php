<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Attach three whirlwind attachments to the robot
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_info = array('class' => 'ability', 'ability_token' => $this_ability->ability_token, 'ability_frame' => 0, 'ability_frame_animate' => array(1, 2));
        $this_robot->robot_attachments[$this_attachment_token.'_1'] = $this_attachment_info;
        $this_robot->robot_attachments[$this_attachment_token.'_2'] = $this_attachment_info;
        $this_robot->robot_attachments[$this_attachment_token.'_1']['ability_frame_offset'] = array('x' => 75, 'y' => -25, 'z' => 10);
        $this_robot->robot_attachments[$this_attachment_token.'_2']['ability_frame_offset'] = array('x' => 95, 'y' => 25, 'z' => 10);
        $this_robot->update_session();

        // Target the opposing robot
        $this_battle->queue_sound_effect('whirlwind-sound');
        $this_battle->queue_sound_effect(array('name' => 'whirlwind-sound', 'delay' => 200));
        $this_battle->queue_sound_effect(array('name' => 'whirlwind-sound', 'delay' => 400));
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 115, -25, 10, $this_ability->print_name().' fires whirlwinds!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Update the two whirlwind's animation frames
        $this_robot->robot_attachments[$this_attachment_token.'_1']['ability_frame'] = 1;
        $this_robot->robot_attachments[$this_attachment_token.'_2']['ability_frame'] = 1;
        $this_robot->update_session();

        // Inflict damage on the opposing robot
        $this_battle->queue_sound_effect(array('name' => 'whirlwind-sound', 'volume' => 0.6));
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(5, 0, 0),
            'success' => array(1, -80, -25, 10, 'A whirlwind hit!'),
            'failure' => array(1, -100, -25, -10, 'One of the whirlwinds missed!')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(1, -80, -25, 10, 'A whilrwind hit!'),
            'failure' => array(1, -100, -25, -10, 'One of the whirlwinds missed!')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Ensure the target has not been disabled
        if ($target_robot->robot_status != 'disabled'){

            // Define the success/failure text variables
            $success_text = '';
            $failure_text = '';

            // Adjust damage/recovery text based on results
            if ($this_ability->ability_results['total_strikes'] == 1){ $success_text = 'Another whirlwind hit!'; }
            if ($this_ability->ability_results['total_misses'] == 1){ $failure_text = 'Another whirlwind missed!'; }

            // Remove the second extra whirlwind attached to the robot
            if (isset($this_robot->robot_attachments[$this_attachment_token.'_2'])){
                unset($this_robot->robot_attachments[$this_attachment_token.'_2']);
                $this_robot->update_session();
            }

            // Update the remaining whirlwind's animation frame
            $this_robot->robot_attachments[$this_attachment_token.'_1']['ability_frame'] = 2;
            $this_robot->update_session();

            // Attempt to trigger damage to the target robot again
            $this_battle->queue_sound_effect(array('name' => 'whirlwind-sound', 'volume' => 0.6));
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(10, 0, 0),
                'success' => array(2, -40, 25, 10, $success_text),
                'failure' => array(2, -60, 25, -10, $failure_text)
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'kickback' => array(0, 0, 0),
                'success' => array(2, -40, 25, 10, $success_text),
                'failure' => array(2, -60, 25, -10, $failure_text)
                ));
            $target_robot->trigger_damage($this_robot, $this_ability,  $energy_damage_amount);

            // Ensure the target has not been disabled
            if ($target_robot->robot_status != 'disabled'){

                // Adjust damage/recovery text based on results again
                if ($this_ability->ability_results['total_strikes'] == 1){ $success_text = 'Another whirlwind hit!'; }
                elseif ($this_ability->ability_results['total_strikes'] == 2){ $success_text = 'A third whirlwind hit!'; }
                if ($this_ability->ability_results['total_misses'] == 1){ $failure_text = 'Another whirlwind missed!'; }
                elseif ($this_ability->ability_results['total_misses'] == 2){ $failure_text = 'A third whirlwind missed!'; }

                // Remove the first extra whirlwind
                if (isset($this_robot->robot_attachments[$this_attachment_token.'_1'])){
                    unset($this_robot->robot_attachments[$this_attachment_token.'_1']);
                    $this_robot->update_session();
                }

                // Attempt to trigger damage to the target robot a third time
                $this_battle->queue_sound_effect(array('name' => 'whirlwind-sound', 'volume' => 0.6));
                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'kickback' => array(15, 0, 0),
                    'success' => array(1, -70, -25, 10, $success_text),
                    'failure' => array(1, -90, -25, -10, $failure_text)
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'kickback' => array(0, 0, 0),
                    'success' => array(1, -70, -25, 10, $success_text),
                    'failure' => array(1, -90, -25, -10, $failure_text)
                    ));
                $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

            }

        }

        // Remove the second whirlwind
        if (isset($this_robot->robot_attachments[$this_attachment_token.'_2'])){
            unset($this_robot->robot_attachments[$this_attachment_token.'_2']);
            $this_robot->update_session();
        }

        // Remove the third whirlwind
        if (isset($this_robot->robot_attachments[$this_attachment_token.'_1'])){
            unset($this_robot->robot_attachments[$this_attachment_token.'_1']);
            $this_robot->update_session();
        }

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
