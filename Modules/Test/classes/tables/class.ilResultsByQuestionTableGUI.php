<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

/**
 * TableGUI class for results by question
 *
 * @author  Helmut Schottmüller <helmut.schottmueller@mac.com>
 * @author  Maximilian Becker <mbecker@databay.de>
 *
 * @version $Id$
 *
 * @ingroup ModulesTest
 */
class ilResultsByQuestionTableGUI extends ilTable2GUI
{
    public function __construct($a_parent_obj, $a_parent_cmd = "")
    {
        global $DIC;
        $ilCtrl = $DIC['ilCtrl'];
        $lng = $DIC['lng'];

        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->addColumn($lng->txt("question_id"), "qid", "");
        $this->addColumn($lng->txt("question_title"), "question_title", "35%");
        $this->addColumn($lng->txt("number_of_answers"), "number_of_answers", "15%");
        $this->addColumn($lng->txt("output"), "", "20%");
        $this->addColumn($lng->txt("file_uploads"), "", "20%");

        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        $this->setRowTemplate("tpl.table_results_by_question_row.html", "Modules/Test");
        $this->setDefaultOrderField("question_title");
        $this->setDefaultOrderDirection("asc");
    }

    protected function fillRow(array $a_set): void
    {
        $this->tpl->setVariable("QUESTION_ID", $a_set['qid']);
        $this->tpl->setVariable("QUESTION_TITLE", $a_set['question_title']);
        $this->tpl->setVariable("NUMBER_OF_ANSWERS", $a_set['number_of_answers']);
        $this->tpl->setVariable("FILE_UPLOADS", $a_set['file_uploads']);
    }

    public function numericOrdering(string $a_field): bool
    {
        switch ($a_field) {
            case 'qid':
            case 'number_of_answers':
                return true;

            default:
                return false;
        }
    }
}
