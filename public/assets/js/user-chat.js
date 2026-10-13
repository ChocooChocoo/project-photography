(function (root) {
    'use strict';

    function createVisiblePoller(run, options = {}) {
        const document = options.document || root.document;
        const scheduler = options.scheduler || root;
        const interval = options.interval || 5000;
        let active = false, busy = false, timer = null, controller = null, pending = false;
        const clear = () => { if (timer !== null) scheduler.clearTimeout(timer); timer = null; };
        async function tick() {
            clear();
            if (!active || document.hidden) return;
            if (busy) { pending = true; return; }
            busy = true;
            controller = new AbortController();
            try { await run(controller.signal); }
            catch (error) { if (!controller.signal.aborted) options.onError?.(error); }
            finally {
                busy = false;
                if (active && !document.hidden) {
                    if (pending) { pending = false; tick(); }
                    else timer = scheduler.setTimeout(tick, interval);
                }
            }
        }
        function visibility() {
            clear();
            if (document.hidden) controller?.abort();
            else if (active) tick();
        }
        document.addEventListener('visibilitychange', visibility);
        return {
            start() { if (!active) { active = true; tick(); } },
            refresh() { if (active) tick(); },
            stop() { active = false; clear(); controller?.abort(); document.removeEventListener('visibilitychange', visibility); },
        };
    }

    if (typeof module !== 'undefined' && module.exports) module.exports = {createVisiblePoller};
    if (!root.document) return;

    async function request(url, options = {}) {
        const response = await root.fetch(url, {...options, headers: {Accept: 'application/json', 'X-CSRF-TOKEN': root.document.querySelector('meta[name="csrf-token"]')?.content, ...options.headers}});
        let result;
        try { result = await response.json(); } catch (_) { throw new Error('Could not load messages. Check your connection and try again.'); }
        if (!response.ok) {
            const error = new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Messaging request failed.');
            error.status = response.status;
            throw error;
        }
        return result;
    }

    function initialize() {
        const document = root.document;
        document.querySelectorAll('[data-chat-unread]').forEach(badge => {
            const poller = createVisiblePoller(async signal => {
                const result = await request(badge.dataset.chatUnread, {signal});
                if (signal.aborted) return;
                badge.textContent = String(result.count);
                badge.classList.toggle('d-none', result.count === 0);
                badge.setAttribute('aria-label', result.count + ' unread messages');
            }, {interval: 30000});
            poller.start();
            root.addEventListener('pagehide', () => poller.stop(), {once: true});
        });
        const surface = document.querySelector('[data-user-chat]');
        const inbox = surface?.querySelector('[data-chat-inbox]');
        if (!surface || !inbox) return;
        const base = surface.dataset.base.replace(/\/$/, '');
        const conversation = Number(surface.dataset.conversation);
        const ownId = Number(surface.dataset.user);
        const inboxStatus = surface.querySelector('[data-chat-inbox-status]');
        const inboxPoller = createVisiblePoller(async signal => {
            const result = await request(base + '/inbox', {signal});
            if (signal.aborted) return;
            inbox.replaceChildren();
            result.conversations.forEach(item => {
                const link = document.createElement('a');
                link.className = 'd-block border-bottom py-3';
                link.href = base + '/' + item.id;
                link.textContent = item.name + (item.booking_reference ? ' (' + item.booking_reference + ')' : '') + (item.unread ? ' · ' + item.unread + ' unread' : '');
                if (item.id === conversation) link.setAttribute('aria-current', 'page');
                inbox.append(link);
            });
            inboxStatus.textContent = result.conversations.length ? '' : 'No conversations yet.';
        }, {onError: error => { inboxStatus.textContent = error.message + ' Retrying...'; }});
        inboxPoller.start();
        root.addEventListener('pagehide', () => inboxPoller.stop(), {once: true});

        const open = surface.querySelector('[data-chat-open]');
        open?.addEventListener('submit', async event => {
            event.preventDefault();
            const button = open.querySelector('button');
            const status = open.querySelector('[role="status"]');
            button.disabled = true;
            status.textContent = 'Opening...';
            try {
                const result = await request(base, {method: 'POST', body: new FormData(open)});
                root.location.assign(result.url);
            } catch (error) { status.textContent = error.message; status.focus(); button.disabled = false; }
        });
        if (!conversation) return;
        const thread = surface.querySelector('[data-chat-thread]');
        const threadStatus = surface.querySelector('[data-chat-thread-status]');
        const composer = surface.querySelector('[data-chat-compose]');
        const sendButton = composer.querySelector('button');
        const text = composer.querySelector('textarea');
        const sendStatus = composer.querySelector('[role="status"]');
        const receipts = new Map();
        const seen = new Set();
        let cursor = 0, canSend = false, sending = false;
        function appendMessage(message) {
            if (seen.has(message.id)) return;
            seen.add(message.id);
            const nearBottom = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 80;
            const article = document.createElement('article');
            article.className = 'border-bottom py-3';
            const author = document.createElement('strong');
            author.textContent = message.sender_id === ownId ? 'You' : surface.dataset.peer;
            const body = document.createElement('p');
            body.className = 'chat-message-body my-2';
            body.textContent = message.body;
            const time = document.createElement('time');
            time.dateTime = message.created_at;
            time.textContent = new Date(message.created_at).toLocaleString();
            article.append(author, body, time);
            if (message.sender_id === ownId) {
                const receipt = document.createElement('span');
                receipt.className = 'ms-2';
                receipt.textContent = message.read_at ? 'Read' : 'Sent';
                article.append(receipt);
                receipts.set(message.id, receipt);
            }
            thread.append(article);
            if (nearBottom) thread.scrollTop = thread.scrollHeight;
        }
        const threadPoller = createVisiblePoller(async signal => {
            const result = await request(base + '/' + conversation + '/messages?after=' + cursor, {signal});
            if (signal.aborted) return;
            result.messages.forEach(appendMessage);
            if (result.messages.length) cursor = result.messages.at(-1).id;
            receipts.forEach((receipt, id) => { if (id <= result.read_through) receipt.textContent = 'Read'; });
            canSend = result.can_send;
            sendButton.disabled = !canSend || sending;
            threadStatus.textContent = !canSend ? 'This contact is no longer eligible for messages. History is read-only.' : (seen.size ? '' : 'No messages yet.');
            if (!document.hidden && cursor) await request(base + '/' + conversation + '/read', {method: 'POST', signal, headers: {'Content-Type': 'application/json'}, body: JSON.stringify({through: cursor})});
            if (result.has_more) threadPoller.refresh();
        }, {onError: error => {
            threadStatus.textContent = error.message + (error.status === 403 ? '' : ' Retrying...');
            if (error.status === 403) { thread.replaceChildren(); canSend = false; sendButton.disabled = true; threadPoller.stop(); }
        }});
        threadPoller.start();
        root.addEventListener('pagehide', () => threadPoller.stop(), {once: true});
        composer.addEventListener('submit', async event => {
            event.preventDefault();
            if (sending || !canSend) return;
            sending = true; sendButton.disabled = true;
            sendStatus.textContent = 'Sending...';
            text.removeAttribute('aria-invalid');
            const draft = text.value;
            try {
                const result = await request(base + '/' + conversation + '/send', {method: 'POST', body: new FormData(composer)});
                appendMessage(result.message);
                if (text.value === draft) text.value = '';
                sendStatus.textContent = 'Message sent.';
                threadPoller.refresh(); inboxPoller.refresh();
            } catch (error) { sendStatus.textContent = error.message; text.setAttribute('aria-invalid', 'true'); sendStatus.focus(); }
            finally { sending = false; sendButton.disabled = !canSend; }
        });
    }
    if (root.document.readyState === 'loading') root.document.addEventListener('DOMContentLoaded', initialize, {once: true});
    else initialize();
})(typeof window === 'undefined' ? globalThis : window);
