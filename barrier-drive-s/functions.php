<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Pull information about the core shield ability itself and update it's damage properties
        $core_shield_info = rpg_ability::get_index_info('core-shield');
        $core_shield_ability = rpg_game::get_ability($this_battle, $this_player, $this_robot, $core_shield_info);
        $core_shield_ability->set_name($this_ability->ability_name);
        $core_shield_ability->set_damage($this_ability->ability_damage);

        // Define a function to "pull" core shields forward a bit (for visual presentation)
        $pull_core_shields = function($battle, $player, $robots) use ($this_robot, $core_shield_info){
            if (empty($robots)){ return; }
            //error_log('$pull_core_shields($player '.gettype($player).', $robots '.gettype($robots).')');
            foreach ($robots AS $robot_key => $robot_info){
                //error_log('checking $robots['.$robot_key.'] = $robot_info = '.print_r($robot_info, true));
                //error_log('checking $robots['.$robot_key.'] = '.print_r(array('token' => $robot_info['robot_token'], 'name' => $robot_info['robot_name']), true));
                $robot = rpg_game::get_robot($battle, $player, $robot_info);
                //error_log('$robot->robot_attachments = '.print_r($robot->robot_attachments, true));
                if (!empty($robot->robot_attachments)){
                    $attachment_tokens = array_keys($robot->robot_attachments);
                    //error_log('found $attachment_tokens = '.print_r($attachment_tokens, true));
                    foreach ($attachment_tokens AS $token_key => $attachment_token){
                        //error_log('checking $attachment_tokens['.$token_key.'] = '.$attachment_token);
                        $attachment_info = $robot->robot_attachments[$attachment_token];
                        //error_log('$attachment_info = '.print_r($attachment_info, true));
                        if (strstr($attachment_token, 'ability_core-shield_')){
                            //error_log('found a core shield! tugging on the '.$attachment_token.' ...');
                            // Collect the type for this core shield we're removing
                            list($ab, $at, $core_type) = explode('_', $attachment_token);
                            // Update this field attachment with an an offset tweak
                            if (!isset($attachment_info['ability_frame_offset']['x'])){ continue; }
                            //error_log('increasing offset x for the '.$attachment_token.' ...');
                            $attachment_info['ability_frame_offset']['x'] += 40;
                            if ($robot->player->player_side !== $this_robot->player->player_side){ $attachment_info['ability_frame_offset']['x'] += 40; }
                            $robot->set_attachment($attachment_token, $attachment_info);
                            $robot->set_frame('damage');
                            }
                        }
                    }
                }
            };

        // Create an array to hold extracted core shields and a function to extra
        $extracted_core_shields = array();
        $extract_core_shields = function($battle, $player, $robots) use (&$extracted_core_shields, $core_shield_info){
            if (empty($robots)){ return; }
            //error_log('$extract_core_shields($player '.gettype($player).', $robots '.gettype($robots).')');
            foreach ($robots AS $robot_key => $robot_info){
                //error_log('checking $robots['.$robot_key.'] = $robot_info = '.print_r($robot_info, true));
                //error_log('checking $robots['.$robot_key.'] = '.print_r(array('token' => $robot_info['robot_token'], 'name' => $robot_info['robot_name']), true));
                $robot = rpg_game::get_robot($battle, $player, $robot_info);
                //error_log('$robot->robot_attachments = '.print_r($robot->robot_attachments, true));
                if (!empty($robot->robot_attachments)){
                    $attachment_tokens = array_keys($robot->robot_attachments);
                    //error_log('found $attachment_tokens = '.print_r($attachment_tokens, true));
                    foreach ($attachment_tokens AS $token_key => $attachment_token){
                        //error_log('checking $attachment_tokens['.$token_key.'] = '.$attachment_token);
                        $attachment_info = $robot->robot_attachments[$attachment_token];
                        //error_log('$attachment_info = '.print_r($attachment_info, true));
                        if (strstr($attachment_token, 'ability_core-shield_')){
                            //error_log('found a core shield! adding to existing '.count($extracted_core_shields).' shields');
                            // Collect the type for this core shield we're removing
                            list($ab, $at, $core_type) = explode('_', $attachment_token);
                            // Add a copy of this core shield to the extracted array
                            $extracted_core_shields[$attachment_token] = $attachment_info;
                            //error_log('$extracted_core_shields['.$attachment_token.'] = $attachment_info;');
                            //error_log('$extracted_core_shields = '.print_r($extracted_core_shields, true));
                            //error_log('$extracted_core_shields.length = '.count($extracted_core_shields));
                            // Update this field attachment with an opacity tweak before removing
                            if (!isset($attachment_info['ability_frame_styles'])){ $attachment_info['ability_frame_styles'] = ''; }
                            $attachment_info['ability_frame_styles'] .= ' opacity: 0.5; ';
                            $robot->set_attachment($attachment_token, $attachment_info);
                            // Remove this attachment from the robot (it'll be re-added later)
                            $robot->set_counter('item_disabled', 1);
                            $robot->set_flag('item_disabled_not_dropped', true);
                            $robot->set_counter('core-shield_cooldown_timer', 1);
                            $robot->unset_attachment($attachment_token);
                            $robot->set_frame('defend');
                            }
                        }
                    }
                }
            };

        // Update the ability's target options and trigger
        $this_battle->queue_sound_effect('hyper-summon-sound');
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 0, 0, -10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // Update the target robot so they're defending while this happens
        $target_robot->set_frame('defend');

        // Loop through all of this and the target player's robots to check for core shields
        $pull_core_shields($this_battle, $this_player, $this_player->values['robots_active']);
        $pull_core_shields($this_battle, $target_player, $target_player->values['robots_active']);

        // Show a zoomed-out frame where the user is pulling away their team's shields
        $this_battle->queue_sound_effect('intense-growing-sound');
        $this_robot->set_frame('taunt');
        $this_robot->set_frame_styles('transform: scaleX(-1); ');
        $this_battle->events_create(false, false, '', '');

        // Loop through all of this and the target player's robots to check for core shields
        $extract_core_shields($this_battle, $this_player, $this_player->values['robots_active']);
        $extract_core_shields($this_battle, $target_player, $target_player->values['robots_active']);
        //error_log('$extracted_core_shields = '.print_r($extracted_core_shields, true));

        // If there weren't any core shields collected, show the failure message and return now
        if (empty($extract_core_shields)){

            // Update the ability's target options and trigger
            $this_battle->queue_sound_effect('no-effect');
            $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(0, 0, 0, 10, '&hellip;but nothing happened.')));
            $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));
            return;

        }

        // Otherwise, we can attach all of the collected core shields to the user now
        $temp_existing_shields = 0;
        foreach ($extracted_core_shields AS $attachment_token => $attachment_info){
            // Adjust the offsets of the sprite so they don't overlap too much
            $attachment_info['ability_frame_offset'] = array(
                'x' => (10 + ($temp_existing_shields * 10)),
                'y' => (0),
                'z' => -1 * (10 + $temp_existing_shields)
                );
            // Attach this core shield to the user now
            $this_robot->set_attachment($attachment_token, $attachment_info);
            $temp_existing_shields++;
        }

        // Show a zoomed-out frame where all the shields have been moved from the other robots to the user
        $this_battle->queue_sound_effect('buff-received');
        $this_robot->set_frame('defend');
        $this_robot->set_frame_styles('');
        $this_battle->events_create(false, false, '', '');

        // Loop through both player's robots and reset their frames to base
        foreach ($this_player->values['robots_active'] AS $robot_key => $robot_info){
            $robot = rpg_game::get_robot($this_battle, $this_player, $robot_info);
            $robot->set_frame('base');
            $robot->set_frame_styles('');
            }
        foreach ($target_player->values['robots_active'] AS $robot_key => $robot_info){
            $robot = rpg_game::get_robot($this_battle, $target_player, $robot_info);
            $robot->set_frame('base');
            $robot->set_frame_styles('');
            }

        // Now throw those shields at the target, one-by-one
        $thrown_core_shields = 0;
        foreach ($extracted_core_shields AS $attachment_token => $attachment_info){

            // If the target robot is already disabled we obviously have to stop
            if ($target_robot->robot_energy <= 0 || $target_robot->robot_status === 'disabled'){ break; }

            // Attach this core shield to the target robot
            $this_robot->unset_attachment($attachment_token);
            //error_log('Now throwing a the core-shield '.print_r($attachment_token, true));

            // Create a temporary ability object for the core shield w/ these attachment details
            $shield_type1 = !empty($attachment_info['ability_type']) ? $attachment_info['ability_type'] : $core_shield_info['ability_type'];
            $shield_type2 = !empty($this_ability->ability_type) ? $this_ability->ability_type : $core_shield_info['ability_type'];
            if ($shield_type2 === $shield_type1){ $shield_type2 = ''; }
            $shield_types = implode('_', array_filter(array($shield_type1, $shield_type2)));
            $shield_name_span = rpg_type::print_span($shield_types, 'Core Shield');
            $shield_image = !empty($attachment_info['ability_image']) ? $attachment_info['ability_image'] : $core_shield_info['ability_image'];
            $core_shield_ability->set_type($shield_type1);
            $core_shield_ability->set_type2($shield_type2);
            $core_shield_ability->set_image($shield_image);
            //error_log('$this_ability->ability_damage = '.print_r($this_ability->ability_damage, true));
            //error_log('$this_ability->ability_type = '.print_r($this_ability->ability_type, true));
            //error_log('$this_ability->ability_type2 = '.print_r($this_ability->ability_type2, true));
            //error_log('$core_shield_ability->ability_damage = '.print_r($core_shield_ability->ability_damage, true));
            //error_log('$core_shield_ability->ability_type = '.print_r($core_shield_ability->ability_type, true));
            //error_log('$core_shield_ability->ability_type2 = '.print_r($core_shield_ability->ability_type2, true));
            //error_log('$core_shield_ability->export_array() = '.print_r($core_shield_ability->export_array(), true));

            // Update the ability's target options and trigger
            $this_battle->queue_sound_effect('hyper-slide-sound');
            $plural_shields = count($extracted_core_shields) > 1 ? true : false;
            if ($plural_shields){ $mid_text = $thrown_core_shields > 0 ? 'another' : 'the first'; }
            else { $mid_text = 'the'; }
            $core_shield_ability->target_options_update(array(
                'frame' => 'throw',
                'success' => array(3, 120, 0, 10, $this_robot->print_name().' releases '.$mid_text.' '.$shield_name_span.'!')
                ));
            $this_robot->trigger_target($target_robot, $core_shield_ability, array('prevent_default_text' => true));

            // Inflict damage on the opposing robot
            $core_shield_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(10, 0, 0),
                'success' => array(2, 20, 0, 99, 'The '.$shield_name_span.' crashes into the target!'),
                'failure' => array(2, 30, 0, -10, 'The '.$shield_name_span.' had no effect&hellip;')
                ));
            $core_shield_ability->recovery_options_update(array(
                'kind' => 'energy',
                'kickback' => array(10, 0, 0),
                'success' => array(2, 20, 0, 99, 'The '.$shield_name_span.' phases through the target!'),
                'failure' => array(2, 30, 0, -10, 'The '.$shield_name_span.' had no effect&hellip;')
                ));
            $energy_damage_amount = $core_shield_ability->ability_damage;
            $target_robot->trigger_damage($this_robot, $core_shield_ability, $energy_damage_amount);
            $thrown_core_shields++;

        }

        // Regardless of what happens, make sure we remove any shields that we were not able to throw
        foreach ($extracted_core_shields AS $attachment_token => $attachment_info){
            $this_robot->unset_attachment($attachment_token);
        }

        // Return true on success
        return true;

    }
);
?>
