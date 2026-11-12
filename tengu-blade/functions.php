<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Update the ability image if the user is Tengu Man or using a Tengu Man alt
        if ($this_robot->robot_image == 'tengu-man' || strpos($this_robot->robot_image, 'tengu-man_') === 0){
            $this_ability->set_image($this_ability->ability_token.'-2');
        } else {
            $this_ability->set_image($this_ability->ability_base_image);
        }

        // Target the opposing robot, raise the blade (Frame 0)
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 0, 30, 10, $this_robot->print_name().' readies the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Move the user forward so it looks like they are striking the target
        $this_robot->set_frame('throw');
        $this_robot->set_frame_offset('x', 120);

        // Define and attach the slashing animation to the target (Frames 2-4)
        $this_attachment_token = 'ability_'.$this_ability->ability_token.'_slash';
        $this_attachment_info = array(
            'class' => 'ability',
            'ability_id' => $this_ability->ability_id,
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_image,
            'attachment_token' => $this_attachment_token,
            'ability_frame' => 2,
            'ability_frame_animate' => array(2, 3, 4),
            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => 20)
            );
        $target_robot->set_attachment($this_attachment_token, $this_attachment_info);

        // Check to see if there's a Super Block at this position
        $static_attachment_info = array();
        $static_ability_token = 'super-arm';
        $static_ability_object_token = 'super-block';
        $static_key = $target_robot->get_static_attachment_key();
        $static_attachment_token = 'ability_'.$static_ability_token.'_'.$static_ability_object_token.'_'.$static_key;
        if (!empty($this_battle->battle_attachments[$static_key][$static_attachment_token])){
            $static_attachment_info = $this_battle->battle_attachments[$static_key][$static_attachment_token];
            if (!isset($static_attachment_info['ability_frame_styles'])){ $static_attachment_info['ability_frame_styles'] = ''; }
            $static_attachment_info['attachment_weaknesses'][] = '*';
            $static_attachment_info['ability_frame_styles'] .= 'opacity: 0.5; ';
            $static_attachment_info['ability_frame_styles'] .= 'transform: translate('.($target_player->player_side == 'left' ? '-30%' : '30%').', 0); ';
            $this_battle->set_attachment($static_key, $static_attachment_token, $static_attachment_info);
        }

        // Apply the first hit of the Tengu Blade
        $this_battle->queue_sound_effect('ambush-sound');
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(1, 90, 0, 10, 'The '.$this_ability->print_name().' slashed the target!'),
            'failure' => array(1, 90, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(1, 90, 0, 10, 'The '.$this_ability->print_name().' slashed the target!'),
            'failure' => array(1, 90, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

        // If the target wasn't disabled, apply the second hit
        if ($this_ability->ability_results['this_result'] != 'failure' && $target_robot->robot_status != 'disabled' && $target_robot->robot_energy > 0){
            $this_battle->queue_sound_effect('ambush-sound');
            $this_ability->damage_options_update(array(
                'success' => array(1, 90, 0, 10, 'The '.$this_ability->print_name().' slashed the target again!'),
                'failure' => array(1, 90, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'success' => array(1, 90, 0, 10, 'The '.$this_ability->print_name().' slashed the target again!'),
                'failure' => array(1, 90, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
                ));
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
        }

        // If the target still wasn't disabled, apply the third hit
        if ($this_ability->ability_results['this_result'] != 'failure' && $target_robot->robot_status != 'disabled' && $target_robot->robot_energy > 0){
            $this_battle->queue_sound_effect('ambush-sound');
            $this_ability->damage_options_update(array(
                'success' => array(1, 90, 0, 10, 'The '.$this_ability->print_name().' slashed the target one more time!'),
                'failure' => array(1, 90, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'success' => array(1, 90, 0, 10, 'The '.$this_ability->print_name().' slashed the target one more time!'),
                'failure' => array(1, 90, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
                ));
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
        }

        // Check to see if there's still a Super Block at this position, and destroy it
        if (!empty($this_battle->battle_attachments[$static_key][$static_attachment_token])){
            $static_attachment_info = $this_battle->battle_attachments[$static_key][$static_attachment_token];
            if ($this_ability->ability_results['this_result'] != 'failure'){ $static_attachment_info['attachment_duration'] = 0; }
            else { $static_attachment_info['ability_frame_styles'] = ''; }
            $this_battle->set_attachment($static_key, $static_attachment_token, $static_attachment_info);
        }

        // Clean up the slashing visual attachment from the target
        $target_robot->unset_attachment($this_attachment_token);

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

        // Update the ability image if the user is Tengu Man or using a Tengu Man alt
        if ($this_robot->robot_image == 'tengu-man' || strpos($this_robot->robot_image, 'tengu-man_') === 0){
            $this_ability->set_image($this_ability->ability_token.'-2');
        } else {
            $this_ability->set_image($this_ability->ability_base_image);
        }

        // If the user has Extended Range, allow bench targeting
        if ($this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Return true on success
        return true;

    }
);
?>
