-- This file documents temporary indexes to add at the beginning of
-- and ILIAS 9 -> 10 update to speed up the migrations.
--
-- Copy it to the appropriate DB server and run it with:
-- mariadb -u adm_xxx -p ilias_hslu < add_indexes.sql

ALTER TABLE exc_mem_ass_status
  ADD INDEX IF NOT EXISTS idx_feedback_rcid (feedback_rcid);

ALTER TABLE il_meta_contribute
  ADD INDEX IF NOT EXISTS idx_obj_type (obj_type),
  ADD INDEX IF NOT EXISTS idx_rbac_id (rbac_id),
  ADD INDEX IF NOT EXISTS idx_obj_id (obj_id);

ALTER TABLE il_meta_description
  ADD INDEX IF NOT EXISTS idx_obj_type (obj_type),
  ADD INDEX IF NOT EXISTS idx_rbac_id (rbac_id),
  ADD INDEX IF NOT EXISTS idx_obj_id (obj_id);

ALTER TABLE il_meta_entity
  ADD INDEX IF NOT EXISTS idx_obj_type (obj_type),
  ADD INDEX IF NOT EXISTS idx_rbac_id (rbac_id),
  ADD INDEX IF NOT EXISTS idx_obj_id (obj_id);

ALTER TABLE il_meta_format
  ADD INDEX IF NOT EXISTS idx_obj_type (obj_type),
  ADD INDEX IF NOT EXISTS idx_rbac_id (rbac_id),
  ADD INDEX IF NOT EXISTS idx_obj_id (obj_id);

ALTER TABLE il_meta_general
  ADD INDEX IF NOT EXISTS idx_obj_type (obj_type),
  ADD INDEX IF NOT EXISTS idx_rbac_id (rbac_id),
  ADD INDEX IF NOT EXISTS idx_obj_id (obj_id);

ALTER TABLE il_meta_identifier
  ADD INDEX IF NOT EXISTS idx_obj_type (obj_type),
  ADD INDEX IF NOT EXISTS idx_rbac_id (rbac_id),
  ADD INDEX IF NOT EXISTS idx_obj_id (obj_id);

ALTER TABLE il_meta_keyword
  ADD INDEX IF NOT EXISTS idx_obj_type (obj_type),
  ADD INDEX IF NOT EXISTS idx_rbac_id (rbac_id),
  ADD INDEX IF NOT EXISTS idx_obj_id (obj_id);

ALTER TABLE il_meta_language
  ADD INDEX IF NOT EXISTS idx_obj_type (obj_type),
  ADD INDEX IF NOT EXISTS idx_rbac_id (rbac_id),
  ADD INDEX IF NOT EXISTS idx_obj_id (obj_id);

ALTER TABLE il_meta_lifecycle
  ADD INDEX IF NOT EXISTS idx_obj_type (obj_type),
  ADD INDEX IF NOT EXISTS idx_rbac_id (rbac_id),
  ADD INDEX IF NOT EXISTS idx_obj_id (obj_id);

ALTER TABLE il_meta_rights
  ADD INDEX IF NOT EXISTS idx_obj_type (obj_type),
  ADD INDEX IF NOT EXISTS idx_rbac_id (rbac_id),
  ADD INDEX IF NOT EXISTS idx_obj_id (obj_id);

ALTER TABLE il_meta_technical
  ADD INDEX IF NOT EXISTS idx_obj_type (obj_type),
  ADD INDEX IF NOT EXISTS idx_rbac_id (rbac_id),
  ADD INDEX IF NOT EXISTS idx_obj_id (obj_id);

ALTER TABLE il_meta_coverage
  ADD INDEX IF NOT EXISTS idx_obj_type (obj_type),
  ADD INDEX IF NOT EXISTS idx_rbac_id (rbac_id),
  ADD INDEX IF NOT EXISTS idx_obj_id (obj_id);
