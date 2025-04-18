<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token.'_'.$target_robot->robot_id;
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 4,
            'ability_frame_animate' => array(4, 3),
            'ability_frame_offset' => array('x' => -20, 'y' => 80, 'z' => -10)
            );

        // Target the opposing robot
        $this_battle->queue_sound_effect('thunder-sound');
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(3, 0, 60, -10, $this_robot->print_name().' summons a '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);
        $this_robot->robot_frame = 'summon';
        $this_robot->update_session();

        // Attach this ability to the target
        $target_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
        $target_robot->update_session();

        // Inflict damage on the opposing robot
        $this_battle->queue_sound_effect('electric-sound');
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(15, 0, 0),
            'success' => array(0, 0, 10, 10, 'The '.$this_ability->print_name().' zapped the target!'),
            'failure' => array(1, 0, 10, -10, 'The '.$this_ability->print_name().' missed the target&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(0, 0, 0),
            'success' => array(0, 0, 10, 10, 'The '.$this_ability->print_name().' charged the target!'),
            'failure' => array(1, 0, 10, -10, 'The '.$this_ability->print_name().' missed the target&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
        $this_robot->robot_frame = 'base';
        $this_robot->update_session();

        // Remove this ability from the target
        unset($target_robot->robot_attachments[$this_attachment_token]);
        $target_robot->update_session();

        // Return true on success
        return true;

        }
);
?>
