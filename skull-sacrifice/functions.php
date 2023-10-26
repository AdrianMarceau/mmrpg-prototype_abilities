<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's first attachment token
        $fx_attachment_token = 'ability_'.$this_ability->ability_token.'_fx';
        $fx_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability->ability_token,
            'ability_frame' => 1,
            'ability_frame_animate' => array(1,2,3),
            'ability_frame_offset' => array('x' => -20, 'y' => 20, 'z' => -10),
            'attachment_token' => $fx_attachment_token
            );

        // Check to see if the ability is going to be a success (based on remaining energy
        $resources_available = true;
        $resources_missing = array();
        $life_energy_required = ceil($this_robot->robot_base_energy / 2);
        $weapon_energy_required = ceil($this_robot->robot_base_weapons / 2);
        if ($this_robot->robot_energy < $life_energy_required){
            $resources_available = false;
            $resources_missing[] = 'life';
        }
        if ($this_robot->robot_weapons < $weapon_energy_required){
            $resources_available = false;
            $resources_missing[] = 'weapon';
        }

        // Target the ally robot we are reviving
        $this_battle->queue_sound_effect(array('name', 'electric-sound', 'delay' => 0));
        $this_battle->queue_sound_effect(array('name', 'electric-sound', 'delay' => 200));
        $this_ability->target_options_update(array(
            'frame' => 'summon',
            'success' => array(0, 0, 0, -10, $this_robot->print_name().' attempts the '.$this_ability->print_name().' technique!')
            ));
        $this_robot->trigger_target($target_robot, $this_ability);

        // If the user does not have the resources available, show the no-effect text
        if (!$resources_available){

            // Update the ability's target options and trigger
            $this_battle->queue_sound_effect('no-effect');
            $no_effect_text = '...but '.$this_robot->get_pronoun('subject').' wasn\'t strong enough! <br />';
            $no_effect_text .= $this_robot->print_name().' needs more '.implode(' and ', $resources_missing).' energy!';
            $this_ability->target_options_update(array(
                'frame' => 'defend',
                'success' => array(9, 0, 0, 10, $no_effect_text)
                ));
            $this_robot->trigger_target($target_robot, $this_ability, array('prevent_default_text' => true));
            return;

        }

        // Otherwise, remove disabled flags to allow this robot to show on the canvas
        // This will allow us to revive it in the next step without issues
        $target_robot->unset_flag('apply_disabled_state');
        $target_robot->unset_flag('hidden');
        $target_robot->unset_attachment('object_defeat-explosion');
        $target_robot->set_frame('defeat');

        // Restore the target robot's health and weapons back to their full amounts
        $target_robot->set_status('active');
        $target_robot->set_frame('disabled');
        $target_robot->set_energy(0);
        $target_robot->set_weapons(0);
        $target_robot->set_attack($target_robot->robot_base_attack);
        $target_robot->set_defense($target_robot->robot_base_defense);
        $target_robot->set_speed($target_robot->robot_base_speed);
        $target_robot->set_counter('attack_mods', 0);
        $target_robot->set_counter('defense_mods', 0);
        $target_robot->set_counter('speed_mods', 0);
        $target_robot->set_attachment($fx_attachment_token, $fx_attachment_info);

        // Target this robot's self
        $this_robot->set_frame($this_robot->robot_token === 'spark-man' ? 'victory' : 'taunt');
        $this_battle->queue_sound_effect('use-reviving-ability');
        $this_battle->queue_sound_effect(array('name', 'electric-sound', 'delay' => 200));
        $this_battle->queue_sound_effect(array('name', 'electric-sound', 'delay' => 400));
        $this_ability->target_options_update(array(
            'frame' => 'defend',
            'success' => array(0, 0, 0, -10,
                $target_robot->print_name().'\'s battle data is being restored!'
                )
            ));
        $target_robot->trigger_target($target_robot, $this_ability);

        // Increase this robot's life energy stat
        $this_robot->set_frame($this_robot->robot_token === 'spark-man' ? 'taunt' : 'summon');
        $target_robot->set_frame('taunt');
        $this_ability->recovery_options_update(array(
            'kind' => 'energy',
            'percent' => true,
            'modifiers' => false,
            'frame' => 'taunt',
            'success' => array(9, 0, 0, -9999, $target_robot->print_name().'\'s life energy was restored!'),
            'failure' => array(9, 0, 0, -9999, $target_robot->print_name().'\'s life energy was not affected&hellip;')
            ));
        $energy_recovery_amount = $life_energy_required; //ceil($target_robot->robot_base_energy * ($this_ability->ability_recovery / 100));
        if ($energy_recovery_amount > $target_robot->robot_base_energy){ $energy_recovery_amount = $target_robot->robot_base_energy; }
        $this_robot->set_energy(max(1, ($this_robot->robot_energy - $energy_recovery_amount)));
        $target_robot->trigger_recovery($target_robot, $this_ability, $energy_recovery_amount);

        // Increase this robot's weapon energy stat
        $this_robot->set_frame($this_robot->robot_token === 'spark-man' ? 'base2' : 'defense');
        $target_robot->set_frame('taunt');
        $this_ability->recovery_options_update(array(
            'kind' => 'weapons',
            'percent' => true,
            'modifiers' => false,
            'frame' => 'taunt',
            'success' => array(9, 0, 0, -9999, $target_robot->print_name().'\'s weapon energy was restored!'),
            'failure' => array(9, 0, 0, -9999, $target_robot->print_name().'\'s weapon energy was not affected&hellip;')
            ));
        $weapons_recovery_amount = $weapon_energy_required; //ceil($target_robot->robot_base_weapons * ($this_ability->ability_recovery / 100));
        if ($weapons_recovery_amount > $target_robot->robot_base_weapons){ $weapons_recovery_amount = $target_robot->robot_base_weapons; }
        $this_robot->set_weapons($this_robot->robot_weapons - $weapons_recovery_amount);
        $target_robot->trigger_recovery($target_robot, $this_ability, $weapons_recovery_amount);

        // Reset both robot's frames to be sure
        $this_robot->reset_frame();
        $target_robot->reset_frame();
        $target_robot->unset_attachment($fx_attachment_token);

        // Return true on success
        return true;

    }
);
?>
