<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's attachment token
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability->ability_token,
            'ability_image' => $this_ability->ability_token,
            'ability_frame' => 0,
            'ability_frame_animate' => array(0, 1),
            'ability_frame_offset' => array('x' => -5, 'y' => 20, 'z' => -10),
            'ability_frame_classes' => ' ',
            'ability_frame_styles' => ' '
            );

        // Define the target and impact frames based on user
        $this_frames = array('target' => 0, 'impact' => 0);
        //if (preg_match('/_alt$/', $this_robot->robot_image)){ $this_frames = array('target' => 4, 'impact' => 5); }
        //elseif (preg_match('/_alt2$/', $this_robot->robot_image)){ $this_frames = array('target' => 0, 'impact' => 1); }

        // Change the image to the full-screen rain effect
        $this_ability->ability_image = '_effects/yellow-overlay';
        $this_ability->ability_frame_classes = 'sprite_fullscreen ';
        $this_ability->ability_frame_styles = 'opacity: 0.5; filter: alpha(opacity=50); ';
        $this_ability->update_session();

        // Attach this ability attachment to the robot using it
        $this_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
        $this_robot->update_session();

        // Update the ability's target options and trigger
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array($this_frames['target'], -5, 0, -10, $this_robot->print_name().' uses the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Ensure this robot stays in the summon position for the duration of the defense
        $this_robot->robot_frame = 'defend';
        $this_robot->update_session();

        // Attach this ability attachment to the target robot taking it
        $this_attachment_info['ability_frame'] = 2;
        $this_attachment_info['ability_frame_animate'] = array(2);
        $target_robot->robot_attachments[$this_attachment_token] = $this_attachment_info;
        $target_robot->update_session();

        // Inflict damage on the opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array($this_frames['impact'], -5, 0, 99, 'The '.$this_ability->print_name().' blinded the target!'),
            'failure' => array($this_frames['impact'], -5, 0, -10, 'The '.$this_ability->print_name().' had no effect&hellip;')
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array($this_frames['impact'], -5, 0, 99, 'The '.$this_ability->print_name().' enlightened the target!'),
            'failure' => array($this_frames['impact'], -5, 0, -10, 'The '.$this_ability->print_name().' had no effect&hellip;')
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

        // Change the image to the full-screen rain effect
        $this_ability->ability_image = $this_ability->ability_token;
        $this_ability->ability_frame_classes = '';
        $this_ability->ability_frame_styles = 'display: none; ';
        $this_ability->update_session();

        // Remove the ability attachment from this robot
        unset($this_robot->robot_attachments[$this_attachment_token]);
        $this_robot->update_session();

        // Remove the ability attachment from the target robot
        unset($target_robot->robot_attachments[$this_attachment_token]);
        $target_robot->update_session();

        // If the target was disabled, trigger the event now
        if ($target_robot->robot_status == 'disabled' || $target_robot->robot_energy == 0){
            $target_robot->trigger_disabled($this_robot);
        }

        // Ensure the target is not disabled before apply a stat change
        if ($target_robot->robot_status != 'disabled'
            && $this_ability->ability_results['this_result'] != 'failure'){

            // Call the global stat break function with customized options
            rpg_ability::ability_function_stat_break($target_robot, 'attack', 2);

        }

        // Change the image to the full-screen rain effect
        $this_ability->ability_image = $this_ability->ability_token;
        $this_ability->ability_frame_classes = '';
        $this_ability->ability_frame_styles = '';
        $this_ability->update_session();

        // Ensure this robot stays goes back to the base frame after the attack
        $this_robot->robot_frame = 'base';
        $this_robot->update_session();

        // Return true on success
        return true;

        },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If the user is holding a Target Module, allow bench targeting
        if ($this_robot->has_item('target-module')){ $this_ability->set_target('select_target'); }
        else { $this_ability->reset_target(); }

        // Return true on success
        return true;

        }
);
?>
