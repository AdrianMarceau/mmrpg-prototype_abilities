<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 100, -1, 10, $this_robot->print_name().' fires off a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(1, -60, -1, 10, 'The '.$this_ability->print_name().' zapped the target!'),
            'failure' => array(1, -90, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(1, -30, -1, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
            'failure' => array(1, -60, 0, -10, 'The '.$this_ability->print_name().' had no effect&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Disable their passive skill if the attack was successful
        if ($target_robot->robot_status != 'disabled'
            && $this_ability->ability_results['this_result'] != 'failure'){

            // Create an options object for this function and populate
            $options = rpg_game::new_options_object();
            $extra_objects = array('options' => $options);
            $extra_objects['this_ability'] = $this_ability;
            $extra_objects['this_player'] = $target_player;
            $extra_objects['this_robot'] = $target_robot;
            $extra_objects['target_player'] = $this_player;
            $extra_objects['target_robot'] = $this_robot;
            
            // Trigger this robot's custom function if one has been defined for this context
            $existing_counter = $target_robot->get_counter('skill_disabled');
            $target_robot->trigger_custom_function('rpg-skill_disable-skill_before', $extra_objects);
            $target_robot->set_counter('skill_disabled', 3);

            // Define and attach this ability's skillblock attachment
            $this_skillblock_token = 'ability_'.$this_ability->ability_token.'_skill-blocker';
            $this_skillblock_info = array(
                'class' => 'ability',
                'attachment_token' => $this_skillblock_token,
                'attachment_duration' => 3,
                //'ability_id' => $this_ability->ability_id.'_fx',
                'ability_token' => $this_ability->ability_token,
                'ability_image' => $this_ability->ability_base_image,
                'ability_frame' => 4,
                'ability_frame_animate' => array(4, 5),
                'ability_frame_offset' => array('x' => -5, 'y' => 0, 'z' => -6),
                'ability_frame_classes' => ' ',
                'ability_frame_styles' => 'opacity: 0.6; '
                );
            $this_skillblock = rpg_game::get_ability($this_battle, $target_player, $target_robot, $this_skillblock_info);
            $target_robot->set_attachment($this_skillblock_token, $this_skillblock_info);

            // Display an event showing this skill-blocking after effect
            if (empty($existing_counter)){
                $this_battle->queue_sound_effect('shields-down');
                $header = $this_robot->robot_name.'\'s '.$this_ability->ability_name;
                $body = 'The '.$this_ability->print_name().' disabled '.$target_robot->print_name_s().' passive skill!';
                $this_robot->set_frame('taunt');
                $target_robot->set_frame('defend');
                $this_battle->events_create($target_robot, false, $header, $body, array(
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $target_robot->player->player_side,
                    'event_flag_camera_focus' => $target_robot->robot_position,
                    'event_flag_camera_depth' => $target_robot->robot_key,
                    ));
                $this_robot->reset_frame();
                $target_robot->reset_frame();
            }

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
