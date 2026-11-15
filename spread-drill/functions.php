<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's visual attachments for the smaller drills (using frame 2)
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_info = array(
            'class' => 'ability',
            'ability_id' => $this_ability->ability_id,
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 2,
            'ability_frame_animate' => array(2),
            'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => 0)
            );
            
        // Pre-create the two smaller drill attachments with large X offsets to push them across the screen
        $this_attachment_info1 = $this_attachment_info;
        $this_attachment_info1['ability_id'] .= '01';
        $this_attachment_info1['ability_frame_offset'] = array('x' => 300, 'y' => 15, 'z' => -15);
        $this_attachment_info2 = $this_attachment_info;
        $this_attachment_info2['ability_id'] .= '02';
        $this_attachment_info2['ability_frame_offset'] = array('x' => 300, 'y' => -15, 'z' => -10);

        // Target the active opposing robot (using frame 0 for the large drill)
        $this_battle->queue_sound_effect(array('name' => 'shot-sound-alt'));
        $this_ability->target_options_update(array(
            'frame' => 'shoot',
            'success' => array(0, 135, 0, 10, $this_robot->print_name().' fires the '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Inflict damage on the active opposing robot
        $this_ability->damage_options_update(array(
            'kind' => 'energy',
            'kickback' => array(10, 0, 0),
            'success' => array(0, -45, 0, 10, 'The '.$this_ability->print_name().' collided with the target!'),
            'failure' => array(0, -105, 0, -10, 'The '.$this_ability->print_name().' flew past the target&hellip;'),
            'options' => array('apply_position_modifiers' => false)
            ));
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'frame' => 'taunt',
            'kickback' => array(10, 0, 0),
            'success' => array(0, -45, 0, 10, 'The '.$this_ability->print_name().' healed the target!'),
            'failure' => array(0, -105, 0, -10, 'The '.$this_ability->print_name().' flew past the target&hellip;'),
            'options' => array('apply_position_modifiers' => false)
            ));
        $energy_damage_amount = $this_ability->ability_damage;
        $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);

        // If the initial attack was successful, split the drill and target the bench
        if ($this_ability->ability_results['this_result'] != 'failure'){
            
            // Generate the small drill attachments on the user to simulate the split
            $this_robot->set_attachment($this_attachment_token.'_1', $this_attachment_info1);
            $this_robot->set_attachment($this_attachment_token.'_2', $this_attachment_info2);
            
            // Helper function to find a valid benched target that hasn't been hit yet
            $target_robot_ids = array();
            $get_random_target_robot = function() use($this_battle, $target_player, &$target_robot_ids){
                $active_robot_keys = array_keys($target_player->values['robots_active']);
                shuffle($active_robot_keys);
                foreach ($active_robot_keys AS $robot_key){
                    $robot_info = $target_player->values['robots_active'][$robot_key];
                    if ($robot_info['robot_position'] == 'active'){ continue; }
                    if (in_array($robot_info['robot_id'], $target_robot_ids)){ continue; }
                    $random_target_robot = rpg_game::get_robot($this_battle, $target_player, $robot_info);
                    $target_robot_ids[] = $robot_info['robot_id'];
                    return $random_target_robot;
                }
                return false;
            };

            // Attempt to find and strike the first benched robot
            $benched_target_1 = $get_random_target_robot();
            if (!empty($benched_target_1) && $benched_target_1->robot_status != 'disabled'){
                
                // Remove the first visual drill attachment and trigger target action
                $this_robot->unset_attachment($this_attachment_token.'_1');
                $this_battle->queue_sound_effect(array('name' => 'shot-sound-alt', 'delay' => 100));
                
                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'kickback' => array(10, 0, 0),
                    'success' => array(2, -40, 5, 10, 'A smaller drill hit '.$benched_target_1->print_name().'!'),
                    'failure' => array(2, -60, 5, -10, 'A smaller drill missed '.$benched_target_1->print_name().'!'),
                    'options' => array('apply_position_modifiers' => false)
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'kickback' => array(0, 0, 0),
                    'success' => array(2, -40, 5, 10, 'A smaller drill hit '.$benched_target_1->print_name().'!'),
                    'failure' => array(2, -60, 5, -10, 'A smaller drill missed '.$benched_target_1->print_name().'!'),
                    'options' => array('apply_position_modifiers' => false)
                    ));
                $benched_target_1->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
            }

            // Attempt to find and strike the second benched robot
            $benched_target_2 = $get_random_target_robot();
            if (!empty($benched_target_2) && $benched_target_2->robot_status != 'disabled'){
                
                // Remove the second visual drill attachment and trigger target action
                $this_robot->unset_attachment($this_attachment_token.'_2');
                $this_battle->queue_sound_effect(array('name' => 'shot-sound-alt', 'delay' => 100));

                $this_ability->damage_options_update(array(
                    'kind' => 'energy',
                    'kickback' => array(10, 0, 0),
                    'success' => array(2, -40, 5, 10, 'A smaller drill hit '.$benched_target_2->print_name().'!'),
                    'failure' => array(2, -60, 5, -10, 'A smaller drill missed '.$benched_target_2->print_name().'!'),
                    'options' => array('apply_position_modifiers' => false)
                    ));
                $this_ability->recovery_options_update(array(
                    'kind' => 'energy',
                    'frame' => 'taunt',
                    'kickback' => array(0, 0, 0),
                    'success' => array(2, -40, 5, 10, 'A smaller drill hit '.$benched_target_2->print_name().'!'),
                    'failure' => array(2, -60, 5, -10, 'A smaller drill missed '.$benched_target_2->print_name().'!'),
                    'options' => array('apply_position_modifiers' => false)
                    ));
                $benched_target_2->trigger_damage($this_robot, $this_ability, $energy_damage_amount, false);
            }
            
            // Clean up any remaining visual attachments in case there were less than 2 benched targets
            if ($this_robot->has_attachment($this_attachment_token.'_1')){ $this_robot->unset_attachment($this_attachment_token.'_1'); }
            if ($this_robot->has_attachment($this_attachment_token.'_2')){ $this_robot->unset_attachment($this_attachment_token.'_2'); }

        }

        // Now that all the damage has been dealt, allow the player to check for disabled robots
        $target_player->check_robots_disabled($this_player, $this_robot);

        // Return true on success
        return true;

    }
);
?>
