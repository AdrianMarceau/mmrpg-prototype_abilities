<?
$functions = array(
    'ability_function' => function($objects){
        return rpg_ability::ability_function_elemental_shot($objects, 'ice', 'chilled', 'cooled');
    },
    'ability_function_onload' => function($objects){
        return rpg_ability::ability_function_elemental_shot_onload($objects);
    }
);
?>
