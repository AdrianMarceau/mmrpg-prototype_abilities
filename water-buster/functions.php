<?
$functions = array(
    'ability_function' => function($objects){
        return rpg_ability::ability_function_elemental_buster($objects, 'bubble', 'drenched', 'revitalized');
    },
    'ability_function_onload' => function($objects){
        return rpg_ability::ability_function_elemental_buster_onload($objects);
    }
);
?>
