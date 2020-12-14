<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define the base attachment duration
        $base_attachment_duration = 2;
        $base_attachment_damage = $this_ability->ability_damage;
        if ($this_robot->robot_core === $this_ability->ability_type){ $base_attachment_damage = ceil($base_attachment_damage * MMRPG_SETTINGS_COREBOOST_MULTIPLIER); }
        if ($this_robot->robot_item === $this_ability->ability_type.'-core'){ $base_attachment_damage = ceil($base_attachment_damage * MMRPG_SETTINGS_SUBCOREBOOST_MULTIPLIER); }
        if ($this_robot->robot_skill === $this_ability->ability_type.'-subcore'){ $base_attachment_damage = ceil($base_attachment_damage * MMRPG_SETTINGS_COREBOOST_MULTIPLIER); }

        // Define this ability's attachment token and info
        $attachment_x_offset = 20;
        $attachment_y_offset = -15;
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => false,
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_image,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $base_attachment_duration,
            'ability_frame' => 1,
            'ability_frame_animate' => array(1, 2, 3),
            'ability_frame_offset' => array('x' => $attachment_x_offset, 'y' => $attachment_y_offset, 'z' => 10),
            'attachment_energy' => 0,
            'attachment_energy_base_percent' => $base_attachment_damage,
            'attachment_create' => array(
                'kind' => 'energy',
                'trigger' => 'special',
                'type' => '',
                'percent' => false,
                'frame' => 'defend',
                'success' => array(4, $attachment_x_offset, $attachment_y_offset, 10, 'The '.$this_ability->print_name().' attached itself to '.$this_robot->print_name().'!'),
                'failure' => array(4, 2, ($attachment_x_offset + 40), $attachment_y_offset, 'The '.$this_ability->print_name().' flew past the target&hellip;')
                ),
            'attachment_destroy' => array(
                'kind' => 'energy',
                'trigger' => 'damage',
                'type' => $this_ability->ability_type,
                'energy' => $base_attachment_damage,
                'percent' => true,
                'modifiers' => true,
                'frame' => 'damage',
                'rates' => array(100, 0, 0),
                'success' => array(3, $attachment_x_offset, $attachment_y_offset, 10, 'The '.$this_ability->print_name().' suddenly exploded!'),
                'failure' => array(0, -9999, -9999, 0, 'The '.$this_ability->print_name().' fizzled and faded away&hellip;'),
                'options' => array(
                    'apply_modifiers' => true,
                    'apply_type_modifiers' => true,
                    'apply_core_modifiers' => true,
                    'apply_field_modifiers' => true,
                    'apply_stat_modifiers' => false,
                    'apply_position_modifiers' => false,
                    'referred_damage' => true,
                    'referred_damage_id' => 0,
                    'referred_damage_stats' => array()
                    )
                )
            );

        // Define this ability's secondary attachment token and info (for fx)
        $this_attachment_fx_token = 'ability_'.$this_ability->ability_token.'_fx';
        $this_attachment_fx_info = array(
            'class' => 'ability',
            'sticky' => false,
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_image.'-2',
            'attachment_token' => $this_attachment_fx_token,
            'attachment_duration' => $base_attachment_duration,
            'ability_frame' => 0,
            'ability_frame_animate' => array(0),
            'ability_frame_offset' => array('x' => $attachment_x_offset, 'y' => $attachment_y_offset, 'z' => 9),
            'attachment_destroy' => array(
                'kind' => 'energy',
                'trigger' => 'special',
                'type' => '',
                'energy' => 0,
                'rates' => array(100, 0, 0),
                'success' => array(0, -9999, -9999, 0, ''),
                'failure' => array(0, -9999, -9999, 0, ''),
                'options' => array(
                    'silent' => true
                    )
                )
            );


        // Create the attachment object for this ability
        $this_attachment = rpg_game::get_ability($this_battle, $target_player, $target_robot, $this_attachment_info);

        // If the target does not have this attachment yet, set it now
        if (!isset($target_robot->robot_attachments[$this_attachment_token])){

            // Target this robot's self
            $this_ability->target_options_update(array(
                'frame' => 'throw',
                'success' => array(0, 100, $attachment_y_offset, 10, $this_robot->print_name().' throw a '.$this_ability->print_name().'!')
                ));
            $this_robot->trigger_target($this_robot, $this_ability);

            // Count the number of existing copies of this attachment on the target side of the field
            $count_existing_copies = function($attachment_token) use ($target_player){
                $copies = 0;
                $robots = $target_player->get_robots_active();
                foreach ($robots AS $key => $robot){
                    if (!isset($robot->robot_attachments[$attachment_token])){ continue; }
                    $copies += 1;
                    }
                return $copies;
                };
            $num_existing_attachments = $count_existing_copies($this_attachment_token);

            // Target this robot's self
            $this_robot->set_frame('base');
            $this_attachment->target_options_update($this_attachment_info['attachment_create']);
            $target_robot->trigger_target($target_robot, $this_attachment);

            // Attach this ability attachment to the robot using it
            $target_robot->set_attachment($this_attachment_token, $this_attachment_info);
            $target_robot->set_attachment($this_attachment_fx_token, $this_attachment_fx_info);

            // Update all instances of this attachment with new power level and settings
            $new_power_level = 1 + $num_existing_attachments;
            $update_attachment_power_levels = function($attachment_token, $attachment_fx_token, $power_level) use ($target_player, $base_attachment_duration, $base_attachment_damage){
                $new_damage = $base_attachment_damage * $power_level;
                $new_duration = $base_attachment_duration;
                $new_fx_frame = $power_level - 1;
                $new_fx_frames = range(0, $new_fx_frame, 1);
                $robots = $target_player->get_robots_active();
                foreach ($robots AS $key => $robot){
                    if (!isset($robot->robot_attachments[$attachment_token])){ continue; }
                    // Update the attachment itself with new duration and damage
                    $robot->update_attachment($attachment_token, 'attachment_power_level', $power_level);
                    $robot->update_attachment($attachment_token, 'attachment_duration', $new_duration);
                    $robot->update_attachment($attachment_token, 'attachment_energy_base_percent', $new_damage);
                    $robot->update_attachment($attachment_token, 'attachment_destroy', 'energy', $new_damage);
                    // Update the attachment fx with new duration and animation
                    $robot->update_attachment($attachment_fx_token, 'attachment_power_level', $power_level);
                    $robot->update_attachment($attachment_fx_token, 'attachment_duration', $new_duration);
                    $robot->update_attachment($attachment_fx_token, 'ability_frame', $new_fx_frame);
                    $robot->update_attachment($attachment_fx_token, 'ability_frame_animate', $new_fx_frames);
                    }
                };
            $update_attachment_power_levels($this_attachment_token, $this_attachment_fx_token, $new_power_level);

        }
        // Else if they already have the attachment, detonate it early
        else {

            // Target the opposing robot
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(0, -9999, -9999, 0, $this_robot->print_name().' detonated the '.$this_ability->print_name().' early!')
                ));
            $this_robot->trigger_target($target_robot, $this_ability);

            // Collect the existing attachment info from the target then remove it
            $existing_attachment_info = $target_robot->robot_attachments[$this_attachment_token];
            $target_robot->unset_attachment($this_attachment_token);

            // Update this ability with the attachment destroy data
            $this_ability->damage_options_update($existing_attachment_info['attachment_destroy']);

            // Collect the energy damage amount and cut it in half for triggering early
            $energy_damage_amount = $existing_attachment_info['attachment_energy_base_percent'];

            // Now that we have the new amount, we can trigger the damage right away
            $damage_trigger_options = $this_attachment_info['attachment_destroy']['options'];
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, true, $damage_trigger_options);

        }

        // Return true on success
        return true;

        }
);
?>
