<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Collect the battle turn and its counter position
        $this_battle_turn = $this_battle->counters['battle_turn'];
        if ($this_battle_turn > 3){ $this_battle_turn = $this_battle_turn % 3; }

        // Define which type of weapon will be generated and power
        $this_swing_stage = 1;
        if ($this_battle_turn % 3 == 0){
            $this_swing_stage = 3;
            $this_swing_weapon = 'vacuum';
            $this_ability->set_image($this_ability->ability_token.'-3');
            $this_ability->set_damage($this_ability->ability_base_damage * 3);
        }
        elseif ($this_battle_turn % 2 == 0){
            $this_swing_stage = 2;
            $this_swing_weapon = 'umbrella';
            $this_ability->set_image($this_ability->ability_token.'-2');
            $this_ability->set_damage($this_ability->ability_base_damage * 2);
        }
        else {
            $this_swing_stage = 1;
            $this_swing_weapon = 'broom';
            $this_ability->set_image($this_ability->ability_token.'-1');
            $this_ability->reset_damage();
        }

        // Update the ability image if the user is in their alt image
        $alt_image_triggers = array('roll_alt', 'roll_alt3', 'roll_alt5');
        if (in_array($this_robot->robot_image, $alt_image_triggers)){
            $this_ability->set_image($this_ability->ability_image.'-b');
        }

        // Target the opposing robot
        $trigger_options = array();
        $trigger_options['prevent_default_text'] = true;
        for ($i = 0; $i < $this_swing_stage; $i++){
            $this_battle->queue_sound_effect(array(
                'name' => 'get-weird-item',
                'delay' => 0 + (50 * $i)
                ));
        }
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'kickback' => array(15, 0, 0),
            'success' => array(0, 30, 10, 10,
                $this_robot->print_name().' uses the '.$this_ability->print_name().
                ' to generate '.(preg_match('/^(a|e|i|o|u)/i', $this_swing_weapon) ? 'an' : 'a').' '.
                $this_swing_weapon.'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability, $trigger_options);

        // Move the user forward so it looks like their swining the weapon
        $this_robot->set_frame('throw');
        $this_robot->set_frame_offset('x', 310);

        // Check to see if there's a Super Block at this position
        $static_attachment_info = array();
        $static_ability_token = 'super-arm';
        $static_ability_object_token = 'super-block';
        if ($target_robot->robot_position == 'active'){ $static_key = $target_player->player_side.'-active'; }
        else { $static_key = $target_player->player_side.'-bench-'.$target_robot->robot_key; }
        $static_attachment_token = 'ability_'.$static_ability_token.'_'.$static_ability_object_token.'_'.$static_key;
        //error_log('Checking for super block at '.$static_key.' with token '.$static_attachment_token);
        //error_log('$this_battle->battle_attachments = '.print_r($this_battle->battle_attachments, true));
        if (!empty($this_battle->battle_attachments[$static_key][$static_attachment_token])){
            $static_attachment_info = $this_battle->battle_attachments[$static_key][$static_attachment_token];
            if (!isset($static_attachment_info['ability_frame_styles'])){ $static_attachment_info['ability_frame_styles'] = ''; }
            $static_attachment_info['attachment_weaknesses'][] = '*';
            $static_attachment_info['ability_frame_styles'] .= 'opacity: 0.5; ';
            $static_attachment_info['ability_frame_styles'] .= 'transform: translate('.($target_player->player_side == 'left' ? '-30%' : '30%').', 0); ';
            $this_battle->set_attachment($static_key, $static_attachment_token, $static_attachment_info);
        }

        // Inflict damage on the opposing robot with a broom
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(20, 0, 0),
            'success' => array(1, 30, 0, 10, 'The '.$this_ability->print_name().'\'s '.$this_swing_weapon.' smashed the target!'),
            'failure' => array(1, -60, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

        // Check to see if there's still a Super Block at this position
        if (!empty($this_battle->battle_attachments[$static_key][$static_attachment_token])){
            $static_attachment_info = $this_battle->battle_attachments[$static_key][$static_attachment_token];
            if ($this_ability->ability_results['this_result'] != 'failure'){ $static_attachment_info['attachment_duration'] = 0; }
            else { $static_attachment_info['ability_frame_styles'] = ''; }
            $this_battle->set_attachment($static_key, $static_attachment_token, $static_attachment_info);
        }

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

        // Update the ability damage and image based on turn
        $next_battle_turn = $this_battle->counters['battle_turn'] + 1;
        if ($next_battle_turn > 3){ $next_battle_turn = $next_battle_turn % 3; }

        if ($next_battle_turn % 3 == 0){
            $this_ability->set_image($this_ability->ability_token.'-3');
            $this_ability->set_damage($this_ability->ability_base_damage * 3);
        } elseif ($next_battle_turn % 2 == 0){
            $this_ability->set_image($this_ability->ability_token.'-2');
            $this_ability->set_damage($this_ability->ability_base_damage * 2);
        } else {
            $this_ability->set_image($this_ability->ability_token.'-1');
            $this_ability->reset_damage();
        }

        // Update the ability image if the user is in their alt image
        $alt_image_triggers = array('roll_alt', 'roll_alt3', 'roll_alt5');
        if (in_array($this_robot->robot_image, $alt_image_triggers)){
            $this_ability->set_image($this_ability->ability_image.'-b');
        }

        // If the user has Extended Range, allow bench targeting
        if ($this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Return true on success
        return true;

        }
);
?>
