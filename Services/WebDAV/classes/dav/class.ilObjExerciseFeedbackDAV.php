<?php

use Sabre\DAV;
use ILIAS\UI\NotImplementedException;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\NotImplemented;
use Sabre\DAV\Exception\Forbidden;

/**
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */
class ilObjExerciseFeedbackDAV extends ilObjExerciseDAV
{
    protected array $feedback_files;
    protected ilExAssignment $ass;
    protected int $usr_id;
    protected int $team_id;
    
    public function __construct(ilObjExercise $a_obj, ilWebDAVRepositoryHelper $repo_helper, ilWebDAVObjDAVHelper $dav_helper, array $data)
    {
        parent::__construct($a_obj, $repo_helper, $dav_helper, $data);
        $this->ass = new ilExAssignment($data['ass_id']);
        
        $this->usr_id = $data['usr_id'];
        
        $team = null;
        if (array_key_exists('team_id', $data)) {
            $this->team_id = $data['team_id'];
            $team = new ilExAssignmentTeam($this->team_id);
        }
        
        $sub = new ilExSubmission($this->ass, $this->usr_id, $team);
        
        $storage = new ilFSStorageExercise(
            $this->ass->getExerciseId(),
            $this->ass->getId()
        );
        $this->feedback_files = $storage->getFeedbackFiles($sub->getFeedbackId());
    }
    
    public function getName()
    {
        return "Feedback";
    }
    
    public function getChild($name)
    {
        if (!$this->repo_helper->checkAccess('write', $this->getRefId())) {
            throw new Forbidden('Permission denied');
        }
        
        $data = $this->getDataArray();
        
        foreach ($this->feedback_files as $file) {
            if ($file == $name) {
                $data['filename'] = $file;
                return $this->dav_helper->createDAVObjectForRefId($this->ref_id, 'exc_feedback_file', $data);
            }
        }
        
        // There is no davable object with the same name. Sorry for you...
        throw new Sabre\DAV\Exception\NotFound("$name not found");
    }

    public function getChildren()
    {
        if (!$this->repo_helper->checkAccess('write', $this->getRefId())) {
            throw new Forbidden('Permission denied');
        }
        
        $data = $this->getDataArray();
        
        if (count($this->feedback_files) == 0) {
            return [];
        }

        foreach ($this->feedback_files as $file) {
            $data['filename'] = $file;
            $child_nodes[$file] = $this->dav_helper->createDAVObjectForRefId($this->ref_id, 'exc_feedback_file', $data);
        }

        return $child_nodes;
    }
    
    public function createFile($name, $webdav_data = null)
    {
        if (!$this->repo_helper->checkAccess('write', $this->getRefId())) {
            throw new Forbidden('No write access');
        }
        
        if (!$this->dav_helper->isValidFileNameWithValidFileExtension($name)) {
            throw new Forbidden('Invalid file name or file extension');
        }
        
        $file_data = [
            'ass_id' => (int) $this->ass->getId(),
            'usr_id' => $this->usr_id,
            'filename' => $name
        ];
        
        if (isset($this->team_id)) {
            $file_data['team_id'] = $this->team_id;
        }
        
        $feedback_file = $this->dav_helper->createDAVObjectForRefId($this->ref_id, 'exc_feedback_file', $file_data);
        return $feedback_file->put($webdav_data);
    }
    
    public function childExists($name)
    {
        if (in_array($name, $this->feedback_files)) {
            return true;
        }
        
        return false;
    }
    
    private function getDataArray() : array
    {
        $data = [
            'ass_id' => (int) $this->ass->getId(),
            'usr_id' => $this->usr_id,
        ];
        
        if (isset($this->team_id)) {
            $data['team_id'] = $this->team_id;
        }
        
        return $data;
    }
}
