<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

// получаем id модуля
$request = HttpApplication::getInstance()->getContext()->getRequest();
$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);

// подключение модуля
Loader::includeModule($module_id);

$aTabs = array(
    array(
        "DIV" => "edit",
        "TAB" => "Результат",
        "TITLE" => "Выводить результирующий массив",
        "OPTIONS" => array(
            "Выводить результирующий массив",
            array(
                "result_checkbox",
                "Вывод",
                "Y",
                array("checkbox"),
            ),
            "Параметр limit",
            array(
                "result_limit",
                "Limit",
                "100",
                array("text",5),
            ),
            "Параметр page",
            array(
                "result_page",
                "Page",
                "0",
                array("text",5),
            ),
        )
    )
);

if ($request->isPost() && check_bitrix_sessid()) {
    foreach ($aTabs as $aTab) {
        foreach ($aTab["OPTIONS"] as $arOption) {
            if (!is_array($arOption)) {
                continue;
            }
            if ($request["apply"]) {
                $optionValue = $request->getPost($arOption[0]);
                if ($arOption[0] == "result_checkbox") {
                    if ($optionValue == "") {
                        $optionValue = "N";
                    }
                }
                Option::set($module_id, $arOption[0], is_array($optionValue) ? implode(",", $optionValue) : $optionValue);
            }
            if ($request["default"]) {
                Option::set($module_id, $arOption[0], $arOption[2]);
            }
        }
    }
    LocalRedirect($APPLICATION->GetCurPage() . "?mid=" . $module_id . "&lang=" . LANG);
}

$tabControl = new CAdminTabControl(
    "tabControl",
    $aTabs
);

$tabControl->Begin();

$objReviews = new \Vladislav\Main\Reviewsbook();
$limit = COption::GetOptionString("vladislav", "result_limit", "100");
$page = COption::GetOptionString("vladislav", "result_page", "0");

$resultReviews = $objReviews->GetList($page,$limit);
?>

<form action="<? echo ($APPLICATION->GetCurPage()); ?>?mid=<? echo ($module_id); ?>&lang=<? echo (LANG); ?>" method="post">
    <?
    $showResult = COption::GetOptionString("vladislav", "result_checkbox", "Y");
    foreach ($aTabs as $aTab) {
        if ($aTab["OPTIONS"]) {
            $tabControl->BeginNextTab();
            __AdmSettingsDrawList($module_id, $aTab["OPTIONS"]);
        }
    }
    $tabControl->Buttons();
    if($showResult == "Y") {
        echo "<pre>";
        print_r($resultReviews);
        echo "</pre>";
    }
    echo (bitrix_sessid_post());
    ?>

    <input class="adm-btn-save" type="submit" name="apply" value="Применить" />
    <input type="submit" name="default" value="По умолчанию" />
</form>
<?
$tabControl->End();
