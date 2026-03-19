@extends('layouts.owner.app')
@section('title', 'Chatbot Configuration')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-md-12">

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Chatbot Configuration</h4>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs mb-3" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a href="#general_settings" data-bs-toggle="tab" class="nav-link active" role="tab">
                                        <i class="ti ti-settings me-1"></i>General Settings
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#manage_intents" data-bs-toggle="tab" class="nav-link" role="tab">
                                        <i class="ti ti-message-circle me-1"></i>Manage Intents
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#conversation_history" data-bs-toggle="tab" class="nav-link" role="tab">
                                        <i class="ti ti-history me-1"></i>Conversation History
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                {{-- GENERAL SETTINGS TAB --}}
                                <div class="tab-pane active show" id="general_settings" role="tabpanel">
                                    <form id="chatbotConfigForm">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="config_name" class="form-label">Configuration Name</label>
                                                <input type="text" class="form-control" id="config_name" name="config_name"
                                                    placeholder="e.g., Main Support Bot">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="bot_name" class="form-label">Bot Name</label>
                                                <input type="text" class="form-control" id="bot_name" name="bot_name"
                                                    placeholder="e.g., Support Assistant">
                                            </div>
                                            <div class="col-md-12">
                                                <label for="welcome_message" class="form-label">Welcome Message</label>
                                                <textarea class="form-control" id="welcome_message" name="welcome_message"
                                                        rows="3" placeholder="Enter the message users see when they start a chat"></textarea>
                                                <small class="text-muted">This message will be shown when a new conversation starts.</small>
                                            </div>
                                            <div class="col-md-12">
                                                <label for="fallback_message" class="form-label">Fallback Message</label>
                                                <textarea class="form-control" id="fallback_message" name="fallback_message"
                                                        rows="3" placeholder="Enter the message when no intent is matched"></textarea>
                                                <small class="text-muted">This message is shown when the bot doesn't understand the user's input.</small>
                                            </div>
                                            <div class="col-md-12">
                                                <label for="bot_avatar" class="form-label">Bot Avatar URL (Optional)</label>
                                                <input type="text" class="form-control" id="bot_avatar" name="bot_avatar"
                                                    placeholder="https://example.com/avatar.jpg">
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input type="checkbox" class="form-check-input" id="chatbotToggleSwitch">
                                                    <label class="form-check-label" for="chatbotToggleSwitch">Enable Chatbot</label>
                                                    <input type="checkbox" class="form-check-input" id="chatbotStatusSwitch">
                                                    <label class="form-check-label" for="chatbotStatusSwitch" id="statusLabel">Loading...</label>
                                                </div>
                                            </div>
                                            <div class="col-12 mt-4">
                                                <button type="submit" class="btn btn-primary" id="saveConfigBtn">
                                                    <i class="ti ti-device-floppy me-1"></i> Save Settings
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                {{-- MANAGE INTENTS TAB --}}
                                <div class="tab-pane" id="manage_intents" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">Chatbot Intents</h5>
                                        <button type="button" class="btn btn-primary btn-sm" id="addIntentBtn">
                                            <i class="ti ti-plus me-1"></i> Add New Intent
                                        </button>
                                    </div>

                                    <div class="alert alert-info mb-3">
                                        <i class="ti ti-info-circle me-1"></i>
                                        Intents are patterns that trigger specific responses. Add keywords that users might type to get the appropriate response.
                                    </div>

                                    <div class="table-responsive">
                                        <table id="intentsTable" class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                            <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                                <tr>
                                                    <th>Intent Name</th>
                                                    <th>Trigger Keywords</th>
                                                    <th>Response Preview</th>
                                                    <th>Priority</th>
                                                    <th>Status</th>
                                                    <th>Quick Replies</th>
                                                    <th class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="intentsTableBody">
                                                <tr>
                                                    <td colspan="7" class="text-center py-4">
                                                        <div class="spinner-border text-primary" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {{-- CONVERSATION HISTORY TAB --}}
                                <div class="tab-pane" id="conversation_history" role="tabpanel">
                                    <h5 class="mb-3">Conversation History</h5>

                                    <div class="table-responsive">
                                        <table class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0" id="conversationsTable">
                                            <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                                <tr>
                                                    <th>Session ID</th>
                                                    <th>User</th>
                                                    <th>Started</th>
                                                    <th>Ended</th>
                                                    <th>Messages</th>
                                                    <th>Status</th>
                                                    <th class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="conversationsTableBody">
                                                <tr>
                                                    <td colspan="7" class="text-center py-4">
                                                        <div class="spinner-border text-primary" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="d-flex justify-content-end mt-3" id="conversationsPagination"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Intent Modal --}}
    <div class="modal fade" id="intentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="intentModalTitle">Add New Intent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="intentForm">
                    <input type="hidden" id="intent_id" name="intent_id">
                    <input type="hidden" id="config_id" name="config_id" value="">

                    <div class="modal-body">
                        <div class="alert alert-info mb-3" id="configInfoBanner" style="display: none;">
                            <i class="ti ti-info-circle me-1"></i>
                            <span id="configInfoText">Adding intent to: <strong>Loading...</strong></span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="intent_name" class="form-label">Intent Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="intent_name" name="intent_name" required>
                                <small class="text-muted">A descriptive name for this intent (e.g., "Pricing Inquiry")</small>
                            </div>

                            <div class="col-md-3">
                                <label for="priority" class="form-label">Priority</label>
                                <input type="number" class="form-control" id="priority" name="priority" min="0" max="100" value="0">
                                <small class="text-muted">Higher priority matches first</small>
                            </div>

                            <div class="col-md-3">
                                <label for="response_type" class="form-label">Response Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="response_type" name="response_type" required>
                                    <option value="text">Text Only</option>
                                    <option value="quick_reply">Text + Quick Replies</option>
                                    <option value="image">Image</option>
                                </select>
                            </div>

                            <div class="col-12" id="imageUrlField" style="display: none;">
                                <label for="image_url" class="form-label">Image URL</label>
                                <input type="text" class="form-control" id="image_url" name="image_url" placeholder="https://example.com/image.jpg">
                            </div>

                            <div class="col-12">
                                <label for="trigger_keywords" class="form-label">Trigger Keywords <span class="text-danger">*</span></label>
                                <div id="keywordsContainer">
                                    <div class="input-group mb-2 keyword-row">
                                        <input type="text" class="form-control" name="trigger_keywords[]" placeholder="Enter keyword" required>
                                        <button type="button" class="btn btn-outline-success add-keyword">
                                            <i class="ti ti-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Add keywords that will trigger this intent (e.g., "price", "cost", "how much")</small>
                            </div>

                            <div class="col-12">
                                <label for="response_text" class="form-label">Response Text <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="response_text" name="response_text" rows="4" required></textarea>
                            </div>

                            <div class="col-12" id="quickRepliesSection" style="display: none;">
                                <label class="form-label">Quick Replies</label>
                                <div id="quickRepliesContainer">
                                    <!-- Quick replies will be added here -->
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addQuickReplyBtn">
                                    <i class="ti ti-plus me-1"></i> Add Quick Reply
                                </button>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveIntentBtn">Save Intent</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- View Conversation Modal --}}
    <div class="modal fade" id="viewConversationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Conversation Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="chat-history" id="conversationMessages" style="max-height: 400px; overflow-y: auto;">
                        <!-- Messages will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            // ==================== INITIALIZATION ====================
            
            // Load chatbot config
            loadChatbotConfig();
            
            // Load intents when tab is shown
            $('a[href="#manage_intents"]').on('shown.bs.tab', function() {
                loadIntents();
            });
            
            // Load conversations when tab is shown
            $('a[href="#conversation_history"]').on('shown.bs.tab', function() {
                loadConversations();
            });

            // ==================== GENERAL SETTINGS ====================
            
            // Load chatbot config
            function loadChatbotConfig() {
                $.ajax({
                    url: '{{ route("chatbot.config.get") }}',
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            if (response.config) {
                                $('#config_name').val(response.config.config_name || '');
                                $('#bot_name').val(response.config.bot_name || '');
                                $('#welcome_message').val(response.config.welcome_message || '');
                                $('#fallback_message').val(response.config.fallback_message || '');
                                $('#bot_avatar').val(response.config.bot_avatar || '');
                                
                                // Update status switch
                                $('#chatbotStatusSwitch').prop('checked', response.config.is_active);
                                $('#statusLabel').text(response.config.is_active ? 'Active' : 'Inactive');
                            } else {
                                // Set defaults
                                $('#bot_name').val('Support Assistant');
                                $('#welcome_message').val('Hello! How can I assist you today?');
                                $('#fallback_message').val('I apologize, but I don\'t understand. Please contact our support team for assistance.');
                                $('#chatbotStatusSwitch').prop('checked', true);
                                $('#statusLabel').text('Active');
                            }
                        } else {
                            Swal.fire('Error', response.message || 'Failed to load configuration', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to load chatbot configuration', 'error');
                    }
                });
            }

            // Save config form
            $('#chatbotConfigForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    config_name: $('#config_name').val(),
                    bot_name: $('#bot_name').val(),
                    welcome_message: $('#welcome_message').val(),
                    fallback_message: $('#fallback_message').val(),
                    bot_avatar: $('#bot_avatar').val(),
                    is_active: $('#chatbotStatusSwitch').is(':checked')
                };
                
                $('#saveConfigBtn').prop('disabled', true).html('<i class="ti ti-loader spinner me-1"></i> Saving...');
                
                $.ajax({
                    url: '{{ route("chatbot.config.save") }}',
                    type: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Failed to save configuration', 'error');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Failed to save configuration';
                        Swal.fire('Error', message, 'error');
                    },
                    complete: function() {
                        $('#saveConfigBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save Settings');
                    }
                });
            });

            // Toggle chatbot status
            $('#chatbotStatusSwitch').on('change', function() {
                const isActive = $(this).is(':checked');
                
                $.ajax({
                    url: '{{ route("chatbot.config.toggle") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#statusLabel').text(response.is_active ? 'Active' : 'Inactive');
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            // Revert switch on error
                            $(this).prop('checked', !isActive);
                            Swal.fire('Error', response.message || 'Failed to update status', 'error');
                        }
                    },
                    error: function() {
                        // Revert switch on error
                        $(this).prop('checked', !isActive);
                        Swal.fire('Error', 'Failed to update chatbot status', 'error');
                    }
                });
            });

            // ==================== INTENT MANAGEMENT ====================
            
            // Load intents
            function loadIntents() {
                $('#intentsTableBody').html(`
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                `);
                
                $.ajax({
                    url: '{{ route("chatbot.intents.get") }}',
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            if (!response.config) {
                                // No config found
                                $('#intentsTableBody').html(`
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="ti ti-settings-off fs-1 text-warning d-block mb-2"></i>
                                            <h5>No Chatbot Configuration Found</h5>
                                            <p class="text-muted">Please go to <strong>Chatbot Settings</strong> tab and save your configuration first.</p>
                                            <a href="{{ route('chatbot.config') }}" class="btn btn-primary mt-2">
                                                <i class="ti ti-settings me-1"></i> Go to Settings
                                            </a>
                                        </td>
                                    </tr>
                                `);
                            } else {
                                renderIntentsTable(response.intents);
                            }
                        } else {
                            $('#intentsTableBody').html(`
                                <tr>
                                    <td colspan="7" class="text-center text-danger py-4">
                                        ${response.message || 'Failed to load intents'}
                                    </td>
                                </tr>
                            `);
                        }
                    },
                    error: function() {
                        $('#intentsTableBody').html(`
                            <tr>
                                <td colspan="7" class="text-center text-danger py-4">
                                    Failed to load intents. Please try again.
                                </td>
                            </tr>
                        `);
                    }
                });
            }

            // Render intents table
            function renderIntentsTable(intents) {
                if (!intents || intents.length === 0) {
                    $('#intentsTableBody').html(`
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="ti ti-message-circle-off fs-1 text-muted d-block mb-2"></i>
                                No intents found. Click "Add New Intent" to create your first intent.
                            </td>
                        </tr>
                    `);
                    return;
                }
                
                let html = '';
                
                intents.forEach(intent => {
                    const keywords = intent.trigger_keywords ? intent.trigger_keywords.slice(0, 3).join(', ') : '';
                    const keywordCount = intent.trigger_keywords ? intent.trigger_keywords.length : 0;
                    const moreText = keywordCount > 3 ? ` +${keywordCount - 3} more` : '';
                    
                    const quickReplyCount = intent.quick_replies ? intent.quick_replies.length : 0;
                    const quickReplyBadge = quickReplyCount > 0 
                        ? `<span class="badge bg-info">${quickReplyCount} replies</span>` 
                        : '<span class="badge bg-secondary">None</span>';
                    
                    const statusBadge = intent.is_active 
                        ? '<span class="badge bg-success">Active</span>' 
                        : '<span class="badge bg-secondary">Inactive</span>';
                    
                    const responsePreview = intent.response_text.length > 50 
                        ? intent.response_text.substring(0, 50) + '...' 
                        : intent.response_text;
                    
                    html += `
                        <tr data-id="${intent.id}">
                            <td><strong>${escapeHtml(intent.intent_name)}</strong></td>
                            <td>
                                <span class="small">${escapeHtml(keywords)}${moreText}</span>
                            </td>
                            <td><span class="small">${escapeHtml(responsePreview)}</span></td>
                            <td><span class="badge bg-info">${intent.priority || 0}</span></td>
                            <td>${statusBadge}</td>
                            <td>${quickReplyBadge}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary edit-intent" data-id="${intent.id}" title="Edit">
                                    <i class="ti ti-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success toggle-intent" data-id="${intent.id}" data-active="${intent.is_active}" title="${intent.is_active ? 'Deactivate' : 'Activate'}">
                                    <i class="ti ti-${intent.is_active ? 'eye-off' : 'eye'}"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-intent" data-id="${intent.id}" title="Delete">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                $('#intentsTableBody').html(html);
            }

            // Helper to escape HTML
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Show response type fields
            $('#response_type').on('change', function() {
                const type = $(this).val();
                
                if (type === 'image') {
                    $('#imageUrlField').show();
                    $('#quickRepliesSection').hide();
                } else if (type === 'quick_reply') {
                    $('#imageUrlField').hide();
                    $('#quickRepliesSection').show();
                } else {
                    $('#imageUrlField').hide();
                    $('#quickRepliesSection').hide();
                }
            });

            // Add keyword row
            $(document).on('click', '.add-keyword', function() {
                const row = `
                    <div class="input-group mb-2 keyword-row">
                        <input type="text" class="form-control" name="trigger_keywords[]" placeholder="Enter keyword" required>
                        <button type="button" class="btn btn-outline-danger remove-keyword">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                `;
                $('#keywordsContainer').append(row);
            });

            // Remove keyword row
            $(document).on('click', '.remove-keyword', function() {
                if ($('.keyword-row').length > 1) {
                    $(this).closest('.keyword-row').remove();
                }
            });

            // Add quick reply
            let quickReplyCount = 0;
            $('#addQuickReplyBtn').on('click', function() {
                const replyHtml = `
                    <div class="card mb-2 quick-reply-card" data-index="${quickReplyCount}">
                        <div class="card-body p-2">
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <input type="text" class="form-control form-control-sm" name="quick_replies[${quickReplyCount}][reply_text]" placeholder="Reply text" required>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select form-select-sm" name="quick_replies[${quickReplyCount}][action_type]">
                                        <option value="trigger_intent">Trigger Intent</option>
                                        <option value="open_url">Open URL</option>
                                        <option value="none">No Action</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control form-control-sm" name="quick_replies[${quickReplyCount}][action_value]" placeholder="Intent/URL">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-quick-reply">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $('#quickRepliesContainer').append(replyHtml);
                quickReplyCount++;
            });

            // Remove quick reply
            $(document).on('click', '.remove-quick-reply', function() {
                $(this).closest('.quick-reply-card').remove();
            });

            // Add intent button
            $('#addIntentBtn').on('click', function() {
                // First check if config exists
                $.ajax({
                    url: '{{ route("chatbot.config.get") }}',
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            if (response.config) {
                                // Set config_id in hidden field
                                $('#config_id').val(response.config.id);
                                
                                // Show config info banner
                                $('#configInfoText').html(`Adding intent to: <strong>${response.config.name || 'Your Chatbot'}</strong>`);
                                $('#configInfoBanner').show();
                                
                                // Reset and show modal
                                resetIntentForm();
                                $('#intentModalTitle').text('Add New Intent');
                                $('#intentModal').modal('show');
                            } else {
                                // No config found - show error and redirect to settings
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'No Chatbot Configuration',
                                    text: 'Please create a chatbot configuration first before adding intents.',
                                    showCancelButton: true,
                                    confirmButtonText: 'Go to Settings',
                                    cancelButtonText: 'Cancel'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = '{{ route("chatbot.config") }}';
                                    }
                                });
                            }
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to load chatbot configuration', 'error');
                    }
                });
            });

            // Edit intent
            $(document).on('click', '.edit-intent', function() {
                const intentId = $(this).data('id');
                
                $.ajax({
                    url: `/owner/chatbot/intents/${intentId}`,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            // Get current config ID
                            $.ajax({
                                url: '{{ route("chatbot.config.get") }}',
                                type: 'GET',
                                success: function(configResponse) {
                                    if (configResponse.success && configResponse.config) {
                                        $('#config_id').val(configResponse.config.id);
                                        
                                        $('#configInfoText').html(`Editing intent in: <strong>${configResponse.config.name || 'Your Chatbot'}</strong>`);
                                        $('#configInfoBanner').show();
                                        
                                        populateIntentForm(response.intent);
                                        $('#intentModalTitle').text('Edit Intent');
                                        $('#intentModal').modal('show');
                                    }
                                }
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Failed to load intent details', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to load intent details', 'error');
                    }
                });
            });

            // Populate intent form
            function populateIntentForm(intent) {
                $('#intent_id').val(intent.id);
                $('#intent_name').val(intent.intent_name);
                $('#priority').val(intent.priority || 0);
                $('#response_type').val(intent.response_type || 'text').trigger('change');
                $('#image_url').val(intent.image_url || '');
                $('#response_text').val(intent.response_text);
                $('#is_active').prop('checked', intent.is_active);
                
                // Clear and populate keywords
                $('#keywordsContainer').empty();
                if (intent.trigger_keywords && intent.trigger_keywords.length > 0) {
                    intent.trigger_keywords.forEach((keyword, index) => {
                        const keywordRow = `
                            <div class="input-group mb-2 keyword-row">
                                <input type="text" class="form-control" name="trigger_keywords[]" value="${escapeHtml(keyword)}" required>
                                ${index === 0 
                                    ? '<button type="button" class="btn btn-outline-success add-keyword"><i class="ti ti-plus"></i></button>'
                                    : '<button type="button" class="btn btn-outline-danger remove-keyword"><i class="ti ti-trash"></i></button>'
                                }
                            </div>
                        `;
                        $('#keywordsContainer').append(keywordRow);
                    });
                } else {
                    $('#keywordsContainer').html(`
                        <div class="input-group mb-2 keyword-row">
                            <input type="text" class="form-control" name="trigger_keywords[]" placeholder="Enter keyword" required>
                            <button type="button" class="btn btn-outline-success add-keyword">
                                <i class="ti ti-plus"></i>
                            </button>
                        </div>
                    `);
                }
                
                // Clear and populate quick replies
                $('#quickRepliesContainer').empty();
                if (intent.quick_replies && intent.quick_replies.length > 0) {
                    quickReplyCount = intent.quick_replies.length;
                    intent.quick_replies.forEach((reply, index) => {
                        const replyHtml = `
                            <div class="card mb-2 quick-reply-card" data-index="${index}">
                                <div class="card-body p-2">
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control form-control-sm" name="quick_replies[${index}][reply_text]" value="${escapeHtml(reply.reply_text)}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select form-select-sm" name="quick_replies[${index}][action_type]">
                                                <option value="trigger_intent" ${reply.action_type === 'trigger_intent' ? 'selected' : ''}>Trigger Intent</option>
                                                <option value="open_url" ${reply.action_type === 'open_url' ? 'selected' : ''}>Open URL</option>
                                                <option value="none" ${reply.action_type === 'none' ? 'selected' : ''}>No Action</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control form-control-sm" name="quick_replies[${index}][action_value]" value="${escapeHtml(reply.action_value || '')}" placeholder="Intent/URL">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-quick-reply">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        $('#quickRepliesContainer').append(replyHtml);
                    });
                } else {
                    quickReplyCount = 0;
                    $('#quickRepliesContainer').empty();
                }
            }

            // Reset intent form
            function resetIntentForm() {
                $('#intent_id').val('');
                $('#intentForm')[0].reset();
                $('#keywordsContainer').html(`
                    <div class="input-group mb-2 keyword-row">
                        <input type="text" class="form-control" name="trigger_keywords[]" placeholder="Enter keyword" required>
                        <button type="button" class="btn btn-outline-success add-keyword">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                `);
                $('#quickRepliesContainer').empty();
                quickReplyCount = 0;
                $('#is_active').prop('checked', true);
            }

            // Submit intent form
            $('#intentForm').on('submit', function(e) {
                e.preventDefault();
                
                const intentId = $('#intent_id').val();
                const configId = $('#config_id').val();
                
                if (!configId) {
                    Swal.fire('Error', 'No chatbot configuration selected. Please save your settings first.', 'error');
                    return;
                }
                
                const url = intentId 
                    ? `/owner/chatbot/intents/${intentId}`
                    : '{{ route("chatbot.intents.store") }}';
                const method = intentId ? 'PUT' : 'POST';
                
                // Collect form data
                const formData = {
                    config_id: configId,  // Include the config_id
                    intent_name: $('#intent_name').val(),
                    priority: $('#priority').val(),
                    response_type: $('#response_type').val(),
                    image_url: $('#image_url').val(),
                    response_text: $('#response_text').val(),
                    is_active: $('#is_active').is(':checked'),
                    trigger_keywords: [],
                    quick_replies: []
                };
                
                // Collect keywords
                $('input[name="trigger_keywords[]"]').each(function() {
                    if ($(this).val().trim()) {
                        formData.trigger_keywords.push($(this).val().trim());
                    }
                });
                
                // Collect quick replies
                $('.quick-reply-card').each(function(index) {
                    const replyText = $(this).find('input[name^="quick_replies"][name$="[reply_text]"]').val();
                    const actionType = $(this).find('select[name^="quick_replies"][name$="[action_type]"]').val();
                    const actionValue = $(this).find('input[name^="quick_replies"][name$="[action_value]"]').val();
                    
                    if (replyText) {
                        formData.quick_replies.push({
                            reply_text: replyText,
                            action_type: actionType,
                            action_value: actionValue,
                            position: index
                        });
                    }
                });
                
                $('#saveIntentBtn').prop('disabled', true).html('<i class="ti ti-loader spinner me-1"></i> Saving...');
                
                $.ajax({
                    url: url,
                    type: method,
                    data: JSON.stringify(formData),
                    contentType: 'application/json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#intentModal').modal('hide');
                            loadIntents();
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Failed to save intent', 'error');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Failed to save intent';
                        const errors = xhr.responseJSON?.errors;
                        
                        if (errors) {
                            let errorMsg = message + '\n';
                            Object.keys(errors).forEach(key => {
                                errorMsg += `\n${errors[key].join(', ')}`;
                            });
                            Swal.fire('Validation Error', errorMsg, 'error');
                        } else {
                            Swal.fire('Error', message, 'error');
                        }
                    },
                    complete: function() {
                        $('#saveIntentBtn').prop('disabled', false).html('Save Intent');
                    }
                });
            });

            // Toggle intent status
            $(document).on('click', '.toggle-intent', function() {
                const intentId = $(this).data('id');
                const isActive = $(this).data('active');
                const action = isActive ? 'deactivate' : 'activate';
                
                Swal.fire({
                    icon: 'warning',
                    title: `Confirm ${action}`,
                    text: `Are you sure you want to ${action} this intent?`,
                    showCancelButton: true,
                    confirmButtonColor: isActive ? '#DC3545' : '#28A745',
                    confirmButtonText: `Yes, ${action} it!`
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/owner/chatbot/intents/${intentId}/toggle`,
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    loadIntents();
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                } else {
                                    Swal.fire('Error', response.message || 'Failed to update status', 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to update intent status', 'error');
                            }
                        });
                    }
                });
            });

            // Delete intent
            $(document).on('click', '.delete-intent', function() {
                const intentId = $(this).data('id');
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Confirm Delete',
                    text: 'Are you sure you want to delete this intent? This action cannot be undone.',
                    showCancelButton: true,
                    confirmButtonColor: '#DC3545',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/owner/chatbot/intents/${intentId}`,
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    loadIntents();
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                } else {
                                    Swal.fire('Error', response.message || 'Failed to delete intent', 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to delete intent', 'error');
                            }
                        });
                    }
                });
            });

            // ==================== CONVERSATION HISTORY ====================
            
            let currentPage = 1;
            
            function loadConversations(page = 1) {
                $('#conversationsTableBody').html(`
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                `);
                
                $.ajax({
                    url: '{{ route("chatbot.conversations") }}',
                    type: 'GET',
                    data: { page: page },
                    success: function(response) {
                        if (response.success) {
                            renderConversationsTable(response.conversations.data);
                            renderPagination(response.conversations);
                        } else {
                            $('#conversationsTableBody').html(`
                                <tr>
                                    <td colspan="7" class="text-center text-danger py-4">
                                        ${response.message || 'Failed to load conversations'}
                                    </td>
                                </tr>
                            `);
                        }
                    },
                    error: function() {
                        $('#conversationsTableBody').html(`
                            <tr>
                                <td colspan="7" class="text-center text-danger py-4">
                                    Failed to load conversations. Please try again.
                                </td>
                            </tr>
                        `);
                    }
                });
            }

            function renderConversationsTable(conversations) {
                if (!conversations || conversations.length === 0) {
                    $('#conversationsTableBody').html(`
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="ti ti-message-off fs-1 text-muted d-block mb-2"></i>
                                No conversations yet.
                            </td>
                        </tr>
                    `);
                    return;
                }
                
                let html = '';
                
                conversations.forEach(conv => {
                    const user = conv.user 
                        ? `${conv.user.first_name} ${conv.user.last_name}` 
                        : 'Guest';
                    
                    const statusBadge = conv.status === 'active' 
                        ? '<span class="badge bg-success">Active</span>' 
                        : '<span class="badge bg-secondary">Ended</span>';
                    
                    const started = conv.started_at ? new Date(conv.started_at).toLocaleString() : 'N/A';
                    const ended = conv.ended_at ? new Date(conv.ended_at).toLocaleString() : '—';
                    
                    html += `
                        <tr>
                            <td><span class="small">${conv.session_id}</span></td>
                            <td>${escapeHtml(user)}</td>
                            <td>${started}</td>
                            <td>${ended}</td>
                            <td><span class="badge bg-info">${conv.message_count || 0}</span></td>
                            <td>${statusBadge}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary view-conversation" data-id="${conv.id}" title="View Details">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                $('#conversationsTableBody').html(html);
            }

            function renderPagination(pagination) {
                if (!pagination || pagination.last_page <= 1) {
                    $('#conversationsPagination').empty();
                    return;
                }
                
                let html = '<nav><ul class="pagination">';
                
                // Previous button
                html += `<li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${pagination.current_page - 1}">Previous</a>
                </li>`;
                
                // Page numbers
                for (let i = 1; i <= pagination.last_page; i++) {
                    if (i === 1 || i === pagination.last_page || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                        html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>`;
                    } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                        html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                
                // Next button
                html += `<li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next</a>
                </li>`;
                
                html += '</ul></nav>';
                $('#conversationsPagination').html(html);
            }

            // Pagination click
            $(document).on('click', '.pagination a', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page) {
                    currentPage = page;
                    loadConversations(page);
                }
            });

            // View conversation details
            $(document).on('click', '.view-conversation', function() {
                const conversationId = $(this).data('id');
                
                $.ajax({
                    url: `/owner/chatbot/conversations/${conversationId}`,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            renderConversationMessages(response.conversation);
                            $('#viewConversationModal').modal('show');
                        } else {
                            Swal.fire('Error', response.message || 'Failed to load conversation details', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to load conversation details', 'error');
                    }
                });
            });

            function renderConversationMessages(conversation) {
                const user = conversation.user 
                    ? `${conversation.user.first_name} ${conversation.user.last_name}` 
                    : 'Guest';
                
                let html = `
                    <div class="mb-3 p-2 bg-light rounded">
                        <strong>User:</strong> ${escapeHtml(user)}<br>
                        <strong>Session:</strong> ${conversation.session_id}<br>
                        <strong>Started:</strong> ${new Date(conversation.started_at).toLocaleString()}
                    </div>
                    <hr>
                `;
                
                if (conversation.messages && conversation.messages.length > 0) {
                    conversation.messages.forEach(msg => {
                        const isUser = msg.sender_type === 'user';
                        const alignment = isUser ? 'text-end' : 'text-start';
                        const bgClass = isUser ? 'bg-primary text-white' : 'bg-light';
                        const icon = isUser ? 'ti ti-user' : 'ti ti-robot';
                        
                        html += `
                            <div class="mb-3 ${alignment}">
                                <div class="d-inline-block p-2 rounded ${bgClass}" style="max-width: 80%;">
                                    <small><i class="${icon} me-1"></i>${isUser ? 'You' : 'Bot'}</small>
                                    <p class="mb-0">${escapeHtml(msg.message)}</p>
                                    <small class="${isUser ? 'text-white-50' : 'text-muted'}">${new Date(msg.created_at).toLocaleTimeString()}</small>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    html += '<p class="text-muted text-center">No messages in this conversation.</p>';
                }
                
                $('#conversationMessages').html(html);
            }
        });
    </script>
@endsection