<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

// HSLU Patch to allow a multifile upload new class

require_once("./Services/FileUpload/classes/class.ilFileUploadGUI.php");


class ilMediaCastMultiUploadGUI extends ilFileUploadGUI {
	protected static $shared_code_loaded = false;
	
	protected function getSharedHtml()
	{
		global $lng;
		
		// already loaded?
		if (self::$shared_code_loaded)
			return "";

		// make sure required scripts are loaded
		parent::initFileUpload();
		
		// load script template
		$tpl_shared = new ilTemplate("tpl.fileupload_shared.html", true, true, "Services/FileUpload");
		
		// initialize localized texts
		$lng->loadLanguageModule("form");
		$tpl_shared->setCurrentBlock("fileupload_texts");
		$tpl_shared->setVariable("ERROR_MSG_FILE_TOO_LARGE", $lng->txt("form_msg_file_size_exceeds"));
		$tpl_shared->setVariable("ERROR_MSG_WRONG_FILE_TYPE", $lng->txt("form_msg_file_wrong_file_type"));
		$tpl_shared->setVariable("ERROR_MSG_EMPTY_FILE_OR_FOLDER", $lng->txt("error_empty_file_or_folder"));
		$tpl_shared->setVariable("ERROR_MSG_UPLOAD_ZERO_BYTES", $lng->txt("error_upload_was_zero_bytes"));
		$tpl_shared->setVariable("QUESTION_CANCEL_ALL", $lng->txt("cancel_file_upload"));
		$tpl_shared->setVariable("ERROR_MSG_EXTRACT_FAILED", $lng->txt("error_extraction_failed"));
		$tpl_shared->setVariable("PROGRESS_UPLOADING", $lng->txt("uploading"));
		$tpl_shared->setVariable("PROGRESS_EXTRACTING", $lng->txt("extracting"));
		$tpl_shared->setVariable("DROP_FILES_HERE", $lng->txt("drop_files_on_repo_obj_info"));
		$tpl_shared->parseCurrentBlock();
			
		// initialize default values
		$tpl_shared->setCurrentBlock("fileupload_defaults");
		$tpl_shared->setVariable("CONCURRENT_UPLOADS", ilFileUploadSettings::getConcurrentUploads());
		$tpl_shared->setVariable("MAX_FILE_SIZE", ilFileUploadUtil::getMaxFileSize());
		$tpl_shared->setVariable("ALLOWED_SUFFIXES", "");
		$tpl_shared->parseCurrentBlock();
			
		// load panel template
		$tpl_panel = new ilTemplate("tpl.fileupload_panel_template.html", true, true, "Services/FileUpload");
		$tpl_panel->setVariable("TXT_HEADER", $lng->txt("upload_files_title"));
		$tpl_panel->setVariable("TXT_SHOW_ALL_DETAILS", $lng->txt('show_all_details')); 
		$tpl_panel->setVariable("TXT_HIDE_ALL_DETAILS", $lng->txt('hide_all_details'));
		$tpl_panel->setVariable("TXT_SUBMIT", $lng->txt('upload_files'));
		$tpl_panel->setVariable("TXT_CANCEL", $lng->txt('cancel'));
			
		$tpl_shared->setCurrentBlock("fileupload_panel_tmpl");
		$tpl_shared->setVariable("PANEL_TEMPLATE_HTML", $tpl_panel->get());
		$tpl_shared->parseCurrentBlock();
			
		// load row template
		$tpl_row = new ilTemplate("tpl.mcst_fileupload_row_template.html", true, true, "Modules/MediaCast");
		$tpl_row->setVariable("IMG_ALERT", ilUtil::getImagePath("icon_alert.svg"));
		$tpl_row->setVariable("ALT_ALERT", $lng->txt("alert"));
		$tpl_row->setVariable("TXT_CANCEL", $lng->txt("cancel"));
		$tpl_row->setVariable("TXT_REMOVE", $lng->txt("remove"));
		$tpl_row->setVariable("TXT_TITLE", $lng->txt("title"));
		$tpl_row->setVariable("TXT_DESCRIPTION", $lng->txt("description"));
		$tpl_row->setVariable("TXT_KEEP_STRUCTURE", $lng->txt("take_over_structure"));
		$tpl_row->setVariable("TXT_KEEP_STRUCTURE_INFO", $lng->txt("take_over_structure_info"));
		$tpl_row->setVariable("TXT_ACCESS", $lng->txt("access"));
		$tpl_row->setVariable("TXT_ACCESS_USERS", $lng->txt("access_users"));
		$tpl_row->setVariable("TXT_ACCESS_PUBLIC", $lng->txt("access_public"));
		$tpl_row->setVariable("TXT_PENDING", $lng->txt("upload_pending"));
			
		$tpl_shared->setCurrentBlock("fileupload_row_tmpl");
		$tpl_shared->setVariable("ROW_TEMPLATE_HTML", $tpl_row->get());
		$tpl_shared->parseCurrentBlock();
			
		// shared code now loaded
		self::$shared_code_loaded = true;
		
		// create HTML
		return $tpl_shared->get();
	}
}

// END HSLU Patch to allow a multifile upload new class