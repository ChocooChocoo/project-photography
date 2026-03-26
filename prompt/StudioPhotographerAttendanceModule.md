Objective #1: Studio Photographer Attendance Module like the HR module

    1). Let's review the studio-hr module first, as u can see in the studio-hr they can have the attendance. Now I wanted that attendance flow and process for my studio-photographer.

        The related files on that process on the hr side are:
        - app\Models\StudioHR\EmployeeAttendanceModel.php
        - app\Http\Controllers\StudioHR\EmployeeAttendanceController.php
        - resources\views\studio-hr\view-attendance.blade.php
        - resources\views\layouts\studio-hr\sidebar.blade.php

    2). Now what I wanted is the same process but for studio-photographer where the photographer can do an attendance, can check only their own attendance or monitor.

    3). Create a files for the process and follow the same naming convention.

    4). Give the summary first of the process, what files are need to modify, created, or deleted (risky) and wait for my "Go" command. Tell me if I'm missing something.

Instructions that are converted to JSON for better readabilty:

{
  "objective": {
    "id": 1,
    "title": "Studio Photographer Attendance Module like the HR module",
    "description": "Create an attendance system for studio photographers based on the existing studio-hr attendance module"
  },
  "requirements": {
    "step_1": {
      "action": "review_existing_module",
      "module_name": "studio-hr",
      "feature_to_review": "attendance",
      "description": "Review the studio-hr module first, as it has attendance functionality",
      "reference_files": [
        {
          "path": "app\\Models\\StudioHR\\EmployeeAttendanceModel.php",
          "type": "model"
        },
        {
          "path": "app\\Http\\Controllers\\StudioHR\\EmployeeAttendanceController.php",
          "type": "controller"
        },
        {
          "path": "resources\\views\\studio-hr\\view-attendance.blade.php",
          "type": "view"
        },
        {
          "path": "resources\\views\\layouts\\studio-hr\\sidebar.blade.php",
          "type": "layout"
        }
      ],
      "note": "These are the related files for the attendance process on the HR side"
    },
    "step_2": {
      "action": "replicate_process_for_photographers",
      "target_module": "studio-photographer",
      "functionality_requirements": {
        "photographer_can_do_attendance": true,
        "photographer_can_check_own_attendance_only": true,
        "photographer_can_monitor_own_attendance": true
      },
      "description": "Same process but for studio-photographer where the photographer can do an attendance, can check only their own attendance or monitor"
    },
    "step_3": {
      "action": "create_new_files",
      "naming_convention": "follow_same_naming_convention",
      "description": "Create files for the process and follow the same naming convention"
    },
    "step_4": {
      "action": "provide_summary_and_wait_for_approval",
      "summary_requirements": [
        "summary of the process",
        "what files need to be modified",
        "what files need to be created",
        "what files need to be deleted (risky)"
      ],
      "wait_for_command": "Go",
      "additional_request": "Tell me if I'm missing something"
    }
  },
  "workflow": {
    "sequence": [
      "review_existing_module",
      "replicate_process_for_photographers",
      "create_new_files",
      "provide_summary_and_wait_for_approval"
    ],
    "approval_required": true,
    "approval_command": "Go"
  }
}