SET NAMES utf8mb4;

ALTER TABLE projects ADD COLUMN IF NOT EXISTS project_attachment_path VARCHAR(255) NULL AFTER description_bn;
ALTER TABLE projects ADD COLUMN IF NOT EXISTS project_attachment_name VARCHAR(255) NULL AFTER project_attachment_path;
