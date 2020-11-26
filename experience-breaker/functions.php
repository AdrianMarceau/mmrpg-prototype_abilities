<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If the multiplier is already at the limit of 0x, this ability fails
        if (isset($this_field->field_multipliers['experience'])
            && $this_field->field_multipliers['experience'] <= MMRPG_SETTINGS_MULTIPLIER_MIN){

            // Target this robot's self and show the ability failing
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(9, 0, 0, -10,
                    $this_robot->print_name().' activated the '.$this_ability->print_name().'!<br />'.
                    'But the battle\'s experience won\'t go any lower&hellip;'
                    )
                ));
            $this_robot->trigger_target($this_robot, $this_ability);

            // Return true on success (well, failure, but whatever)
            return true;

        }
        // Else if this is a challenge or a player battle, this ability also fails
        elseif (!$this_battle->flags['allow_experience_points']){
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(9, 0, 0, -10,
                    $this_robot->print_name().' activated the '.$this_ability->print_name().'!<br />'.
                    'But experience multipliers have no effect here!'
                    )
                ));
            $this_robot->trigger_target($this_robot, $this_ability, array('prevent_default_text' => true));
            return false;
        }

        // Target this robot's self and show the ability triggering
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(9, 0, 0, -10,
                $this_robot->print_name().' activated the '.$this_ability->print_name().'!<br />'.
                'The ability altered the conditions of the battle field&hellip;'
                )
            ));
        $this_robot->trigger_target($this_robot, $this_ability);

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_info = array(
            'class' => 'ability',
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 0,
            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => -10)
            );

        // Update this and the target robot's frame to a defense/damage
        $this_robot->robot_frame = 'defend';
        $this_robot->update_session();
        $target_robot->robot_frame = 'damage';
        $target_robot->update_session();

        // Attach this ability attachment to this robot temporarily
        $this_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
        $this_robot->update_session();

        // Attach this ability to all robots on this player's side of the field
        $backup_robots_active = $this_player->values['robots_active'];
        $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
        if ($backup_robots_active_count > 0 && $this_player->player_side === 'left'){
            // Loop through the this's benched robots, inflicting les and less damage to each
            $this_key = 0;
            foreach ($backup_robots_active AS $key => $info){
                if ($info['robot_id'] == $this_robot->robot_id){ continue; }
                $temp_this_robot = rpg_game::get_robot($this_battle, $this_player, $info);
                // Attach this ability attachment to the this robot temporarily
                $temp_this_robot->robot_frame = 'defend';
                $temp_this_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
                $temp_this_robot->update_session();
                $this_key++;
            }
        }

        // Attach this ability to all robots on the target's side of the field
        $backup_robots_active = $target_player->values['robots_active'];
        $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
        if ($backup_robots_active_count > 0 && $target_player->player_side === 'left'){
            // Loop through the target's benched robots, inflicting les and less damage to each
            $target_key = 0;
            foreach ($backup_robots_active AS $key => $info){
                $temp_target_robot = rpg_game::get_robot($this_battle, $target_player, $info);
                // Attach this ability attachment to the target robot temporarily
                $temp_target_robot->robot_frame = 'damage';
                $temp_target_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
                $temp_target_robot->update_session();
                $target_key++;
            }
        }

        // Create or decrease the experience booster for this field
        if (!isset($this_field->field_multipliers['experience'])){ $this_field->field_multipliers['experience'] = 1.0; }
        $this_field->field_multipliers['experience'] *= 0.5;
        if ($this_field->field_multipliers['experience'] < MMRPG_SETTINGS_MULTIPLIER_MIN){ $this_field->field_multipliers['experience'] = MMRPG_SETTINGS_MULTIPLIER_MIN; }
        $this_field->update_session();

        // Create the event to show this experience boost
        $random_sayings = array('Oh no!', 'It worked!', 'That\'s not good!');
        $this_battle->events_create($this_robot, false, $this_field->field_name.' Multipliers',
            $random_sayings[array_rand($random_sayings)].' The <span class="ability_name ability_type ability_type_experience">Experience Points</span> harshly fell!<br />'.
            'The multiplier is now at <span class="ability_name ability_type ability_type_experience">Experience x '.number_format($this_field->field_multipliers['experience'], 1).'</span>!'
            );

        // Remove this ability from all robots on this player's side of the field
        $backup_robots_active = $this_player->values['robots_active'];
        $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
        if ($backup_robots_active_count > 0 && $this_player->player_side === 'left'){
            // Loop through the this's benched robots, inflicting les and less damage to each
            $this_key = 0;
            foreach ($backup_robots_active AS $key => $info){
                if ($info['robot_id'] == $this_robot->robot_id){ continue; }
                $temp_this_robot = rpg_game::get_robot($this_battle, $this_player, $info);
                // Attach this ability attachment to the this robot temporarily
                $temp_this_robot->robot_frame = 'base';
                unset($temp_this_robot->robot_attachments[$this_attachment_token]);
                $temp_this_robot->update_session();
                $this_key++;
            }
        }

        // Remove this ability from all robots on the target's side of the field
        $backup_robots_active = $target_player->values['robots_active'];
        $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
        if ($backup_robots_active_count > 0 && $target_player->player_side === 'left'){
            // Loop through the target's benched robots, inflicting les and less damage to each
            $target_key = 0;
            foreach ($backup_robots_active AS $key => $info){
                $temp_target_robot = rpg_game::get_robot($this_battle, $target_player, $info);
                // Attach this ability attachment to the target robot temporarily
                $temp_target_robot->robot_frame = 'base';
                unset($temp_target_robot->robot_attachments[$this_attachment_token]);
                $temp_target_robot->update_session();
                $target_key++;
            }
        }

        // Attach this ability attachment to this robot temporarily
        unset($this_robot->robot_attachments[$this_attachment_token]);
        $this_robot->update_session();

        // Return true on success
        return true;

    }
);
?>
