UPDATE operation SET type = "widget", name = "select"
WHERE type = "push" AND subject IN ( "home_assignment", "site_assignment" ) AND name = "begin";
