<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_battle->queue_sound_effect('shields-down');
        $this_ability->target_options_update(array(
            'frame' => 'slide',
            'kickback' => array(90, 0, 0),
            'success' => array(0, -20, 0, -10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Move the user forward so it looks like their chowing down
        $this_robot->set_frame('defend');
        $this_robot->set_frame_offset('x', 200);
        $this_robot->set_frame_styles('filter: brightness(0.1); ');
        $target_robot->set_frame_styles('filter: brightness(0.1); ');

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'percent' => true,
            'modifiers' => false,
            'kickback' => array(15, 0, 0),
            'success' => array(1, -40, 0, -10, 'The '.$this_ability->print_name().' shook the very soul of the target!'),
            'failure' => array(1, -60, 0, -10, 'The '.$this_ability->print_name().' didn\'t faze the target at all&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'percent' => true,
            'modifiers' => false,
            'kickback' => array(5, 0, 0),
            'success' => array(1, -40, 0, -10, 'The '.$this_ability->print_name().' just made the target stronger! Drats!'),
            'failure' => array(1, -60, 0, -10, 'The '.$this_ability->print_name().' didn\'t faze the target at all&hellip;')
            ));
        $energy_damage_percent = ($this_ability->ability_damage / 100);
        $energy_damage_amount = round($energy_damage_percent * $target_robot->robot_energy);
        if ($energy_damage_amount >= $target_robot->robot_energy){ $energy_damage_amount = $target_robot->robot_energy - 1; }
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, true, array(
            'apply_modifiers' => false
            ));

        // Reset the user and target sprites of any changes for effect
        $this_robot->reset_frame();
        $this_robot->reset_frame_offset();
        $this_robot->reset_frame_styles();
        $target_robot->reset_frame_styles();

        // If the ability was a success and this robot's life energy is less than full
        if ($this_robot->robot_energy < $this_robot->robot_base_energy
            && $this_ability->ability_results['this_result'] != 'failure'
            && $this_ability->ability_results['this_amount'] > 0){

            // Increase this robot's energy stat
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'percent' => true,
                'modifiers' => false,
                'rates' => array(100, 0, 0),
                'type' => '',
                'frame' => 'taunt',
                'success' => array(2, 0, 10, -10, $this_robot->print_name().'&#39;s energy was restored!'),
                'failure' => array(2, 0, 5, -10, $this_robot->print_name().'&#39;s energy was not affected&hellip;')
            ));
            $energy_recovery_amount = $this_ability->ability_results['this_amount'];
            $this_robot->trigger_recovery($this_robot, $this_ability, $energy_recovery_amount);

        }

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Make sure the front-end knows that this abilities damage is persistent
        $this_ability->set_flag('damage_is_fixed', true);

        // If the user has Extended Range, allow bench targeting
        if ($this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Return true on success
        return true;

    }
);
?>
