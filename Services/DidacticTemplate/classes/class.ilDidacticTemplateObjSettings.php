<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Stores the applied template id for objects
 *
 * @author Stefan Meyer <meyer@ilias@gmx.de>
 * @ingroup ServicesDidacticTemplate
 */
class ilDidacticTemplateObjSettings
{

    /**
     * Lookup template id
     * @global ilDB $ilDB
     * @param int $a_ref_id
     * @return int
     */
    public static function lookupTemplateId($a_ref_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $query = 'SELECT tpl_id FROM didactic_tpl_objs ' .
            'WHERE ref_id = ' . $ilDB->quote($a_ref_id, 'integer');
        $res = $ilDB->query($query);
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            return $row->tpl_id;
        }
        return 0;
    }


    /**
     * Delete by obj id
     * @global ilDB $ilDB
     * @param int $a_obj_id
     * @return bool
     */
    public static function deleteByObjId($a_obj_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $query = 'DELETE FROM didactic_tpl_objs ' .
            'WHERE obj_id = ' . $ilDB->quote($a_obj_id, 'integer');
        $ilDB->manipulate($query);
        return true;
    }

    /**
     * Delete by template id
     * @global ilDB $ilDB
     * @param int $a_tpl_id
     * @return bool
     */
    public static function deleteByTemplateId($a_tpl_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $query = 'DELETE FROM didactic_tpl_objs ' .
            'WHERE tpl_id = ' . $ilDB->quote($a_tpl_id, 'integer');
        $ilDB->manipulate($query);
        return true;
    }

    /**
     * Delete by ref_id
     * @global ilDB $ilDB
     * @param int $a_ref_id
     */
    public static function deleteByRefId($a_ref_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $query = 'DELETE FROM didactic_tpl_objs ' .
            'WHERE ref_id = ' . $ilDB->quote($a_ref_id, 'integer');
        $ilDB->manipulate($query);
    }

    /**
     * Assign template to object
     * @global ilDB $ilDB
     * @param int $a_obj_id
     * @param int $a_tpl_id
     * @return bool
     */
    public static function assignTemplate($a_ref_id, $a_obj_id, $a_tpl_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        self::deleteByRefId($a_ref_id);

        $query = 'INSERT INTO didactic_tpl_objs (ref_id,obj_id,tpl_id) ' .
            'VALUES ( ' .
            $ilDB->quote($a_ref_id, 'integer') . ', ' .
            $ilDB->quote($a_obj_id, 'integer') . ', ' .
            $ilDB->quote($a_tpl_id, 'integer') . ' ' .
            ')';
        $ilDB->manipulate($query);
        return true;
    }
    /**
     * Lookup template id
     * @global ilDB $ilDB
     * @param int $a_tpl_id
     * @return array[]
     */
    public static function getAssignmentsByTemplateID($a_tpl_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $query = 'SELECT * FROM didactic_tpl_objs ' .
            'WHERE tpl_id = ' . $ilDB->quote($a_tpl_id, 'integer');
        $res = $ilDB->query($query);
        $assignments = array();

        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $assignments[] = array("ref_id" => $row->ref_id, "obj_id" => $row->obj_id);
        }
        return $assignments;
    }

    /**
     * @param int[] $template_ids
     * @return array
     */
    public static function getAssignmentsForTemplates(array $template_ids) : array
    {
        global $DIC;

        $db = $DIC->database();
        $query = 'select * from didactic_tpl_objs ' .
            'where ' . $db->in('tpl_id', $template_ids, false, ilDBConstants::T_INTEGER);
        $res = $db->query($query);
        $assignments = [];
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $assignments[$row->tpl_id][] = $row->ref_id;
        }
        return $assignments;
    }


    /**
     * transfer auto generated flag if source is auto generated
     *
     * @param int $a_src
     * @param int $a_dest
     * @return bool
     */
    public static function transferAutoGenerateStatus($a_src, $a_dest)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $query = 'SELECT auto_generated FROM didactic_tpl_settings ' .
            'WHERE id = ' . $ilDB->quote($a_src, 'integer');
        $res = $ilDB->query($query);

        $row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT);

        if ($row->auto_generated == 0) {
            return false;
        }

        $query = 'UPDATE didactic_tpl_settings ' .
            'SET ' .
            'auto_generated = ' . $ilDB->quote(1, 'integer') .
            ' WHERE id = ' . $ilDB->quote($a_dest, 'integer');
        $ilDB->manipulate($query);

        $query = 'UPDATE didactic_tpl_settings ' .
            'SET ' .
            'auto_generated = ' . $ilDB->quote(0, 'integer') .
            ' WHERE id = ' . $ilDB->quote($a_src, 'integer');
        $ilDB->manipulate($query);

        return true;
    }
    
// BEGIN PATCH HSLU: Postbox
    static protected $dtpl_cache = array();
    
    /**
     * Lookup the Id for a didactic template
     * @param string $a_tpl_name
     * @return integer
     */
    public static function lookupTemplateIdByName($a_tpl_name)
    {
        global $ilDB;
        
        if(isset(self::$dtpl_cache[$a_tpl_name]))
        {
            return self::$dtpl_cache[$a_tpl_name];
        }
        
        $sql = "SELECT * FROM didactic_tpl_settings WHERE title = " . $ilDB->quote($a_tpl_name, "text");
        $res = $ilDB->query($sql);
        if($row = $ilDB->fetchAssoc($res))
        {
            self::$dtpl_cache[$a_tpl_name] = $row['id'];
            return self::$dtpl_cache[$a_tpl_name];
        }
        return 0;
    }
    
    /**
     * Check if object is the given special folder
     * @param integer $current_ref ref_id of the object
     * @param string $a_dtpl_name name of the didactic template
     * @return boolean
     */
    public static function isSpecialFolder($a_ref_id, $a_dtpl_name)
    {
        $dtpl_id = self::lookupTemplateIdByName($a_dtpl_name);
        if(($a_ref_id == null || $dtpl_id == 0) && ilObject::_lookupType($current_ref, true) != 'fold')
        {
            return false;
        }
        
        return ($dtpl_id == self::lookupTemplateId($a_ref_id));
    }
    
    /**
     * Check if object is in a given special folder
     * @param integer $current_ref ref_id of the object
     * @param string $a_dtpl_name name of the didactic template
     * @return boolean
     */
    public static function isInSpecialFolder($a_ref_id, $a_dtpl_name)
    {
        // If no ref_id given or dtpl does not exist -> return false
        $dtpl_id = self::lookupTemplateIdByName($a_dtpl_name);
        if($a_ref_id == null || $dtpl_id == 0)
        {
            return false;
        }
        
        global $tree;
        
        // Check every parent folder if it has the postbox dtpl setted
        $current_ref = $a_ref_id;
        
        do {
            $current_ref = $tree->getParentId($current_ref);
            if($current_ref == null || $current_ref == '')
            {
                return false;
            }
            
            // Only check folders
            $type = ilObject::_lookupType($current_ref, true);
            if($type == 'fold')
            {
                $current_dtpl_id = self::lookupTemplateId($current_ref);
                if($current_dtpl_id == $dtpl_id)
                {
                    return true;
                }
            }
            
            // Folders are only allowed in folders and groups. So if we are for example in a crs or cat
            // we know it isnt possible that there is any folder higher in the tree
        } while($type == 'fold' || $type == 'grp');
        return false;
    }
    
    /**
     * Checks if object has a special icon for its didactic template
     * @param unknown $a_ref_id
     * @param unknown $a_default_name
     * @return string
     */
    public static function checkAndGetIconForDTPL($a_ref_id, $a_default_name)
    {
        $used_dtpl_id = self::lookupTemplateId($a_ref_id);
        
        if($used_dtpl_id == 0)
        {
            return $a_default_name;
        }
        
        if($used_dtpl_id == self::lookupTemplateIdByName('Briefkasten'))
        {
            return  self::checkPostboxFileReadRights($a_ref_id) ? 'drop' : 'fdrop';
        }
        else if($used_dtpl_id == self::lookupTemplateIdByName('Dateiaustausch'))
        {
            return 'fexch';
        }
        else if($used_dtpl_id == self::lookupTemplateIdByName('Gruppenordner'))
        {
            return 'fldgrp';
        }
        else
        {
            return $a_default_name;
        }
    }
    
    /**
     * Check if the file-read-rights of this folder really fits the condition of a
     * postbox. In a postbox, no member-role has the read access for files!
     * Because read access on files = Download access on files
     * @param integer $a_ref_id
     * @return boolean
     */
    public static function checkPostboxFileReadRights($a_ref_id)
    {
        global $rbacreview;
        
        // Get operation id for "read"-permission
        $read_permission = ilRbacReview::_getOperationIdByName('read');
        
        // Get every parent role
        $parent_roles = $rbacreview->getParentRoleIds($a_ref_id);
        foreach($parent_roles as $parent_role)
        {
            // check every parent role that is for 'members' (e.g.: 'grp_member, 'crs_member')
            if(stristr($parent_role['title'], 'member'))
            {
                // Get operations for files in this role
                $operations = $rbacreview->getOperationsOfRole($parent_role['obj_id'],
                    'file',
                    $parent_role['parent']);
                
                // If 'read'-permission is set -> does not fit the postbox conditions!
                if(in_array($read_permission, $operations))
                {
                    return false;
                }
            }
        }
        
        return true;
    }
// END PATCH HSLU: Postbox
}
