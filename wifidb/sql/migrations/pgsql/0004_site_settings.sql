-- Site settings an administrator changes on admin/settings.php (lib/site_settings.inc.php). A setting with no row here
-- comes from config.inc.php.

CREATE TABLE site_settings (
  name varchar(64) NOT NULL PRIMARY KEY,
  value text NULL,
  updated_by varchar(255) NULL,
  updated_at timestamp(0) NULL
);
