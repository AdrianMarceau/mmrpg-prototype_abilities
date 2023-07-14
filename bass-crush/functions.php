<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define the base attachment duration
        $base_attachment_duration = 6;
        $base_attachment_multiplier = 2.0;

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token; //.'_'.$target_robot->robot_id;
        $this_attachment_info = array(
            'class' => 'ability',
            'ability_id' => $this_ability->ability_id,
            'ability_token' => $this_ability->ability_token,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $base_attachment_duration,
            'attachment_damage_input_booster' => $base_attachment_multiplier,
            'attachment_create' => array(
                'trigger' => 'special',
                'kind' => '',
                'percent' => true,
                'frame' => 'taunt',
                'rates' => array(100, 0, 0),
                'success' => array(0, -10, 0, -18, 'The '.$this_ability->print_name().' hovers behind '.$target_robot->print_name().'!<br /> '.$target_robot->print_name().'&#39;s defenses were compromised!'),
                'failure' => array(0, -10, 0, -18, 'The '.$this_ability->print_name().' hovers behind '.$target_robot->print_name().'!<br /> '.$target_robot->print_name().'&#39;s defenses were compromised!')
                ),
            'attachment_destroy' => array(
                'trigger' => 'special',
                'kind' => '',
                'type' => '',
                'percent' => true,
                'modifiers' => false,
                'frame' => 'defend',
                'rates' => array(100, 0, 0),
                'success' => array(2, -10, 0, -10,  'The '.$this_ability->print_name().' faded away!<br /> '.$target_robot->print_name().'&#39;s defenses returned to normal!'),
                'failure' => array(2, -10, 0, -10, 'The '.$this_ability->print_name().' faded away!<br /> '.$target_robot->print_name().'&#39;s defenses returned to normal!')
                ),
                'ability_frame' => 0,
                'ability_frame_animate' => array(0, 1, 2, 1),
                'ability_frame_offset' => array('x' => -10, 'y' => 0, 'z' => -18)
            );

        // Create the attachment object for this ability
        $this_attachment = rpg_game::get_ability($this_battle, $target_player, $target_robot, $this_attachment_info);

        // If the ability flag was not set, attach the Proto Shield to the target
        if (!isset($target_robot->robot_attachments[$this_attachment_token])){

            // Target this robot's self
            $this_battle->queue_sound_effect(array('name' => 'spawn-sound', 'volume' => 0.5));
            $this_battle->queue_sound_effect('summon-negative');
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array((!isset($this_robot->robot_attachments[$this_attachment_token]) ? 0 : 9), -10, 0, -18, $this_robot->print_name().' summons a '.$this_ability->print_name().'!')
                ));
            $this_robot->trigger_target($this_robot, $this_ability);

            // Target this robot's self
            $this_battle->queue_sound_effect('horn-blow-sound');
            $this_battle->queue_sound_effect(array('name' => 'debuff-received', 'delay' => 200));
            $this_battle->queue_sound_effect(array('name' => 'debuff-received', 'delay' => 400));
            $this_robot->robot_frame = 'base';
            $this_robot->update_session();
            $this_attachment->target_options_update($this_attachment_info['attachment_create']);
            $target_robot->trigger_target($target_robot, $this_attachment);

            // Attach this ability attachment to the robot using it
            $this_attachment_info['ability_frame_animate'] = array(0, 1, 2, 1);
            $target_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
            $target_robot->update_session();

        }
        // Else if the ability flag was set, reinforce the shield by one more duration point
        else {

            // Collect the attachment from the robot to back up its info
            $this_attachment_info = $target_robot->robot_attachments[$this_attachment_token];
            $this_attachment_info['attachment_duration'] = $base_attachment_duration;
            $this_attachment_info['attachment_damage_input_booster'] = $this_attachment_info['attachment_damage_input_booster'] * $base_attachment_multiplier;
            $target_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
            $target_robot->update_session();

            // Target the opposing robot
            $this_battle->queue_sound_effect('summon-negative');
            $this_battle->queue_sound_effect('horn-blow-sound');
            $this_battle->queue_sound_effect(array('name' => 'debuff-received', 'delay' => 200));
            $this_battle->queue_sound_effect(array('name' => 'debuff-received', 'delay' => 400));
            $this_attachment->target_options_update(array(
                'frame' => 'summon',
                'success' => array(9, -10, 0, -10, $this_robot->print_name().' intensified the effects of the '.$this_ability->print_name().'!<br /> The duration of '.$target_robot->print_name().'&#39;s distress was extended!')
                ));
            $this_robot->trigger_target($this_robot, $this_attachment);

        }

        // Either way, update this ability's settings to prevent recovery
        $this_attachment->damage_options_update($this_attachment_info['attachment_destroy'], true);
        $this_attachment->recovery_options_update($this_attachment_info['attachment_destroy'], true);
        $this_attachment->update_session();

        // Return true on success
        return true;

    }
);
?>
