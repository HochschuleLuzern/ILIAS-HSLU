<?php

use Sabre\DAV;
use ILIAS\UI\NotImplementedException;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\NotImplemented;
use Sabre\DAV\Exception\Forbidden;
use function PHPUnit\Framework\isNull;

/**
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */
class ilObjExerciseDAV extends ilObjContainerDAV
{
    private const MODES = ['exc_by_assignment', 'exc_by_user'];
    protected array $modes_translated;
   
    public function __construct(ilObjExercise $a_obj, ilWebDAVRepositoryHelper $repo_helper, ilWebDAVObjDAVHelper $dav_helper, array $data = [])
    {
        parent::__construct($a_obj, $repo_helper, $dav_helper);
        global $DIC;
        foreach (self::MODES as $mode) {
            $this->modes_translated[$mode] = $DIC->language()->txt('webdav_' . $mode);
        }
    }
    
    public function delete()
    {
        throw new Forbidden("Permission denied");
    }
    
    public function setName($a_name)
    {
        throw new Forbidden('Permission denied');
    }

    public function createFile($name, $data = null)
    {
        throw new Forbidden('No write access');
    }

    public function createDirectory($name)
    {
        throw new Forbidden('No write access');
    }
    
    /**
     * Returns a specific child node, referenced by its name
     *
     * This method must throw Sabre\DAV\Exception\NotFound if the node does not
     * exist.
     *
     * @throws Exception\NotFound Exception\BadRequest
     * @param string $name
     * @return Sabre\DAV\INode
     */
    public function getChild($name)
    {
        if (!$this->repo_helper->checkAccess('write', $this->getRefId())) {
            throw new Forbidden('Permission denied');
        }
        
        if ($key = array_search($name, $this->modes_translated)) {
            return $this->dav_helper->createDAVObjectForRefId($this->ref_id, $key);
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
        if (!$this->repo_helper->checkAccess('write', $this->getRefId())) {
            throw new Forbidden('Permission denied');
        }
        
        $child_nodes = [];
        
        foreach ($this->modes_translated as $type => $translation) {
            $child_nodes[] = $this->dav_helper->createDAVObjectForRefId($this->ref_id, $type);
        }
        
        return $child_nodes;
    }
    
    /**
     * Checks if a child-node with the specified name exists
     *
     * @param string $name
     * @return bool
     */
    public function childExists($name)
    {
        if ($key = array_search($name, $this->modes_translated)) {
            return true;
        }
        
        return false;
    }
    
    public function getChildCollectionType()
    {
        return 'none';
    }
    
    protected function isVisibleForUser()
    {
        return $this->repo_helper->checkAccess("write", $this->ref_id);
    }
}
