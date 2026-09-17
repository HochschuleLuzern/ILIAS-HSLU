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

declare(strict_types=1);

use ILIAS\Administration\AdminGUIRequest;
use ILIAS\Repository\Service AS RepositoryService;

/**
 * Handles Administration commands (cut, delete paste)
 *
 * @author Stefan Meyer <meyer@leifos.com>
 */
class ilAdministrationCommandGUI
{
    protected ilGlobalTemplateInterface $tpl;
    protected ilSetting $settings;
    protected ilErrorHandling $error;
    protected ilTree $tree;
    protected ilObjectDefinition$obj_definition;
    protected ?ilCtrl $ctrl = null;
    protected ?ilLanguage $lng = null;
    private ilAdministrationCommandHandling $container;
    protected AdminGUIRequest $request;
    private RepositoryService $repository;

    public function __construct(ilAdministrationCommandHandling $a_container)
    {
        /** @var \ILIAS\DI\Container $DIC */
        global $DIC;

        $this->tpl = $DIC->ui()->mainTemplate();
        $this->settings = $DIC->settings();
        $this->error = $DIC["ilErr"];
        $this->tree = $DIC->repositoryTree();
        $this->obj_definition = $DIC["objDefinition"];
        $ilCtrl = $DIC->ctrl();
        $lng = $DIC->language();

        $this->container = $a_container;
        $this->ctrl = $ilCtrl;
        $this->lng = $lng;
        $this->repository = $DIC->repository();

        $this->request = new AdminGUIRequest(
            $DIC->http(),
            $DIC->refinery()
        );
    }

    public function getContainer(): ilAdministrationCommandHandling
    {
        return $this->container;
    }

    public function delete(): void
    {
        $tpl = $this->tpl;
        $ilSetting = $this->settings;
        $ilErr = $this->error;

        $this->ctrl->setReturnByClass(get_class($this->getContainer()), '');

        $to_delete = $this->request->getSelectedIds();

        if (count($to_delete) === 0) {
            $ilErr->raiseError($this->lng->txt('no_checkbox'), $ilErr->MESSAGE);
        }

        $confirm = new ilConfirmationGUI();
        $confirm->setFormAction($this->ctrl->getFormActionByClass(get_class($this->getContainer()), 'cancel'));
        $confirm->setHeaderText($this->lng->txt('info_delete_sure')); // PATCH HSLU ZEL: fixed bug that broke deletion through search
        $confirm->setCancel($this->lng->txt('cancel'), 'cancelDelete');
        $confirm->setConfirm($this->lng->txt('delete'), 'performDelete');

        foreach ($to_delete as $delete) {
            $obj_id = ilObject::_lookupObjId($delete);
            $type = ilObject::_lookupType($obj_id);

            $confirm->addItem(
                'id[]',
                (string) $delete,
                call_user_func(array(ilObjectFactory::getClassByType($type),'_lookupTitle'), $obj_id),
                ilObject::_getIcon($obj_id, 'small', $type)
            );
        }

        $msg = $this->lng->txt("info_delete_sure");

        if (!$ilSetting->get('enable_trash')) {
            $msg .= "<br/>" . $this->lng->txt("info_delete_warning_no_trash");
        }
        $this->tpl->setOnScreenMessage('question', $msg);

        $tpl->setContent($confirm->getHTML());
    }

    public function performDelete(): void
    {
        // PATCH HSLU ZEL: made non-functioning deletion of objects found by search work.
        $ref_ids_for_deletion = $this->request->getSelectedIds();
        if (count($ref_ids_for_deletion) === 0) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('no_checkbox'), true);
            $this->ctrl->returnToParent($this);
        }
        $this->repository->internal()->domain()->deletion()->deleteObjectsByRefIds($ref_ids_for_deletion);
        $this->tpl->setOnScreenMessage('success', $this->lng->txt('info_deleted'), true);
    }

    public function cut(): void
    {
        $tree = $this->tree;

        $this->ctrl->setReturnByClass(get_class($this->getContainer()), '');

        $ref_id = $tree->getParentId($this->request->getItemRefId());

        $container = new ilContainerGUI(array(), $ref_id, true, false);
        $this->ctrl->setParameter($container, 'ref_id', $ref_id);
        $container->cutObject();
    }

    // Show target selection
    public function showMoveIntoObjectTree(): void
    {
        $objDefinition = $this->obj_definition;

        $this->ctrl->setReturnByClass(get_class($this->getContainer()), '');

        $obj_id = ilObject::_lookupObjId($this->request->getRefId());
        $type = ilObject::_lookupType($obj_id);

        $class_name = "ilObj" . $objDefinition->getClassName($type) . 'GUI';

        // create instance
        $container = new $class_name(array(), $this->request->getRefId(), true, false);
        $container->showMoveIntoObjectTreeObject();
    }

    // Target selection
    public function showLinkIntoMultipleObjectsTree(): void
    {
        $objDefinition = $this->obj_definition;

        $this->ctrl->setReturnByClass(get_class($this->getContainer()), '');

        $obj_id = ilObject::_lookupObjId($this->request->getRefId());
        $type = ilObject::_lookupType($obj_id);

        $class_name = "ilObj" . $objDefinition->getClassName($type) . 'GUI';

        // create instance
        $container = new $class_name(array(), $this->request->getRefId(), true, false);
        $container->showLinkIntoMultipleObjectsTreeObject();
    }

    // Start linking object
    public function link(): void
    {
        $tree = $this->tree;

        $this->ctrl->setReturnByClass(get_class($this->getContainer()), '');

        $ref_id = $tree->getParentId($this->request->getItemRefId());

        $container = new ilContainerGUI(array(), $ref_id, true, false);
        $this->ctrl->setParameter($container, 'ref_id', $ref_id);
        $container->linkObject();
    }

    public function showPasteTree(): void
    {
        $tree = $this->tree;

        $this->ctrl->setReturnByClass(get_class($this->getContainer()), '');

        $ref_id = $tree->getParentId($this->request->getRefId());

        $container = new ilContainerGUI(array(), $ref_id, true, false);
        $container->showPasteTreeObject();
    }

    // Paste object
    public function paste(): void
    {
        $objDefinition = $this->obj_definition;

        $this->ctrl->setReturnByClass(get_class($this->getContainer()), '');

        $obj_id = ilObject::_lookupObjId($this->request->getItemRefId());
        $type = ilObject::_lookupType($obj_id);

        $class_name = "ilObj" . $objDefinition->getClassName($type) . 'GUI';

        // create instance
        $container = new $class_name(array(), $this->request->getItemRefId(), true, false);
        $container->pasteObject();
    }

    public function performPasteIntoMultipleObjects(): void
    {
        $objDefinition = $this->obj_definition;

        $this->ctrl->setReturnByClass(get_class($this->getContainer()), '');

        $obj_id = ilObject::_lookupObjId($this->request->getRefId());
        $type = ilObject::_lookupType($obj_id);

        $class_name = "ilObj" . $objDefinition->getClassName($type) . 'GUI';

        // create instance
        $container = new $class_name(array(), $this->request->getRefId(), true, false);
        $container->performPasteIntoMultipleObjectsObject();
    }
}
