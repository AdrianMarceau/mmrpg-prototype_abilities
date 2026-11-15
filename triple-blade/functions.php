<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define the base attachment duration and multiplier
        $base_attachment_duration = 99; // Essentially indefinite until thrown
        $base_attachment_multiplier = 1.25;

        // Define this ability's attachment tokens
        $this_attachment_token_1 = 'ability_'.$this_ability->ability_token.'_1';
        $this_attachment_token_2 = 'ability_'.$this_ability->ability_token.'_2';
        $this_attachment_token_3 = 'ability_'.$this_ability->ability_token.'_3';

        // Define the first attachment (The Primary Attachment carrying the stat boosts)
        $this_attachment_info_1 = array(
            'class' => 'ability',
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 1,
            'ability_frame_animate' => array(1, 2, 3),
            'ability_frame_offset' => array('x' => -25, 'y' => 25, 'z' => -10),
            'attachment_token' => $this_attachment_token_1,
            'attachment_duration' => $base_attachment_duration,
            'attachment_damage_output_booster_cutter' => $base_attachment_multiplier,
            'attachment_destroy' => array(
                'trigger' => 'special',
                'kind' => '',
                'type' => '',
                'percent' => true,
                'modifiers' => false,
                'frame' => 'defend',
                'rates' => array(100, 0, 0),
                'success' => array(0, -9999, -9999, -9999,  'The '.$this_ability->print_name().' faded away!<br /> '.$this_robot->print_name().'\'s Cutter-type attacks returned to normal!'),
                'failure' => array(0, -9999, -9999, -9999, 'The '.$this_ability->print_name().' faded away!<br /> '.$this_robot->print_name().'\'s Cutter-type attacks returned to normal!')
                )
            );

        // Define the second attachment (Visual Only)
        $this_attachment_info_2 = $this_attachment_info_1;
        unset($this_attachment_info_2['attachment_damage_output_booster_cutter']);
        unset($this_attachment_info_2['attachment_destroy']);
        $this_attachment_info_2['attachment_token'] = $this_attachment_token_2;
        $this_attachment_info_2['ability_frame'] = 4;
        $this_attachment_info_2['ability_frame_animate'] = array(4, 5, 6);
        $this_attachment_info_2['ability_frame_offset'] = array('x' => -10, 'y' => 35, 'z' => -12);

        // Define the third attachment (Visual Only)
        $this_attachment_info_3 = $this_attachment_info_1;
        unset($this_attachment_info_3['attachment_damage_output_booster_cutter']);
        unset($this_attachment_info_3['attachment_destroy']);
        $this_attachment_info_3['attachment_token'] = $this_attachment_token_3;
        $this_attachment_info_3['ability_frame'] = 7;
        $this_attachment_info_3['ability_frame_animate'] = array(7, 8, 9);
        $this_attachment_info_3['ability_frame_offset'] = array('x' => 5, 'y' => 25, 'z' => -14);

        // Check if this ability is already charged (checking for the primary attachment)
        $is_charged = isset($this_robot->robot_attachments[$this_attachment_token_1]) ? true : false;

        // If the user has Quick Charge, auto-charge the ability
        if ($this_robot->has_attribute('quick-charge')){ $is_charged = true; }

        // If the ability flag was not set, this ability begins charging/summoning
        if (!$is_charged){

            // Attach all three ability attachments to the robot using it
            $this_robot->robot_attachments[$this_attachment_token_1] = $this_attachment_info_1;
            $this_robot->robot_attachments[$this_attachment_token_2] = $this_attachment_info_2;
            $this_robot->robot_attachments[$this_attachment_token_3] = $this_attachment_info_3;
            $this_robot->update_session();

            // Target this robot's self
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array(-1, 0, 50, -10, $this_robot->print_name().' summons the '.$this_ability->print_name().'!')
                ));
            $this_robot->trigger_target($this_robot, $this_ability);

        }
        // Else if the ability flag was set, the blades are thrown at the target
        else {

            // Update this ability's target options and trigger the throw            
            $this_robot->unset_attachment($this_attachment_token_1);
            $this_ability->target_options_update(array(
                'frame' => 'throw',
                'kickback' => array(-5, 0, 0),
                'success' => array(0, 100, 0, 10, $this_robot->print_name().' throws the '.$this_ability->print_name().'!'),
                ));
            $this_robot->trigger_target($target_robot, $this_ability);
            $this_robot->set_frame('throw');

            // First Hit
            $this_battle->queue_sound_effect('blade-sound');
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(10, 0, 0),
                'success' => array(0, -40, 0, 10, 'The first blade hit the target!'),
                'failure' => array(0, -60, 100, 0, -10, 'The first blade missed&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'kickback' => array(5, 0, 0),
                'success' => array(0, -40, 0, 10, 'The first blade was absorbed!'),
                'failure' => array(0, -60, 0, -10, 'The first blade missed&hellip;')
                ));
            $energy_damage_amount = $this_ability->ability_damage;
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

            // Second Hit
            if ($target_robot->robot_status != 'disabled' && $target_robot->robot_energy > 0){       
            	$this_robot->unset_attachment($this_attachment_token_2);
                $this_battle->queue_sound_effect('blade-sound');
                $this_ability->damage_options_update(array(
                    'success' => array(0, -60, 0, 10, 'The second blade hit the target!'),
                    'failure' => array(0, -80, 0, -10, 'The second blade missed&hellip;')
                    ));
                $this_ability->recovery_options_update(array(
                    'success' => array(0, -60, 0, 10, 'The second blade was absorbed!'),
                    'failure' => array(0, -80, 0, -10, 'The second blade missed&hellip;')
                    ));
                $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
            }

            // Third Hit
            if ($target_robot->robot_status != 'disabled' && $target_robot->robot_energy > 0){                       
		            $this_robot->unset_attachment($this_attachment_token_3);
                $this_battle->queue_sound_effect('blade-sound');
                $this_ability->damage_options_update(array(
                    'success' => array(0, -80, 0, 10, 'The third blade hit the target!'),
                    'failure' => array(0, -100, 0, -10, 'The third blade missed&hellip;')
                    ));
                $this_ability->recovery_options_update(array(
                    'success' => array(0, -80, 0, 10, 'The third blade was absorbed!'),
                    'failure' => array(0, -100, 0, -10, 'The third blade missed&hellip;')
                    ));
                $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
            }

            // Remove all ability attachments from the robot using it
            $this_robot->reset_frame();
            $this_robot->unset_attachment($this_attachment_token_1);
            $this_robot->unset_attachment($this_attachment_token_2);
            $this_robot->unset_attachment($this_attachment_token_3);
            
            // Check for disabled robots
            $target_player->check_robots_disabled($this_player, $this_robot);

        }

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's attachment token
        $this_attachment_token_1 = 'ability_'.$this_ability->ability_token.'_1';

        // Check if this ability is already charged
        $is_charged = isset($this_robot->robot_attachments[$this_attachment_token_1]) ? true : false;

        // If the ability flag had already been set, reduce the weapon energy to zero
        if ($is_charged){ $this_ability->set_energy(0); }
        // Otherwise, return the weapon energy back to default
        else { $this_ability->reset_energy(); }

        // If the user has Quick Charge, auto-charge the ability
        if ($this_robot->has_attribute('quick-charge')){ $is_charged = true; }

        // If the user has Extended Range, allow bench targeting
        if ($is_charged && $this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Return true on success
        return true;

    }
);
?>
