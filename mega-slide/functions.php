<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_battle->queue_sound_effect(array('name' => 'hyper-slide-sound', 'volume' => 1.0));
        $this_battle->queue_sound_effect(array('name' => 'hyper-slide-sound', 'volume' => 0.6, 'delay' => 200));
        $this_ability->target_options_update(array(
            'frame' => 'slide',
            'kickback' => array(150, 0, 0),
            'success' => array(0, 25, 0, -10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')
            ));
        $trigger_options = array();
        $this_robot->trigger_target($target_robot, $this_ability, $trigger_options);

        // Inflict damage on the opposing robot
        $this_battle->queue_sound_effect('hyper-slide-sound');
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(60, 0, 0),
            'rates' => array('auto', 'auto', $this_ability->ability_recovery2),
            'success' => array(1, -65, 0, 10, 'The '.$this_ability->print_name().' crashes into the target!'),
            'failure' => array(0, -85, 0, -10, 'The '.$this_ability->print_name().' missed the target&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(20, 0, 0),
            'rates' => array('auto', 'auto', $this_ability->ability_recovery2),
            'success' => array(1, -35, 0, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
            'failure' => array(1, -65, 0, -10, 'The '.$this_ability->print_name().' missed the target&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

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
