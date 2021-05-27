<?php

use Sabre\DAV;
use ILIAS\UI\NotImplementedException;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\NotImplemented;
use Sabre\DAV\Exception\Forbidden;

/**
 * Class ilObjContainerDAV
 *
 * Base implementation for container objects to be represented as WebDAV collection.
 *
 * @author Raphael Heer <raphael.heer@hslu.ch>
 * $Id$
 *
 * @extends ilObjectDAV
 * @implements Sabre\DAV\ICollection
 */
abstract class ilObjContainerDAV extends ilObjectDAV implements Sabre\DAV\ICollection
{
    protected string $child_collection_type = "none";
    
    public function __construct(ilObject $a_obj, ilWebDAVRepositoryHelper $repo_helper, ilWebDAVObjDAVHelper $dav_helper)
    {
        parent::__construct($a_obj, $repo_helper, $dav_helper);
    }

    public function createFile($name, $data = null)
    {
        if ($this->repo_helper->checkCreateAccessForType($this->obj->getRefId(), 'file')) {
            if ($this->dav_helper->isValidFileNameWithValidFileExtension($name)) {
                if ($this->childExists($name)) {
                    $file_dav = $this->getChild($name);
                    return $file_dav->put($data);
                } else {
                    $file_obj = new ilObjFile();
                    $file_obj->setTitle($name);
                    $file_obj->setFileName($name);
                    $file_obj->setVersion(1);
                    $file_obj->setMaxVersion(1);
                    $file_obj->createDirectory();
                    $file_obj->create();

                    $file_obj->createReference();
                    $file_obj->putInTree($this->obj->getRefId());
                    $file_obj->setPermissions($this->ref_id);
                    $file_obj->update();

                    $file_dav = new ilObjFileDAV($file_obj, $this->repo_helper, $this->dav_helper);
                    return $file_dav->handleFileUpload($data, "create");
                }
            } else {
                throw new Forbidden('Invalid file name or file extension');
            }
        } else {
            throw new Forbidden('No write access');
        }
    }

    public function createDirectory($name)
    {
        global $DIC;

        $type = $this->child_collection_type;
        if ($this->repo_helper->checkCreateAccessForType($this->getRefId(), $type) && $this->dav_helper->isDAVableObjTitle($name)) {
            switch ($type) {
                case 'cat':
                    $new_obj = new ilObjCategory();
                    break;

                case 'fold':
                    $new_obj = new ilObjFolder();
                    break;

                default:
                    ilLoggerFactory::getLogger('WebDAV')->info(get_class($this) . ' ' . $this->obj->getTitle() . " -> $type is not supported as webdav directory");
                    throw new NotImplemented("Create type '$type' as collection is not implemented yet");
            }

            $new_obj->setType($type);
            $new_obj->setOwner($DIC->user()->getId());
            $new_obj->setTitle($name);
            $new_obj->create();

            $new_obj->createReference();
            $new_obj->putInTree($this->obj->getRefId());
            $new_obj->setPermissions($this->obj->getRefId());
            $new_obj->update();
        } else {
            throw new Forbidden();
        }
    }
    
    public function getChild($name)
    {
        $child_node = null;

        if ($name == ilProblemInfoFileDAV::PROBLEM_INFO_FILE_NAME) {
            return new ilProblemInfoFileDAV($this, $this->repo_helper, $this->dav_helper);
        }

        foreach ($this->repo_helper->getChildrenOfRefId($this->obj->getRefId()) as $child_ref) {
            if ($this->dav_helper->isDAVableObject($child_ref, true)) {
                if ($this->repo_helper->getObjectTitleFromRefId($child_ref, true) == $name) {
                    $child = $this->dav_helper->createDAVObjectForRefId($child_ref);
                    
                    if ($child->isVisibleForUser()) {
                        $child_node = $child;
                    }
                }
            }
        }
        
        if (!is_null($child_node)) {
            return $child_node;
        }
        
        throw new Sabre\DAV\Exception\NotFound("$name not found");
    }
    
    /**
     * Returns an array with all the child nodes
     *
     * @return ilObject[]
     */
    public function getChildren()
    {
        $child_nodes = array();
        $already_seen_titles = array();
        $problem_info_file_needed = false;

        foreach ($this->repo_helper->getChildrenOfRefId($this->obj->getRefId()) as $child_ref) {
            if ($this->dav_helper->isDAVableObject($child_ref, true)) {
                $title = $this->repo_helper->getObjectTitleFromRefId($child_ref);
                if (in_array($title, $already_seen_titles)) {
                    $problem_info_file_needed = true;
                    continue;
                }

                $already_seen_titles[] = $title;
                
                $child = $this->dav_helper->createDAVObjectForRefId($child_ref);

                if ($child->isVisibleForUser()) {
                    $child_nodes[$child_ref] = $child;
                }
            } elseif (!$problem_info_file_needed
                && $this->dav_helper->isDAVableObjType($this->repo_helper->getObjectTypeFromRefId($child_ref))
                && $this->dav_helper->hasTitleForbiddenChars($this->repo_helper->getObjectTitleFromRefId($child_ref))) {
                $problem_info_file_needed = true;
            }
        }

        if ($problem_info_file_needed) {
            $child_nodes[] = new ilProblemInfoFileDAV($this, $this->repo_helper, $this->dav_helper);
        }

        return $child_nodes;
    }
    
    /**
     * Checks if a child-node with the specified name exists
     *            // Check if file has valid extension
     * @param string $name
     * @return bool
     */
    public function childExists($name)
    {
        foreach ($this->repo_helper->getChildrenOfRefId($this->obj->getRefId()) as $child_ref) {
            if ($this->dav_helper->isDAVableObject($child_ref, true)) {
                if ($this->repo_helper->getObjectTitleFromRefId($child_ref, true) == $name) {
                    $child = $this->dav_helper->createDAVObjectForRefId($child_ref);
                    if ($child->isVisibleForUser()) {
                        return true;
                    } else {
                        /*
                         * This is an interesting edge case. What happens if there are 2 objects with the same name
                         * but User1 only has access to the first and user2 has only access to the second?
                         */
                        return false;
                    }
                }
            }
        }
        
        return false;
    }
}
