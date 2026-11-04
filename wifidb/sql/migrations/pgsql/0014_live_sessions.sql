-- Live API version 2 (lib/live_session.inc.php, api/live.php): a session belongs to the account that started it, with
-- the title and notes its import gets once it's finished
ALTER TABLE live_titles ADD COLUMN started_at timestamp(0) NULL, ADD COLUMN completed_at timestamp(0) NULL;
CREATE INDEX live_users_session ON live_users (session_id);
