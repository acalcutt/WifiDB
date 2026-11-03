-- Signing in with a linked Google, GitHub, Discord or Facebook account: lib/linked_logins.inc.php

-- One row per outside account, and the member it was linked to
CREATE TABLE user_linked_logins (
  provider varchar(20) NOT NULL,
  subject varchar(255) NOT NULL,
  username varchar(255) NOT NULL,
  email varchar(255) NULL,
  display_name varchar(255) NULL,
  created timestamp(0) NOT NULL,
  last_used timestamp(0) NULL,
  PRIMARY KEY (provider, subject)
);
CREATE INDEX user_linked_logins_username ON user_linked_logins (username);

-- A sign-in on its way through the provider, or one waiting to be linked: minutes long, and in the database so any
-- node can finish what another started
CREATE TABLE linked_login_states (
  token varchar(64) NOT NULL PRIMARY KEY,
  data text NOT NULL,
  expires timestamp(0) NOT NULL
);
CREATE INDEX linked_login_states_expires ON linked_login_states (expires);
