<?
$functions = array(
    'ability_function' => function($objects){
        extract($objects);
        $this_battle->queue_sound_effect('cosmic-sound');
        return rpg_ability::ability_function_elemental_shot($objects, 'cosmic', 'doomed', 'blessed');
    },
    'ability_function_onload' => function($objects){
        return rpg_ability::ability_function_elemental_shot_onload($objects);
    }
);
?>
