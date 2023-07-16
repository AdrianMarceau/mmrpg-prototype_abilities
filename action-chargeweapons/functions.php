<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define the base attachment duration
        $base_attachment_duration = 1;
        $base_attachment_multiplier = 1.25;

        // Define this ability's attachment token
        $this_battle->queue_sound_effect(array('name' => 'charge-sound', 'volume' => 0.5));
        $this_battle->queue_sound_effect(array('name' => 'charge-sound', 'volume' => 1.0, 'delay' => 200));
        $this_attachment_token = 'ability_'.$this_ability->ability_token;
        $this_attachment_info = array(
            'class' => 'ability',
            'ability_id' => $this_ability->ability_id,
            'ability_token' => $this_ability->ability_token,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $base_attachment_duration,
            'attachment_damage_input_booster' => $base_attachment_multiplier
            );

        // Create the attachment object for this ability
        $this_attachment = rpg_game::get_ability($this_battle, $this_player, $this_robot, $this_attachment_info);
        $this_robot->set_attachment($this_attachment_token, $this_attachment_info);

        // Target this robot's self and show the ability triggering
        $temp_weapons_current = $this_robot->robot_weapons;
        $temp_weapons_lost = $this_robot->robot_base_weapons - $this_robot->robot_weapons;
        $temp_weapons_recovery = ceil($this_robot->robot_base_weapons * 0.25);
        if ($temp_weapons_recovery > $temp_weapons_lost){ $temp_weapons_recovery = $temp_weapons_lost; }
        $temp_weapons_new = $temp_weapons_current + $temp_weapons_recovery;

        // Trigger the charging message and increase WE if applicable
        if ($temp_weapons_recovery > 0){ $this_robot->set_weapons($temp_weapons_new); }
        $pronoun = 'itself';
        if ($this_robot->robot_gender == 'male'){ $pronoun = 'himself'; }
        elseif ($this_robot->robot_gender == 'female'){ $pronoun = 'herself'; }
        $this_ability->target_options_update(array(
            'frame' => 'defend',
            'success' => array(9, 0, 0, -10,
                $this_robot->print_name().' regenerated depleted weapon energy!<br /> '.
                $this_robot->print_name().' left '.$pronoun.' open to attack, however... '
                )
            ));
        $this_robot->trigger_target($this_robot, $this_ability, array('prevent_default_text' => true));

        // Return true on success
        return true;

        }
);
?>
