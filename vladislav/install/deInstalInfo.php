<?
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
if ($errorException = $APPLICATION->getException()) {
    CAdminMessage::showMessage(
        Loc::getMessage("DEINSTALL_FAILED") . ": " . $errorException->GetString()
    );
} else {
    CAdminMessage::showNote(
        Loc::getMessage("DEINSTALL_SUCCESS")
    );
}
?>
<form action="<?= $APPLICATION->getCurPage(); ?>">
    <input type="submit" value="<?= Loc::getMessage("RETURN_MODULES"); ?>">
</form>