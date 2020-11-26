<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Create a reference to the target robot, whichever one it is
        if ($this_robot->player_id == $target_robot->player_id){ $temp_target_robot = $target_robot; }
        else { $temp_target_robot = $this_robot; }

        // If the target has already been disabled, we cannot continue
        if ($temp_target_robot->robot_status == 'disabled'){
            $this_ability->target_options_update(array(
                'frame' => 'defend',
                'success' => array(0, -10, 40, -15,
                    $this_robot->print_name().' tried to use the '.$this_ability->print_name().' technique...<br /> '.
                    '...but '.$temp_target_robot->print_name().' has already been disabled! '
                    )
                ));
            $this_robot->trigger_target($temp_target_robot, $this_ability, array('prevent_default_text' => true));
            return false;
        }

        // Target this robot's self
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 0, 10, -10, $this_robot->print_name().' uses the '.$this_ability->print_name().' technique!')
            ));
        $this_robot->trigger_target($this_robot, $this_ability);

        // If the target of this ability is not the user
        if ($temp_target_robot->robot_id != $this_robot->robot_id){

            // Increase this robot's energy stat
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'percent' => true,
                'modifiers' => true,
                'frame' => 'taunt',
                'success' => array(0, -2, 0, -10, $temp_target_robot->print_name().'&#39;s energy was restored!'),
                'failure' => array(9, -2, 0, -10, $temp_target_robot->print_name().'&#39;s energy was not affected&hellip;')
                ));
            $energy_recovery_amount = ceil($temp_target_robot->robot_base_energy * ($this_ability->ability_recovery / 100));
            $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false, 'apply_stat_modifiers' => false);
            $temp_target_robot->trigger_recovery($this_robot, $this_ability, $energy_recovery_amount, true, $trigger_options);

        }
        // Otherwise if the user if targeting themselves
        else {

            // Increase the target robot's energy stat
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'percent' => true,
                'frame' => 'taunt',
                'success' => array(0, -2, 0, -10, $this_robot->print_name().'&#39;s energy was restored!'),
                'failure' => array(9, -2, 0, -10, $this_robot->print_name().'&#39;s energy was not affected&hellip;')
                ));
            $energy_recovery_amount = ceil($this_robot->robot_base_energy * ($this_ability->ability_recovery / 100));
            $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false, 'apply_stat_modifiers' => false);
            $this_robot->trigger_recovery($this_robot, $this_ability, $energy_recovery_amount, true, $trigger_options);

        }

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If used by support robot OR the has a Target Module, allow bench targetting
        if ($this_robot->robot_core === '' || $this_robot->robot_class == 'mecha'){ $this_ability->set_target('select_this'); }
        elseif ($this_robot->has_item('target-module')){ $this_ability->set_target('select_this'); }
        else { $this_ability->set_target('auto'); }

        // Return true on success
        return true;

        }
);
?>
