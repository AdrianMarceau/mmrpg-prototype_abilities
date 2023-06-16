<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define the target and impact frames based on user
        $this_frames = array('target' => 0, 'impact' => 1);
        //if (preg_match('/_alt$/', $this_robot->robot_image)){ $this_frames = array('target' => 2, 'impact' => 3); }
        //elseif (preg_match('/_alt2$/', $this_robot->robot_image)){ $this_frames = array('target' => 4, 'impact' => 5); }

        // Define this ability's attachment token
        $static_attachment_key = $target_robot->get_static_attachment_key();
        $static_attachment_duration = 3;
        $this_attachment_info = rpg_ability::get_static_attachment('bubble-spray', 'foamy-bubbles', $static_attachment_key, $static_attachment_duration);
        $this_attachment_token = $this_attachment_info['attachment_token'];

        // Update the ability's target options and trigger
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array($this_frames['target'], 90, 15, 10, $this_robot->print_name().' sprays the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Apply or re-apply this attachment to the battle field, regardless of the ability's damage/recovery
        $attachment_already_exists = isset($this_battle->battle_attachments[$static_attachment_key][$this_attachment_token]) ? true : false;
        $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token] = $this_attachment_info;
        $this_battle->update_session();

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array($this_frames['impact'], -30, -10, 10, 'The '.$this_ability->print_name().' crashed into the target!'),
            'failure' => array($this_frames['impact'], -30, -10, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array($this_frames['impact'], -30, -10, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
            'failure' => array($this_frames['impact'], -30, -10, -10, 'The '.$this_ability->print_name().' missed&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Return true on success
        return true;

        }
);
?>
