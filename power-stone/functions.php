<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Attach three whirlwind attachments to the robot
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_info = array('class' => 'ability', 'ability_token' => $this_ability->ability_token, 'ability_frame' => 0, 'ability_frame_animate' => array(2, 3, 0, 1));
        $this_attachment_one = $this_attachment_info;
        $this_attachment_two = $this_attachment_info;
        $this_attachment_three = $this_attachment_info;
        $this_attachment_one['ability_frame_offset'] = array('x' => -35, 'y' => 35, 'z' => -10); // top-middle rock
        $this_attachment_two['ability_frame_offset'] = array('x' => -5, 'y' => -25, 'z' => -10); // bottom-right rock
        $this_attachment_three['ability_frame_offset'] = array('x' => -80, 'y' => -25, 'z' => -10); // bottom-left rock
        $this_robot->set_attachment($this_attachment_token.'_1', $this_attachment_one);
        $this_robot->set_attachment($this_attachment_token.'_2', $this_attachment_two);
        $this_robot->set_attachment($this_attachment_token.'_3', $this_attachment_three);

        // Target the opposing robot
        $this_battle->queue_sound_effect('spawn-sound');
        $this_battle->queue_sound_effect(array('name' => 'spawn-sound', 'delay' => 200));
        $this_battle->queue_sound_effect(array('name' => 'spawn-sound', 'delay' => 400));
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(9, 9, 9, -9999, $this_robot->print_name().' raises a trio of '.$this_ability->print_name(true).'!') // bottom-left rock
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Show the boulers rotating around the user for a frame before throwing
        $this_robot->set_frame('defend');
        $this_attachment_one['ability_frame'] = 1;
        $this_attachment_two['ability_frame'] = 1;
        $this_attachment_three['ability_frame'] = 1;
        $this_attachment_one['ability_frame_offset'] = array('x' => -59, 'y' => 0, 'z' => -10); // top-middle rock
        $this_attachment_two['ability_frame_offset'] = array('x' => 17, 'y' => -25, 'z' => -10); // bottom-right rock
        $this_attachment_three['ability_frame_offset'] = array('x' => -47, 'y' => -89, 'z' => -10); // bottom-left rock
        $this_robot->set_attachment($this_attachment_token.'_1', $this_attachment_one);
        $this_robot->set_attachment($this_attachment_token.'_2', $this_attachment_two);
        $this_robot->set_attachment($this_attachment_token.'_3', $this_attachment_three);
        $this_battle->events_create(false, false, '', '', array(
            'event_flag_camera_action' => true,
            'event_flag_camera_side' => $this_robot->player->player_side,
            'event_flag_camera_focus' => $this_robot->robot_position,
            'event_flag_camera_depth' => $this_robot->robot_key
            ));

        // Show the boulers rotating around the user for a frame before throwing
        $this_robot->set_frame('defend');
        $this_attachment_one['ability_frame'] = 2;
        $this_attachment_two['ability_frame'] = 2;
        $this_attachment_three['ability_frame'] = 2;
        $this_attachment_one['ability_frame_offset'] = array('x' => -50, 'y' => -50, 'z' => -10); // top-middle rock
        $this_attachment_two['ability_frame_offset'] = array('x' => 36, 'y' => -7, 'z' => -10); // bottom-right rock
        $this_attachment_three['ability_frame_offset'] = array('x' => 36, 'y' => -115, 'z' => -10); // bottom-left rock
        $this_robot->set_attachment($this_attachment_token.'_1', $this_attachment_one);
        $this_robot->set_attachment($this_attachment_token.'_2', $this_attachment_two);
        $this_robot->set_attachment($this_attachment_token.'_3', $this_attachment_three);
        $this_battle->events_create(false, false, '', '', array(
            'event_flag_camera_action' => true,
            'event_flag_camera_side' => $this_robot->player->player_side,
            'event_flag_camera_focus' => $this_robot->robot_position,
            'event_flag_camera_depth' => $this_robot->robot_key
            ));

        // Define the number of hits and the hit text
        $num_hits = 0;
        $num_misses = 0;
        $get_hit_text = function () use ($this_ability, $num_hits){
            switch ($num_hits){
                case 3: { return 'A third '.$this_ability->print_name().' '; break; }
                case 2: { return 'Another '.$this_ability->print_name().' '; break; }
                case 1: default: { return 'One of the '.$this_ability->print_name(true).' '; break; }
            }
        };
        $get_miss_text = function () use ($this_ability, $num_misses){
            switch ($num_misses){
                case 3: { return 'A third '.$this_ability->print_name().' '; break; }
                case 2: { return 'Another '.$this_ability->print_name().' '; break; }
                case 1: default: { return 'One of the '.$this_ability->print_name(true).' '; break; }
            }
        };

        // Define a quick function for removing all attachments quickly
        $remove_all_attachments = function () use ($this_robot, $this_attachment_token){
            $this_robot->unset_attachment($this_attachment_token.'_1');
            $this_robot->unset_attachment($this_attachment_token.'_2');
            $this_robot->unset_attachment($this_attachment_token.'_3');
        };

        // Inflict damage on the opposing robot
        $this_robot->set_frame('throw');
        $this_robot->unset_attachment($this_attachment_token.'_1');
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(4, 0, 5, 10, $get_hit_text().' crashed into the target!'),
            'failure' => array(4, -50, 5, -10, $get_miss_text().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(4, 0, 5, 10, $get_hit_text().' was absorbed by the target!'),
            'failure' => array(4, 0, 5, -10, $get_miss_text().' had no effect&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
        if ($target_robot->robot_status === 'disabled' || empty($target_robot->robot_energy)){ $remove_all_attachments(); }
        $this_robot->reset_frame();

        // If the hit was successful, trigger an appropriate stat boost
        if ($this_ability->ability_results['this_result'] !== 'failure'){
            // Increment the number of hits
            $num_hits++;
            // Call the global stat boost function with customized options
            rpg_ability::ability_function_stat_boost($this_robot, 'attack', 1, $this_ability, array(
                'initiator_robot' => $this_robot
                ));
        } else {
            // Increment the number of misses
            $num_misses++;
        }

        // Now that all the damage has been dealt, allow the player to check for disabled
        $target_player->check_robots_disabled($this_player, $this_robot);

        // As long as the target has not been disabled we can attempt another hit
        if ($target_robot->robot_status != 'disabled'){

            // Inflict damage on the opposing robot
            $this_robot->set_frame('throw');
            $this_robot->unset_attachment($this_attachment_token.'_2');
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(20, 0, 0),
                'success' => array(4, 0, 5, 10, 'Oh! '.$get_hit_text().' crashed into the target!'),
                'failure' => array(4, 0, 5, -10, $get_miss_text().' missed&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'kickback' => array(0, 0, 0),
                'frame' => 'taunt',
                'success' => array(4, 0, 5, 10, 'Oh no! '.$get_hit_text().' was absorbed by the target!'),
                'failure' => array(4, 0, 5, -10, $get_miss_text().' had no effect&hellip;')
                ));
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
            if ($target_robot->robot_status === 'disabled' || empty($target_robot->robot_energy)){ $remove_all_attachments(); }
            $this_robot->reset_frame();

            // If the hit was successful, trigger an appropriate stat boost
            if ($this_ability->ability_results['this_result'] !== 'failure'){
                // Increment the number of hits
                $num_hits++;
                // Call the global stat boost function with customized options
                rpg_ability::ability_function_stat_boost($this_robot, 'attack', 1, $this_ability, array(
                    'initiator_robot' => $this_robot
                    ));
            } else {
                // Increment the number of misses
                $num_misses++;
            }

            // Now that all the damage has been dealt, allow the player to check for disabled
            $target_player->check_robots_disabled($this_player, $this_robot);

            // If this attack returns and strikes a third time (random chance)
            if ($target_robot->robot_energy != 'disabled'){

                // Inflict damage on the opposing robot
                $this_robot->set_frame('throw');
                $this_robot->unset_attachment($this_attachment_token.'_3');
                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'kickback' => array(30, 0, 0),
                    'success' => array(4, 0, 5, 10, 'There it is! '.$get_hit_text().' crashed into the target!'),
                    'failure' => array(4, 0, 5, -10, $get_miss_text().' missed&hellip;')
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'kickback' => array(0, 0, 0),
                    'success' => array(4, 0, 5, 10, 'Wow! '.$get_hit_text().' was absorbed by the target!'),
                    'failure' => array(4, 0, 5, -10, $get_miss_text().' had no effect&hellip;')
                    ));
                $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
                if ($target_robot->robot_status === 'disabled' || empty($target_robot->robot_energy)){ $remove_all_attachments(); }
                $this_robot->reset_frame();

                // If the hit was successful, trigger an appropriate stat boost
                if ($this_ability->ability_results['this_result'] !== 'failure'){
                    // Increment the number of hits
                    $num_hits++;
                    // Call the global stat boost function with customized options
                    rpg_ability::ability_function_stat_boost($this_robot, 'attack', 1, $this_ability, array(
                        'initiator_robot' => $this_robot
                        ));
                } else {
                    // Increment the number of misses
                    $num_misses++;
                }

                // Now that all the damage has been dealt, allow the player to check for disabled
                $target_player->check_robots_disabled($this_player, $this_robot);
                if ($target_robot->robot_status === 'disabled'){ $remove_all_attachments(); }

            }

        }

        // Make sure we always remove any remaining attachments after the move concludes
        $remove_all_attachments();

        // Return true on success
        return true;

    }
);
?>
