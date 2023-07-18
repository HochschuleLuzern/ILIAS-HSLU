<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* logout script for ilias
*
* @author Raphael Heer <raphael.heer@hslu.ch>
* @version $Id$
*
* This script allows login posts from other pages
*/
require_once("Services/Init/classes/class.ilInitialisation.php");

ilInitialisation::initILIAS();
/** @var $session ilAuthSession */
if(isset($DIC['ilAuthSession'])) {
    $session = $DIC['ilAuthSession'];
    $session->logout();
}

ilInitialisation::reinitILIAS();

$ilCtrl->initBaseClass("ilStartUpGUI");
$ilCtrl->setCmd('doStandardAuthentication');
$ilCtrl->setTargetScript("ilias.php");
$ilCtrl->callBaseClass();

?>

