Objective #1: Finance Module creation

    1). Currently on this project, I already have a module for Admin, Owner, Photographer and HR but the Finance is not already implemented yet.

    2). Create a file for the modules of the finance.

        - for controllers make for Finance
        - for views make for studio-finance
        - make a middleware for finance
        - add routes for finance below the studio-hr routes.

    3). For now just create a page that redirects to the dashboard.

    4). For the layouts create an app,sidebar,theme,topbar blades for finance.

Instructions that are converted to JSON for better readabilty:

{
  "objective": {
    "id": 1,
    "title": "Finance Module creation",
    "description": "Create a new Finance module for the project"
  },
  "current_state": {
    "existing_modules": [
      "Admin",
      "Owner",
      "Photographer",
      "HR"
    ],
    "missing_module": "Finance",
    "implementation_status": "not already implemented yet"
  },
  "requirements": {
    "step_1": {
      "action": "acknowledge_current_state",
      "description": "Currently on this project, I already have a module for Admin, Owner, Photographer and HR but the Finance is not already implemented yet"
    },
    "step_2": {
      "action": "create_files_for_finance_module",
      "description": "Create a file for the modules of the finance",
      "components": {
        "controllers": {
          "directory_name": "Finance",
          "description": "for controllers make for Finance"
        },
        "views": {
          "directory_name": "studio-finance",
          "description": "for views make for studio-finance"
        },
        "middleware": {
          "create": true,
          "description": "make a middleware for finance"
        },
        "routes": {
          "location": "below the studio-hr routes",
          "description": "add routes for finance below the studio-hr routes"
        }
      }
    },
    "step_3": {
      "action": "create_initial_page",
      "functionality": "page that redirects to the dashboard",
      "description": "For now just create a page that redirects to the dashboard"
    },
    "step_4": {
      "action": "create_layout_blades",
      "description": "For the layouts create an app,sidebar,theme,topbar blades for finance",
      "layout_files": [
        "app",
        "sidebar",
        "theme",
        "topbar"
      ],
      "target_module": "finance"
    }
  },
  "workflow": {
    "sequence": [
      "acknowledge_current_state",
      "create_files_for_finance_module",
      "create_initial_page",
      "create_layout_blades"
    ]
  }
}