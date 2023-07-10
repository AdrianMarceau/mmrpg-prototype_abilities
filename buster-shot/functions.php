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
                $target_text = $this_robot->print_name().' fires a '.$this_ability->print_name().'!';
            } else {
                $target_text = $this_robot->print_name().' fires another '.$this_ability->print_name().'!';
                $target_options['prevent_default_text'] = true;
            }
            $target_options['event_flag_sound_effects'] = array(
                array('name' => 'shot-sound', 'volume' => 1.0)
                );
            $this_ability->target_options_update(array(
                'frame' => 'shoot',
                'success' => array(0, 105, 0, 10, $target_text)
                ));
            $this_robot->trigger_target($target_robot, $this_ability, $target_options);

            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(10, 0, 0),
                'success' => array(0, -60, 0, 10, 'The '.$this_ability->print_name().' hit the target!'),
                'failure' => array(0, -60, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
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

        // Loop through any attachments and boost power by 10% for each buster charge
        $temp_new_damage = $this_ability->ability_base_damage;
        $temp_new_damage_booster = 0;
        foreach ($this_robot->robot_attachments AS $this_attachment_token => $this_attachment_info){
            if (preg_match('/-buster$/i', $this_attachment_token)){ $temp_new_damage_booster += 1; }
        }
        $temp_new_damage += $temp_new_damage * ($temp_new_damage_booster / 7);
        $temp_new_damage = ceil($temp_new_damage);

        // Update the ability's damage with the new amount
        $this_ability->set_damage($temp_new_damage);

        // Trigger this robot's item function if one has been defined for this context
        $this_robot->trigger_custom_function('rpg-ability_elemental-shot_onload_after', $extra_objects);

        // Return true on success
        return true;

        }
);
?>
