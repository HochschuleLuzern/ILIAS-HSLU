-- This file is to remove the temporary indexes after
-- the ILIAS 9 -> 10 update is done.
--
-- Copy it to the appropriate DB server and run it with:
-- mariadb -u adm_xxx -p ilias_hslu < drop_indexes.sql

ALTER TABLE exc_mem_ass_status
  DROP INDEX IF EXISTS idx_feedback_rcid;

ALTER TABLE il_meta_contribute
  DROP INDEX IF EXISTS idx_obj_type,
  DROP INDEX IF EXISTS idx_rbac_id,
  DROP INDEX IF EXISTS idx_obj_id;

ALTER TABLE il_meta_description
  DROP INDEX IF EXISTS idx_obj_type,
  DROP INDEX IF EXISTS idx_rbac_id,
  DROP INDEX IF EXISTS idx_obj_id;

ALTER TABLE il_meta_entity
  DROP INDEX IF EXISTS idx_obj_type,
  DROP INDEX IF EXISTS idx_rbac_id,
  DROP INDEX IF EXISTS idx_obj_id;

ALTER TABLE il_meta_format
  DROP INDEX IF EXISTS idx_obj_type,
  DROP INDEX IF EXISTS idx_rbac_id,
  DROP INDEX IF EXISTS idx_obj_id;

ALTER TABLE il_meta_general
  DROP INDEX IF EXISTS idx_obj_type,
  DROP INDEX IF EXISTS idx_rbac_id,
  DROP INDEX IF EXISTS idx_obj_id;

ALTER TABLE il_meta_identifier
  DROP INDEX IF EXISTS idx_obj_type,
  DROP INDEX IF EXISTS idx_rbac_id,
  DROP INDEX IF EXISTS idx_obj_id;

ALTER TABLE il_meta_keyword
  DROP INDEX IF EXISTS idx_obj_type,
  DROP INDEX IF EXISTS idx_rbac_id,
  DROP INDEX IF EXISTS idx_obj_id;

ALTER TABLE il_meta_language
  DROP INDEX IF EXISTS idx_obj_type,
  DROP INDEX IF EXISTS idx_rbac_id,
  DROP INDEX IF EXISTS idx_obj_id;

ALTER TABLE il_meta_lifecycle
  DROP INDEX IF EXISTS idx_obj_type,
  DROP INDEX IF EXISTS idx_rbac_id,
  DROP INDEX IF EXISTS idx_obj_id;

ALTER TABLE il_meta_rights
  DROP INDEX IF EXISTS idx_obj_type,
  DROP INDEX IF EXISTS idx_rbac_id,
  DROP INDEX IF EXISTS idx_obj_id;

ALTER TABLE il_meta_technical
  DROP INDEX IF EXISTS idx_obj_type,
  DROP INDEX IF EXISTS idx_rbac_id,
  DROP INDEX IF EXISTS idx_obj_id;

ALTER TABLE il_meta_coverage
  DROP INDEX IF EXISTS idx_obj_type,
  DROP INDEX IF EXISTS idx_rbac_id,
  DROP INDEX IF EXISTS idx_obj_id;
