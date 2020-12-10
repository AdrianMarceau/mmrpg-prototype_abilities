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
            'ability_frame_offset' => array('x' => 120, 'y' => 0, 'z' => 10)
            );

        // Attach this ability attachment to the robot using it
        $this_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
        $this_robot->update_session();

        // Target the opposing robot
        $this_ability->target_options_update(array(
            'frame' => ($this_robot->robot_token == 'hard-man' ? 'throw' : 'shoot'),
            'success' => array(2, 60, ($this_robot->robot_token == 'hard-man' ? 10 : 0), -10, $this_robot->print_name().' fires the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Attach this ability attachment to the robot using it
        unset($this_robot->robot_attachments[$this_attachment_token]);
        $this_robot->update_session();

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(60, 0, 0),
            'success' => array(0, 50, 0, 10, 'The '.$this_ability->print_name().' crashes into the target!'),
            'failure' => array(0, -120, 0, -10, 'The '.$this_ability->print_name().' flew past the target&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(60, 0, 0),
            'success' => array(0, 50, 0, 10, 'The '.$this_ability->print_name().' crashes into the target!'),
            'failure' => array(0, -120, 0, -10, 'The '.$this_ability->print_name().' flew past the target&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        // Trigger a defense break if the ability was successful
        if ($target_robot->robot_status != 'disabled'
            && $this_ability->ability_results['this_result'] != 'failure'){

            // Call the global stat break function with customized options
            rpg_ability::ability_function_stat_break($target_robot, 'defense', 1, $this_ability, array(
                'initiator_robot' => $this_robot
                ));

        }

        // Return true on success
        return true;

        },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If the user has Extended Range, allow bench targeting
        if ($this_robot->has_attribute('extended-range')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Return true on success
        return true;

        }
);
?>
