<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If this ability is attached, remove it
        $this_attachment_backup = false;
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        if (isset($this_robot->robot_attachments[$this_attachment_token])){
            $this_attachment_backup = $this_robot->robot_attachments[$this_attachment_token];
            unset($this_robot->robot_attachments[$this_attachment_token]);
            $this_robot->update_session();
        }

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'throw',
            'kickback' => array(0, 0, 0),
            'success' => array(0, 85, 35, 10, $this_robot->print_name().' thows a '.$this_ability->print_name().'!'),
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'frame' => 'damage',
            'kickback' => array(10, 5, 0),
            'success' => array(2, 30, 0, 10, 'The '.$this_ability->print_name().' exploded on contact!'),
            'failure' => array(1, -65, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(2, 30, 0, 10, 'The '.$this_ability->print_name().' exploded on contact!'),
            'failure' => array(1, -65, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

        // Randomly trigger a bench damage if the ability was successful
        $backup_robots_active = $target_player->values['robots_active'];
        $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
        if ($this_ability->ability_results['this_result'] != 'failure'){

            // Loop through the target's benched robots, inflicting 10% base damage to each
            foreach ($backup_robots_active AS $key => $info){
                if ($info['robot_id'] == $target_robot->robot_id){ continue; }
                if (!$this_battle->critical_chance(ceil((9 - $info['robot_key']) * 10))){ break; }
                $this_ability->ability_results_reset();
                $temp_target_robot = rpg_game::get_robot($this_battle, $target_player, $info);
                // Update the ability options text
                $this_ability->damage_options_update(array(
                    'success' => array(2, -20, -5, -5, $temp_target_robot->print_name().' was damaged by the blast!'),
                    'failure' => array(3, 0, 0, -9999, '')
                    ));
                $this_ability->recovery_options_update(array(
                    'success' => array(2, -20, -5, -5, $temp_target_robot->print_name().' was refreshed by the blast!'),
                    'failure' => array(3, 0, 0, -9999, '')
                    ));
                $energy_damage_amount = ceil($this_ability->ability_damage * 0.20); //ceil($this_ability->ability_damage / $backup_robots_active_count);
                $temp_target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
                if ($this_ability->ability_results['this_result'] == 'failure'){ break; }
            }

        }

        // If there was a removed attachment, put it back
        if (!empty($this_attachment_backup)){
            $this_robot->robot_attachments[$this_attachment_token] = $this_attachment_backup;
            $this_robot->update_session();
        }

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

        },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If the user is holding a Target Module, allow bench targeting
        if ($this_robot->has_item('target-module')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Return true on success
        return true;

        }
);
?>
