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
class ilObjExerciseByAssignmentDAV extends ilObjExerciseContentDAV
{
    private const MODE = 'exc_by_assignment';
    
    public function __construct(ilObjExercise $a_obj, ilWebDAVRepositoryHelper $repo_helper, ilWebDAVObjDAVHelper $dav_helper, array $data)
    {
        $this->mode = self::MODE;
        parent::__construct($a_obj, $repo_helper, $dav_helper, $data);
    }
    
    protected function getNameFromDerivedClass()
    {
        if (!isset($this->ass)) {
            throw new NotFound("Not found");
        }
        
        if (!isset($this->usr_id)) {
            return $this->ass->getTitle();
        }
        
        if (isset($this->team_id)) {
            $team = new ilExAssignmentTeam($this->team_id);
            $members = $team->getMembers();
            $filename = 'Team' . $team->getId() . "-";
            
            foreach ($members as $member) {
                $member = new ilObjUser($member);
                $name = (explode(' ', $member->getLastname()))[0];
                
                $filename .= $name;
                
                if (strlen($filename) > 32) {
                    return substr($filename, 0, 32);
                }
            }
            
            return $filename;
        }
        
        $user = ilObjUser::_lookupName($this->usr_id);
        $filename = $user['lastname'] . "_" . $user['firstname'] . "-" . $user['login'];
        return str_replace(' ', '_', $filename);
    }
    
    protected function getChildFromDerivedClass($name)
    {
        if (isset($this->usr_id) && ($file = $this->getFileOrFeedback($name))) {
            return $file;
        }
        
        if (isset($this->ass) && ($submission = $this->getSubmission($name))) {
            return $submission;
        }
        
        if ($assignment = $this->getAssignment($name)) {
            return $assignment;
        }
    }
    
    protected function getChildrenFromDerivedClass()
    {
        if (isset($this->usr_id)) {
            return $this->getFilesAndFeedback();
        }
        
        if (isset($this->ass)) {
            return $this->getSubmissions();
        }
        
        return $this->getAssignments();
    }
    
    public function childExists($name)
    {
        if (isset($this->usr_id)) {
            return $this->fileOrFeedbackExists($name);
        }
        
        if (isset($this->ass)) {
            return $this->submissionExists($name);
        }
        
        return $this->assignmentExists($name);
    }
}
