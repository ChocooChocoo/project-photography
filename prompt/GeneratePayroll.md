Objective #1: Generate Employee Payroll

    Database Schema: DatabaseStudio.sql

    1). Now here in the studio-hr side, I've created a blank blade for the page of payroll generation. In this page this is where the hr will generate the payroll. But always check for their role if they had an access for this module.

    2). The generation of the employees payroll will be based on their attendance if regular employee and for studio-photographer it will be based on the attendance and booking.

    3). When generate, check the database table "tbl_employee_payroll" because this is where the employees payroll settings setup.

    4). I wanted here that the HR can generate a payroll for bulk. Since the regular employee and studio-photographer is different the bulk generation will be filtered first like choose employee then it has a checkbox.

    5). Now create a table for the generated payroll or "sahod" of the employee. Use only artisan migration for creating a table and models and other files.

    6). Before you execute and provide the changes, give the summary of the process first and wait for the "Go" command.


Objective #2: UIUX and List of Generated Payroll

    1). Now since we can already generate a payroll for every employee, now in that page, make the page has a table so I can switch for Generate Payroll | View List of Generated Payroll tab.

    2). In the Generate Payroll, this is where we can generate a payroll, in the View List of Generated Payroll tab, this is the table will display all the generated payroll. Add an action button for view then it will show the modal for details. Use the modal template for that (ModalTemplate.php) this is the modal template file I have.

    3). Use this tab layout below. Make sure that this tab is inside the main card for better UIUX.

        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a href="#generate-payroll" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                    Generate Payroll
                </a>
            </li>
            <li class="nav-item">
                <a href="#generated-payroll" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                    View Generated Payrolls
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane" id="generate-payroll">
                {{-- CONTENT --}}
            </div>
            <div class="tab-pane show active" id="generated-payroll">
                {{-- CONTENT --}}
            </div>
        </div>