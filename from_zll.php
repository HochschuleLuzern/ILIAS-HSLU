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

$_REQUEST['baseClass'] = 'ilStartUpGUI';
$_GET['client_id'] = 'hslu';
$_REQUEST['cmd']['doStandardAuthentication'] = 'Anmelden';

ilInitialisation::initIlias();

$ilCtrl->initBaseClass("ilStartUpGUI");
$ilCtrl->setCmd('doStandardAuthentication');
$ilCtrl->setTargetScript("ilias.php");
$ilCtrl->callBaseClass();

?>

