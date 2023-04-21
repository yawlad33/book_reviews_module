<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;


Loc::loadMessages(__FILE__);

class Vladislav extends CModule
{
    public $MODULE_ID;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;
    public $errors;
    private $IBLOCK_TYPE_CODE = "book_reviews";

    function __construct()
    {
        $arModuleVersion = array();
        include_once(__DIR__ . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_ID = "vladislav";
        $this->MODULE_NAME = "Книги и отзывы";
        $this->MODULE_DESCRIPTION = "Результат выполнения задания по итогам собеседования";
        $this->PARTNER_NAME = "Vladislav github";
        $this->PARTNER_URI = "https://vladislav.ru";
    }

    function DoInstall()
    {
        global $DB;
        global $APPLICATION;
        $this->InstallEvents();
        $this->AddIblockType();

        $this->iBlockIdBooks = $this->AddIblock("Books", $this->IBLOCK_TYPE_CODE, "Книги");
        $this->AddProp($this->iBlockIdBooks, "Название", "TITLE");
        $this->AddProp($this->iBlockIdBooks, "Автор", "AUTHOR");
        $this->AddProp($this->iBlockIdBooks, "Год", "YEAR");
        $this->AddProp($this->iBlockIdBooks, "Средняя оценка", "RATING");

        $this->iBlockIdReviews = $this->AddIblock("Reviews", $this->IBLOCK_TYPE_CODE, "Отзывы на книги");
        $this->AddProp($this->iBlockIdReviews, "Дата", "DATE", "date");
        $this->AddProp($this->iBlockIdReviews, "Текст", "REVIEW");
        $this->AddProp($this->iBlockIdReviews, "Оценка", "RATING");
        $this->AddProp($this->iBlockIdReviews, "Книга", "BOOK", "element", $this->iBlockIdBooks);

        $this->InstallDB();

        $elementForReviewId = $this->addBooksExample();
        $this->addReviewsExample($elementForReviewId);
        ModuleManager::RegisterModule("vladislav");
        $APPLICATION->includeAdminFile(
            Loc::getMessage("INSTALL_TITLE"),
            __DIR__ . "/instalInfo.php"
        );

        return true;
    }

    function DoUninstall()
    {
        global $APPLICATION;
        $this->DelIblocks();
        $this->DelIblocks();

        $this->UnInstallDB();
        $this->UnInstallEvents();
        ModuleManager::UnRegisterModule("vladislav");
        $APPLICATION->includeAdminFile(
            Loc::getMessage("DEINSTALL_TITLE"),
            __DIR__ . "/deInstalInfo.php"
        );

        return true;
    }

    function InstallDB()
    {
        global $DB;
        $this->errors = false;
        $this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"] . "/local/modules/vladislav/install/db/install.sql");
        if (!$this->errors) {
            $sql =
                "insert  into `book_reviews` (`ID`, `BOOKSID`, `REVIEWSID`) values (1," . $this->iBlockIdBooks . "," .
                $this->iBlockIdReviews . ")";
            $DB->Query($sql);
            return true;
        } else
            return $this->errors;
    }

    function UnInstallDB()
    {
        global $DB;
        $this->errors = false;
        $this->errors =
            $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"] . "/local/modules/vladislav/install/db/uninstall.sql");
        if (!$this->errors) {
            return true;
        } else
            return $this->errors;
    }

    function InstallEvents()
    {
        RegisterModuleDependences("iblock", "OnAfterIBlockElementAdd", "vladislav", "\\Vladislav\\Main\\Main",
            "changeRating");
        RegisterModuleDependences("iblock", "OnAfterIBlockElementUpdate", "vladislav", "\\Vladislav\\Main\\Main",
            "changeRating");
        RegisterModuleDependences("iblock", "OnBeforeIBlockElementDelete", "vladislav", "\\Vladislav\\Main\\Main",
            "changeRating");
        return true;
    }

    function UnInstallEvents()
    {
        UnRegisterModuleDependences("iblock", "OnAfterIBlockElementAdd", "vladislav", "\\Vladislav\\Main\\Main",
            "changeRating");
        UnRegisterModuleDependences("iblock", "OnAfterIBlockElementUpdate", "vladislav", "\\Vladislav\\Main\\Main",
            "changeRating");
        UnRegisterModuleDependences("iblock", "OnBeforeIBlockElementDelete", "vladislav", "\\Vladislav\\Main\\Main",
            "changeRating");
        return true;
    }

    public function AddIblockType()
    {
        global $DB;
        CModule::IncludeModule("iblock");

        $iblockTypeCode = "book_reviews";

        $db_iblock_type = CIBlockType::GetList(
            array("SORT" => "ASC"),
            array("ID" => $iblockTypeCode)
        );

        if (!$ar_iblock_type = $db_iblock_type->Fetch()) {
            $obBlocktype = new CIBlockType;
            $DB->StartTransaction();

            $arIBType = array(
                "ID" => $iblockTypeCode,
                "SECTIONS" => "Y",
                "IN_RSS" => "N",
                "SORT" => 500,
                "LANG" => array(
                    "ru" => array(
                        "NAME" => GetMessage("IB_TYPE_NAME"),
                    )
                )
            );

            $resIBT = $obBlocktype->Add($arIBType);
            if (!$resIBT) {
                $DB->Rollback();
                echo "Error: " . $obBlocktype->LAST_ERROR;
                die();
            } else {
                $DB->Commit();
            }
        } else {
            return false;
        }

        return $resIBT;
    }

    public function AddIblock($code, $type, $name)
    {
        CModule::IncludeModule("iblock");

        $iblockCode = $code;
        $iblockType = $type;

        $ib = new CIBlock;

        $resIBE = CIBlock::GetList(
            array(),
            array(
                "TYPE" => $iblockType,
                "CODE" => $iblockCode
            )
        );
        if ($ar_resIBE = $resIBE->Fetch()) {
            return false;
        } else {

            $arFieldsIB = array(
                "ACTIVE" => "Y",
                "NAME" => $name,
                "CODE" => $iblockCode,
                "API_CODE" => $iblockCode,
                "IBLOCK_TYPE_ID" => $iblockType,
                "SITE_ID" => "s1",
                "GROUP_ID" => array("2" => "R"),
                "FIELDS" => array(
                    "CODE" => array(
                        "IS_REQUIRED" => "Y",
                        "DEFAULT_VALUE" => array(
                            "TRANS_CASE" => "L",
                            "UNIQUE" => "Y",
                            "TRANSLITERATION" => "Y",
                            "TRANS_SPACE" => "-",
                            "TRANS_OTHER" => "-"
                        )
                    )
                )
            );
            return $ib->Add($arFieldsIB);
        }
    }

    public function AddProp($IBLOCK_ID, $name, $code, $type = "string", $iblockElement = 0)
    {
        CModule::IncludeModule("iblock");
        switch ($type) {
            case "date":
                $arFieldsProp = array(
                    "NAME" => $name,
                    "ACTIVE" => "Y",
                    "SORT" => "100",
                    "CODE" => $code,
                    "PROPERTY_TYPE" => "S",
                    "USER_TYPE" => "DateTime",
                    "IBLOCK_ID" => $IBLOCK_ID
                );
                break;
            case "element":
                $arFieldsProp = array(
                    "NAME" => $name,
                    "ACTIVE" => "Y",
                    "SORT" => "100",
                    "CODE" => $code,
                    "PROPERTY_TYPE" => "E",
                    "LINK_IBLOCK_ID" => $iblockElement,
                    "IBLOCK_ID" => $IBLOCK_ID
                );
                break;
            case "string":

                $arFieldsProp = array(
                    "NAME" => $name,
                    "ACTIVE" => "Y",
                    "SORT" => "100",
                    "CODE" => $code,
                    "PROPERTY_TYPE" => "S",
                    "IBLOCK_ID" => $IBLOCK_ID
                );
                break;
        }

        $ibp = new CIBlockProperty;
        $propID = $ibp->Add($arFieldsProp);

        return $propID;
    }

    public function DelIblocks()
    {
        global $DB;
        CModule::IncludeModule("iblock");

        $DB->StartTransaction();
        if (!CIBlockType::Delete($this->IBLOCK_TYPE_CODE)) {
            $DB->Rollback();

            CAdminMessage::ShowMessage(array(
                "TYPE" => "ERROR",
                "MESSAGE" => GetMessage("VTEST_IBLOCK_TYPE_DELETE_ERROR"),
                "DETAILS" => "",
                "HTML" => true
            ));
        }
        $DB->Commit();
    }

    public function addBooksExample()
    {
        CModule::IncludeModule("iblock");
        $oElement = new CIBlockElement();

        $arFields = array(
            "ACTIVE" => "Y",
            "IBLOCK_ID" => $this->iBlockIdBooks,
            "NAME" => "Евгений Онегин",
            "CODE" => "onegin",
            "PROPERTY_VALUES" => array(
                "TITLE" => "Евгений Онегин",
                "AUTHOR" => "Пушкин",
                "YEAR" => 1833,
                "RATING" => 4.8,

            )
        );
        $idElement = $oElement->Add($arFields, false, false, true);

        $arFields = array(
            "ACTIVE" => "Y",
            "IBLOCK_ID" => $this->iBlockIdBooks,
            "NAME" => "Робинзон Крузо",
            "CODE" => "robinzon",
            "PROPERTY_VALUES" => array(
                "TITLE" => "Робинзон Крузо",
                "AUTHOR" => "Дефо",
                "YEAR" => 1719,
                "RATING" => 3.8

            )
        );
        $idElement = $oElement->Add($arFields, false, false, true);

        $arFields = array(
            "ACTIVE" => "Y",
            "IBLOCK_ID" => $this->iBlockIdBooks,
            "NAME" => "Великий Гэтсби",
            "CODE" => "gatsby",
            "PROPERTY_VALUES" => array(
                "TITLE" => "Великий Гэтсби",
                "AUTHOR" => "Фицджеральд",
                "YEAR" => 1925,
                "RATING" => 4.7,

            )
        );
        $idElement = $oElement->Add($arFields, false, false, true);

        return $idElement;
    }

    public function addReviewsExample($idElementReview)
    {
        CModule::IncludeModule("iblock");

        $oElement = new CIBlockElement();
        $arFields = array(
            "ACTIVE" => "Y",
            "IBLOCK_ID" => $this->iBlockIdReviews,
            "NAME" => "Великий Гэтсби отзыв",
            "CODE" => "gatsby1",
            "PROPERTY_VALUES" => array(
                "DATE" => date("d.m.Y"),
                "REVIEW" => "Замечательная книга! Открыл для себя много нового.",
                "RATING" => 4,
                "BOOK" => $idElementReview,

            )
        );
        $idElement = $oElement->Add($arFields, false, false, true);

        $arFields = array(
            "ACTIVE" => "Y",
            "IBLOCK_ID" => $this->iBlockIdReviews,
            "NAME" => "Великий Гэтсби отзыв",
            "CODE" => "gatsby2",
            "PROPERTY_VALUES" => array(
                "DATE" => date("d.m.Y"),
                "REVIEW" => "Впервые сталкиваюсь с такой подачей произвеедния, фантастика!",
                "RATING" => 5,
                "BOOK" => $idElementReview,

            )
        );
        $idElement = $oElement->Add($arFields, false, false, true);

        $arFields = array(
            "ACTIVE" => "Y",
            "IBLOCK_ID" => $this->iBlockIdReviews,
            "NAME" => "Великий Гэтсби отзыв",
            "CODE" => "gatsby3",
            "PROPERTY_VALUES" => array(
                "DATE" => date("d.m.Y"),
                "REVIEW" => "Великий Гэтсби это настоящее открытие своего времени.",
                "RATING" => 5,
                "BOOK" => $idElementReview,

            )
        );
        $idElement = $oElement->Add($arFields, false, false, true);
    }
}
