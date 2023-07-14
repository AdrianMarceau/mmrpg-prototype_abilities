<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target this robot's self
        $this_battle->queue_sound_effect('summon-positive');
        $this_ability->target_options_update(array('frame' => 'summon', 'success' => array(0, 0, 0, -10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')));
        $this_robot->trigger_target($this_robot, $this_ability);

        // Call the global stat boost function with customized options
        rpg_ability::ability_function_stat_boost($this_robot, 'attack', $this_ability->ability_recovery2, $this_ability);

        // Loop through this player's active bots and raise their stats
        $backup_robots_active = $this_player->values['robots_active'];
        $backup_robots_active_count = !empty($backup_robots_active) ? count($backup_robots_active) : 0;
        if ($backup_robots_active_count > 0){
            $this_robot->set_frame_styles('transform: scaleX(-1);');
            $this_key = 0;
            $this_frame_key = 0;
            foreach ($backup_robots_active AS $key => $info){
                if ($info['robot_id'] == $this_robot->robot_id){ continue; }
                $this_battle->queue_sound_effect(array('name' => 'summon-positive', 'volume' => 0.3));
            	$this_robot->set_frame($this_frame_key % 2 === 0 ? 'summon' : 'taunt');
                $temp_this_robot = rpg_game::get_robot($this_battle, $this_player, $info);
                rpg_ability::ability_function_stat_boost($temp_this_robot, 'attack', $this_ability->ability_recovery2, $this_ability, array(
                    'initiator_robot' => $this_robot
                    ));
                $this_key++;
                $this_frame_key++;
            }
            $this_robot->reset_frame_styles();
            $this_robot->reset_frame();
        }

        // Return true on success
        return true;

    }
);
?>
