<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Collect the target robot and correct for bugs
        if ($target_robot->player->player_id == $this_robot->player->player_id){ $temp_ally_robot = $target_robot; }
        else { $temp_ally_robot = $this_robot; }

        // Update the ability's target options and trigger
        $target_options = array();
        $target_options['prevent_default_text'] = true;
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 0, 0, 10,
                $this_robot->print_name().' '.(
                    $temp_ally_robot->robot_id == $this_robot->robot_id
                    ? 'targets '.$this_robot->get_pronoun('reflexive').'...'
                    : 'targets '.$temp_ally_robot->print_name().'!'
                    ).' <br /> '.
                $this_robot->print_name().' uses the '.$this_ability->print_name().'!'
                )
            ));
        $this_battle->queue_sound_effect('beeping-sound');
        $this_robot->trigger_target($temp_ally_robot, $this_ability, $target_options);

        // Define the default ability info vars
        $relay_buster_token = '';
        $relay_buster_info = array();
        $relay_buster_object = false;

        // Automatically fail if the user has targetted itself
        if ($temp_ally_robot->robot_id == $this_robot->robot_id){

            // Print out a failure message as the robot can't target itself
            $target_options = array();
            $target_options['prevent_default_text'] = true;
            $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(9, 0, 0, -10, 'But this robot cannot target itself!<br />' )));
            $this_battle->queue_sound_effect('no-effect');
            $this_robot->trigger_target($this_robot, $this_ability, $target_options);

        }
        // Automatically fail if there was no buster charge to transfer
        elseif (empty($this_robot->robot_attachments)
            && empty($this_robot->counters['attack_mods'])
            && empty($this_robot->counters['defense_mods'])
            && empty($this_robot->counters['speed_mods'])){

                // Print out a failure message if nothing could be transferred
                $target_options = array();
                $target_options['prevent_default_text'] = true;
                $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(9, 0, 0, -10, 'But there was nothing to transfer&hellip;')));
                $this_battle->queue_sound_effect('no-effect');
                $this_robot->trigger_target($this_robot, $this_ability, $target_options);

        }
        // Otherwise, we have at least something to transfer
        else {

            // Define a list to keep all things transferred
            $transferred_things = array();

            // Otherwise if not empty, loop through this robot's attachments, looking for a buster or shield
            if (!empty($this_robot->robot_attachments)){
                //$this_battle->events_create(false, false, 'debug', '$this_robot->robot_attachments = '.print_r($this_robot->robot_attachments, true));
                foreach ($this_robot->robot_attachments AS $attachment_token => $attachment_info){

                    // If this is a buster charge of any kind, move it to the target robot
                    if (preg_match('/^ability_([a-z]+)-buster$/i', $attachment_token)){

                        // Collect the information for this buster ability
                        $relay_buster_token = $attachment_token;
                        $relay_buster_info = $attachment_info;

                        // Remove this attachment from the source robot
                        unset($this_robot->robot_attachments[$relay_buster_token]);
                        $this_robot->update_session();

                        // Append this attachment to the new target robot
                        $temp_ally_robot->set_attachment($relay_buster_token, $relay_buster_info);

                        // Add to transferred things if not already there
                        if (!in_array('buster charges', $transferred_things)){ $transferred_things[] = 'buster charges'; }

                    }
                    // If this is a core shield of any kind, move it to the target robot
                    elseif (preg_match('/^ability_core-shield_([a-z]+)$/i', $attachment_token)){

                        // Collect the information for this shield ability
                        $relay_shield_token = $attachment_token;
                        $relay_shield_info = $attachment_info;

                        // Remove this attachment from the source robot
                        unset($this_robot->robot_attachments[$relay_shield_token]);
                        $this_robot->update_session();

                        // Append this attachment to the new target robot
                        $temp_ally_robot->set_attachment($relay_shield_token, $relay_shield_info);

                        // Add to transferred things if not already there
                        if (!in_array('core shields', $transferred_things)){ $transferred_things[] = 'core shields'; }

                    }

                }
            }

            // Loop through stats and pass any positive changes onto the target as well
            $stat_kinds = array('attack', 'defense', 'speed');
            foreach ($stat_kinds AS $stat_kind){
                if (!empty($this_robot->counters[$stat_kind.'_mods'])){

                    // Apply the stat buffs to the target robot first
                    $temp_ally_robot->counters[$stat_kind.'_mods'] += $this_robot->counters[$stat_kind.'_mods'];
                    if ($temp_ally_robot->counters[$stat_kind.'_mods'] > MMRPG_SETTINGS_STATS_MOD_MAX){ $temp_ally_robot->counters[$stat_kind.'_mods'] = MMRPG_SETTINGS_STATS_MOD_MAX; }
                    elseif ($temp_ally_robot->counters[$stat_kind.'_mods'] < MMRPG_SETTINGS_STATS_MOD_MIN){ $temp_ally_robot->counters[$stat_kind.'_mods'] = MMRPG_SETTINGS_STATS_MOD_MIN; }
                    $temp_ally_robot->update_session();

                    // Remove the stat buffs from the user next
                    $this_robot->counters[$stat_kind.'_mods'] = 0;
                    $this_robot->update_session();

                    // Add to transferred things if not already there
                    if (!in_array('stat changes', $transferred_things)){ $transferred_things[] = 'stat changes'; }

                }
            }

            // Check to make sure there were actually things transferred in the process
            if (!empty($transferred_things)){

                // Print out a failure message if nothing could be transferred
                $target_options = array();
                $target_options['prevent_default_text'] = true;
                $this_find = array('{target_player}', '{target_robot}', '{this_player}', '{this_robot}');
                $this_replace = array($target_player->player_name, $target_robot->robot_name, $this_player->player_name, $temp_ally_robot->robot_name);
                $temp_ally_robot->set_frame('taunt');
                $this_ability->target_options_update(array('frame' => 'summon', 'success' => array(9, 0, 0, -10,
                    $this_robot->print_name().' transferred '.preg_replace('/, ([a-z]+)$/', ' and $1', implode(', ', $transferred_things)).' to '.$temp_ally_robot->print_name().'! '.
                    (!empty($temp_ally_robot->robot_quotes['battle_taunt']) ? '<br /> '.$temp_ally_robot->print_quote('battle_taunt', $this_find, $this_replace) : '')
                    )));
                $this_battle->queue_sound_effect('growing-sound');
                $temp_ally_robot->trigger_target($temp_ally_robot, $this_ability, $target_options);
                $temp_ally_robot->set_frame('base');

            } else {

                // Print out a failure message if nothing could be transferred
                $target_options = array();
                $target_options['prevent_default_text'] = true;
                $this_ability->target_options_update(array('frame' => 'defend', 'success' => array(9, 0, 0, -10, 'But there was nothing to transfer&hellip;')));
                $this_battle->queue_sound_effect('no-effect');
                $this_robot->trigger_target($this_robot, $this_ability, $target_options);

            }

        }

        // Return true on success
        return true;

        }
);
?>
