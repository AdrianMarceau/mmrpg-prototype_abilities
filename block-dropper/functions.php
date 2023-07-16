<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define a variable to hold the number of blocks
        $number_of_blocks = 0;

        // Count the number of active robots on the target's side of the  field
        $target_robot_ids = array();
        $target_robots_active = $target_player->values['robots_active'];
        $target_robots_active_count = $target_player->counters['robots_active'];
        $get_next_target_robot = function($robot_id = 0) use($this_battle, $target_player, &$target_robot_ids){
            $robot_info = array();
            $active_robot_keys = array_keys($target_player->values['robots_active']);
            shuffle($active_robot_keys);
            foreach ($active_robot_keys AS $key_key => $robot_key){
                $robot_info = $target_player->values['robots_active'][$robot_key];
                if (!empty($robot_id) && $robot_info['robot_id'] !== $robot_id){ continue; }
                if (!in_array($robot_info['robot_id'], $target_robot_ids)){
                    $robot_id = $robot_info['robot_id'];
                    $target_robot_ids[] = $robot_id;
                    $next_target_robot = rpg_game::get_robot($this_battle, $target_player, $robot_info);
                    return $next_target_robot;
                    }
                }
            };

        // Attach up to two extra object attachments to the robot (for a total of three on-screen)
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_info = array(
            'class' => 'ability',
            'ability_id' => $this_ability->ability_id,
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 2,
            'ability_frame_animate' => array(2),
            'ability_frame_offset' => array('x' => 40, 'y' => 95, 'z' => 20)
            );

        // The first attachment always exists (though it's part of the attack itself)
        $this_attachment_info1 = $this_attachment_info;
        $target_robot_1 = $get_next_target_robot($target_robot->robot_id);
        $number_of_blocks++;

        // Only add an additional attachments if there are enough targets
        if ($target_robots_active_count >= 2){
            $this_attachment_info2 = $this_attachment_info;
            $this_attachment_info2['ability_id'] .= '02';
            $this_attachment_info2['ability_frame_offset'] = array('x' => 120, 'y' => 55, 'z' => 30);
            $this_attachment_info2['ability_frame'] = 4;
            $this_attachment_info2['ability_frame_animate'] = array(4);
            $this_robot->set_attachment($this_attachment_token.'_2', $this_attachment_info2);
            $target_robot_2 = $get_next_target_robot();
            $number_of_blocks++;
        }

        // Only add an additional attachments if there are enough targets
        if ($target_robots_active_count >= 3){
            $this_attachment_info3 = $this_attachment_info;
            $this_attachment_info3['ability_id'] .= '03';
            $this_attachment_info3['ability_frame_offset'] = array('x' => 140, 'y' => 140, 'z' => 20);
            $this_attachment_info2['ability_frame'] = 3;
            $this_attachment_info3['ability_frame_animate'] = array(3);
            $this_robot->set_attachment($this_attachment_token.'_3', $this_attachment_info3);
            $target_robot_3 = $get_next_target_robot();
            $number_of_blocks++;
        }

        // Only add an additional attachments if there are enough targets
        if ($target_robots_active_count >= 4){
            $this_attachment_info4 = $this_attachment_info;
            $this_attachment_info4['ability_id'] .= '04';
            $this_attachment_info4['ability_frame_offset'] = array('x' => 240, 'y' => 100, 'z' => 30);
            $this_attachment_info4['ability_frame'] = 5;
            $this_attachment_info4['ability_frame_animate'] = array(5);
            $this_robot->set_attachment($this_attachment_token.'_4', $this_attachment_info4);
            $target_robot_4 = $get_next_target_robot();
            $number_of_blocks++;
        }

        // Queue sound effects for each block being generated
        for ($i = 0; $i < $number_of_blocks; $i++){
            $this_battle->queue_sound_effect(array(
                'name' => 'summon-sound',
                'delay' => 0 + ($i * 200)
                ));
        }

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(
                $this_attachment_info1['ability_frame'],
                $this_attachment_info1['ability_frame_offset']['x'],
                $this_attachment_info1['ability_frame_offset']['y'],
                $this_attachment_info1['ability_frame_offset']['z'],
                $this_robot->print_name().' raises blocks with the '.$this_ability->print_name().'!'
                )
            ));
        $this_robot->trigger_target($target_robot_1, $this_ability);

        // Remove the first attachment as it is no-longer in from view
        if ($this_robot->has_attachment($this_attachment_token.'_1')){ $this_robot->unset_attachment($this_attachment_token.'_1'); }

        // Remove the second attachment as it is no-longer in from view
        if ($this_robot->has_attachment($this_attachment_token.'_2')){ $this_robot->unset_attachment($this_attachment_token.'_2'); }

        // Remove the third attachment as it is no-longer in from view
        if ($this_robot->has_attachment($this_attachment_token.'_3')){ $this_robot->unset_attachment($this_attachment_token.'_3'); }

        // Remove the fourth attachment as it is no-longer in from view
        if ($this_robot->has_attachment($this_attachment_token.'_4')){ $this_robot->unset_attachment($this_attachment_token.'_4'); }

        // Create an empty event for dramatic pause
        $this_robot->set_frame('base');
        $this_battle->events_create(false, false, '', '');

        // Inflict damage on the opposing robot
        $this_robot->set_frame('throw');
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(0, 0, 0),
            'success' => array(6, -45, 0, 10, 'The '.$this_ability->print_name().' crashed into the target!'),
            'failure' => array(6, -105, 0, -10, 'The '.$this_ability->print_name().' just missed the target&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(6, -45, 0, 10, 'The '.$this_ability->print_name().' crashed into the target!'),
            'failure' => array(6, -105, 0, -10, 'The '.$this_ability->print_name().' just missed the target&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot_1->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

        // If a second attachment has been created, we can fire it off at a different target
        if (isset($this_attachment_info2)){

            // Define the success/failure text variables
            $success_text = '';
            $failure_text = '';

            // Adjust damage/recovery text based on results
            if ($this_ability->ability_results['total_strikes'] == 1){ $success_text = 'Another block hit!'; }
            if ($this_ability->ability_results['total_misses'] == 1){ $failure_text = 'Another block missed!'; }

            // Remove the attachment before we fire it off as an ability sprite
            if ($this_robot->has_attachment($this_attachment_token.'_2')){ $this_robot->unset_attachment($this_attachment_token.'_2'); }

            // Attempt to trigger damage to the target robot again
            $this_ability->ability_results_reset();
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(0, 0, 0),
                'success' => array(7, -45, 0, 10, $success_text),
                'failure' => array(7, -105, 0, -10, $failure_text)
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'kickback' => array(0, 0, 0),
                'success' => array(7, -45, 0, 10, $success_text),
                'failure' => array(7, -105, 0, -10, $failure_text)
                ));
            $target_robot_2->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

        }

        // If a third attachment has been created, we can fire it off at a different target
        if (isset($this_attachment_info3)){

            // Adjust damage/recovery text based on results again
            if ($this_ability->ability_results['total_strikes'] == 1){ $success_text = 'Another block hit!'; }
            elseif ($this_ability->ability_results['total_strikes'] == 2){ $success_text = 'A third block hit!'; }
            if ($this_ability->ability_results['total_misses'] == 1){ $failure_text = 'Another block missed!'; }
            elseif ($this_ability->ability_results['total_misses'] == 2){ $failure_text = 'A third block missed!'; }

            // Remove the attachment before we fire it off as an ability sprite
            if ($this_robot->has_attachment($this_attachment_token.'_3')){ $this_robot->unset_attachment($this_attachment_token.'_3'); }

            // Attempt to trigger damage to the target robot a third time
            $this_ability->ability_results_reset();
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(0, 0, 0),
                'success' => array(8, -45, 0, 10, $success_text),
                'failure' => array(8, -105, 0, -10, $failure_text)
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'kickback' => array(0, 0, 0),
                'success' => array(8, -45, 0, 10, $success_text),
                'failure' => array(8, -105, 0, -10, $failure_text)
                ));
            $target_robot_3->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

        }

        // If a forth attachment has been created, we can fire it off at a different target
        if (isset($this_attachment_info4)){

            // Adjust damage/recovery text based on results again
            if ($this_ability->ability_results['total_strikes'] == 1){ $success_text = 'Another block hit!'; }
            elseif ($this_ability->ability_results['total_strikes'] == 2){ $success_text = 'A fourth block hit!'; }
            if ($this_ability->ability_results['total_misses'] == 1){ $failure_text = 'Another block missed!'; }
            elseif ($this_ability->ability_results['total_misses'] == 2){ $failure_text = 'A fourth block missed!'; }

            // Remove the attachment before we fire it off as an ability sprite
            if ($this_robot->has_attachment($this_attachment_token.'_4')){ $this_robot->unset_attachment($this_attachment_token.'_4'); }

            // Attempt to trigger damage to the target robot a third time
            $this_ability->ability_results_reset();
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(0, 0, 0),
                'success' => array(9, -45, 0, 10, $success_text),
                'failure' => array(9, -105, 0, -10, $failure_text)
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'kickback' => array(0, 0, 0),
                'success' => array(9, -45, 0, 10, $success_text),
                'failure' => array(9, -105, 0, -10, $failure_text)
                ));
            $target_robot_4->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

        }

        // Return the user to their base frame now that we're done
        $this_robot->set_frame('base');

        // Loop through all robots on the target side and disable any that need it
        $target_robots_active = $target_player->get_robots();
        foreach ($target_robots_active AS $key => $robot){
            if ($robot->robot_id == $target_robot->robot_id){ $temp_target_robot = $target_robot; }
            else { $temp_target_robot = $robot; }
            if (($temp_target_robot->robot_energy < 1 || $temp_target_robot->robot_status == 'disabled')
                && empty($temp_target_robot->flags['apply_disabled_state'])){
                $temp_target_robot->trigger_disabled($this_robot);
            }
        }

        // Return true on success
        return true;

    }
);
?>
