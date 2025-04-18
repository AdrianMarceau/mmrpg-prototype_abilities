<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_info = array(
            'class' => 'ability',
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 0,
            'ability_frame_animate' => array(1, 2, 1, 0),
            'ability_frame_offset' => array('x' => -10, 'y' => -10, 'z' => -20)
            );

        // Loop through each existing attachment and alter the start frame by one
        foreach ($this_robot->robot_attachments AS $key => $info){ array_push($this_attachment_info['ability_frame_animate'], array_shift($this_attachment_info['ability_frame_animate'])); }

        // Check if this ability is already charged
        $is_charged = isset($this_robot->robot_attachments[$this_attachment_token]) ? true : false;

        // If this ability is being used by a robot of a matching original player, boost power
        $is_boosted = false;
        if (!empty($this_robot->robot_original_player) && $this_robot->robot_original_player == 'dr-cossack'){
            $is_boosted = true;
            $this_ability->set_damage(ceil($this_ability->ability_base_damage * 1.2));
        } else {
            $this_ability->reset_damage();
        }

        // If the user has Quick Charge, auto-charge the ability
        if ($this_robot->has_attribute('quick-charge')){ $is_charged = true; }

        // If this ability is being used by a robot of a matching original player, boost power
        if ($is_charged || $is_boosted){
            $new_name = $this_ability->ability_base_name;
            if ($is_boosted){ $new_name .= '+'; }
            if ($is_charged){ $new_name .= ' ✦'; }
            $this_ability->set_name($new_name);
        } else {
            $this_ability->reset_name();
        }

        // If the ability flag was not set, this ability begins charging
        if (!$is_charged){

            // Target this robot's self
            $this_battle->queue_sound_effect(array('name' => 'charge-sound', 'volume' => 0.6));
            $this_battle->queue_sound_effect(array('name' => 'charge-sound', 'volume' => 0.8, 'delay' => 85));
            $this_battle->queue_sound_effect(array('name' => 'charge-sound', 'volume' => 1.0, 'delay' => 170));
            $this_ability->target_options_update(array(
                'frame' => 'defend',
                'success' => array(1, -10, 0, -10, $this_robot->print_name().' charges the '.$this_ability->print_name().'&hellip;')
                ));
            $trigger_options = array();
            $this_robot->trigger_target($this_robot, $this_ability, $trigger_options);

            // Call the global stat boost function with customized options
            rpg_ability::ability_function_stat_boost($this_robot, 'speed', 1, $this_ability);

            // Attach this ability attachment to the robot using it
            $this_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
            $this_robot->update_session();

        }
        // Else if the ability flag was set, the ability is released at the target
        else {

            // Remove this ability attachment to the robot using it
            unset($this_robot->robot_attachments[$this_attachment_token]);
            $this_robot->update_session();

            // Update this ability's target options and trigger
            $this_battle->queue_sound_effect(array('name' => 'blast-sound', 'volume' => 1.0));
            $this_battle->queue_sound_effect(array('name' => 'blast-sound', 'volume' => 0.8, 'delay' => 40));
            $this_battle->queue_sound_effect(array('name' => 'blast-sound', 'volume' => 0.6, 'delay' => 80));
            $this_ability->target_options_update(array(
                'frame' => 'shoot',
                'kickback' => array(-5, 0, 0),
                'success' => array(3, 100, -15, 10, $this_robot->print_name().' fires the '.$this_ability->print_name().'!'),
                ));
            $trigger_options = array();
            $this_robot->trigger_target($target_robot, $this_ability, $trigger_options);

            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(20, 0, 0),
                'success' => array(3, -110, -15, 10, 'A massive energy shot hit the target!'),
                'failure' => array(3, -110, -15, -10, 'The '.$this_ability->print_name().' shot missed&hellip;')
                ));
            $energy_damage_amount = $this_ability->ability_damage;
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        }

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token;

        // Check if this ability is already charged
        $is_charged = isset($this_robot->robot_attachments[$this_attachment_token]) ? true : false;

        // If the ability flag had already been set, reduce the weapon energy to zero
        if ($is_charged){ $this_ability->set_energy(0); }
        // Otherwise, return the weapon energy back to default
        else { $this_ability->reset_energy(); }

        // If the user has Quick Charge, auto-charge the ability
        if ($this_robot->has_attribute('quick-charge')){ $is_charged = true; }

        // If the user has Extended Range, allow bench targeting
        if ($is_charged && $this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // If this ability is being used by a robot of a matching original player, boost power
        $is_boosted = false;
        if (!empty($this_robot->robot_original_player) && $this_robot->robot_original_player == 'dr-cossack'){
            $is_boosted = true;
            $this_ability->set_damage(ceil($this_ability->ability_base_damage * 1.2));
        } else {
            $this_ability->reset_damage();
        }

        // If this ability is being used by a robot of a matching original player, boost power
        if ($is_charged || $is_boosted){
            $new_name = $this_ability->ability_base_name;
            if ($is_boosted){ $new_name .= '+'; }
            if ($is_charged){ $new_name .= ' ✦'; }
            $this_ability->set_name($new_name);
        } else {
            $this_ability->reset_name();
        }

        // Return true on success
        return true;

    }
);
?>
