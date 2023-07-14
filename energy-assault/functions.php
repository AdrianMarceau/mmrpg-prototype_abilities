<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target this robot's self
        $this_battle->queue_sound_effect('summon-negative');
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(9, 0, 0, -10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Decrease the target robot's attack stat
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'percent' => true,
            'kickback' => array(10, 0, 0),
            'success' => array(0, -2, 0, -10, $target_robot->print_name().'&#39;s systems were damaged!'),
            'failure' => array(0, -2, 0, -10, 'It had no effect on '.$target_robot->print_name().'&hellip;')
            ));
        $energy_damage_amount = ceil($target_robot->robot_base_energy * ($this_ability->ability_damage / 100));
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Attach this ability to all robots on the target player's side of the field
        $backup_robots_active = $target_player->values['robots_active'];
        $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
        if ($backup_robots_active_count > 0){
            // Loop through the this's benched robots, restoring energy one by one
            $this_key = 0;
            foreach ($backup_robots_active AS $key => $info){
                if ($info['robot_id'] == $target_robot->robot_id){ continue; }
                $this_battle->queue_sound_effect(array('name' => 'summon-negative', 'volume' => 0.3));
                $temp_target_robot = rpg_game::get_robot($this_battle, $target_player, $info);
                // Increase this robot's energy stat
                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'percent' => true,
                    'kickback' => array(10, 0, 0),
                    'success' => array(0, -2, 0, -10, $temp_target_robot->print_name().'&#39;s systems were damaged!'),
                    'failure' => array(0, -2, 0, -10, 'It had no effect on '.$temp_target_robot->print_name().'&hellip;')
                    ));
                $energy_damage_amount = ceil($temp_target_robot->robot_base_energy * ($this_ability->ability_damage / 100));
                $temp_target_robot->trigger_damage($temp_target_robot, $this_ability, $energy_damage_amount);
                $this_key++;
            }
        }

        // Return true on success
        return true;

    }
);
?>
