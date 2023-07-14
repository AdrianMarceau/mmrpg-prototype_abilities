<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Create an options object for this function and populate
        $options = rpg_game::new_options_object();
        $extra_objects = array('this_ability' => $this_ability, 'options' => $options);

        // Check speed to see how many times buster shot can hit
        $options->num_buster_shots = 1;
        if ($this_robot->robot_speed > $target_robot->robot_speed){
            $options->num_buster_shots = floor($this_robot->robot_speed / $target_robot->robot_speed);
        }

        // Predefine the damage amount so we can reduce with subsequent shots
        $energy_damage_amount = $this_ability->ability_damage;

        // Loop through the allowed number of shots and fire that many times
        for ($num_shot = 1; $num_shot <= $options->num_buster_shots; $num_shot++){

            // Update the ability's target options and trigger
            $target_text = '';
            $target_options = array();
            if ($num_shot === 1){
                $target_text = $this_robot->print_name().' fires a '.$this_ability->print_name().'!';
            } else {
                $target_text = $this_robot->print_name().' fires another '.$this_ability->print_name().'!';
                $target_options['prevent_default_text'] = true;
            }
            $this_ability->target_options_update(array(
                'frame' => 'shoot',
                'success' => array(0, 105, 0, 10, $target_text)
                ));
            $this_battle->queue_sound_effect('shot-sound');
            $this_robot->trigger_target($target_robot, $this_ability, $target_options);

            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(10, 0, 0),
                'success' => array(0, -60, 0, 10, 'The '.$this_ability->print_name().' hit the target!'),
                'failure' => array(0, -60, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
                ));
            if ($num_shot > 1){ $energy_damage_amount -= ($energy_damage_amount * 0.10); }
            $trigger_options = array('apply_position_modifiers' => false, 'force_flags' => array('flag_critical'));
            $target_robot->trigger_damage($this_robot, $this_ability, ceil($energy_damage_amount), true, $trigger_options);

            // Break early if the target has been disabled
            if ($target_robot->robot_energy < 1 || $target_robot->robot_status === 'disabled'){ break; }

        }

        // Return true on success
        return true;

        }
);
?>
