@extends('layouts.owner.app')
@section('title', 'Create Studio Employee')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Create Studio Employee</h4>
                        </div>
                        <div class="card-body">
                            <form class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="form-group mb-3">
                                        <h4 class="card-title text-primary mb-3">Studio Selection</h4>
                                        <label class="form-label">Select Studio</label>
                                        <select class="form-select" required>
                                            <option value="">Select Studio</option>
                                            <option value="">Studio Name</option>
                                            <option value="">Studio Name</option>
                                            <option value="">Studio Name</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a studio.
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <h4 class="card-title text-primary mb-3">Employee Information</h4>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" class="form-control" name="last_name" placeholder="Enter last name" required>
                                                <div class="invalid-feedback">
                                                    Please enter a valid last name.
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">First Name</label>
                                                <input type="text" class="form-control" name="first_name" placeholder="Enter first name" required>
                                                <div class="invalid-feedback">
                                                    Please enter a valid first name.
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Middle Name</label>
                                                <input type="text" class="form-control" name="middle_name" placeholder="Enter middle name" required>
                                                <div class="invalid-feedback">
                                                    Please enter a valid middle name.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <div class="row g-3">
                                            <div class="col-md-12">
                                                <label class="form-label">Email Address</label>
                                                <input type="email" class="form-control" name="email" placeholder="Enter email address" required>
                                                <div class="invalid-feedback">
                                                    Please enter a valid email address.
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label">Contact Number</label>
                                                <input type="text" class="form-control" name="mobile_number" placeholder="Enter contact number" required data-toggle="input-mask" data-mask-format="+(63)000 000 0000">
                                                <div class="invalid-feedback">
                                                    Please enter a valid contact number.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <h4 class="card-title text-primary mb-3">Studio Position</h4>
                                        <div class="row g-3">
                                            <div class="col-md-12">
                                                <label class="form-label">Employee's Role</label>
                                                <select class="form-select" required>
                                                    <option value="">Select Role</option>
                                                    <option value="studio-hr">Human Resource</option>
                                                    <option value="studio-finance">Finance</option>
                                                    <option value="studio-photographer">Photographer</option>
                                                </select>
                                                <div class="invalid-feedback">
                                                    Please select a role.
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label">Role Type</label>
                                                <select class="form-select" required>
                                                    <option value="">Select Role Type</option>
                                                    <option value="Manager">Manager</option>
                                                    <option value="Staff">Staff</option>
                                                    <option value="Photographer">Photographer</option>
                                                </select>
                                                <div class="invalid-feedback">
                                                    Please select a role type.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <h4 class="card-title text-primary mb-3">Role-Based Access Control (RBAC)</h4>
                                        <div class="card border">
                                            <div class="card-body">
                                                <p class="text-muted mb-4">
                                                    <i class="ti ti-shield-lock me-1"></i>
                                                    Configure granular permissions for this role. Each toggle controls specific CRUD operations.
                                                </p>
                                                
                                                <div class="row g-4">
                                                    <div class="col-md-3">
                                                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                                                            <div>
                                                                <i class="ti ti-plus-circle text-success me-2"></i>
                                                                <span class="fw-medium">Create</span>
                                                            </div>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch" id="rbacCreate">
                                                                <label class="form-check-label" for="rbacCreate"></label>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted d-block mt-2 ps-1">
                                                            Create: Allows user to add new records, upload files, and create new entries in the system
                                                        </small>
                                                    </div>
                                                    
                                                    <div class="col-md-3">
                                                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                                                            <div>
                                                                <i class="ti ti-eye-fill text-info me-2"></i>
                                                                <span class="fw-medium">Read</span>
                                                            </div>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch" id="rbacRead">
                                                                <label class="form-check-label" for="rbacRead"></label>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted d-block mt-2 ps-1">
                                                            Read: Enables viewing, searching, and accessing existing records and information
                                                        </small>
                                                    </div>
                                                    
                                                    <div class="col-md-3">
                                                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                                                            <div>
                                                                <i class="ti ti-pencil-square text-warning me-2"></i>
                                                                <span class="fw-medium">Update</span>
                                                            </div>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch" id="rbacUpdate">
                                                                <label class="form-check-label" for="rbacUpdate"></label>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted d-block mt-2 ps-1">
                                                            Update: Allows modification, editing, and updating of existing records and information
                                                        </small>
                                                    </div>
                                                    
                                                    <div class="col-md-3">
                                                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                                                            <div>
                                                                <i class="ti ti-trash-fill text-danger me-2"></i>
                                                                <span class="fw-medium">Delete</span>
                                                            </div>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch" id="rbacDelete">
                                                                <label class="form-check-label" for="rbacDelete"></label>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted d-block mt-2 ps-1">
                                                            Delete: Grants ability to remove, archive, or permanently delete records from the system
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <h4 class="card-title text-primary mb-3">Employee Schedule</h4>
                                        <div class="row g-4 mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Start Time</label>
                                                <input type="time" class="form-control" name="start_time" value="09:00" required>
                                                <small class="text-muted">
                                                    <i class="ti ti-info-circle me-1"></i>
                                                    Regular work start time
                                                </small>
                                                <div class="invalid-feedback">
                                                    Please select a start time.
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label class="form-label">End Time</label>
                                                <input type="time" class="form-control" name="end_time" value="18:00" required>
                                                <small class="text-muted">
                                                    <i class="ti ti-info-circle me-1"></i>
                                                    Regular work end time
                                                </small>
                                                <div class="invalid-feedback">
                                                    Please select an end time.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <label class="form-label mb-2">Select Operating Days</label>
                                            <div class="mb-2">
                                                <div class="btn-group w-100 mb-1" role="group" aria-label="Weekday toggle button group" id="operatingDaysGroup">
                                                    <input type="checkbox" class="btn-check" id="btnMonday" name="operating_days[]" value="monday" autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="btnMonday">Monday</label>

                                                    <input type="checkbox" class="btn-check" id="btnTuesday" name="operating_days[]" value="tuesday" autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="btnTuesday">Tuesday</label>

                                                    <input type="checkbox" class="btn-check" id="btnWednesday" name="operating_days[]" value="wednesday" autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="btnWednesday">Wednesday</label>

                                                    <input type="checkbox" class="btn-check" id="btnThursday" name="operating_days[]" value="thursday" autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="btnThursday">Thursday</label>

                                                    <input type="checkbox" class="btn-check" id="btnFriday" name="operating_days[]" value="friday" autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="btnFriday">Friday</label>

                                                    <input type="checkbox" class="btn-check" id="btnSaturday" name="operating_days[]" value="saturday" autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="btnSaturday">Saturday</label>

                                                    <input type="checkbox" class="btn-check" id="btnSunday" name="operating_days[]" value="sunday" autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="btnSunday">Sunday</label>
                                                </div>
                                                <small class="d-block text-muted">Check which days the employee will work</small>
                                                <div class="invalid-feedback" id="operating_days_error">Please select at least one day.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-start">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">Submit Employee</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
