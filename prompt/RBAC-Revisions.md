Database File with Whole Schema: DatabaseStudio.sql

Objective #1: Roles Management in Owner Side
    
    1). In the roles management, I wanted to add new field for is_system. It's either switch or checkbox. Whether role is system-protected

    2). Add new column name (is_system) in the "tbl_roles". Use artisan migrate

    3). Before to continue in execution and proceeding to Objective #2, give the summary and the files need to modify and wait for the "Go!" command. 

Objective #2: Permissions in Owner Side

    1). Revise the creation of the permissions. Add new field for (resource, action and permission_string).

        resouce:            Resource name (e.g., "user", "invoice")
        action:             Action name (e.g., "create", "read")\
        permission_string:  Combined string "resource:action" (UNIQUE)

    2). In the field of permission_string, it is readonly, then if I type resource and action, it will automatically displays realtime. for example. resource = invoice and action = create, the permission_string would be invoice:create.
    
    3). modify columns in the "tbl_permissions" and add those new fields needed. Use artisan migrate.