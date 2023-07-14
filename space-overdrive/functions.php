<?
$functions = array(
    'ability_function' => function($objects){
        extract($objects);
        $this_battle->queue_sound_effect('cosmic-sound');
        $this_battle->queue_sound_effect(array('name' => 'cosmic-sound', 'delay' => 100));
        $this_battle->queue_sound_effect(array('name' => 'cosmic-sound', 'delay' => 200));
        return rpg_ability::ability_function_elemental_overdrive($objects, 'cosmic', 'discorded', 'harmonized');
    },
    'ability_function_onload' => function($objects){
        return rpg_ability::ability_function_elemental_overdrive_onload($objects);
    }
);
?>
