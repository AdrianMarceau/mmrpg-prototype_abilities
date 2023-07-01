<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Create an options object for this function and populate
        $options = rpg_game::new_options_object();
        $extra_objects = array('this_ability' => $this_ability, 'options' => $options);

        // Check speed to see how many times wheel cutter can hit
        $options->num_buster_shots = 1;
        if ($this_robot->robot_speed > $target_robot->robot_speed){
            $options->num_buster_shots = floor($this_robot->robot_speed / $target_robot->robot_speed);
        }

        // Trigger this robot's item function if one has been defined for this context
        $this_robot->trigger_custom_function('rpg-ability_elemental-shot_before', $extra_objects);
        if ($options->return_early){ return $options->return_value; }

        // Predefine the damage amount so we can reduce with subsequent shots
        $energy_damage_amount = $this_ability->ability_damage;

        // Loop through the allowed number of shots and fire that many times
        for ($num_shot = 1; $num_shot <= $options->num_buster_shots; $num_shot++){

            // Update the ability's target options and trigger
            $target_text = '';
            $target_options = array();
            if ($num_shot === 1){
                $target_text = $this_robot->print_name().' launches the '.$this_ability->print_name().' to attack!';
            } else {
                $target_text = $this_robot->print_name().' sets another '.$this_ability->print_name().'to chase the opponent!';
                $target_options['prevent_default_text'] = true;
            }
            $this_ability->target_options_update(array(
                'frame' => 'throw',
                'success' => array(0, 100, -10, 10, $target_text)
                ));
            $this_robot->trigger_target($target_robot, $this_ability, $target_options);

            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(10, 0, 0),
                'success' => array(1, -60, -15, 5, 'The '.$this_ability->print_name().' hit its target dead-on!'),
                'failure' => array(2, 50, 30, 10, 'The '.$this_ability->print_name().' bounced right past the foe&hellip;')
                ));
            if ($num_shot > 1){ $energy_damage_amount -= ($energy_damage_amount * 0.10); }
            $target_robot->trigger_damage($this_robot, $this_ability, ceil($energy_damage_amount));

            // Break early if the target has been disabled
            if ($target_robot->robot_energy < 1 || $target_robot->robot_status === 'disabled'){ break; }

        }

        // Trigger this robot's item function if one has been defined for this context
        $this_robot->trigger_custom_function('rpg-ability_elemental-shot_after', $extra_objects);

        // Return true on success
        return true;

        },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Reset the ability target (unless otherwise stated later)
        $this_ability->reset_target();

        // Create an options object for this function and populate
        $options = rpg_game::new_options_object();
        $options->buster_charge_boost = 2;
        $extra_objects = array('this_ability' => $this_ability, 'options' => $options);

        // Trigger this robot's item function if one has been defined for this context
        $this_robot->trigger_custom_function('rpg-ability_elemental-shot_onload_before', $extra_objects);
        if ($options->return_early){ return $options->return_value; }

        // Trigger this robot's item function if one has been defined for this context
        $this_robot->trigger_custom_function('rpg-ability_elemental-shot_onload_after', $extra_objects);

        // Return true on success
        return true;

        }
);
?>
