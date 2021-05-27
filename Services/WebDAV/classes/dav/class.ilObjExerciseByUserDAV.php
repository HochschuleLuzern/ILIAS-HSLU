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
class ilObjExerciseByUserDAV extends ilObjExerciseContentDAV
{
    private const MODE = 'exc_by_user';
    
    public function __construct(ilObjExercise $a_obj, ilWebDAVRepositoryHelper $repo_helper, ilWebDAVObjDAVHelper $dav_helper, array $data)
    {
        $this->mode = self::MODE;
        parent::__construct($a_obj, $repo_helper, $dav_helper, $data);
    }
    
    protected function getNameFromDerivedClass()
    {
        if (!isset($this->usr_id)) {
            throw new NotFound("Not found");
        }
        
        if (isset($this->ass)) {
            return $this->ass->getTitle();
        }
        
        $user = ilObjUser::_lookupName($this->usr_id);
        $filename = $user['lastname'] . "_" . $user['firstname'] . "-" . $user['login'];
        return str_replace(' ', '_', $filename);
    }

    protected function getChildFromDerivedClass($name)
    {
        if (isset($this->ass) && ($file = $this->getFileOrFeedback($name))) {
            return $file;
        }
        
        if (isset($this->usr_id) && ($assignment = $this->getAssignment($name))) {
            return $assignment;
        }
        
        if ($submission = $this->getSubmission($name)) {
            return $submission;
        }
    }

    protected function getChildrenFromDerivedClass()
    {
        if (isset($this->ass)) {
            return $this->getFilesAndFeedback();
        }
        
        if (isset($this->usr_id)) {
            return $this->getAssignments();
        }
        
        return $this->getSubmissions();
    }
    
    public function childExists($name)
    {
        if (isset($this->ass)) {
            return $this->fileOrFeedbackExists($name);
        }
        
        if (isset($this->usr_id)) {
            return $this->assignmentExists($name);
        }
        
        return $this->submissionExists($name);
    }
}
