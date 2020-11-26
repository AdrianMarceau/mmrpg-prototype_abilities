<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'kickback' => array(-10, 0, 0),
            'success' => array(0, 75, 0, 10, $this_robot->print_name().' fires a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(30, 0, 0),
            'success' => array(1, 20, 0, 10, 'The '.$this_ability->print_name().' collided with the target!'),
            'failure' => array(0, -75, 0, -10, 'The '.$this_ability->print_name().' <em>just</em> missed the target&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(10, 0, 0),
            'success' => array(1, 20, 0, 10, 'The '.$this_ability->print_name().'&#39;s energy was absorbed by the target!'),
            'failure' => array(0, -75, 0, -10, 'The '.$this_ability->print_name().' was ignored by the target&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Return true on success
        return true;

    }
);
?>
