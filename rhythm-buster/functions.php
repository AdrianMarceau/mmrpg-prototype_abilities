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

        // If the user has Quick Charge, auto-charge the ability
        if ($this_robot->has_attribute('quick-charge')){ $is_charged = true; }

        // If the ability flag was not set, this ability begins charging
        if (!$is_charged){

            // Target this robot's self
            $this_ability->target_options_update(array(
                'frame' => 'defend',
                'success' => array(1, -10, 0, -10, $this_robot->print_name().' charges the '.$this_ability->print_name().'&hellip;')
                ));
            $this_robot->trigger_target($this_robot, $this_ability);

            // Increase this robot's speed stat slightly
            if ($this_robot->robot_energy < $this_robot->robot_base_energy){
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'percent' => true,
                    'modifiers' => true,
                    'rates' => array(100, 0, 0),
                    'success' => array(2, -10, 0, -10, $this_robot->print_name().'&#39;s energy was restored!'),
                    'failure' => array(2, -10, 0, -10, $this_robot->print_name().'&#39;s energy was not affected&hellip;')
                    ));
                $energy_recovery_amount = ceil($this_robot->robot_base_energy * ($this_ability->ability_recovery2 / 100));
                $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false, 'apply_stat_modifiers' => false);
                $this_robot->trigger_recovery($this_robot, $this_ability, $energy_recovery_amount, true, $trigger_options);
            }

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
            $this_ability->target_options_update(array(
                'frame' => 'shoot',
                'kickback' => array(-5, 0, 0),
                'success' => array(3, 100, -15, 10, $this_robot->print_name().' fires the '.$this_ability->print_name().'!'),
                ));
            $this_robot->trigger_target($target_robot, $this_ability);

            // Update this ability's target options and trigger
            $this_ability->target_options_update(array(
                'frame' => 'damage',
                'kickback' => array(-10, 0, 0),
                'success' => array(3, -110, -15, 10, 'A massive energy shot hit the target!'),
                ));
            $target_robot->trigger_target($this_robot, $this_ability, array('prevent_default_text' => true));

            // Ensure the target is not disabled before apply a stat change
            if ($target_robot->robot_status != 'disabled'
                && $this_ability->ability_results['this_result'] != 'failure'){

                // Call the global stat break function with customized options
                rpg_ability::ability_function_stat_break($target_robot, 'speed', 3, $this_ability, array(
                    'initiator_robot' => $this_robot
                    ));

            }

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

        // If this is being used by the owning support robot, it's even more effective at healing
        if ($this_robot->robot_token.'-buster' === $this_ability->ability_token){ $this_ability->set_recovery2(ceil($this_ability->ability_base_recovery2 * 1.5)); }
        else { $this_ability->set_recovery2($this_ability->ability_base_recovery2); }

        // If the ability flag had already been set, reduce the weapon energy to zero
        if ($is_charged){ $this_ability->set_energy(0); }
        // Otherwise, return the weapon energy back to default
        else { $this_ability->reset_energy(); }

        // If the user has Quick Charge, auto-charge the ability
        if ($this_robot->has_attribute('quick-charge')){ $is_charged = true; }

        // If the user has Extended Range, allow bench targeting
        if ($is_charged && $this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // If this ability is being already charged, we should put an indicator
        if ($is_charged){
            $new_name = $this_ability->ability_base_name;
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
