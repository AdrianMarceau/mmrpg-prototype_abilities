<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_battle->queue_sound_effect('laser-sound');
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 150, 0, 10, $this_robot->print_name().' fires the '.$this_ability->print_name().'!'),
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_battle->queue_sound_effect(array('name' => 'laser-sound', 'volume' => 0.9));
        $temp_offset = $target_player->counters['robots_active'] > 1 ? -250 : -150;
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(15, 5, 0),
            'success' => array(0, $temp_offset, 0, 10, 'The '.$this_ability->print_name().' burned through the target!'),
            'failure' => array(0, ($temp_offset - 50), 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'frame' => 'taunt',
            'kickback' => array(5, 0, 0),
            'success' => array(0, $temp_offset, 0, 10, 'The '.$this_ability->print_name().' energy was absorbed by the target!'),
            'failure' => array(0, ($temp_offset - 50), 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false, 'apply_stat_modifiers' => true);
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false, $trigger_options);

        // Randomly trigger a bench damage if the ability was successful
        $backup_robots_active = $target_player->values['robots_active'];
        $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
        if (isset($this_robot->robot_attachments['ability_gemini-clone'])
            && !empty($this_robot->flags['gemini-clone_is_using_ability'])){
            $backup_robots_active = array_reverse($backup_robots_active);
        }
        if (true){ //$this_ability->ability_results['this_result'] != 'failure'

            // Loop through the target's benched robots, inflicting les and less damage to each
            $target_key = 0;
            foreach ($backup_robots_active AS $key => $info){
                if ($info['robot_id'] == $target_robot->robot_id){ continue; }
                if (!$this_battle->critical_chance($this_ability->ability_accuracy)){ continue; }
                $this_battle->queue_sound_effect(array('name' => 'laser-sound', 'volume' => (0.9 - ($key * 0.1))));
                $this_ability->ability_results_reset();
                $temp_target_robot = rpg_game::get_robot($this_battle, $target_player, $info);
                // Update the ability options text
                $temp_frame = $target_key == 0 || $target_key % 2 == 0 ? 1 : 0;
                $temp_kickback = ($target_key == 0 || $target_key % 2 == 0 ? -1 : 1) * (10 + (5 * $target_key));
                $temp_offset = 100 - ($target_key * 10);
                $temp_offset = $temp_frame == 0 ? $temp_offset * -1 : ceil($temp_offset * 0.75);
                $this_ability->damage_options_update(array(
                    'kickback' => array($temp_kickback, 0, 0),
                    'success' => array($temp_frame, $temp_offset, 0, 10, 'The '.$this_ability->print_name().' burned through the target!'),
                    'failure' => array($temp_frame, ($temp_offset * 2), 0, 10, '')
                    ));
                $this_ability->recovery_options_update(array(
                    'kickback' => array($temp_kickback, 0, 0),
                    'success' => array($temp_frame, $temp_offset, 0, 10, 'The '.$this_ability->print_name().'&#39;s energy was absorbed by the target!'),
                    'failure' => array($temp_frame, $temp_offset * 2, 0, 10, '')
                    ));
                //$energy_damage_amount = ceil($this_ability->ability_damage / ($key + 2));
                $energy_damage_amount = ceil($this_ability->ability_damage / ($target_robot->robot_key + 2));
                $temp_target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false, $trigger_options);
                $target_key++;
            }

        }

        // Now that all the damage has been dealt, allow the player to check for disabled
        $target_player->check_robots_disabled($this_player, $this_robot);

        // Return true on success
        return true;

    }
);
?>
