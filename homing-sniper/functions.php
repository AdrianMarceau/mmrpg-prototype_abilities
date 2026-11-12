<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token.'_lockon';
        $this_attachment_info = array(
            'class' => 'ability',
            'ability_id' => $this_ability->ability_id,
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_image,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => 2,
            'attachment_target_lockon' => true,
            'ability_frame' => 5,
            'ability_frame_animate' => array(5, 6, 7),
            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => 10),
            'attachment_create' => array(
                'trigger' => 'special',
                'kind' => '',
                'percent' => true,
                'modifiers' => false,
                'frame' => 'taunt',
                'rates' => array(100, 0, 0),
                'success' => array(5, 0, 0, 10, 'A targeting reticle locked onto '.$target_robot->print_name().'!<br /> '.$target_robot->print_name().'\'s evasion was nullified!'),
                'failure' => array(5, 0, 0, 10, 'A targeting reticle locked onto '.$target_robot->print_name().'!<br /> '.$target_robot->print_name().'\'s evasion was nullified!')
                ),
            'attachment_destroy' => array(
                'trigger' => 'special',
                'kind' => '',
                'type' => '',
                'percent' => true,
                'modifiers' => false,
                'frame' => 'defend',
                'rates' => array(100, 0, 0),
                'success' => array(0, -9999, -9999, -9999,  'The targeting reticle on '.$target_robot->print_name().' faded away...'),
                'failure' => array(0, -9999, -9999, -9999, 'The targeting reticle on '.$target_robot->print_name().' faded away...')
                )
            );

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 85, 0, 10, $this_robot->print_name().' fires the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(0, -35, 0, 10, 'The '.$this_ability->print_name().' struck the target!'),
            'failure' => array(0, -65, 0, -10, 'The '.$this_ability->print_name().' missed...')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(10, 0, 0),
            'success' => array(0, -35, 0, 10, 'The '.$this_ability->print_name().' struck the target!'),
            'failure' => array(0, -65, 0, -10, 'The '.$this_ability->print_name().' missed...')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Attach the lockon to the target if the attack was successful and they aren't disabled
        if ($this_ability->ability_results['this_result'] != 'failure' && $target_robot->robot_status != 'disabled'){
            
            // Attach the ability to the target
            $target_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
            $target_robot->update_session();

            // Target the opposing robot to show the lock-on message
            $this_ability->target_options_update($this_attachment_info['attachment_create'], true);
            $target_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));

        }

        // Return true on success
        return true;

    }
);
?>
