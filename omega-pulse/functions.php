<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Update the ability's target options and trigger
        if ($this_robot->robot_gender == 'female'){ $pronoun = 'her'; }
        elseif ($this_robot->robot_gender == 'male'){ $pronoun = 'his'; }
        else { $pronoun = 'its'; }
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, -10, 0, -1, $this_robot->print_name().' taps into '.$pronoun.' hidden power...', 1)
            ));
        $target_options = array('prevent_default_text' => true);
        $this_robot->trigger_target($target_robot, $this_ability, $target_options);

        // Update the ability's target options and trigger
        $this_ability->target_options_update(array(
            'frame' => 'throw',
            'success' => array(2, 120, -20, 10, $this_robot->print_name().' releases an '.$this_ability->print_name().'!', 2)
            ));
        $target_options = array('prevent_default_text' => true);
        $this_robot->trigger_target($target_robot, $this_ability, $target_options);

        // Update ability options and trigger damage on the target
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(15, 0, 0),
            'success' => array(4, -160, -20, 10, 'The '.$this_ability->print_name().' collided with the target!', 2),
            'failure' => array(4, -160, -20, -10, 'The '.$this_ability->print_name().' missed the target...', 2)
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(4, -140, -20, 10, 'The '.$this_ability->print_name().' invigorated the target!', 2),
            'failure' => array(4, -140, -20, -10, 'The '.$this_ability->print_name().' missed the target...', 2)
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Return true on success
        return true;

        },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Generate this robot's omega string, collect it's hidden power, and update type1
        $robot_hidden_power = $this_robot->robot_omega;
        if (!empty($robot_hidden_power)){
            $robot_ability_image = $this_ability->get_base_image().'_'.$robot_hidden_power;
            $this_ability->set_type($robot_hidden_power);
            $this_ability->set_image($robot_ability_image);
        } else {
            $this_ability->reset_type();
            $this_ability->reset_image();
            $robot_hidden_power = false;
        }

        // If the user is holding a Target Module, allow bench targeting
        if ($this_robot->has_item('target-module')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Return true on success
        return true;

        }
);
?>
