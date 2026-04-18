Here’s an enhanced version of your instruction set with cleaner wording, clearer requirements, and more professional structure:

---

# Objective: Refactor the UI/UX of the Procurement Process

## 1) Redesign the Procurement Dashboard Cards

Redesign the procurement dashboard cards into a more organized, clean, and visually engaging widget layout.

### Requirements:

* Use the provided widget template as the base structure.
* Apply **appropriate icons and contextual colors** for each procurement-related widget.
* Ensure the widgets are relevant to the procurement process.
* Use **pure widget code only**.

* **Do not use custom CSS**.
* Keep the layout responsive and consistent with the existing UI framework.

### Widget Template:

```html
<div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3 align-items-center">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="avatar avatar-lg flex-shrink-0">
                        <span class="avatar-title bg-info-subtle text-info rounded fs-24">
                            <i class="ti ti-clipboard-list"></i>
                        </span>
                    </div>
                    <div class="text-end">
                        <h4 class="mb-0">28</h4>
                        <p class="mb-0 text-muted">Active Projects</p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted fs-xs fw-semibold">PROGRESS</span>
                        <span class="text-muted">75%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: 75%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="avatar avatar-lg flex-shrink-0">
                        <span class="avatar-title bg-success-subtle text-success rounded fs-24">
                            <i class="ti ti-checklist"></i>
                        </span>
                    </div>
                    <div class="text-end">
                        <h4 class="mb-0">124</h4>
                        <p class="mb-0 text-muted">Tasks Completed</p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted fs-xs fw-semibold">TARGET</span>
                        <span class="text-muted">88%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: 88%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="avatar avatar-lg flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle text-warning rounded fs-24">
                            <i class="ti ti-clock-hour-4"></i>
                        </span>
                    </div>
                    <div class="text-end">
                        <h4 class="mb-0">16</h4>
                        <p class="mb-0 text-muted">Pending Tasks</p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted fs-xs fw-semibold">DEADLINES</span>
                        <span class="text-muted">42%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" style="width: 42%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="avatar avatar-lg flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle text-danger rounded fs-24">
                            <i class="ti ti-user-cog"></i>
                        </span>
                    </div>
                    <div class="text-end">
                        <h4 class="mb-0">9</h4>
                        <p class="mb-0 text-muted">Project Managers</p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted fs-xs fw-semibold">ALLOCATED</span>
                        <span class="text-muted">100%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-danger" style="width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

---

## 2) Redesign the Timeline Section

Update the procurement timeline using the provided timeline component structure.

### Requirements:

* Use the template below as the standard layout.
* Replace sample events with **procurement-related activities**
* Use only **subtle background styles** for timeline icons.
* **Do not use filled badge colors**.
* Make the timeline clear, professional, and aligned with the procurement workflow.

### Timeline Template:

```html
<div class="col-xxl-6">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Timeline with Icons</h4>
        </div>

        <div class="card-body">

            <div class="timeline timeline-icon-based">
                <!-- Event 1 -->
                <div class="timeline-item d-flex align-items-stretch">
                    <div class="timeline-time pe-3 text-muted">5 mins ago</div>
                    <div class="timeline-dot text-bg-primary">
                        <i class="ti ti-bug fs-xl"></i>
                    </div>
                    <div class="timeline-content ps-3 pb-4">
                        <h5 class="mb-1">Bug Fix Deployed</h5>
                        <p class="mb-1 text-muted">Resolved a critical login issue affecting mobile users.</p>
                        <span class="text-primary fw-semibold">By Marcus Bell</span>
                    </div>
                </div>
            
                <!-- Event 2 -->
                <div class="timeline-item d-flex align-items-stretch">
                    <div class="timeline-time pe-3 text-muted">Today, 9:00 AM</div>
                    <div class="timeline-dot bg-danger-subtle">
                        <i class="ti ti-phone-call fs-xl text-danger"></i>
                    </div>
                    <div class="timeline-content ps-3 pb-4">
                        <h5 class="mb-1">Marketing Strategy Call</h5>
                        <p class="mb-1 text-muted">Outlined Q2 goals and content plan for the product launch campaign.</p>
                        <span class="text-primary fw-semibold">By Emily Davis</span>
                    </div>
                </div>
            
                <!-- Event 3 -->
                <div class="timeline-item d-flex align-items-stretch">
                    <div class="timeline-time pe-3 text-muted">Yesterday, 4:45 PM</div>
                    <div class="timeline-dot text-bg-warning">
                        <i class="ti ti-layers-subtract fs-xl"></i>
                    </div>
                    <div class="timeline-content ps-3 pb-4">
                        <h5 class="mb-1">Feature Planning Session</h5>
                        <p class="mb-1 text-muted">Prioritized new features for the upcoming release based on user feedback.</p>
                        <span class="text-primary fw-semibold">By Daniel Kim</span>
                    </div>
                </div>
            
                <!-- Event 4 -->
                <div class="timeline-item d-flex align-items-stretch">
                    <div class="timeline-time pe-3 text-muted">Tuesday, 11:30 AM</div>
                    <div class="timeline-dot bg-info-subtle">
                        <i class="ti ti-layout-dashboard fs-xl text-info"></i>
                    </div>
                    <div class="timeline-content ps-3 pb-4">
                        <h5 class="mb-1">UI Enhancements Pushed</h5>
                        <p class="mb-1 text-muted">Improved dashboard responsiveness and added dark mode support.</p>
                        <span class="text-primary fw-semibold">By Sofia Martinez</span>
                    </div>
                </div>
            
                <!-- Event 5 -->
                <div class="timeline-item d-flex align-items-stretch">
                    <div class="timeline-time pe-3 text-muted">Last Thursday, 2:20 PM</div>
                    <div class="timeline-dot text-bg-secondary">
                        <i class="ti ti-shield-lock fs-xl"></i>
                    </div>
                    <div class="timeline-content ps-3">
                        <h5 class="mb-1">Security Audit Completed</h5>
                        <p class="mb-1 text-muted">Reviewed backend API endpoints and applied new encryption standards.</p>
                        <span class="text-primary fw-semibold">By Jonathan Lee</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
```

---

## 3) Redesign the Invoice UI/UX

Improve the invoice interface by using a **payroll or payslip-inspired design layout**.

### Requirements:

* Use a layout/style similar to a **payroll statement** or **employee payslip**.
* The invoice should look structured, readable, and professional.
* Organize the content clearly into sections.
* The design should emphasize clarity, hierarchy, and easy scanning.
* Keep the UI aligned with the procurement system theme.
* Focus on improving both **layout** and **visual presentation**.

---

## Expected Output

* Refactored procurement dashboard widgets
* Procurement-focused timeline UI
* Invoice page redesigned with a payroll/payslip-inspired layout
* Clean, modern, and consistent UI/UX across all procurement modules
* No custom CSS, only framework-supported classes and components

--
