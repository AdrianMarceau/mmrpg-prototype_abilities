<?
$functions = array(
    'ability_function' => function($objects){
        return rpg_ability::ability_function_elemental_shot($objects, 'wind', 'upended', 'intensified');
    },
    'ability_function_onload' => function($objects){
        return rpg_ability::ability_function_elemental_shot_onload($objects);
    }
);
?>
?>
