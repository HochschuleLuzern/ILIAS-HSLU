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
class ilObjExerciseFileDAV extends ilObjectDAV implements Sabre\DAV\IFile
{
    protected ilExAssignment $ass;
    protected ilExSubmission $sub;
    protected array $file = [];
    protected array $stats = [];
    
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
        $this->file_id = $data['file_id'];
        $file = $this->sub->getFiles([$this->file_id]);
        
        if (is_array($file) && count($file) > 0) {
            $this->file = $file[0];
        }
        
        if (!file_exists($this->file['filename'])) {
            throw new NotFound('File not found');
        }
    }
    
    public function getName()
    {
        return $this->file['filetitle'];
    }
    
    public function getSize()
    {
        return filesize($this->file['filename']);
    }
    
    public function getETag()
    {
        return '"' . sha1(
            fileinode($this->file['filename']) .
            filesize($this->file['filename']) .
            filemtime($this->file['filename'])
        ) . '"';
    }
    
    public function getContentType()
    {
        return $this->file['mimetype'];
    }
        
    public function get()
    {
        if (!$this->repo_helper->checkAccess('write', $this->getRefId())) {
            throw new Forbidden('Permission denied');
        }
        return fopen($this->file['filename'], 'r');
    }
    
    public function delete()
    {
        throw new Forbidden('Permission denied');
    }
    
    public function put($data)
    {
        throw new Forbidden('Permission denied');
    }
    
    public function setName($name)
    {
        throw new Forbidden('Permission denied');
    }
}
