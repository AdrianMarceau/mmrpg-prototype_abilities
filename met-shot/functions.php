<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define the target and impact frames based on user
        $this_frames = array('target' => 0, 'impact' => 0);
        //if (preg_match('/_alt$/', $this_robot->robot_image)){ $this_frames = array('target' => 2, 'impact' => 3); }
        //elseif (preg_match('/_alt2$/', $this_robot->robot_image)){ $this_frames = array('target' => 4, 'impact' => 5); }

        // Update the ability's target options and trigger
        $target_options = array();
        $target_options['event_flag_sound_effects'] = array(
            array('name' => 'shot', 'volume' => 1.25)
            );
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array($this_frames['target'], 105, -6, 10, $this_robot->print_name().' fires a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability, $target_options);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array($this_frames['impact'], -60, -6, 10, 'The '.$this_ability->print_name().' hit the target!'),
            'failure' => array($this_frames['impact'], -60, -6, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Return true on success
        return true;

        }
);
?>
