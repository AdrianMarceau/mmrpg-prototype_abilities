<?
$functions = array(
  'ability_function' => function($objects){

    // Extract all objects into the current scope
    extract($objects);

    // Define the target and impact frames based on user
    $this_frames = array('target' => 0, 'impact' => 1);
    //if (preg_match('/_alt$/', $this_robot->robot_image)){ $this_frames = array('target' => 1, 'impact' => 1); }
    //elseif (preg_match('/_alt2$/', $this_robot->robot_image)){ $this_frames = array('target' => 2, 'impact' => 2); }

    // Update the ability's target options and trigger
    $this_ability->target_options_update(array(
      'frame' => 'summon',
      'kickback' => array(0, 30, 0),
      'success' => array($this_frames['target'], 0, -40, 10, $this_robot->print_name().' uses '.$this_ability->print_name().'!')
      ));
    $this_robot->trigger_target($target_robot, $this_ability);

    // Inflict damage on the opposing robot
    $this_ability->damage_options_update(array(
      'kind' => 'energy',
      'kickback' => array(20, 0, 0),
      'success' => array($this_frames['impact'], -20, 0, 10, 'The '.$this_ability->print_name().' frosted the target!', 2),
      'failure' => array($this_frames['impact'], -60, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;', 2)
      ));
    $this_ability->recovery_options_update(array(
      'kind' => 'energy',
      'kickback' => array(20, 0, 0),
      'success' => array($this_frames['impact'], -20, 0, 10, 'The '.$this_ability->print_name().' chilled the target!', 2),
      'failure' => array($this_frames['impact'], -60, 0, -10, 'The '.$this_ability->print_name().' missed&hellip;', 2)
      ));
    $energy_damage_amount = $this_ability->ability_damage;
    $target_robot->trigger_damage($this_robot, $this_ability, $energy_damage_amount);

    // Return true on success
    return true;

    }
);
?>
