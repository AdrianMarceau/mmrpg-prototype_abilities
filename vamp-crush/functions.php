<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'slide',
            'kickback' => array(80, 0, 0),
            'success' => array(0, 80, 0, 10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(15, 0, 0),
            'success' => array(1, -65, -10, 10, 'The '.$this_ability->print_name().' crashes into the target!'),
            'failure' => array(1, -85, -5, -10, 'The '.$this_ability->print_name().' continued past the target&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(5, 0, 0),
            'success' => array(1, -35, -10, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
            'failure' => array(1, -65, -5, -10, 'The '.$this_ability->print_name().' continued past the target&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // If the ability was a success and this robot's life energy is less than full
        if ($this_robot->robot_energy < $this_robot->robot_base_energy
            && $this_ability->ability_results['this_result'] != 'failure'
            && $this_ability->ability_results['this_amount'] > 0){

            // Increase this robot's energy stat
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'percent' => true,
                'modifiers' => false,
                'type' => '',
                'frame' => 'taunt',
                'success' => array($this_frames['target'], -9999, 5, -10, $this_robot->print_name().'&#39;s energy was restored!'),
                'failure' => array($this_frames['target'], -9999, 5, -10, $this_robot->print_name().'&#39;s energy was not affected&hellip;')
            ));
            $energy_recovery_amount = $this_ability->ability_results['this_amount'];
            $this_robot->trigger_recovery($this_robot, $this_ability, $energy_recovery_amount);

      
            }
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
