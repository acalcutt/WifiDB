-- the first migration
CREATE TABLE widgets (id INTEGER PRIMARY KEY, name TEXT);   -- a trailing comment; with a semicolon
INSERT INTO widgets (name) VALUES ('one;two');
