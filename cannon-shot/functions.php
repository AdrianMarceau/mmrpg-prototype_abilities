<?
$functions = array(
  'ability_function' => function($objects){

    // Extract all objects into the current scope
    extract($objects);

    // Define the target and impact frames based on user
    $this_frames = array('target' => 0, 'impact' => 0);
    if (preg_match('/_alt$/', $this_robot->robot_image)){ $this_frames = array('target' => 1, 'impact' => 1); }
    elseif (preg_match('/_alt2$/', $this_robot->robot_image)){ $this_frames = array('target' => 2, 'impact' => 2); }

    // Update the ability's target options and trigger
    $this_battle->queue_sound_effect('cannon-sound');
    $this_ability->target_options_update(array(
      'frame' => 'shoot',
      'success' => array($this_frames['target'], 160, 40, 10, $this_robot->print_name().' fires a '.$this_ability->print_name().'!')
      ));
    $this_robot->trigger_target($target_robot, $this_ability);

    // Inflict damage on the opposing robot
    $this_ability->damage_options_update(array(
      'kind' => 'energy',
      'kickback' => array(10, 0, 0),
      'success' => array($this_frames['impact'], -10, -20, 10, 'The '.$this_ability->print_name().' hit the target!'),
      'failure' => array($this_frames['impact'], -10, -20, -10, 'The '.$this_ability->print_name().' missed&hellip;')
      ));
    $this_ability->recovery_options_update(array(
      'kind' => 'energy',
      'kickback' => array(10, 0, 0),
      'success' => array($this_frames['impact'], -10, -20, 10, 'The '.$this_ability->print_name().' was absorbed by the target!'),
      'failure' => array($this_frames['impact'], -10, -20, -10, 'The '.$this_ability->print_name().' missed&hellip;')
      ));
    $energy_damage_amount = $this_ability->ability_damage;
    $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

    // Return true on success
    return true;

    }
);
?>
