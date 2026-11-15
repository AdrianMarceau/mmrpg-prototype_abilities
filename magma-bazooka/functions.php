<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot and wind-up the attack (Frame 0)
        $this_robot->set_frame('shoot');
        $this_battle->queue_sound_effect('fireball-sound');
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 100, 0, 10, $this_robot->print_name().' fires the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot (Frame 1)
        $this_battle->queue_sound_effect('blast-sound');
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(15, 0, 0),
            'success' => array(1, -20, 0, 10, 'The '.$this_ability->print_name().' crashed into the target!'),
            'failure' => array(1, -60, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(10, 0, 0),
            'success' => array(1, -20, 0, 10, 'The '.$this_ability->print_name().' warmed the target!'),
            'failure' => array(1, -60, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Return the user to their base frame
        $this_robot->set_frame('base');

        // If the ability was successful and actually did damage, check for weakness application
        if ($target_robot->robot_status != 'disabled'
            && $this_ability->ability_results['this_result'] != 'failure'
            && !empty($this_ability->ability_results['this_amount'])
            && !$target_robot->has_weakness('flame')){

            // Define a quick array of heat-themed prefixes and pick one at random
            $prefixes = array(
                'Yikes, that\'s hot!',
                'Wow, what a burn!',
                'Ouch, too hot!',
                'Sizzling!',
                'Melting!',
                'Burning up!',
                'Way too hot!'
            );
            $prefix = $prefixes[array_rand($prefixes)].' ';

            // Inflict the weakness on the target by adding it to the appropriate lists
            $weaknesses = $target_robot->get_weaknesses();
            $weaknesses[] = 'flame';
            $target_robot->set_weaknesses($weaknesses);
            $target_robot->set_base_weaknesses($weaknesses);
            
            // If the target had a resistance to this type, make sure we remove it
            $resistances = $target_robot->get_resistances();
            if (in_array('flame', $resistances)){
                $resistances = array_diff($resistances, array('flame'));
                $target_robot->set_resistances($resistances);
                $target_robot->set_base_resistances($resistances);
            }
            
            // If the target had an immunity to this type, make sure we remove it
            $immunities = $target_robot->get_immunities();
            if (in_array('flame', $immunities)){
                $immunities = array_diff($immunities, array('flame'));
                $target_robot->set_immunities($immunities);
                $target_robot->set_base_immunities($immunities);
            }
            
            // If the robot had an affinity to this type, make sure we remove it
            $affinities = $target_robot->get_affinities();
            if (in_array('flame', $affinities)){
                $affinities = array_diff($affinities, array('flame'));
                $target_robot->set_affinities($affinities);
                $target_robot->set_base_affinities($affinities);
            }

            // Play sound and show a visual event for the weakness being inflicted
            $target_robot->set_frame('defend');
            
            // Apply a sepia/red filter to simulate being super-heated
            $target_robot->set_frame_styles('filter: sepia(1) saturate(3) hue-rotate(310deg) brightness(0.9); ');
            
            $this_battle->queue_sound_effect('debuff-received');
            $this_battle->events_create($target_robot, false, $this_robot->robot_name.'\'s '.$this_ability->ability_name,
                $prefix.$target_robot->print_name().' is feeling the heat! <br />'.
                ucfirst($target_robot->get_pronoun('subject')).' suddenly found '.$target_robot->get_pronoun('reflexive').' weak to the '.rpg_type::print_span('flame').' type!',
                array(
                    'this_ability' => $this_ability,
                    'canvas_show_this_ability_overlay' => false,
                    'canvas_show_this_ability_underlay' => false,
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $target_robot->player->player_side,
                    'event_flag_camera_focus' => $target_robot->robot_position,
                    'event_flag_camera_depth' => $target_robot->robot_key
                    )
                );
                
            // Reset frames and styles after the event
            $target_robot->reset_frame();
            $target_robot->reset_frame_styles();
            $this_battle->events_create($target_robot, false, '', '',
                array(
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $target_robot->player->player_side,
                    'event_flag_camera_focus' => $target_robot->robot_position,
                    'event_flag_camera_depth' => $target_robot->robot_key
                    )
                );
        }

        // Return true on success
        return true;

    }
);
?>
