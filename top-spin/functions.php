<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_battle->queue_sound_effect(array('name' => 'spinning-sound', 'volume' => 0.8));
        $this_battle->queue_sound_effect(array('name' => 'spinning-sound', 'volume' => 0.9, 'delay' => 200));
        $this_battle->queue_sound_effect(array('name' => 'spinning-sound', 'volume' => 1.0, 'delay' => 300));
        $target_options = array();
        $this_ability->target_options_update(array(
            'frame' => 'throw',
            'success' => array(0, 100, 0, 10, $this_robot->print_name().' throws a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability, $target_options);

        // Inflict damage on the opposing robot
        $this_battle->queue_sound_effect(array('name' => 'spinning-sound', 'volume' => 0.5));
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(mt_rand(5, 10), 0, 0),
            'success' => array(1, -20, 0, 10, 'The '.$this_ability->print_name().' hit the target!'),
            'failure' => array(1, -80, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(1, -20, 0, 10, 'The '.$this_ability->print_name().' hit the target!'),
            'failure' => array(1, -80, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Define a few random success messages to use
        $temp_success_messages = array('Oh! It hit again!', 'Wow! Another hit?!', 'Nice! One more time!', 'It just keeps spinning!', 'Oh wow! Another hit!', 'Awesome, another hit!');

        // If this attack returns and strikes a second time (random chance)
        $temp_hit_counter = 0;
        while ($this_ability->ability_results['this_result'] != 'failure'
            && $target_robot->robot_status != 'disabled'
            && $temp_hit_counter < $this_robot->robot_level){

            // Define the offset variables
            $temp_frame = $temp_hit_counter == 0 || $temp_hit_counter % 2 == 0 ? 1 : 0;
            $temp_offset = 40 - ($temp_hit_counter * 10);
            $temp_offset = $temp_frame == 0 ? $temp_offset * -1 : ceil($temp_offset * 0.75);
            $temp_accuracy = $this_ability->ability_base_accuracy - $temp_hit_counter;
            if ($temp_accuracy < 1){ $temp_accuracy = 1; }
            $this_ability->ability_accuracy = $temp_accuracy;
            $this_ability->update_session();

            // Check to see if the doctor's sprite should 'flip' visually
            $temp_doctor_flip = $temp_hit_counter % 2 === 0 ? true : false;
            if ($temp_hit_counter >= 10){ $temp_doctor_flip = mt_rand(0, 1) === 0 ? true : false; }
            $target_player->set_frame_styles($temp_doctor_flip ? 'transform: scaleX(-1); ' : '');

            // Inflict damage on the opposing robot
            $this_battle->queue_sound_effect(array('name' => 'spinning-sound', 'volume' => 0.5));
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(mt_rand(0, 20), 0, 0),
                'success' => array($temp_frame, $temp_offset, mt_rand(0, 10), 10, $temp_success_messages[array_rand($temp_success_messages)]),
                'failure' => array($temp_frame, ($temp_offset* 2), 0, -10, '')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'kickback' => array(0, 0, 0),
                'frame' => 'taunt',
                'success' => array($temp_frame, $temp_offset, mt_rand(0, 10), 10, $temp_success_messages[array_rand($temp_success_messages)]),
                'failure' => array($temp_frame, ($temp_offset * 2), 0, -10, '')
                ));
            $energy_damage_amount = ceil($energy_damage_amount * 1.10);
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

            // Increment the hit counter
            $temp_hit_counter++;

        }

        // Reset the accuracy back to base values
        $this_ability->ability_accuracy = $this_ability->ability_base_accuracy;
        $this_ability->update_session();

        // Reset the doctor back to base styles
        $target_player->reset_frame_styles();

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
