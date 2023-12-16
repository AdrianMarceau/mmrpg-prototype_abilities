<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Check to see which stat is highest for this robot
        $available_stats = array('attack', 'defense', 'speed');
        $best_stat = rpg_robot::get_best_stat($target_robot, true);
        if ($best_stat === 'all'){
            if (!empty($target_player->player_type) && in_array($target_player->player_type, $available_stats)){ $best_stat = $target_player->player_type; }
            elseif (!empty($this_player->player_type) && in_array($this_player->player_type, $available_stats)){ $best_stat = $this_player->player_type; }
            else { $best_stat = $available_stats[array_rand($available_stats)]; }
        }

        // Target the opposing robot
        $this_battle->queue_sound_effect('summon-negative');
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 75, 0, 10,
                $this_robot->print_name().' cloaks '.$this_robot->get_pronoun('reflexive').' in darkness! <br />'.
                $this_robot->get_pronoun('subject').' triggered a '.$this_ability->print_name().' on '.$target_robot->print_name().'!'
                )
            ));
        $this_robot->trigger_target($this_robot, $this_ability, array('prevent_default_text' => true));

        // Call the global stat break function with customized options
        $break_amount = (MMRPG_SETTINGS_STATS_MOD_MIN - $target_robot->counters[$best_stat.'_mods']) * -1;
        rpg_ability::ability_function_stat_break($target_robot, $best_stat, $break_amount, $this_ability, array(
            'initiator_robot' => $this_robot
            ));

        // If the user is not explicitly an empty core, we cut their health in half
        if ($this_robot->robot_core !== 'empty'){

            // Decrease this robot's energy stat by half
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'percent' => true,
                'modifiers' => false,
                'type' => '',
                'frame' => 'damage',
                'success' => array(6, -9999, 5, -10, 'The '.$this_ability->print_name().' cut '.$this_robot->print_name_s().' remaining health in half!'),
                'failure' => array(6, -9999, 5, -10, $this_robot->print_name().' somehow survived the '.$this_ability->print_name().'&hellip;')
                ));
            $energy_damage_amount = floor($this_robot->robot_energy / 2);
            $this_robot->trigger_damage($target_robot, $this_ability, $energy_damage_amount, true, array(
                'apply_modifiers' => false,
                'apply_target_attachment_damage_breakers' => false
                ));

        }

        // Return true on success
        return true;

    }
);
?>
