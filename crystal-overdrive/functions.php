<?
$functions = array(
    'ability_function' => function($objects){
        return rpg_ability::ability_function_elemental_overdrive($objects, 'diamond', 'overwhelmed', 'beautified');
    },
    'ability_function_onload' => function($objects){
        return rpg_ability::ability_function_elemental_overdrive_onload($objects);
    }
);
?>
