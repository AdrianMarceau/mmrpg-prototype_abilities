<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, -2, 0, -10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Decrease the target robot's attack stat
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'percent' => true,
            'modifiers' => true,
            'kickback' => array(10, 0, 0),
            'success' => array(0, -2, 0, -10, $target_robot->print_name().'&#39;s systems were damaged!'),
            'failure' => array(9, -2, 0, -10, 'It had no effect on '.$target_robot->print_name().'&hellip;')
            ));
        $energy_damage_amount = ceil($target_robot->robot_base_energy * ($this_ability->ability_damage / 100));
        $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false, 'apply_stat_modifiers' => false);
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, true, $trigger_options);

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If used by support robot OR the has a Target Module, allow bench targetting
        if ($this_robot->robot_core === '' || $this_robot->robot_class == 'mecha'){ $this_ability->set_target('select_target'); }
        elseif ($this_robot->has_item('target-module')){ $this_ability->set_target('select_target'); }
        else { $this_ability->set_target('auto'); }

        // Return true on success
        return true;

        }
);
?>
