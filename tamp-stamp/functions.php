<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define the properties for this ability's effect attachment
        $fx_attachment_token = 'ability_'.$this_ability->ability_token.'_fx';
        $fx_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'attachment_token' => $fx_attachment_token,
            'ability_id' => $this_ability->ability_id,
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_token,
            'ability_frame' => 1,
            'ability_frame_animate' => array(0),
            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => -10),
            'ability_frame_classes' => '',
            'ability_frame_styles' => '',
            );

        // Attach the effect to this robot showing it zooming upward
        $fx_attachment_info['ability_frame_offset'] = array('x' => -20, 'y' => -35, 'z' => -10);
        $fx_attachment_info['ability_frame_styles'] = 'transform: rotate(90deg);';
        $this_robot->set_attachment($fx_attachment_token, $fx_attachment_info);

        // Update the ability's target options and trigger
        $this_robot->set_frame_offset(array('x' => 0, 'y' => 50, 'z' => 10));
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'kickback' => array(0, 0, 0),
            'success' => array(9, 0, 0, -9999, $this_robot->print_name().' uses the  '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Show an empty frame in-between while it's in the air
        $this_robot->set_frame_offset(array('x' => 90, 'y' => 200, 'z' => 20));
        $this_battle->events_create(false, false, '', '', array(
            'event_flag_camera_action' => false
            ));

        // Attach the effect to this robot showing it zooming downward
        $fx_attachment_info['ability_frame_offset'] = array('x' => 20, 'y' => 35, 'z' => -10);
        $fx_attachment_info['ability_frame_styles'] = 'transform: rotate(-90deg);';
        $this_robot->set_attachment($fx_attachment_token, $fx_attachment_info);

        // Inflict damage on the opposing robot
        $this_robot->set_frame_offset(array('x' => 180, 'y' => 40, 'z' => 30));
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(5, -10, 0),
            'success' => array(2, 0, 80, 10, 'The '.$this_ability->print_name().' crashed into the target!'),
            'failure' => array(2, 0, 80, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'kickback' => array(5, -10, 0),
            'success' => array(2, 0, 80, 10, 'The '.$this_ability->print_name().' crashed into the target!'),
            'failure' => array(2, 0, 80, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
        $this_robot->reset_frame_offset();

        // Remove the effect attachment from this robot
        $this_robot->unset_attachment($fx_attachment_token);

        // Return true on success
        return true;

        }
);
?>
