<?
$functions = array(
  'ability_function' => function($objects){

    // Extract all objects into the current scope
    extract($objects);

    // Define the target and impact frames based on user
    $this_battle->queue_sound_effect(array('name' => 'slide-sound', 'volume' => 1.0));
    $this_frames = array('target' => 0, 'impact' => 1);
    if (preg_match('/_alt$/', $this_robot->robot_image)){ $this_frames = array('target' => 2, 'impact' => 3); }
    elseif (preg_match('/_alt2$/', $this_robot->robot_image)){ $this_frames = array('target' => 4, 'impact' => 5); }

    // Update the ability's target options and trigger
    $this_ability->target_options_update(array(
      'frame' => 'summon',
      'kickback' => array(50, 0, 0),
      'success' => array($this_frames['target'], 50, 0, 10, $this_robot->print_name().' uses the '.$this_ability->print_name().'!')
      ));
    $this_robot->robot_frame_styles = 'display: none; ';
    $this_robot->update_session();
    $this_robot->trigger_target($target_robot, $this_ability);

    // Inflict damage on the opposing robot
    $this_ability->damage_options_update(array(
      'kind' => 'energy',
      'kickback' => array(30, 0, 0),
      'success' => array($this_frames['impact'], -60, 0, 10, 'The '.$this_ability->print_name().' crashed into the target!'),
      'failure' => array($this_frames['impact'], -120, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
      ));
    $this_ability->recovery_options_update(array(
      'kind' => 'energy',
      'kickback' => array(15, 0, 0),
      'success' => array($this_frames['impact'], -60, 0, 10, 'The '.$this_ability->print_name().' crashed into the target!'),
      'failure' => array($this_frames['impact'], -120, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;')
      ));
    $energy_damage_amount = $this_ability->ability_damage;
    $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);
    $this_robot->robot_frame_styles = '';
    $this_robot->update_session();

    // Return true on success
    return true;

    }
);
?>
