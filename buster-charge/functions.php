<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Create a reference to the target robot, whichever one it is
        if ($this_robot->player_id == $target_robot->player_id){ $temp_target_robot = $target_robot; }
        else { $temp_target_robot = $this_robot; }

        // Update the ability's target options and trigger
        $this_ability->target_options_update(array(
            'frame' => 'defend',
            'success' => array(0, 0, 0, -10, $this_robot->print_name().' starts charging weapon energy&hellip;')
            ));
        $this_robot->trigger_target($this_robot, $this_ability);

        // If the target of this ability is not the user
        if ($temp_target_robot->robot_id != $this_robot->robot_id){

            // Recover the target robot's weapon energy
            $this_ability->recovery_options_update(array(
                'kind' => 'weapons',
                'percent' => true,
                'modifiers' => false,
                'kickback' => array(10, 0, 0),
                'success' => array(0, 0, 0, -10, 'The '.$this_ability->print_name().' restored depleted power!'),
                'failure' => array(0, 0, 0, -10, 'The '.$this_ability->print_name().' had no effect&hellip;')
                ));
            $weapons_recovery_amount = ceil($temp_target_robot->robot_base_weapons * ($this_ability->ability_recovery2 / 100));
            $trigger_options = array('apply_modifiers' => false, 'apply_position_modifiers' => false, 'apply_stat_modifiers' => false);
            $temp_target_robot->trigger_recovery($this_robot, $this_ability, $weapons_recovery_amount, true, $trigger_options);

        }
        // Otherwise if the user if targeting themselves
        else {

            // Recover this robot's weapon energy
            $this_ability->recovery_options_update(array(
                'kind' => 'weapons',
                'percent' => true,
                'modifiers' => false,
                'kickback' => array(10, 0, 0),
                'success' => array(0, 0, 0, -10, 'The '.$this_ability->print_name().' restored depleted power!'),
                'failure' => array(0, 0, 0, -10, 'The '.$this_ability->print_name().' had no effect&hellip;')
                ));
            $weapons_recovery_amount = ceil($this_robot->robot_base_weapons * ($this_ability->ability_recovery2 / 100));
            $trigger_options = array('apply_modifiers' => false, 'apply_position_modifiers' => false, 'apply_stat_modifiers' => false);
            $this_robot->trigger_recovery($this_robot, $this_ability, $weapons_recovery_amount, true, $trigger_options);

        }

        // Return true on success
        return true;

        },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If used by support robot OR the has a Target Module, allow bench targetting
        if ($this_robot->robot_core === '' || $this_robot->robot_class == 'mecha'){ $this_ability->set_target('select_this'); }
        elseif ($this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_this'); }
        else { $this_ability->set_target('auto'); }

        // Return true on success
        return true;

        }
);
?>
