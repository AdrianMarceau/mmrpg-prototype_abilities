<?
$functions = array(
    'ability_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define the sprite sheet and animation defaults
        $this_field_token = $this_battle->battle_field->field_background;
        $this_sprite_sheet = 1;
        $this_target_frame = 0;
        $this_impact_frame = 1;
        $this_object_name = 'boulder';

        // Collect the sprite index for this ability
        $this_sprite_index = rpg_ability::get_static_index($this_ability, 'super-block', 'sprite-index');

        // If the field token has a place in the index, update values
        if (isset($this_sprite_index[$this_field_token])){
            $this_sprite_sheet = $this_sprite_index[$this_field_token][0];
            $this_target_frame = $this_sprite_index[$this_field_token][1];
            $this_impact_frame = $this_sprite_index[$this_field_token][2];
            $this_object_name = $this_sprite_index[$this_field_token][3];
        }

        // Upper-case object name while being sensitive to of/the/a/etc.
        $this_object_name = ucwords($this_object_name);
        $this_object_name = str_replace(array(' A ', ' An ', ' Of ', ' The '), array(' a ', ' an ', ' of ', ' the '), $this_object_name);
        $this_object_name_span = rpg_type::print_span('impact_shield', $this_object_name);

        // Define this ability's attachment token
        $static_attachment_key = $this_robot->get_static_attachment_key();
        $static_attachment_duration = 10;
        $this_attachment_info = rpg_ability::get_static_attachment($this_ability, 'super-block', $static_attachment_key, $static_attachment_duration);
        $this_attachment_token = $this_attachment_info['attachment_token'];

        // Create the attachment object for this ability
        $this_attachment = rpg_game::get_ability($this_battle, $this_player, $this_robot, $this_attachment_info);
        $this_attachment->set_image($this_attachment_info['ability_image']);

        // Update the image of the actual ability so it matches
        $this_ability->set_image($this_attachment_info['ability_image']);

        // Check if this ability is already summoned to the field
        $is_summoned = isset($this_battle->battle_attachments[$static_attachment_key][$this_attachment_token]) ? true : false;

        // If the user has Quick Charge, auto-summon the ability
        if ($this_robot->has_attribute('quick-charge')){ $is_summoned = true; }

        // If the ability flag was not set, this ability begins charging
        if (!$is_summoned){

            // Attach this ability attachment to the battle field itself
            //$this_attachment_info['ability_frame_styles'] = 'opacity: 0.5; ';
            $this_attachment_info['ability_frame_styles'] = 'transform: scale(0.5) translate(0, 50%); ';
            $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token] = $this_attachment_info;
            $this_battle->update_session();

            // Target this robot's self
            $this_battle->queue_sound_effect('spawn-sound');
            $trigger_options = array();
            $trigger_options['prevent_default_text'] = true;
            $this_ability->target_options_update(array(
                'frame' => 'summon',
                'success' => array($this_target_frame, -9999, -9999, 0, $this_robot->print_name().' uses the '.$this_ability->print_name().' technique! ')
                ));
            $this_robot->trigger_target($target_robot, $this_ability, $trigger_options);

            // Attach this ability attachment to the battle field itself
            $this_attachment_info['ability_frame_styles'] = '';
            $this_battle->battle_attachments[$static_attachment_key][$this_attachment_token] = $this_attachment_info;
            $this_battle->update_session();

            // Target this robot's self
            $this_battle->queue_sound_effect('smack-sound');
            $trigger_options = array();
            $trigger_options['prevent_default_text'] = true;
            $this_ability->target_options_update(array(
                'frame' => 'defend',
                'success' => array($this_target_frame, -9999, -9999, 0, 'The '.$this_ability->print_name().' created '.
                    (preg_match('/^(a|e|i|o|u)/i', $this_object_name) ? 'an ' : 'a ').
                    $this_object_name_span.
                    ' as a shield!<br /> '.
                    'Damage from incoming attacks will be reduced!'
                    )
                ));
            $this_robot->trigger_target($target_robot, $this_ability, $trigger_options);

        }
        // Else if the ability flag was set, the block is thrown and the attachment goes away
        else {

            // Remove this ability attachment from the battle field itself
            unset($this_battle->battle_attachments[$static_attachment_key][$this_attachment_token]);
            $this_battle->update_session();

            // Target the opposing robot
            $this_battle->queue_sound_effect('swing-sound');
            $trigger_options = array();
            $this_ability->target_options_update(array(
                'frame' => 'throw',
                'success' => array($this_impact_frame, 175, 15, 10, $this_ability->print_name().' throws the '.$this_object_name_span.'!')
                ));
            $this_robot->trigger_target($target_robot, $this_ability, $trigger_options);

            // Inflict damage on the opposing robot
            $this_ability->damage_options_update(array(
                'kind' => 'energy',
                'kickback' => array(20, 0, 0),
                'success' => array($this_impact_frame, -125, 5, 10, 'The '.$this_object_name_span.' crashed into the target!'),
                'failure' => array($this_impact_frame, -125, 5, -10, 'The '.$this_object_name_span.' missed the target&hellip;')
                ));
            $this_ability->recovery_options_update(array(
                'kind' => 'energy',
                'frame' => 'taunt',
                'kickback' => array(0, 0, 0),
                'success' => array($this_impact_frame, -125, 5, 10, 'The '.$this_object_name_span.' crashed into the target!'),
                'failure' => array($this_impact_frame, -125, 5, -10, 'The '.$this_object_name_span.' missed the target&hellip;')
                ));
            $energy_damage_amount = $this_ability->ability_damage;
            $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

        }

        // Either way, update this ability's settings to prevent recovery
        $this_ability->damage_options_update($this_attachment_info['attachment_destroy'], true);
        $this_ability->recovery_options_update($this_attachment_info['attachment_destroy'], true);
        $this_ability->update_session();

        // Return true on success
        return true;

    },
    'ability_function_onload' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define this ability's attachment token
        $static_attachment_key = $this_robot->get_static_attachment_key();
        $this_attachment_token = 'ability_'.$this_ability->ability_token.'_'.$static_attachment_key;

        // Check if this ability is already summoned to the field
        $is_summoned = isset($this_battle->battle_attachments[$static_attachment_key][$this_attachment_token]) ? true : false;

        // Check if this ability has a true core-match
        $is_corematch = $this_robot->robot_core == $this_ability->ability_type ? true : false;

        // If the ability flag had already been set, reduce the weapon energy to zero
        if ($is_summoned){ $this_ability->set_energy(0); }
        // Otherwise, return the weapon energy back to default
        else { $this_ability->reset_energy(); }

        // If the user has Quick Charge, auto-charge the ability
        if ($this_robot->has_attribute('quick-charge')){ $is_summoned = true; }

        // If the ability is already summoned and is core-match or Target Module, allow bench targeting
        if ($is_summoned && ($is_corematch || $this_robot->has_attribute('extended-range'))){ $this_ability->set_target('select_target'); }
        else { $this_ability->set_target('auto'); }

        // Define the sprite sheet and animation defaults
        $this_field_token = $this_battle->battle_field->field_background;
        $this_sprite_sheet = 1;

        // Collect the sprite index for this ability
        $this_sprite_index = rpg_ability::get_static_index($this_ability, 'super-block', 'sprite-index');

        // If the field token has a place in the index, update values
        if (isset($this_sprite_index[$this_field_token])){ $this_sprite_sheet = $this_sprite_index[$this_field_token][0]; }

        // Update the ability's image in the session
        $this_ability->set_image($this_ability->ability_token.($this_sprite_sheet > 1 ? '-'.$this_sprite_sheet : ''));

        // Return true on success
        return true;

    },
    'static_attachment_function_super-block' => function($objects, $static_attachment_key, $this_attachment_duration = 99){

        // Extract all objects and config into the current scope
        extract($objects);

        // Collect details for this particular super block based on the current field
        $this_sprite_sheet = 1;
        $this_target_frame = 0;
        $this_impact_frame = 1;
        $this_object_name = 'boulder';
        $this_sprite_index = rpg_ability::get_static_index($this_ability, 'super-block', 'sprite-index');
        $this_field_token1 = $this_battle->battle_field->field_background;
        $this_field_token2 = $this_battle->battle_field->field_foreground;
        if (isset($this_sprite_index[$this_field_token1])){ $this_sheet_token = $this_field_token1; }
        elseif (isset($this_sprite_index[$this_field_token2])){ $this_sheet_token = $this_field_token2; }
        if (isset($this_sheet_token)){
            $this_sprite_sheet = $this_sprite_index[$this_sheet_token][0];
            $this_target_frame = $this_sprite_index[$this_sheet_token][1];
            $this_impact_frame = $this_sprite_index[$this_sheet_token][2];
            $this_object_name = $this_sprite_index[$this_sheet_token][3];
        }
        $this_object_name = ucwords($this_object_name);
        $this_object_name = str_replace(array(' A ', ' An ', ' Of ', ' The '), array(' a ', ' an ', ' of ', ' the '), $this_object_name);
        $this_object_name_span = rpg_type::print_span('impact_shield', $this_object_name);

        // Generate the static attachment info using provided config
        $existing_attachments = isset($this_battle->battle_attachments[$static_attachment_key]) ? count($this_battle->battle_attachments[$static_attachment_key]) : 0;
        $show_field_position = strstr($static_attachment_key, 'active') ? 'active' : 'bench';
        $show_block_behind = $show_field_position === 'active' && strstr($static_attachment_key, 'left') ? true : false;
        $this_ability_token = $this_ability->ability_token;
        $this_attachment_token = 'ability_'.$this_ability_token.'_'.$this_attachment->attachment_token.'_'.$static_attachment_key;
        $this_attachment_image = $this_ability_token.($this_sprite_sheet > 1 ? '-'.$this_sprite_sheet : '');
        $this_attachment_destroy_text = 'The protective '.$this_object_name_span.' in front of {this_robot} faded away... ';
        $this_attachment_info = array(
            'class' => 'ability',
            'sticky' => true,
            'ability_token' => $this_ability_token,
            'ability_image' => $this_attachment_image,
            'attachment_token' => $this_attachment_token,
            'attachment_duration' => $this_attachment_duration,
            'attachment_sticky' => true,
            'attachment_damage_input_breaker' => 0.50,
            'attachment_weaknesses' => array('explode', 'impact'),
            'attachment_weaknesses_trigger' => 'target',
            'attachment_destroy' => array(
                'trigger' => 'special',
                'kind' => '',
                'type' => '',
                'percent' => true,
                'modifiers' => false,
                'frame' => 'defend',
                'rates' => array(100, 0, 0),
                'success' => array(9, -9999, -9999, 10, $this_attachment_destroy_text),
                'failure' => array(9, -9999, -9999, 10, $this_attachment_destroy_text)
                ),
            'ability_frame' => $this_target_frame,
            'ability_frame_animate' => array($this_target_frame),
            'ability_frame_offset' => array(
                'x' => (($show_field_position === 'active' ? 55 : 45) + ($existing_attachments * 8)),
                'y' => ($show_block_behind ? 2 : -2),
                'z' => ($show_block_behind ? -20 : (2 + $existing_attachments))
                )
            );

        // Return true on success
        return $this_attachment_info;

    },
    'static_index_function_super-block_sprite-index' => function($objects){

        // Extract all objects and config into the current scope
        extract($objects);

        // Define the sprite sheets and the stages they contain
        static $this_sprite_index;
        if (empty($this_sprite_index)){

            /*
            // Define the sprite sheet index for the fields for internal reference
            Sheet 1 : field, intro-field, wily-castle/light-laboratory/cossack-citadel, final-destination, prototype-complete
            Sheet 2 : mountain-mines, arctic-jungle, steel-mill, electrical-tower, abandoned-warehouse
            Sheet 3 : oil-wells, clock-citadel, orb-city, pipe-station, atomic-furnace
            Sheet 4 : industrial-facility, underground-laboratory, preserved-forest, photon-collider, waterfall-institute
            Sheet 5 : sky-ridge, mineral-quarry,
            Sheet 6 :
            Sheet 7 :
            Sheet 8 :
             */

            // Redefine the index var then populate

            // Sheet ONE
            $this_sprite_index['field'] = array(1, 0, 1, 'plain block');
            $this_sprite_index['intro-field'] = array(1, 2, 3, 'piece of fence', 'pieces of fence');
                $this_sprite_index['gentle-countryside'] = array(1, 2, 3, 'piece of fence', 'pieces of fence');
            $this_sprite_index['final-destination'] = array(1, 6, 7, 'shiny metal block');
            $this_sprite_index['final-destination-2'] = array(1, 6, 7, 'shiny metal block');
            $this_sprite_index['final-destination-3'] = array(1, 6, 7, 'shiny metal block');
            $this_sprite_index['prototype-complete'] = array(1, 8, 9, 'rocky boulder');

            // Sheet TWO
            $this_sprite_index['mountain-mines'] = array(2, 0, 1, 'heavy boulder');
                $this_sprite_index['maniacal-hideaway'] = array(2, 0, 1, 'heavy boulder');
            $this_sprite_index['arctic-jungle'] = array(2, 2, 3, 'frozen pillar');
                $this_sprite_index['wintry-forefront'] = array(2, 2, 3, 'frozen pillar');
            $this_sprite_index['steel-mill'] = array(2, 4, 5, 'heated pillar');
            $this_sprite_index['electrical-tower'] = array(2, 6, 7, 'summoned pillar');
            $this_sprite_index['abandoned-warehouse'] = array(2, 8, 9, 'concrete block');

            // Sheet THREE
            $this_sprite_index['oil-wells'] = array(3, 0, 1, 'bucket blockade');
            $this_sprite_index['clock-citadel'] = array(3, 2, 3, 'emerald pillar');
            $this_sprite_index['orb-city'] = array(3, 4, 5, 'explosive pillar');
            $this_sprite_index['pipe-station'] = array(3, 6, 7, 'bundle of pipebombs', 'bundles of pipebombs');
            $this_sprite_index['atomic-furnace'] = array(3, 8, 9, 'heated pillar');

            // Sheet FOUR
            $this_sprite_index['industrial-facility'] = array(4, 0, 1, 'titanium block');
            $this_sprite_index['underground-laboratory'] = array(4, 2, 3, 'smooth platform');
            $this_sprite_index['preserved-forest'] = array(4, 4, 5, 'wooden platform');
            $this_sprite_index['photon-collider'] = array(4, 6, 7, 'crystal pillar');
            $this_sprite_index['waterfall-institute'] = array(4, 8, 9, 'moss-covered platform');

            // Sheet FIVE
            $this_sprite_index['sky-ridge'] = array(5, 0, 1, 'windy pillar');
            $this_sprite_index['mineral-quarry'] = array(5, 2, 3, 'mineral pillar');
            $this_sprite_index['lighting-control'] = array(5, 4, 5, 'summoned platform');
            $this_sprite_index['robosaur-boneyard'] = array(5, 6, 7, 'boney pillar');
            $this_sprite_index['space-simulator'] = array(5, 8, 9, 'crystal blockade');

            // Sheet SIX
            $this_sprite_index['submerged-armory'] = array(6, 0, 1, 'iron blockade');
            $this_sprite_index['egyptian-excavation'] = array(6, 2, 3, 'ancient stone');
            $this_sprite_index['rusty-scrapheap'] = array(6, 4, 5, 'rusty scrapheap');
            $this_sprite_index['rainy-sewers'] = array(6, 6, 7, 'slippery pillar');
            $this_sprite_index['construction-site'] = array(6, 8, 9, 'block platform');

            // Sheet SEVEN
            $this_sprite_index['magnetic-generator'] = array(7, 0, 1, 'large battery', 'large batteries');
            $this_sprite_index['power-plant'] = array(7, 2, 3, 'summoned platform');
            $this_sprite_index['reflection-chamber'] = array(7, 4, 5, 'pulsing platform');
            $this_sprite_index['rocky-plateau'] = array(7, 6, 7, 'large beam');
            $this_sprite_index['septic-system'] = array(7, 8, 9, 'purifying unit');

            // Sheet EIGHT
            $this_sprite_index['serpent-column'] = array(8, 0, 1, 'serpentine column');
            $this_sprite_index['spinning-greenhouse'] = array(8, 2, 3, 'compact greenhouse');
            $this_sprite_index['wily-castle'] = array(8, 4, 5, 'heavy metal block');
            $this_sprite_index['light-laboratory'] = array(8, 6, 7, 'heavy metal block');
            $this_sprite_index['cossack-citadel'] = array(8, 8, 9, 'heavy metal block');

            // Sheet NINE
            $this_sprite_index['prototype-subspace'] = array(9, 0, 1, 'glitched platform');

        }

        // Return the populated sprite index for the ability
        return $this_sprite_index;

    }
);
?>
