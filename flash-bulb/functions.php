<?
$functions = array(
  'ability_function' => function($objects){

    // Extract all objects into the current scope
    extract($objects);

    // Define the target and impact frames based on user
    $this_frames = array('target' => 0, 'impact' => 0);
    //if (preg_match('/_alt$/', $this_robot->robot_image)){ $this_frames = array('target' => 4, 'impact' => 5); }
    //elseif (preg_match('/_alt2$/', $this_robot->robot_image)){ $this_frames = array('target' => 0, 'impact' => 1); }

    // Change the image to the full-screen rain effect
    $this_ability->ability_frame_classes = 'sprite_fullscreen ';
    $this_ability->ability_frame_styles = 'opacity: 0.5; filter: alpha(opacity=50); ';
    $this_ability->update_session();

    // Update the ability's target options and trigger
    $this_ability->target_options_update(array(
      'frame' => 'summon',
      'success' => array($this_frames['target'], -5, 0, -10, $this_robot->print_name().' uses the '.$this_ability->print_name().'!')
      ));
    $this_robot->trigger_target($target_robot, $this_ability);

    // Ensure this robot stays in the summon position for the duration of the attack
    $this_robot->robot_frame = 'defend';
    $this_robot->update_session();

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
    $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

    // Change the image to the full-screen rain effect
    $this_ability->ability_frame_classes = '';
    $this_ability->ability_frame_styles = 'display: none; ';
    $this_ability->update_session();

    // Randomly trigger a defense break if the ability was successful
    if ($target_robot->robot_status != 'disabled'
      && $this_ability->ability_results['this_result'] != 'failure'){

      // Call the global stat break function with customized options
      rpg_ability::ability_function_stat_break($target_robot, 'attack', 1, $this_ability, array(
        'initiator_robot' => $this_robot
        ));

    }

    // Change the image to the full-screen rain effect
    $this_ability->ability_frame_classes = '';
    $this_ability->ability_frame_styles = '';
    $this_ability->update_session();

    // Ensure this robot stays goes back to the base frame after the attack
    $this_robot->robot_frame = 'base';
    $this_robot->update_session();

    // Return true on success
    return true;

    }
);
?>
