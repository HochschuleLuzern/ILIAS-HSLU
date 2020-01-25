<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

// HSLU Patch to allow a multifile upload new class

require_once("./Services/Form/classes/class.ilDragDropFileInputGUI.php");


class ilMediaCastFileInputGUI extends ilDragDropFileInputGUI {
	protected $uniqueId = 0;
	protected $submit_button_name = null;
	protected $cancel_button_name = null;
	
	protected static $uniqueInc = 1;
	
	static protected function getNextUniqueId()
	{
		return self::$uniqueInc++;
	}
	
	function __construct($a_title = "", $a_postvar = "")
	{
		parent::__construct($a_title, $a_postvar);
		$this->uniqueId = self::getNextUniqueId();
	}
	
	function render($a_mode = "")
	{
		global $lng, $tpl, $ilUser;
	
		$quota_exceeded = $quota_legend = false;
		if(self::$check_wsp_quota)
		{
			include_once "Services/DiskQuota/classes/class.ilDiskQuotaHandler.php";
			if(!ilDiskQuotaHandler::isUploadPossible())
			{
				$lng->loadLanguageModule("file");
				return $lng->txt("personal_workspace_quota_exceeded_warning");
			}
			else
			{
				$quota_legend = ilDiskQuotaHandler::getStatusLegend();
			}
		}
	
		// make sure jQuery is loaded
		iljQueryUtil::initjQuery();
	
		// add file upload scripts
		include_once("./Modules/MediaCast/classes/class.ilMediaCastMultiUploadGUI.php");
		ilMediaCastMultiUploadGUI::initFileUpload();
	
		// load template
		$this->tpl = new ilTemplate("tpl.prop_dndfiles.html", true, true, "Services/Form");
	
		// general variables
		$this->tpl->setVariable("UPLOAD_ID", $this->uniqueId);
	
		// input
		$this->tpl->setVariable("FILE_SELECT_ICON", ilObject::_getIcon("", "", "fold"));
		$this->tpl->setVariable("TXT_SHOW_ALL_DETAILS", $lng->txt('show_all_details'));
		$this->tpl->setVariable("TXT_HIDE_ALL_DETAILS", $lng->txt('hide_all_details'));
		$this->tpl->setVariable("TXT_SELECTED_FILES", $lng->txt('selected_files'));
		$this->tpl->setVariable("TXT_DRAG_FILES_HERE", $lng->txt('drag_files_here'));
		$this->tpl->setVariable("TXT_NUM_OF_SELECTED_FILES", $lng->txt('num_of_selected_files'));
		$this->tpl->setVariable("TXT_SELECT_FILES_FROM_COMPUTER", $lng->txt('select_files_from_computer'));
		$this->tpl->setVariable("TXT_OR", $lng->txt('logic_or'));
		$this->tpl->setVariable("INPUT_ACCEPT_SUFFIXES", $this->getInputAcceptSuffixes($this->getSuffixes()));
	
		// info
		$this->tpl->setCurrentBlock("max_size");
		$this->tpl->setVariable("TXT_MAX_SIZE", $lng->txt("file_notice")." ".$this->getMaxFileSizeString());
		$this->tpl->parseCurrentBlock();
	
		if($quota_legend)
		{
			$this->tpl->setVariable("TXT_MAX_SIZE", $quota_legend);
			$this->tpl->parseCurrentBlock();
		}
	
		$this->outputSuffixes($this->tpl);
	
		// create file upload object
		$upload = new ilMediaCastMultiUploadGUI("ilFileUploadDropZone_" . $this->uniqueId, $this->uniqueId, false);
		$upload->enableFormSubmit("ilFileUploadInput_" . $this->uniqueId, $this->submit_button_name, $this->cancel_button_name);
		$upload->setDropAreaId("ilFileUploadDropArea_" . $this->uniqueId);
		$upload->setFileListId("ilFileUploadList_" . $this->uniqueId);
		$upload->setFileSelectButtonId("ilFileUploadFileSelect_" . $this->uniqueId);
	
		$this->tpl->setVariable("FILE_UPLOAD", $upload->getHTML());
	
		return $this->tpl->get();
	}
	
	function checkInput()
	{
		global $lng;
	
		// if no information is received, something went wrong
		// this is e.g. the case, if the post_max_size has been exceeded
		if (!is_array($_FILES[$this->getPostVar()]))
		{
			$this->setAlert($lng->txt("form_msg_file_size_exceeds"));
			return false;
		}
	
		// empty file, could be a folder
		if ($_FILES[$this->getPostVar()]["size"] < 1)
		{
			$this->setAlert($lng->txt("error_upload_was_zero_bytes"));
			return false;
		}
	
		// call base
		$inputValid = ilFileInputGUI::checkInput();
	
		// set additionally sent input on post array
		if ($inputValid)
		{
			$_POST[$this->getPostVar()]["title"] = isset($_POST["title"]) ? $_POST["title"] : "";
			$_POST[$this->getPostVar()]["description"] = isset($_POST["description"]) ? $_POST["description"] : "";
			$_POST[$this->getPostVar()]["visibility"] = isset($_POST["visibility"]) ? $_POST["visibility"] : "";
	
			include_once("./Services/Utilities/classes/class.ilStr.php");
			$_POST[$this->getPostVar()]["name"] = ilStr::normalizeUtf8String($_POST[$this->getPostVar()]["name"]);
			$_POST[$this->getPostVar()]["title"] = ilStr::normalizeUtf8String($_POST[$this->getPostVar()]["title"]);
	
		}
	
		return $inputValid;
	}
	
	function setCommandButtonNames($a_submit_name, $a_cancel_name)
	{
		$this->submit_button_name = $a_submit_name;
		$this->cancel_button_name = $a_cancel_name;
	}
}

// END HSLU Patch to allow a multifile upload new class