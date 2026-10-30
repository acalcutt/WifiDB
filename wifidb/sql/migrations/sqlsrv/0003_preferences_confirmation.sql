-- Saving preferences can ask for a second factor (user_info.mfa_prefs): when it was last confirmed, per session. Kept apart from admin_until so neither counts for the other: lib/two_factor.inc.php
ALTER TABLE [dbo].[user_login_hashes] ADD [prefs_until] [datetime2](0) NULL
GO
