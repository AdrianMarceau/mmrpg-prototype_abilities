<?
$functions = array(
	'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Ensure this robot stays in the summon position for the duration of the attack
        $this_robot->robot_frame = 'summon';
        $this_robot->update_session();

       	// Print out the DEVPOWER header so we know it's serious
        $this_battle->queue_sound_effect('hyper-summon-sound');
		$this_battle->events_create(
            false, false,
            'DEVPOWER // CLEARMISSION',
            '<strong class="ability_name ability_type ability_type_nature_shield">DevPower : Clear Mission!</strong>'
            );

        // Count the number of active robots on the target's side of the field
        $target_robots_active = $target_player->counters['robots_active'];

        // Inflict damage on the opposing robot
        $damage_type = '';
        if ($this_robot->robot_core === 'copy'){ $damage_type = !empty($target_robot->robot_weaknesses[0]) ? $target_robot->robot_weaknesses[0] : ''; }
        elseif (!empty($this_robot->robot_core)){ $damage_type = $this_robot->robot_core; }
        if (in_array($damage_type, $target_robot->robot_affinities) || in_array($damage_type, $target_robot->robot_immunities)){ $damage_type = ''; }
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'type' => $damage_type,
            'frame' => 'damage',
            'modifiers' => false,
            'success' => array(0, 0, 0, 0, 'The <strong class="ability_name ability_type type_'.(!empty($damage_type) ? $damage_type : 'none').'">DevPower</strong> cleared out '.$target_robot->print_name().'!')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'type' => $damage_type,
            'frame' => 'damage',
            'modifiers' => false,
            'success' => array(0, 0, 0, 0, 'The <strong class="ability_name ability_type type_'.(!empty($damage_type) ? $damage_type : 'none').'">DevPower</strong> cleared out '.$target_robot->print_name().'!')
            ));
        $energy_damage_amount = $target_robot->robot_base_energy * 2;
        $trigger_options = array();
        $trigger_options['apply_modifiers'] = true;
        $trigger_options['apply_position_modifiers'] = false;
        $trigger_options['apply_stat_modifiers'] = false;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false, $trigger_options);

        // Loop through the target's benched robots, inflicting damage to each
        $backup_target_robots_active = $target_player->values['robots_active'];
        foreach ($backup_target_robots_active AS $key => $info){
            if ($info['robot_id'] == $target_robot->robot_id){ continue; }
            $temp_target_robot = rpg_game::get_robot($this_battle, $target_player, $info);
            $this_ability->ability_results_reset();
            $damage_type = '';
            if ($this_robot->robot_core === 'copy'){ $damage_type = !empty($temp_target_robot->robot_weaknesses[0]) ? $temp_target_robot->robot_weaknesses[0] : ''; }
            elseif (!empty($this_robot->robot_core)){ $damage_type = $this_robot->robot_core; }
            if (in_array($damage_type, $temp_target_robot->robot_affinities) || in_array($damage_type, $temp_target_robot->robot_immunities)){ $damage_type = ''; }
            elseif (!empty($temp_target_robot->robot_attachments['ability_core-shield_'.$damage_type])){ $damage_type = ''; }
            $this_ability->damage_options_update(array(
	            'kind' => 'energy',
                'type' => $damage_type,
	            'frame' => 'damage',
	            'modifiers' => false,
	            'success' => array(0, 0, 0, 0, 'The <strong class="ability_name ability_type type_'.(!empty($damage_type) ? $damage_type : 'none').'">DevPower</strong> cleared out '.$temp_target_robot->print_name().'!')
                ));
            $this_ability->recovery_options_update(array(
	            'kind' => 'energy',
                'type' => $damage_type,
	            'frame' => 'damage',
	            'modifiers' => false,
	            'success' => array(0, 0, 0, 0, 'The <strong class="ability_name ability_type type_'.(!empty($damage_type) ? $damage_type : 'none').'">DevPower</strong> cleared out '.$temp_target_robot->print_name().'!')
                ));
            $energy_damage_amount = $temp_target_robot->robot_base_energy * 2;
            $temp_target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false, $trigger_options);
        }

        // Now that all the damage has been dealt, allow the player to check for disabled
        $target_player->check_robots_disabled($this_player, $this_robot);

        // Return true on success
        return true;


        }
	);
?>