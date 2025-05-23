<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define the target and impact frames based on user
        $this_frames = array('target' => 0, 'impact' => 1);
        //if (preg_match('/_alt$/', $this_robot->robot_image)){ $this_frames = array('target' => 4, 'impact' => 5); }
        //elseif (preg_match('/_alt2$/', $this_robot->robot_image)){ $this_frames = array('target' => 0, 'impact' => 1); }

        // Update the ability's target options and trigger
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array($this_frames['target'], 0, 30, 10, $this_robot->print_name().' uses the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array($this_frames['impact'], 0, 5, 10, 'The '.$this_ability->print_name().' shocked the target!'),
            'failure' => array(2, -40, 0, 10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array($this_frames['impact'], 0, 5, 10, 'The '.$this_ability->print_name().' charged the target!'),
            'failure' => array(2, -40, 0, 10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

        // Randomly trigger a bench damage if the ability was successful
        $backup_robots_active = $target_player->values['robots_active'];
        $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
        if ($this_ability->ability_results['this_result'] != 'failure'){

            // Loop through the target's benched robots, inflicting 20% base damage to each
            foreach ($backup_robots_active AS $key => $info){
                if ($info['robot_id'] == $target_robot->robot_id){ continue; }
                if (!$this_battle->critical_chance(ceil((9 - $info['robot_key']) * 20))){ continue; }
                $this_ability->ability_results_reset();
                $temp_target_robot = rpg_game::get_robot($this_battle, $target_player, $info);
                // Update the ability options text
                $this_ability->damage_options_update(array(
                    'success' => array($this_frames['impact'], 0, 5, 10, $temp_target_robot->print_name().' was hit by a spark!'),
                    'failure' => array(2, -40, 0, 10, '')
                    ));
                $this_ability->recovery_options_update(array(
                    'success' => array($this_frames['impact'], 0, 5, 10, $temp_target_robot->print_name().' absorbed a spark!'),
                    'failure' => array(2, -40, 0, 10, '')
                    ));
                $energy_damage_amount = ceil($this_ability->ability_damage * 0.20); //ceil($this_ability->ability_damage / $backup_robots_active_count);
                $temp_target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
                //if ($this_ability->ability_results['this_result'] == 'failure'){ break; }
            }

        }

        // Now that all the damage has been dealt, allow the player to check for disabled
        $target_player->check_robots_disabled($this_player, $this_robot);

        // If there was a removed attachment, put it back
        if (!empty($this_attachment_backup)){
            $this_robot->robot_attachments[$this_attachment_token] = $this_attachment_backup;
            $this_robot->update_session();
        }

        // Return true on success
        return true;

        // Return true on success
        return true;

        }
);
?>
