<?
$functions = array(
    'ability_function' => function($objects){
        return rpg_ability::ability_function_elemental_buster($objects, 'barrier', 'disrupted', 'bolstered');
    },
    'ability_function_onload' => function($objects){
        return rpg_ability::ability_function_elemental_buster_onload($objects);
    }
);
?>
