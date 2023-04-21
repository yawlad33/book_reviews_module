<?
defined("B_PROLOG_INCLUDED") and (B_PROLOG_INCLUDED === true) or die();
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
$aMenu = array(
    array(
        "parent_menu" => "global_menu_services",
        "sort" => 1,
        "text" => "Владислав для вакансии",
        "items_id" => "menu_webforms",
        "icon" => "form_menu_icon",
        "items" => array(
            array(
                "text" => "Книги и отзывы",
                "url" => "settings.php?lang=ru&mid=vladislav",
            ),
        )
    ),
);
return $aMenu;
