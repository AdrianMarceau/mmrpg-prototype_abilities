<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Check to see which stat is highest for this robot
        $available_stats = array('attack', 'defense', 'speed');
        $worst_stat = rpg_robot::get_worst_stat($this_robot, true);
        if ($worst_stat === 'all'){
            if (!empty($this_player->player_type) && in_array($this_player->player_type, $available_stats)){ $worst_stat = $this_player->player_type; }
            else { $worst_stat = $available_stats[array_rand($available_stats)]; }
        }

        // Target the opposing robot
        $this_battle->queue_sound_effect('summon-positive');
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 75, 0, 10,
                $this_robot->print_name().' cloaks '.$this_robot->get_pronoun('reflexive').' in darkness! <br />'.
                $this_robot->get_pronoun('subject').' triggered a '.$this_ability->print_name().'!'
                )
            ));
        $this_robot->trigger_target($this_robot, $this_ability, array('prevent_default_text' => true));

        // Call the global stat boost function with customized options
        $boost_amount = MMRPG_SETTINGS_STATS_MOD_MAX - $target_robot->counters[$worst_stat.'_mods'];
        rpg_ability::ability_function_stat_boost($this_robot, $worst_stat, $boost_amount, $this_ability, array(
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
            $this_robot->set_base_energy($new_base_energy_amount);
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
