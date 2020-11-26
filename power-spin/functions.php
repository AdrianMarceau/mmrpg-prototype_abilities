<?
$functions = array(
  'ability_function' => function($objects){

    // Extract all objects into the current scope
    extract($objects);

    // Define the target and impact frames based on user
    $this_frames = array('target' => 0, 'impact' => 0);
    if (preg_match('/_alt$/', $this_robot->robot_image)){ $this_frames = array('target' => 2, 'impact' => 2); }
    elseif (preg_match('/_alt2$/', $this_robot->robot_image)){ $this_frames = array('target' => 4, 'impact' => 4); }

    // Update the ability's target options and trigger
    $this_ability->target_options_update(array(
      'frame' => 'shoot',
      'success' => array($this_frames['target'], 100, 0, 10, $this_robot->print_name().' activated the '.$this_ability->print_name().'!', 2)
      ));
    $this_robot->trigger_target($target_robot, $this_ability);

    // Inflict damage on the opposing robot
    $this_ability->damage_options_update(array(
      'kind' => 'energy',
      'kickback' => array(10, 0, 0),
      'success' => array($this_frames['impact'], -60, 0, 10, 'The '.$this_ability->print_name().'&#39;s top strikes the target!', 2),
      'failure' => array($this_frames['impact'], -60, 0, -10, 'The '.$this_ability->print_name().'&#39;s top missed&hellip;', 2)
      ));
    $this_ability->recovery_options_update(array(
      'kind' => 'energy',
      'kickback' => array(10, 0, 0),
      'success' => array($this_frames['impact'], -60, 0, 10, 'The '.$this_ability->print_name().'&#39;s top grazes the target!', 2),
      'failure' => array($this_frames['impact'], -60, 0, -10, 'The '.$this_ability->print_name().'&#39;s top missed&hellip;', 2)
      ));
    $energy_damage_amount = $this_ability->ability_damage;
    $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

    // Return true on success
    return true;

    }
);
?>
