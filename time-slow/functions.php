<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'defend',
            'success' => array(1, -10, 0, -10, $this_robot->print_name().' charges the '.$this_ability->print_name().'&hellip;')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Shift user into summon mode right before the target is hit
        $this_robot->robot_frame = 'summon';
        $this_robot->update_session();

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(15, 0, 0),
            'success' => array(3, 5, 70, -10, 'The '.$this_ability->print_name().' was unleashed on the target!'),
            'failure' => array(9, 5, 70, -10, 'The '.$this_ability->print_name().' was ignored by the target&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(3, 5, 70, -10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
            'failure' => array(9, 5, 70, -10, 'The '.$this_ability->print_name().' didn\'t affect the target&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Return the user to their base frame after attack
        $this_robot->robot_frame = 'base';
        $this_robot->update_session();

        // Call the global stat break function with customized options
        rpg_ability::ability_function_stat_break($target_robot, 'speed', 2, $this_ability, array(
            'initiator_robot' => $this_robot
            ));

        // Return true on success
        return true;

        },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If the user has Extended Range, allow bench targeting
        if ($this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Return true on success
        return true;

        }
);
?>
