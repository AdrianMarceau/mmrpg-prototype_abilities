<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Update the ability's target options and trigger
        $this_battle->queue_sound_effect('charge-sound');
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(1, -10, 0, -1, $this_robot->print_name().' taps into '.$this_robot->get_pronoun('possessive2').' core power...', 1)
            ));
        $target_options = array('prevent_default_text' => true);
        $this_robot->trigger_target($target_robot, $this_ability, $target_options);

        // Update the ability's target options and trigger
        $this_battle->queue_sound_effect('laser-sound');
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(2, 120, -20, 10, $this_robot->print_name().' fires the '.$this_ability->print_name().'!', 2)
            ));
        $target_options = array('prevent_default_text' => true);
        $this_robot->trigger_target($target_robot, $this_ability, $target_options);

        // Update ability options and trigger damage on the target
        $this_battle->queue_sound_effect(array('name' => 'laser-sound', 'volume' => 0.6));
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(15, 0, 0),
            'success' => array(4, -140, -20, 10, 'The '.$this_ability->print_name().' burned through the target!', 3),
            'failure' => array(4, -140, -20, -10, 'The '.$this_ability->print_name().' missed the target...', 3)
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(4, -120, -20, 10, 'The '.$this_ability->print_name().' invigorated the target!', 3),
            'failure' => array(4, -120, -20, -10, 'The '.$this_ability->print_name().' missed the target...', 3)
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Return true on success
        return true;

        },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Collect this robots core and item types
        $ability_base_type = !empty($this_ability->ability_base_type) ? $this_ability->ability_base_type : '';
        $robot_core_type = !empty($this_robot->robot_core) ? $this_robot->robot_core : '';
        $robot_item_type = !empty($this_robot->robot_item) && strstr($this_robot->robot_item, '-core') ? str_replace('-core', '', $this_robot->robot_item) : '';

        // Define the types for this ability
        $ability_types = array();
        $ability_types[] = $ability_base_type;
        if (!empty($robot_core_type) && $robot_core_type != 'copy' && !in_array($robot_core_type, $ability_types)){ $ability_types[] = $robot_core_type; }
        if (!empty($robot_item_type) && $robot_item_type != 'copy' && !in_array($robot_item_type, $ability_types)){ $ability_types[] = $robot_item_type; }
        $ability_types = array_reverse($ability_types);
        $ability_types = array_slice($ability_types, 0, 2);

        // Collect this robot's primary type and change its image if necessary
        $this_ability->set_image($this_ability->ability_token.'_'.$ability_types[0]);
        $this_ability->set_type($ability_types[0]);
        if (!empty($ability_types[1])){
            $this_ability->set_image2($this_ability->ability_token.'_'.$ability_types[1].'2');
            $this_ability->set_type2($ability_types[1]);
        } else {
            $this_ability->set_image2('');
            $this_ability->set_type2('');
        }

        // If the user has Extended Range, allow bench targeting
        if ($this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Return true on success
        return true;

        }
);
?>
