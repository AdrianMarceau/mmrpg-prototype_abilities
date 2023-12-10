<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'throw',
            'success' => array(0, 110, 0, 10, $this_robot->print_name().' throws a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(1, -90, 0, 10, 'The '.$this_ability->print_name().' zapped the target!'),
            'failure' => array(1, -100, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(1, -45, 0, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
            'failure' => array(1, -100, 0, -10, 'The '.$this_ability->print_name().' had no effect&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Disable their passive skill if the attack was successful
        if ($target_robot->robot_status != 'disabled'
            && $this_ability->ability_results['this_result'] != 'failure'){

            // Create an options object for this function and populate
            $options = rpg_game::new_options_object();
            $extra_objects = array('options' => $options);
            $extra_objects['this_ability'] = $this_ability;
            $extra_objects['this_player'] = $target_player;
            $extra_objects['this_robot'] = $target_robot;
            $extra_objects['target_player'] = $this_player;
            $extra_objects['target_robot'] = $this_robot;
            
            // Trigger this robot's custom function if one has been defined for this context
            $target_robot->trigger_custom_function('rpg-skill_disable-skill_before', $extra_objects);
            $target_robot->set_counter('skill_disabled', 4);

        }

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
