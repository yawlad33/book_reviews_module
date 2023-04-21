<?
namespace Vladislav\Main;
use Vladislav\Data\DataTable;
use Bitrix\Main\ORM\Query;
use Bitrix\Main\Entity;
\Bitrix\Main\Loader::IncludeModule("iblock");

class Reviewsbook extends Entity\DataManager
{
    private $booksId;
    private $reviewsId;

    function __construct()
    {
        global $DB;

        $strSql = "SELECT BOOKSID,REVIEWSID FROM book_reviews ";
        $res = $DB->Query($strSql);
        if ($row = $res->Fetch()) {
            $this->booksId = $row["BOOKSID"];
            $this->reviewsId = $row["REVIEWSID"];
        }
    }

    public static function getList($page = 0, $limit = 100)
    {
        $page = (int)$page;
        $limit = (int)$limit;
        $resList = \Bitrix\Iblock\Elements\ElementReviewsTable::getList([
            "select" => [
                "ID",
                "NAME",
                "BOOK_" => "BOOK",
                "DATE_" => "DATE",
                "TITLE_" => "BOOKS.TITLE",
                "AUTHOR_" => "BOOKS.AUTHOR",
                "YEAR_" => "BOOKS.YEAR",
                "REVIEW_" => "REVIEW",
                "RATING_" => "RATING",

            ],
            "limit" => $limit,
            "offset" => $page,
            "runtime" => [
                new Entity\ReferenceField(
                    "BOOKS",
                    \Bitrix\Iblock\Elements\ElementBooksTable::class,
                    Query\Join::on("this.BOOK_VALUE", "ref.ID")
                )
            ]
        ]);
        $resultArr = [];
        while ($item = $resList->fetch()) {
            $resultArr[] = [
                "date" => explode(" ", $item["DATE_VALUE"])[0],
                "text" => $item["REVIEW_VALUE"],
                "rating" => $item["RATING_VALUE"],
                "book" => [
                    "title" => $item["TITLE_VALUE"],
                    "author" => $item["AUTHOR_VALUE"],
                    "year" => $item["YEAR_VALUE"],
                ],
            ];
        }
        return $resultArr;
    }

}
