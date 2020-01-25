/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

// HSLU Patch to allow a multifile upload new file
var mc = {
	submitFile: function(e, data) {
		 // add file specific form data
		var titleValue = $("#title_" + data.id).val();
        var descValue = $("#desc_" + data.id).val();
        var visibilityValue = $("input[name=visibility_" + data.id+"]:checked").val();

        log("File.getFormData (%s): title: %s, description: %s, visibility: %s", this.id, titleValue, descValue, visibilityValue);

        data.formData = { title: titleValue, description: descValue, visibility: visibilityValue};
	}
}
//END HSLU Patch to allow a multifile upload new file