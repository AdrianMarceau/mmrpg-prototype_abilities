<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target this robot's self
        $this_battle->queue_sound_effect('summon-negative');
        $this_ability->target_options_update(array('frame' => 'summon', 'success' => array(0, 0, 0, -10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Call the global stat break function with customized options
        rpg_ability::ability_function_stat_break($target_robot, 'speed', 1, $this_ability, array(
            'initiator_robot' => $this_robot
            ));

        // Loop through the target player's active bots and lower their stats
        $backup_robots_active = $target_player->values['robots_active'];
        $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
        if ($backup_robots_active_count > 0){
            $this_key = 0;
            foreach ($backup_robots_active AS $key => $info){
                if ($info['robot_id'] == $target_robot->robot_id){ continue; }
                $this_battle->queue_sound_effect(array('name' => 'summon-negative', 'volume' => 0.3));
                $temp_target_robot = rpg_game::get_robot($this_battle, $target_player, $info);
                rpg_ability::ability_function_stat_break($temp_target_robot, 'speed', $this_ability->ability_damage2, $this_ability, array(
                    'initiator_robot' => $this_robot
                    ));
                $this_key++;
            }
        }

        // Return true on success
        return true;

    }
);
?>
