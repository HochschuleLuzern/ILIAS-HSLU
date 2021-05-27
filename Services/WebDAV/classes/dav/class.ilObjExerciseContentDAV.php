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
abstract class ilObjExerciseContentDAV extends ilObjExerciseDAV
{
    protected string $mode;
    protected ilExAssignment $ass;
    protected int $usr_id;
    protected int $team_id;
    
    public function __construct(ilObjExercise $a_obj, ilWebDAVRepositoryHelper $repo_helper, ilWebDAVObjDAVHelper $dav_helper, array $data)
    {
        parent::__construct($a_obj, $repo_helper, $dav_helper);
        if (array_key_exists('ass_id', $data)) {
            $this->ass = new ilExAssignment($data['ass_id']);
        }
        
        if (array_key_exists('usr_id', $data)) {
            $this->usr_id = $data['usr_id'];
        }
        
        if (array_key_exists('team_id', $data)) {
            $this->team_id = $data['team_id'];
        }
    }
    
    public function getName()
    {
        if (!isset($this->ass) && !isset($this->usr_id)) {
            return $this->modes_translated[$this->mode];
        }
        
        return $this->getNameFromDerivedClass();
    }
    
    abstract protected function getNameFromDerivedClass();
    
    public function getChild($name)
    {
        if (!$this->repo_helper->checkAccess('write', $this->getRefId())) {
            throw new Forbidden('Permission denied');
        }
        if (isset($this->ass) && isset($this->usr_id)) {
            $this->sub = new ilExSubmission($this->ass, $user_id, $team);
        }
        
        if ($name == ilProblemInfoFileDAV::PROBLEM_INFO_FILE_NAME) {
            return new ilProblemInfoFileDAV($this, $this->repo_helper, $this->dav_helper);
        }
        
        if ($child = $this->getChildFromDerivedClass($name)) {
            return $child;
        }
        
        throw new Sabre\DAV\Exception\NotFound("$name not found");
    }
    
    abstract protected function getChildFromDerivedClass($name);
    
    public function getChildren()
    {
        if (!$this->repo_helper->checkAccess('write', $this->getRefId())) {
            throw new Forbidden('Permission denied');
        }
        
        return $this->getChildrenFromDerivedClass();
    }
    abstract protected function getChildrenFromDerivedClass();
    
    protected function getAssignment(string $name)
    {
        $child_node = null;
        
        $ref_id = $this->obj->getRefId();
        $exc_id = $this->repo_helper->getObjectIdFromRefId($ref_id);
        
        $data = [];
        
        foreach (ilExAssignment::getInstancesByExercise($exc_id) as $ass) {
            if ($ass->getTitle() == $name && $ass->getAssignmentType()->getSubmissionType() == ilExSubmission::TYPE_FILE) {
                $data['ass_id'] = $ass->getId();
                
                if (isset($this->usr_id)) {
                    $data['usr_id'] = $this->usr_id;
                }
                
                if (!isset($this->team_id) &&
                    isset($this->usr_id) &&
                    $ass->getAssignmentType()->usesTeams() &&
                    ($team_id = ilExAssignmentTeam::getTeamId($ass->getId(), $this->usr_id))) {
                    $this->team_id = $team_id;
                }
                
                if (isset($this->team_id)) {
                    $data['team_id'] = $this->team_id;
                }
                
                $child_node = $this->dav_helper->createDAVObjectForRefId($ref_id, $this->mode, $data);
            }
        }
        
        if (!is_null($child_node)) {
            return $child_node;
        }
        
        return false;
    }
    
    protected function getSubmission(string $name)
    {
        $ref_id = $this->obj->getRefId();
        
        $data = [];
        
        if (isset($this->ass)) {
            $data['ass_id'] = (int) $this->ass->getId();
        }
        
        if (strstr($name, 'Team') == $name) {
            $name = substr($name, 4);
            $end = strrpos($name, "-");
            if (($name = (int) substr($name, 0, $end)) > 0) {
                $data['team_id'] = $name;
                $team = new ilExAssignmentTeam($data['team_id']);
                $data['usr_id'] = ($team->getMembers())[0];
            }
        } elseif (($start = strrpos($name, "-")) !== false) {
            $username = substr($name, $start + 1);
            if ($username && ($usr_id = (int) ilObjUser::_lookupId($username)) > 0) {
                $data['usr_id'] = $usr_id;
            }
        }
        
        if (array_key_exists('usr_id', $data)) {
            return $this->dav_helper->createDAVObjectForRefId($ref_id, $this->mode, $data);
        }
        
        return false;
    }
    
    protected function getFileOrFeedback($name)
    {
        $data = [
            'ass_id' => (int) $this->ass->getId(),
            'usr_id' => $this->usr_id,
        ];
        
        if (isset($this->team_id)) {
            $data['team_id'] = $this->team_id;
        }
        
        if ($name == 'Feedback' && $this->obj->hasTutorFeedbackFile()) {
            return $this->dav_helper->createDAVObjectForRefId($this->ref_id, 'exc_feedback', $data);
        }
        
        $sub = new ilExSubmission($this->ass, $this->usr_id, $team);
        $files = $sub->getFiles();
        
        foreach ($files as $file) {
            if ($file['filetitle'] == $name) {
                $data['file_id'] = (int) $file['returned_id'];
                return $this->dav_helper->createDAVObjectForRefId($this->ref_id, 'exc_file', $data);
            }
        }
    }
    
    protected function getAssignments()
    {
        $child_nodes = [];
        $already_seen_titles = [];
        $problem_info_file_needed = false;
        
        $ref_id = $this->obj->getRefId();
        $exc_id = $this->repo_helper->getObjectIdFromRefId($ref_id);
        
        $data = [];
        
        foreach (ilExAssignment::getInstancesByExercise($exc_id) as $ass) {
            if ($ass->getAssignmentType()->getSubmissionType() != ilExSubmission::TYPE_FILE) {
                continue;
            }
            
            $title = $ass->getTitle();
            if (in_array($title, $already_seen_titles)
                || $this->dav_helper->hasTitleForbiddenChars($title)) {
                $problem_info_file_needed = true;
                continue;
            }
                
            $data['ass_id'] = $ass->getId();
            
            if (isset($this->usr_id)) {
                $data['usr_id'] = $this->usr_id;
            }
                
            $child_nodes[$ass->getId()] = $this->dav_helper->createDAVObjectForRefId($ref_id, $this->mode, $data);
            $already_seen_titles[] = $title;
        }
        
        if ($problem_info_file_needed) {
            $child_nodes[] = new ilProblemInfoFileDAV($this, $this->repo_helper, $this->dav_helper);
        }
        
        return $child_nodes;
    }
    
    protected function getSubmissions()
    {
        $child_nodes = [];
        $ref_id = $this->obj->getRefId();
        
        $data = [];
        
        if (!isset($this->ass)) {
            $exc_members = new ilExerciseMembers($this->obj);
            $members = $exc_members->getMembers();
            
            foreach ($members as $member) {
                $data['usr_id'] = (int) $member;
                $child_nodes[$data['usr_id']] = $this->dav_helper->createDAVObjectForRefId($ref_id, $this->mode, $data);
            }
            
            return $child_nodes;
        }
        
        $data = [
            'ass_id' => (int) $this->ass->getId()
        ];
                
        if ($this->ass->getAssignmentType()->usesTeams()) {
            $teams = ilExAssignmentTeam::getInstancesFromMap($this->ass->getId());
            foreach ($teams as $team) {
                $data['team_id'] = (int) $team->getId();
                $team = new ilExAssignmentTeam($data['team_id']);
                $data['usr_id'] = ($team->getMembers())[0];
                $child_nodes[$data['team_id']] = $this->dav_helper->createDAVObjectForRefId($ref_id, $this->mode, $data);
            }
            
            return $child_nodes;
        }
        
        $usrs = $this->ass->getMemberListData();
        foreach ($usrs as $usr) {
            $data['usr_id'] = (int) $usr['usr_id'];
            $child_nodes[$data['usr_id']] = $this->dav_helper->createDAVObjectForRefId($ref_id, $this->mode, $data);
        }
        
        return $child_nodes;
    }
    
    protected function getFilesAndFeedback()
    {
        $child_nodes = [];
        $already_seen_titles = [];
        $problem_info_file_needed = false;
        
        $team = null;
        if (isset($this->team_id)) {
            $team = new ilExAssignmentTeam($this->team_id);
        }
        
        $sub = new ilExSubmission($this->ass, $this->usr_id, $team);
        $files = $sub->getFiles();
        
        $data = [
            'ass_id' => (int) $this->ass->getId(),
            'usr_id' => $this->usr_id,
        ];
        
        if (isset($this->team_id)) {
            $data['team_id'] = $this->team_id;
        }
        
        foreach ($files as $file) {
            if (in_array($file['filetitle'], $already_seen_titles)) {
                $problem_info_file_needed = true;
                continue;
            }
            
            $data['file_id'] = (int) $file['returned_id'];
            
            $child_nodes[$data['file_id']] = $this->dav_helper->createDAVObjectForRefId($this->ref_id, 'exc_file', $data);
            $already_seen_titles[] = $file['filename'];
        }
        
        if ($this->obj->hasTutorFeedbackFile()) {
            $child_nodes[] = $this->dav_helper->createDAVObjectForRefId($this->ref_id, 'exc_feedback', $data);
        }
        
        if ($problem_info_file_needed) {
            $child_nodes[] = new ilProblemInfoFileDAV($this, $this->repo_helper, $this->dav_helper);
        }
        
        return $child_nodes;
    }
    
    protected function assignmentExists($name)
    {
        $ref_id = $this->obj->getRefId();
        $exc_id = $this->repo_helper->getObjectIdFromRefId($ref_id);
        
        foreach (ilExAssignment::getInstancesByExercise($exc_id) as $ass) {
            if ($ass->getTitle() == $name && $ass->getAssignmentType()->getSubmissionType() == ilExSubmission::TYPE_FILE) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function submissionExists($name)
    {
        if (strstr($name, 'Team') == $name) {
            $name = substr($name, 4);
            $end = strrpos($name, "-");
            
            if (($name = (int) substr($name, 0, $end)) > 0) {
                return true;
            }
        }
        
        if (($start = strrpos($name, "-")) !== false) {
            $username = substr($name, $start + 1);
            
            if ($username && ilObjUser::_lookupId($username) > 0) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function fileOrFeedbackExists($name)
    {
        if ($name == 'Feedback' && $this->obj->hasTutorFeedbackFile()) {
            return true;
        }
        
        if ($name == ilProblemInfoFileDAV::PROBLEM_INFO_FILE_NAME) {
            return true;
        }
        
        $files = $this->sub->getFiles();
        
        foreach ($files as $file) {
            if ($file['filetitle'] == $name) {
                return true;
            }
        }
        
        return false;
    }
}
