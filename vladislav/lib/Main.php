<?
namespace Vladislav\Main;
use CIBlockElement;
\Bitrix\Main\Loader::IncludeModule("iblock");

class Main
{
    public static function changeRating($arFields)
    {
        $bookId = 0;
        $idElement = !empty($arFields["PROPERTY_VALUES"]) ? $arFields["ID"] : $arFields;

        $res = \Bitrix\Iblock\Elements\ElementReviewsTable::getList([
            "filter" => [
                "ID" => $idElement
            ],
            "select" => [
                "BOOK_" => "BOOK",
            ],

        ]);
        if ($val = $res->fetch())
            $bookId = (int)($val["BOOK_VALUE"]);

        $res = \Bitrix\Iblock\Elements\ElementReviewsTable::getList([
            "filter" => [
                "BOOK.value" => $bookId
            ],
            "select" => [
                "RATING_" => "RATING"
            ],

        ]);
        $middleRating = 0;
        $countRating = 0;

        while ($item = $res->fetch()) {
            if ($idElement == $item["RATING_IBLOCK_ELEMENT_ID"] && empty($arFields["PROPERTY_VALUES"]))
                continue;
            $middleRating += $item["RATING_VALUE"];
            $countRating++;
        }
        $middleRating = $middleRating / $countRating;
        $middleRating = round($middleRating, 2);

        CIblockElement::SetPropertyValuesEx($bookId, false, ["RATING" => $middleRating]);
    }
}
