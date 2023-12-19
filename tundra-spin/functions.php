<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define a few random success messages to use
        $temp_success_messages = array(
            'Oh! '.ucfirst($this_robot->get_pronoun('subject')).' hit again!',
            'Wow! Another hit?!',
            'Nice moves! One more time!',
            ucfirst($this_robot->get_pronoun('subject')).' just keeps going!',
            'Oh wow! Another rotation!',
            'Cold! It\'s another hit!'
            );

        // Check to see how high the relevant field multiplier is
        $num_extra_hits = 0;
        $max_hit_counter = 2;
        $relevant_boost_type = 'freeze';
        $relevant_field_multiplier = $this_field->get_multiplier($relevant_boost_type);
        if (!empty($relevant_field_multiplier) && $relevant_field_multiplier > 1){
            $num_extra_hits = floor(($relevant_field_multiplier - 1) / 0.5);
            $max_hit_counter += $num_extra_hits;
        }
        //error_log('$relevant_boost_type: '.print_r($relevant_boost_type, true));
        //error_log('$relevant_field_multiplier: '.print_r($relevant_field_multiplier, true));
        //error_log('$num_extra_hits: '.print_r($num_extra_hits, true));
        //error_log('$max_hit_counter: '.print_r($max_hit_counter, true));

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token.'_fx';
        $this_attachment_info = array(
            'class' => 'ability',
            'attachment_token' => $this_attachment_token,
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 4,
            'ability_frame_animate' => array(4,5),
            'ability_frame_offset' => array('x' => 0, 'y' => 50, 'z' => -10)
            );

        // Now move the user forward so it looks like they're still up close
        $this_robot->set_frame('summon');
        $this_battle->events_create();
        $this_robot->reset_frame();

        // Target the opposing robot
        $this_battle->queue_sound_effect('ice-sound');
        $this_battle->queue_sound_effect(array('name' => 'zephyr-sound', 'volume' => 0.8));
        $this_ability->target_options_update(array(
            'frame' => 'slide',
            'kickback' => array(120, 0, 10),
            'success' => array(0, 5, 0, -10, $this_robot->print_name().' starts the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Add the snow effect on the target robot
        $target_robot->set_attachment($this_attachment_token, $this_attachment_info);

        // Now move the user forward so it looks like they're colliding with the target
        $this_robot->set_frame('slide');
        $this_robot->set_frame_offset('x', 260);
        $this_robot->set_frame_styles('');

        // Inflict damage on the opposing robot
        $this_battle->queue_sound_effect('ice-sound');
        $this_battle->queue_sound_effect(array('name' => 'zephyr-sound', 'volume' => 0.6));
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(mt_rand(5, 10), 0, 0),
            'success' => array(2, -40, 0, -10, 'The '.$this_ability->print_name().' hit the target!'),
            'failure' => array(1, -80, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(2, -40, 0, -10, 'The '.$this_ability->print_name().' hit the target!'),
            'failure' => array(1, -80, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
        //error_log('$energy_damage_amount: '.print_r($energy_damage_amount, true));

        // If this attack returns and strikes a second time (random chance)
        $extra_hit_counter = 0;
        //error_log('$max_hit_counter: '.print_r($max_hit_counter, true));
        //error_log('$extra_hit_counter: '.print_r($extra_hit_counter, true));
        while ($target_robot->robot_energy > 0
            && $target_robot->robot_status !== 'disabled'
            && ($extra_hit_counter + 1) < $max_hit_counter){

            // Define the offset variables
            $temp_frame = $extra_hit_counter == 0 || $extra_hit_counter % 2 == 0 ? 3 : 2;
            $temp_offset = 40 - ($extra_hit_counter * 10);
            $temp_offset = $temp_frame == 0 ? $temp_offset * -1 : ceil($temp_offset * 0.75);
            $temp_accuracy = $this_ability->ability_base_accuracy - $extra_hit_counter;
            if ($temp_accuracy < 1){ $temp_accuracy = 1; }
            $this_ability->set_accuracy($temp_accuracy);

            // Check to see if the user's sprite should change to a different pose
            $temp_robot_frame = 'base';
            if ($extra_hit_counter % 7 === 0){ $temp_robot_frame = 'base2'; }
            elseif ($extra_hit_counter % 3 === 0){ $temp_robot_frame = 'taunt'; }
            elseif ($extra_hit_counter % 2 === 0){ $temp_robot_frame = 'defend'; }
            $this_robot->set_frame($temp_robot_frame);

            // Check to see if the target doctor's sprite should 'flip' visually
            $temp_doctor_flip = $extra_hit_counter % 2 === 0 ? true : false;
            if ($extra_hit_counter >= 10){ $temp_doctor_flip = mt_rand(0, 1) === 0 ? true : false; }
            $target_player->set_frame_styles($temp_doctor_flip ? 'transform: scaleX(-1); ' : '');

            // Decide whether we're coming back or sliding forward
            $slide_direction = $extra_hit_counter % 2 === 0 ? 'back' : 'forward';
            //error_log('$slide_direction: '.print_r($slide_direction, true));

            // If the last hit was odd-numbered, that means we're coming back
            if ($slide_direction === 'back'){

                // Now move the user forward so it looks like they're colliding with the target
                $this_robot->set_frame('slide');
                $this_robot->set_frame_offset('x', mt_rand(120, 140));
                $this_robot->set_frame_styles('transform: scaleX(-1); -moz-transform: scaleX(-1); -webkit-transform: scaleX(-1); ');

            }
            // Otherwise if the last hit was even-numbered, that means we're sliding forward
            elseif ($slide_direction === 'forward'){

                // Now move the user forward so it looks like they're colliding with the target
                $this_robot->set_frame('slide');
                $this_robot->set_frame_offset('x', mt_rand(260, 280));
                $this_robot->set_frame_styles('');

            }

            // Inflict damage on the opposing robot
            $this_battle->queue_sound_effect('ice-sound');
            $this_battle->queue_sound_effect(array('name' => 'zephyr-sound', 'volume' => 0.3));
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(mt_rand(0, 20), 0, 0),
                'success' => array($temp_frame, $temp_offset, 0, -10, $temp_success_messages[array_rand($temp_success_messages)]),
                'failure' => array($temp_frame, ($temp_offset * 2), 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'kickback' => array(0, 0, 0),
                'frame' => 'taunt',
                'success' => array($temp_frame, $temp_offset, 0, -10, $temp_success_messages[array_rand($temp_success_messages)]),
                'failure' => array($temp_frame, ($temp_offset * 2), 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
                ));
            $energy_damage_amount = $energy_damage_amount;
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
            //error_log('$energy_damage_amount: '.print_r($energy_damage_amount, true));

            // Increment the hit counter
            $extra_hit_counter++;
            //error_log('$extra_hit_counter: '.print_r($extra_hit_counter, true));

            // If the ability was a failure and it was due to an immunity, we can break early
            if ($this_ability->ability_results['this_result'] === 'failure'
                && ($target_robot->has_immunity($this_ability->ability_type)
                    || $target_robot->has_affinity($this_ability->ability_type))){
                //error_log('Breaking early due to immunity or affinity');
                break;
            }

        }

        // Now move the user forward so it looks like they're still up close
        $this_robot->set_frame('defend');
        $this_robot->set_frame_offset('x', 120);
        $this_robot->set_frame_styles('transform: scaleX(-1); -moz-transform: scaleX(-1); -webkit-transform: scaleX(-1); ');
        $this_battle->events_create();

        // Reset the accuracy back to base values
        $this_ability->reset_accuracy();

        // Reset the offset and move the user back to their position
        $this_player->reset_frame();
        $this_player->reset_frame_styles();
        $this_robot->reset_frame();
        $this_robot->reset_frame_offset();
        $this_robot->reset_frame_styles();

        // Reset the offset and move the user back to their position
        $target_player->reset_frame();
        $target_player->reset_frame_styles();
        $target_robot->reset_frame();
        $target_robot->reset_frame_offset();
        $target_robot->reset_frame_styles();

        // Remove the snow effect from the target robot
        $target_robot->unset_attachment($this_attachment_token);

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
