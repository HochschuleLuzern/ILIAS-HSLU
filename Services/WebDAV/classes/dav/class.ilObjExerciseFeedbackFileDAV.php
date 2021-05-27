<?php

use Sabre\DAV;
use ILIAS\UI\NotImplementedException;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\NotImplemented;
use Sabre\DAV\Exception\Forbidden;
use phpDocumentor\Reflection\Types\Array_;

/**
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */
class ilObjExerciseFeedbackFileDAV extends ilObjectDAV implements Sabre\DAV\IFile
{
    protected ilExAssignment $ass;
    protected ilExSubmission $sub;
    protected ilFSStorageExercise $storage;
    protected string $filename;
    protected string $filepath;
    
    public function __construct(ilObjExercise $a_obj, ilWebDAVRepositoryHelper $repo_helper, ilWebDAVObjDAVHelper $dav_helper, array $data)
    {
        parent::__construct($a_obj, $repo_helper, $dav_helper);
        $this->ass = new ilExAssignment($data['ass_id']);
        $usr_id = $data['usr_id'];
        
        $team = null;
        if (array_key_exists('team_id', $data)) {
            $team = new ilExAssignmentTeam($data['team_id']);
        }
        
        $this->sub = new ilExSubmission($this->ass, $usr_id, $team);
        
        $this->filename = $data['filename'];
        
        $this->storage = new ilFSStorageExercise($this->ass->getExerciseId(), $this->ass->getId());
        $this->filepath = $this->storage->getFeedbackFilePath($this->sub->getFeedbackId(), $data['filename']);
    }
    
    public function getName()
    {
        return $this->filename;
    }
    
    public function getSize()
    {
        return filesize($this->filepath);
    }
    
    public function getETag()
    {
        return '"' . sha1(
            fileinode($this->filepath) .
            filesize($this->filepath) .
            filemtime($this->filepath)
        ) . '"';
    }
    
    public function getContentType()
    {
        return mime_content_type($this->filepath);
    }
        
    public function get()
    {
        if (!$this->repo_helper->checkAccess('write', $this->getRefId())) {
            throw new Forbidden('Permission denied');
        }
        
        if (!file_exists($this->filepath)) {
            throw new NotFound('File not found');
        }
        
        return fopen($this->filepath, 'r');
    }
    
    public function delete()
    {
        if (!$this->repo_helper->checkAccess('write', $this->getRefId())) {
            throw new Forbidden('Permission denied');
        }
        
        if (!file_exists($this->filepath)) {
            throw new NotFound('File not found');
        }
        
        $this->storage->deleteFile($this->filepath);
    }
    
    public function put($data)
    {
        if (!$this->repo_helper->checkAccess('write', $this->getRefId())) {
            throw new Forbidden('Permission denied');
        }
        $feedback_id = $this->sub->getFeedbackId();
        $noti_rec_ids = $this->sub->getUserIds();

        $fb_path = $this->storage->getFeedbackPath($feedback_id);
        $target = $fb_path . "/" . $this->getName();
        
        // rename file
        file_put_contents($target, $data);
        
        if ($noti_rec_ids) {
            foreach ($noti_rec_ids as $user_id) {
                $member_status = $this->ass->getMemberStatus($user_id);
                $member_status->setFeedback(true);
                $member_status->update();
            }
            
            $this->obj->sendFeedbackFileNotification(
                $this->getName(),
                $noti_rec_ids,
                $this->obj->getId()
            );
        }
        
        return $this->getETag();
    }
    
    public function setName($name)
    {
        if (!$this->repo_helper->checkAccess('write', $this->getRefId())) {
            throw new Forbidden('Permission denied');
        }
        
        if (!file_exists($this->filepath)) {
            throw new NotFound('File not found');
        }
        
        $this->storage->copyFile($this->filepath, $this->storage->getFeedbackFilePath($this->sub->getFeedbackId(), $name));
        $this->storage->deleteFile($this->filepath);
    }
}
