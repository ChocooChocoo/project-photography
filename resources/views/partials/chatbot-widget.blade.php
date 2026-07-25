{{--
    Photography AI assistant widget (shared by the client, owner, and studio
    photographer portals).

    Usage:
        @include('partials.chatbot-widget', ['ownerId' => $studio->user_id])
        @include('partials.chatbot-widget', ['ownerId' => auth()->id(), 'launcher' => true])

    Params:
        $ownerId  (required) studio owner user id the assistant answers for
        $launcher (optional) render a floating launch button; default true

    The browser never sees the model, the provider, or any credential -- it only
    talks to this application's own /chatbot/* endpoints.
--}}
@php
    $assistantOwnerId = $ownerId ?? null;
    $assistantLauncher = $launcher ?? true;
@endphp

@if($assistantOwnerId)
    @if($assistantLauncher)
        <button type="button" class="btn btn-primary rounded-circle shadow"
            data-bs-toggle="modal" data-bs-target="#studioChatbotModal"
            style="position: fixed; bottom: 24px; right: 24px; width: 56px; height: 56px; z-index: 1035;"
            title="Ask the photography assistant" aria-label="Ask the photography assistant">
            <i class="ti ti-message-circle fs-5"></i>
        </button>
    @endif

    <div class="modal fade" id="studioChatbotModal" tabindex="-1" aria-labelledby="studioChatbotModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studioChatbotModalLabel">
                        <i class="ti ti-robot me-2"></i>
                        <span id="modalBotName">Photography Assistant</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="modalChatMessages" class="p-3" style="max-height: 55vh; overflow-y: auto;">
                        <div class="text-center py-4" id="modalLoadingMessages">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Connecting to assistant...</p>
                        </div>
                    </div>

                    <div class="p-3 border-top" id="modalChatInputArea" style="display: none;">
                        <form id="modalChatForm" class="d-flex gap-2">
                            <input type="text" class="form-control" id="modalMessageInput" maxlength="600"
                                placeholder="Ask about bookings, packages, services, or availability..." autocomplete="off">
                            <button type="submit" class="btn btn-primary" id="modalSendBtn">
                                <i class="ti ti-send"></i>
                            </button>
                        </form>
                        <div class="mt-2 d-flex gap-2 flex-wrap" id="modalQuickReplies"></div>
                        <p class="small text-muted mb-0 mt-2">
                            This assistant answers photography-service questions only.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const ownerId = @json((string) $assistantOwnerId);
            const csrfToken = @json(csrf_token());
            const endpoints = {
                config: @json(route('chatbot.config')),
                start: @json(route('chatbot.start')),
                message: @json(route('chatbot.message')),
                end: @json(route('chatbot.end')),
            };

            const modal = document.getElementById('studioChatbotModal');
            if (!modal) return;

            const messagesEl = document.getElementById('modalChatMessages');
            const inputArea = document.getElementById('modalChatInputArea');
            const form = document.getElementById('modalChatForm');
            const input = document.getElementById('modalMessageInput');
            const sendBtn = document.getElementById('modalSendBtn');
            const chipsEl = document.getElementById('modalQuickReplies');
            const nameEl = document.getElementById('modalBotName');

            let sessionId = null;
            let botName = 'Photography Assistant';
            let busy = false;

            function escapeHtml(text) {
                if (text === null || text === undefined) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function post(url, payload) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                }).then(res => res.json());
            }

            function scrollToBottom() {
                messagesEl.scrollTop = messagesEl.scrollHeight;
            }

            function appendUser(message) {
                messagesEl.insertAdjacentHTML('beforeend', `
                    <div class="d-flex justify-content-end mb-3">
                        <div class="bg-primary text-white p-2 rounded" style="max-width: 75%;">
                            <small><i class="ti ti-user me-1"></i>You</small>
                            <p class="mb-0">${escapeHtml(message)}</p>
                            <small class="text-white-50">${new Date().toLocaleTimeString()}</small>
                        </div>
                    </div>
                `);
                scrollToBottom();
            }

            function renderPackages(packages) {
                let html = '<p class="mb-2 fw-semibold">Our current packages:</p>';

                packages.forEach((pkg, index) => {
                    const inclusions = Array.isArray(pkg.inclusions) && pkg.inclusions.length
                        ? `<ul class="mb-0 ps-3 small">${pkg.inclusions.map(i => `<li>${escapeHtml(i)}</li>`).join('')}</ul>`
                        : '<p class="mb-0 small text-muted">No listed inclusions.</p>';

                    html += `
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <div>
                                    <div class="fw-semibold">${index + 1}. ${escapeHtml(pkg.name || 'Package')}</div>
                                    ${pkg.category ? `<div class="small text-muted">${escapeHtml(pkg.category)}</div>` : ''}
                                </div>
                                <span class="badge badge-soft-success">${escapeHtml(pkg.price || '')}</span>
                            </div>
                            ${pkg.description ? `<p class="mb-2 small">${escapeHtml(pkg.description)}</p>` : ''}
                            <div class="small fw-semibold mb-1">Includes:</div>
                            ${inclusions}
                        </div>
                    `;
                });

                return html;
            }

            function appendBot(message, metadata) {
                metadata = metadata || {};
                let body = `<p class="mb-0">${escapeHtml(message).replace(/\n/g, '<br>')}</p>`;

                if (Array.isArray(metadata.packages) && metadata.packages.length) {
                    body += `<div class="mt-2">${renderPackages(metadata.packages)}</div>`;
                }

                messagesEl.insertAdjacentHTML('beforeend', `
                    <div class="d-flex justify-content-start mb-3">
                        <div class="bg-light p-2 rounded" style="max-width: 85%;">
                            <small><i class="ti ti-robot me-1"></i>${escapeHtml(botName)}</small>
                            <div class="mb-0">${body}</div>
                            <small class="text-muted">${new Date().toLocaleTimeString()}</small>
                        </div>
                    </div>
                `);
                scrollToBottom();
            }

            function showTyping() {
                busy = true;
                sendBtn.disabled = true;
                messagesEl.insertAdjacentHTML('beforeend', `
                    <div class="d-flex justify-content-start mb-3" id="modalTypingIndicator">
                        <div class="bg-light p-2 rounded">
                            <small><i class="ti ti-robot me-1"></i>${escapeHtml(botName)}</small>
                            <p class="mb-0"><i class="ti ti-dots"></i> typing...</p>
                        </div>
                    </div>
                `);
                scrollToBottom();
            }

            function hideTyping() {
                busy = false;
                sendBtn.disabled = false;
                const el = document.getElementById('modalTypingIndicator');
                if (el) el.remove();
            }

            function renderChips(chips) {
                if (!Array.isArray(chips) || !chips.length) {
                    chipsEl.innerHTML = '';
                    return;
                }

                chipsEl.innerHTML = chips.map(chip => `
                    <button type="button" class="btn btn-sm btn-outline-primary js-assistant-chip"
                        data-text="${escapeHtml(chip.text)}">${escapeHtml(chip.text)}</button>
                `).join('');
            }

            function showError(message) {
                const loader = document.getElementById('modalLoadingMessages');
                if (!loader) {
                    appendBot(message);
                    return;
                }

                loader.innerHTML = `
                    <i class="ti ti-alert-circle fs-1 text-danger d-block mb-2"></i>
                    <p class="text-danger mb-0">${escapeHtml(message)}</p>
                `;
            }

            function resetPanel() {
                sessionId = null;
                messagesEl.innerHTML = `
                    <div class="text-center py-4" id="modalLoadingMessages">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Connecting to assistant...</p>
                    </div>
                `;
                inputArea.style.display = 'none';
                chipsEl.innerHTML = '';
            }

            function startChat() {
                post(endpoints.start, { owner_id: ownerId })
                    .then(res => {
                        if (!res.success) {
                            showError(res.message || 'Unable to start the assistant.');
                            return;
                        }

                        sessionId = res.session_id;
                        const loader = document.getElementById('modalLoadingMessages');
                        if (loader) loader.remove();
                        inputArea.style.display = '';
                        appendBot(res.welcome_message || 'Hello! Ask me anything about our photography services.');
                    })
                    .catch(() => showError('Unable to start the assistant. Please try again.'));
            }

            modal.addEventListener('show.bs.modal', function () {
                const url = endpoints.config + '?owner_id=' + encodeURIComponent(ownerId);

                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(res => res.json())
                    .then(res => {
                        if (!res.success || !res.config) {
                            showError('Unable to load the assistant right now.');
                            return;
                        }

                        botName = res.config.bot_name || botName;
                        nameEl.textContent = botName;

                        if (!res.config.is_active) {
                            showError('The assistant is currently unavailable. Please try again later.');
                            return;
                        }

                        startChat();
                    })
                    .catch(() => showError('Unable to reach the assistant.'));
            });

            modal.addEventListener('hidden.bs.modal', function () {
                if (sessionId) {
                    post(endpoints.end, { session_id: sessionId }).catch(() => {});
                }

                resetPanel();
            });

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const message = input.value.trim();
                if (!message || busy) return;

                input.value = '';
                appendUser(message);
                showTyping();

                post(endpoints.message, {
                    session_id: sessionId,
                    owner_id: ownerId,
                    message: message,
                })
                    .then(res => {
                        hideTyping();

                        if (!res.success) {
                            appendBot(res.message || 'The assistant is temporarily unavailable. Please try again shortly.');
                            return;
                        }

                        appendBot(res.message, res.metadata);
                        renderChips(res.quick_replies);
                    })
                    .catch(() => {
                        hideTyping();
                        appendBot('The assistant is temporarily unavailable. Please try again shortly.');
                    });
            });

            chipsEl.addEventListener('click', function (event) {
                const chip = event.target.closest('.js-assistant-chip');
                if (!chip || busy) return;

                input.value = chip.dataset.text || '';
                form.dispatchEvent(new Event('submit'));
            });
        })();
    </script>
@endif
