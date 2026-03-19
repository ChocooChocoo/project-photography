@extends('layouts.client.app')
@section('title', 'Chat Support')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">Chat Support</h4>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header bg-primary text-white" id="chatHeader">
                            <h5 class="card-title mb-0 text-white">
                                <i class="ti ti-robot me-2"></i>
                                <span id="botName">Support Assistant</span>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div id="chatMessages" class="p-3" style="height: 400px; overflow-y: auto; background-color: #f8f9fa;">
                                <div class="text-center py-5" id="loadingMessages">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Connecting to support...</p>
                                </div>
                            </div>
                            
                            <div class="p-3 border-top" id="chatInputArea" style="display: none;">
                                <form id="chatForm" class="d-flex gap-2">
                                    <input type="text" class="form-control" id="messageInput" 
                                        placeholder="Type your message here..." autocomplete="off">
                                    <button type="submit" class="btn btn-primary" id="sendBtn">
                                        <i class="ti ti-send"></i>
                                    </button>
                                </form>
                                <div class="mt-2 d-flex gap-2 flex-wrap" id="quickReplies"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden fields for session data -->
    <input type="hidden" id="ownerId" value="{{ request('owner_id') }}">
    <input type="hidden" id="sessionId" value="">
@endsection

{{-- SCRIPT --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            const ownerId = $('#ownerId').val();
            let sessionId = null;
            let botName = 'Support Assistant';
            let isTyping = false;
            
            // ==================== INITIALIZATION ====================
            
            // Load chatbot config
            loadChatbotConfig();
            
            function loadChatbotConfig() {
                if (!ownerId) {
                    showError('No studio selected. Please go back and try again.');
                    return;
                }
                
                $.ajax({
                    url: '/client/chatbot/config',
                    type: 'GET',
                    data: { owner_id: ownerId },
                    success: function(response) {
                        if (response.success && response.config) {
                            botName = response.config.bot_name || 'Support Assistant';
                            $('#botName').text(botName);
                            
                            if (response.config.is_active) {
                                startNewChat();
                            } else {
                                showError('Chat support is currently unavailable. Please try again later.');
                            }
                        } else {
                            showError('Failed to load chat configuration.');
                        }
                    },
                    error: function() {
                        showError('Failed to connect to chat service.');
                    }
                });
            }
            
            function startNewChat() {
                $.ajax({
                    url: '/client/chatbot/start',
                    type: 'POST',
                    data: {
                        owner_id: ownerId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            sessionId = response.session_id;
                            $('#sessionId').val(sessionId);
                            
                            // Clear loading and show chat
                            $('#loadingMessages').hide();
                            $('#chatInputArea').show();
                            
                            // Add welcome message
                            addBotMessage(response.welcome_message || `Hello! How can I assist you today?`);
                        } else {
                            showError(response.message || 'Failed to start chat.');
                        }
                    },
                    error: function() {
                        showError('Failed to start chat. Please try again.');
                    }
                });
            }
            
            // ==================== MESSAGE HANDLING ====================
            
            $('#chatForm').on('submit', function(e) {
                e.preventDefault();
                
                const message = $('#messageInput').val().trim();
                if (!message || isTyping) return;
                
                // Clear input
                $('#messageInput').val('');
                
                // Add user message to chat
                addUserMessage(message);
                
                // Show typing indicator
                showTypingIndicator();
                
                // Send to server
                $.ajax({
                    url: '/client/chatbot/message',
                    type: 'POST',
                    data: {
                        session_id: sessionId,
                        owner_id: ownerId,
                        message: message,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        // Remove typing indicator
                        hideTypingIndicator();
                        
                        if (response.success) {
                            // Add bot response
                            addBotMessage(response.message);
                            
                            // Add quick replies if any
                            if (response.quick_replies && response.quick_replies.length > 0) {
                                displayQuickReplies(response.quick_replies);
                            } else {
                                clearQuickReplies();
                            }
                        } else {
                            addBotMessage('I apologize, but I encountered an error. Please try again.');
                        }
                    },
                    error: function() {
                        hideTypingIndicator();
                        addBotMessage('Sorry, I\'m having trouble connecting. Please try again.');
                    }
                });
            });
            
            function addUserMessage(message) {
                const messageHtml = `
                    <div class="d-flex justify-content-end mb-3">
                        <div class="bg-primary text-white p-2 rounded" style="max-width: 70%;">
                            <small><i class="ti ti-user me-1"></i>You</small>
                            <p class="mb-0">${escapeHtml(message)}</p>
                            <small class="text-white-50">${new Date().toLocaleTimeString()}</small>
                        </div>
                    </div>
                `;
                $('#chatMessages').append(messageHtml);
                scrollToBottom();
            }
            
            function addBotMessage(message) {
                const messageHtml = `
                    <div class="d-flex justify-content-start mb-3">
                        <div class="bg-light p-2 rounded" style="max-width: 70%;">
                            <small><i class="ti ti-robot me-1"></i>${escapeHtml(botName)}</small>
                            <p class="mb-0">${escapeHtml(message)}</p>
                            <small class="text-muted">${new Date().toLocaleTimeString()}</small>
                        </div>
                    </div>
                `;
                $('#chatMessages').append(messageHtml);
                scrollToBottom();
            }
            
            function showTypingIndicator() {
                isTyping = true;
                const typingHtml = `
                    <div class="d-flex justify-content-start mb-3" id="typingIndicator">
                        <div class="bg-light p-2 rounded">
                            <small><i class="ti ti-robot me-1"></i>${escapeHtml(botName)}</small>
                            <p class="mb-0"><i class="ti ti-dots"></i> typing...</p>
                        </div>
                    </div>
                `;
                $('#chatMessages').append(typingHtml);
                scrollToBottom();
            }
            
            function hideTypingIndicator() {
                isTyping = false;
                $('#typingIndicator').remove();
            }
            
            function displayQuickReplies(replies) {
                let html = '';
                replies.forEach(reply => {
                    html += `
                        <button type="button" class="btn btn-sm btn-outline-primary quick-reply-btn" 
                                data-text="${escapeHtml(reply.text)}" 
                                data-action="${escapeHtml(reply.action || '')}"
                                data-action-type="${reply.action_type || 'trigger_intent'}">
                            ${escapeHtml(reply.text)}
                        </button>
                    `;
                });
                $('#quickReplies').html(html);
            }
            
            function clearQuickReplies() {
                $('#quickReplies').empty();
            }
            
            // Quick reply click handler
            $(document).on('click', '.quick-reply-btn', function() {
                const text = $(this).data('text');
                const action = $(this).data('action');
                const actionType = $(this).data('action-type');
                
                if (actionType === 'open_url' && action) {
                    window.open(action, '_blank');
                } else {
                    // Send as message
                    $('#messageInput').val(text);
                    $('#chatForm').submit();
                }
            });
            
            // ==================== HELPER FUNCTIONS ====================
            
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            function scrollToBottom() {
                const chat = $('#chatMessages');
                chat.scrollTop(chat[0].scrollHeight);
            }
            
            function showError(message) {
                $('#loadingMessages').html(`
                    <i class="ti ti-alert-circle fs-1 text-danger d-block mb-2"></i>
                    <p class="text-danger">${escapeHtml(message)}</p>
                    <button class="btn btn-primary mt-3" onclick="location.reload()">
                        <i class="ti ti-refresh me-1"></i> Try Again
                    </button>
                `);
            }
            
            // Load message history if session exists (for returning users)
            function loadHistory() {
                if (!sessionId) return;
                
                $.ajax({
                    url: '/client/chatbot/history',
                    type: 'GET',
                    data: { session_id: sessionId },
                    success: function(response) {
                        if (response.success && response.history) {
                            $('#chatMessages').empty();
                            
                            response.history.forEach(msg => {
                                if (msg.sender_type === 'user') {
                                    addUserMessage(msg.message);
                                } else {
                                    addBotMessage(msg.message);
                                }
                            });
                            
                            $('#loadingMessages').hide();
                            $('#chatInputArea').show();
                        }
                    },
                    error: function() {
                        // Silently fail, start new chat
                    }
                });
            }
            
            // ==================== FEEDBACK BUTTONS ====================
            
            // Add feedback buttons after bot messages (optional)
            function addFeedbackButtons(messageId) {
                const feedbackHtml = `
                    <div class="mt-1">
                        <small class="text-muted me-2">Was this helpful?</small>
                        <button class="btn btn-sm btn-outline-success btn-feedback" data-message="${messageId}" data-helpful="1">
                            <i class="ti ti-thumb-up"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-feedback" data-message="${messageId}" data-helpful="0">
                            <i class="ti ti-thumb-down"></i>
                        </button>
                    </div>
                `;
                return feedbackHtml;
            }
            
            // Feedback handler
            $(document).on('click', '.btn-feedback', function() {
                const messageId = $(this).data('message');
                const helpful = $(this).data('helpful');
                
                $.ajax({
                    url: helpful ? '/client/chatbot/helpful' : '/client/chatbot/not-helpful',
                    type: 'POST',
                    data: {
                        session_id: sessionId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Disable feedback buttons
                            $(`.btn-feedback[data-message="${messageId}"]`).prop('disabled', true);
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Thank You!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    }
                });
            });
        });
    </script>
@endsection