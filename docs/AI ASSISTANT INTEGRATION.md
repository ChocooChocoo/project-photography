# AI Assistant Integration (Groq)

Reference for the photography AI assistant that replaced the fixed-response
chatbot. Covers architecture, configuration, security controls, fallback
behavior, and usage limits.

**No credential value appears in this document, and none may ever be added to
it.** Setup instructions name variables only.

---

## 1. What changed

| Before | After |
|---|---|
| DB keyword matcher: message → `tbl_chatbot_intents.trigger_keywords` → stored `response_text` | Groq chat completion generated per message |
| BotMan (`botman/botman`, `botman/driver-web`) — loaded but unused | Removed from `composer.json` |
| Replies limited to what the owner typed in | Replies generated dynamically, grounded in live studio data |
| Client portal only (`/client/chatbot/*`) | Cross-portal (`/chatbot/*`): client, owner, studio photographer |
| `tbl_chatbot_intents` rows were the answers | The same rows are now **studio knowledge facts** injected as untrusted reference data |
| Conversation transcripts readable by any authenticated user | Transcripts scoped to the user who started them |
| Exception messages returned in JSON error bodies | Fixed generic copy; details logged only |

`tbl_chatbot_configs`, `tbl_chatbot_intents`, `tbl_chatbot_conversations`, and
`tbl_chatbot_messages` keep their schema and their data.

`tbl_chatbot_quick_replies` was dropped. Per-intent quick replies were buttons
of the fixed-response chatbot; the assistant writes its own replies, and the
suggestion chips under the input come from `tbl_chatbot_configs.settings`
(`quick_reply_defaults`), not from that table.

Five columns belonging to the matcher were dropped with it, since nothing read
them after the rewrite:

| Column | Why it went |
|---|---|
| `tbl_chatbot_configs.fallback_message` | Fallback copy is fixed in `Ai\ChatbotGuard`, so an owner cannot soften a security refusal |
| `tbl_chatbot_intents.trigger_keywords` | The assistant understands the question; there is no keyword list to match |
| `tbl_chatbot_intents.response_type` | Replies are always generated text |
| `tbl_chatbot_intents.image_url` | The assistant does not emit stored images |
| `tbl_chatbot_intents.match_count` | Counted matcher hits; nothing incremented it |

A knowledge entry is now **topic + reference answer + priority + active**.

---

## 2. Architecture

```
Browser (Blade + fetch)
  │  POST /chatbot/message   {session_id, owner_id, message}
  ▼
App\Http\Controllers\ChatbotController          auth + throttle:30,1
  │  session ownership check, generic error handling
  ▼
App\Services\ChatbotService::processMessage()
  │
  ├─ 1. ChatbotGuard::sanitizeInput()      strip control/zero-width chars, cap length
  ├─ 2. evaluateMessage()                  owner-configured profanity / spam / noise
  ├─ 3. ChatbotGuard::inspectInput()       prompt injection + credential probes
  ├─ 4. GroqRateLimiter::attempt()         request + token budget windows
  ├─ 5. GroqClient::chat()                 HTTPS to Groq (the only key reader)
  └─ 6. ChatbotGuard::inspectOutput()      off-topic marker, secret/instruction leaks
  ▼
Sanitized reply persisted to tbl_chatbot_messages and returned as JSON
```

**Server-side vs client-side responsibilities**

| Server-side only | Client-side |
|---|---|
| The Groq API key, base URL, model id, and all request construction | Rendering messages, suggestion chips, and package cards |
| The system prompt and every security rule | Collecting the typed message and posting it to this app |
| Studio context assembly (packages, knowledge entries) | Escaping text before insertion into the DOM |
| Input and output validation, budget accounting, transcript storage | Nothing about the provider — the browser never learns Groq is involved |

The browser talks only to this application's own routes. It never receives the
key, the provider hostname, the model id, or the system prompt.

### Files

| Path | Role |
|---|---|
| `app/Services/ChatbotService.php` | Orchestration, system prompt, studio context, history, persistence |
| `app/Services/Ai/GroqClient.php` | Transport. Reads `services.groq.api_key`. Returns reason codes, never provider error text |
| `app/Services/Ai/ChatbotGuard.php` | Input/output guardrails, fallback copy |
| `app/Services/Ai/GroqRateLimiter.php` | Cache-based request/token budget windows |
| `app/Http/Controllers/ChatbotController.php` | Cross-portal endpoints, ownership checks |
| `app/Http/Requests/Chatbot/ChatbotMessageRequest.php` | Request validation (600-char cap) |
| `resources/views/partials/chatbot-widget.blade.php` | Shared widget (modal + vanilla JS + fetch) |
| `config/services.php` (`groq` block) | Model, endpoint, timeouts, budget caps |

---

## 3. Configuration

Add to `.env` (see `.env.example` for the documented placeholders):

| Variable | Required | Default | Notes |
|---|---|---|---|
| `GROQ_API_KEY` | yes | — | Server-side only. Never prefix with `VITE_`. Absent key ⇒ assistant returns the "temporarily unavailable" fallback and makes no HTTP call |
| `GROQ_MODEL` | no | `qwen/qwen3.6-27b` | Model id sent to Groq |
| `GROQ_BASE_URL` | no | `https://api.groq.com/openai/v1` | OpenAI-compatible endpoint |
| `GROQ_TIMEOUT` | no | `20` | Seconds |
| `GROQ_MAX_TOKENS` | no | `400` | Ceiling on reply length |
| `GROQ_TEMPERATURE` | no | `0.3` | Low, to keep answers factual |
| `GROQ_REASONING_FORMAT` | no | `parsed` | Keeps a reasoning model's chain of thought out of the reply body. Set to an empty string for models that reject the parameter |
| `GROQ_REASONING_EFFORT` | no | `none` | Groq accepts `none` or `default`. `none` is used deliberately — see §7 |
| `GROQ_PACKAGE_CONTEXT_LIMIT` | no | `10` | Max package rows injected into the prompt |
| `GROQ_FAQ_CONTEXT_LIMIT` | no | `6` | Max studio knowledge entries injected |
| `GROQ_LIMIT_RPM` / `GROQ_LIMIT_RPD` | no | `25` / `900` | Request windows |
| `GROQ_LIMIT_TPM` / `GROQ_LIMIT_TPD` | no | `7000` / `180000` | Token windows |
| `GROQ_LIMIT_USER_RPM` | no | `8` | Per-user requests per minute |
| `GROQ_HISTORY_MESSAGES` / `GROQ_HISTORY_CHARACTERS` | no | `6` / `3000` | Conversation context size |

### Credential setup

1. Create an API key in the Groq console.
2. Paste it into your local `.env` as `GROQ_API_KEY`. `.env` is gitignored;
   `.env.example` carries the empty placeholder only.
3. In staging and production, set it through the host's secret manager or
   environment settings — not in a file inside the repository.
4. Run `php artisan config:clear` after changing it.
5. If a key is ever committed, pasted into an issue, or otherwise exposed,
   revoke it in the Groq console and issue a replacement. Rotation is the only
   remedy; scrubbing the text is not sufficient.

> **Model id note.** `qwen/qwen3.6-27b` is the id the project specifies, the
> configured default, and confirmed available on the project's Groq account
> (`GET /openai/v1/models`). Because the id is read from configuration, swapping
> models needs no code change — set `GROQ_MODEL`. A model id the account cannot
> serve produces a provider error, which surfaces as the "temporarily
> unavailable" fallback.

---

## 4. Scope: what the assistant answers

**In scope** — bookings and booking steps, packages, pricing and inclusions,
services offered, shoot logistics, deliverables, schedules, availability, and
how to reach the studio team.

**Out of scope** — everything else. Out-of-scope requests do not receive a
general-purpose answer; they receive the domain fallback and an invitation to
ask a photography question.

### Grounding data

The system prompt is assembled per request from live database state:

- **Studio profile** — name, address, contact from `tbl_studios`.
- **Packages** — active rows from `tbl_packages` for the owner's studio
  (name, price, category, description, first three inclusions). Inactive
  packages are never included.
- **Studio knowledge** — up to 12 active `tbl_chatbot_intents` rows rendered as
  `Q: <topic> / A: <reference answer>`, owner-editable under
  *Owner → Inquiries & AI Assistant → Studio Knowledge*.

Every block is wrapped in `<untrusted_data source="...">` markers, and the rules
state explicitly that such content is material to answer *about*, never
instructions to follow. The assistant is told to state only facts present in
that context and to never invent prices, dates, or inclusions.

---

## 5. Security controls

### 5.1 Credential protection

- `config('services.groq.api_key')` is read in exactly one place:
  `GroqClient::chat()`.
- Sent via `Http::withToken()`; the key is never concatenated into a logged
  string, a URL, or an error message.
- Never reaches a Blade view, a JSON response, the Vite bundle, or any
  documentation. There is no `VITE_`-prefixed AI variable.
- Provider error bodies are discarded. Only a status code and a short internal
  reason code (`provider_error`, `transport_error`, `invalid_payload`,
  `provider_rate_limited`, `not_configured`) are logged.
- Exception messages from the transport layer are never logged — only the
  exception class — because they can contain the request URL and headers.

### 5.2 Fixed, non-editable instructions

The behavior contract lives in the `ChatbotService::SECURITY_RULES` PHP
constant. It is not a database column, so the owner portal cannot weaken it, and
it is re-sent as the system message on **every** request, so no accumulated
conversation history can displace it.

### 5.3 Input validation (before any provider call)

1. **Sanitization** — control characters, zero-width characters, and bidi
   overrides removed; whitespace collapsed; length capped at 600 characters
   (also enforced by `ChatbotMessageRequest`).
2. **Owner moderation** — profanity word-boundary match, repetition/spam
   heuristics, noise-phrase match, from `tbl_chatbot_configs.settings.moderation`.
3. **Injection and probe patterns** — instruction override ("ignore previous
   instructions"), role reassignment ("you are now", "act as", "developer
   mode"), prompt disclosure ("system prompt", "repeat everything above"),
   credential and environment probes (`api key`, `.env`, `GROQ_`, `APP_KEY`,
   `DB_PASSWORD`, `config(`), source-code and log requests, SQL, and
   encoding/obfuscation laundering (base64, rot13, "decode this").

A blocked message is answered locally. The provider is never contacted, so
abuse also costs no budget. The matched pattern is never reported back to the
user, so the filter cannot be mapped by probing.

### 5.4 Output validation (before anything is displayed)

1. `<think>` blocks stripped.
2. Empty replies and replies over 2000 characters are rejected.
3. The `[OFFTOPIC]` sentinel becomes the domain fallback.
4. Instruction-echo markers (distinctive system-prompt phrases) reject the reply.
5. Leak patterns reject the reply: `gsk_`/`sk-` key shapes, `base64:` app-key
   shape, `NAME=` environment assignments, known variable names, `Illuminate\`
   internals, stack frames, absolute filesystem paths, `SQLSTATE[`.
6. Live secret values (Groq key, `APP_KEY`, DB password, Stripe/PayMongo keys)
   are compared literally, in case a real value is ever regurgitated.

Rejected output is **replaced whole**, never partially redacted, and the
discarded text is never persisted or logged. Only the guarded reply is written
to `tbl_chatbot_messages`.

### 5.5 Transport and authorization

- Endpoints require authentication and carry `throttle:30,1` as a second line of
  defense in front of the budget windows.
- `session_id` is checked against `Auth::id()` on every message, end, history,
  and feedback call; a mismatch returns 403 with neutral copy.
- Owner knowledge-entry updates, deletes, and toggles are scoped through the
  owner's own config.
- Controller `catch` blocks return fixed copy; the cause goes to the log only.

### 5.6 Logging policy

Logged: conversation id, guard outcome code, HTTP status, token count,
exception class.

Never logged: user message text, model reply text, system prompt, request
payloads, response bodies, headers, or any configuration value.

---

## 6. Fallback behavior

All fallback copy is fixed, concise, professional, non-technical, and identical
for photographers and clients. Every message keeps the conversation open by
inviting a photography question — a user who triggers a fallback can continue
normally with their next message.

| Code | Trigger | Provider called? |
|---|---|---|
| `off_topic` | Model emitted `[OFFTOPIC]` | yes |
| `blocked_language` | Profanity / abusive language | no |
| `spam_or_repetition` | Repetition or spam heuristics | no |
| `noise_or_unnecessary` | Unclear or meaningless input | no |
| `secure_refusal` | Injection or credential probe on input, **or** a leak/echo detected in output | input: no · output: yes |
| `rate_limited` | A request or token window is exhausted | no |
| `service_unavailable` | Missing key, provider error, timeout, unusable payload, empty or oversized reply | attempted |
| `empty_input` | Empty or over-length message | no |

Abusive language is never repeated or escalated in the reply.

---

## 7. Rate and token management

Provider limits: **30 requests/minute, 1,000 requests/day, 8,000
tokens/minute, 200,000 tokens/day.** Configured caps sit under each so
concurrency cannot overshoot the real limit.

| Window | Cache key | Default cap |
|---|---|---|
| Requests / minute | `groq:rpm:{YmdHi}` | 25 |
| Requests / day | `groq:rpd:{Ymd}` | 900 |
| Tokens / minute | `groq:tpm:{YmdHi}` | 7,000 |
| Tokens / day | `groq:tpd:{Ymd}` | 180,000 |
| Requests / user / minute | `groq:user:{id}:{YmdHi}` | 8 |

Flow: estimate tokens (`ceil(chars / 4) + max_tokens`) → reserve against every
window → call the provider → reconcile the reservation against the reported
`usage.total_tokens`. Any window that would be exceeded returns `rate_limited`
without an HTTP call. The per-user window keeps one account from draining the
studio's shared allowance.

Counters are advisory — the cache is not transactional, so a burst can overshoot
a window by a request or two, which is exactly why the caps are set below the
provider's limits.

### Keeping each request small

Three deliberate choices, all of which were measured against a live studio with
18 active packages:

1. **`reasoning_effort: none`.** `qwen/qwen3.6-27b` is a reasoning model. Left at
   its default it spends most of the reply budget thinking out loud — a
   one-sentence answer measured **624 total tokens with reasoning versus 40
   without**, and at `max_tokens: 400` the visible answer was truncated mid-way
   through the reasoning. Worse, that reasoning restated the security rules back
   into the reply body, which the output guard correctly rejected as an
   instruction echo. `reasoning_format: parsed` moves any reasoning to a separate
   field, and `reasoning_effort: none` skips it.
2. **Conditional package detail.** Full package rows are injected only when the
   message mentions price, packages, rates, cost, fees, or inclusions
   (`messageMentionsPricing`). Other questions get a one-line summary (count,
   price range, categories). Measured on the same studio: **~2,070 estimated
   tokens for a pricing question versus ~1,330 otherwise** (9,060 → 6,675 →
   3,724 characters of system prompt across the untrimmed, trimmed, and summary
   forms).
3. **Bounded context.** Last 6 messages capped at ~3,000 characters, at most 10
   package rows (descriptions truncated to 140 characters), at most 6 knowledge
   entries (answers truncated to 240 characters), 400-token reply ceiling.

**Practical throughput.** The security rules alone are ~650 tokens and are not
negotiable, so a realistic request costs 1,300–2,100 tokens. Against the 8,000
tokens-per-minute tier that is roughly **3–5 assistant messages per minute
across the whole platform**, after which users see the `rate_limited` fallback
until the window rolls over. If that proves too tight, in order of preference:
raise the Groq tier, lower `GROQ_PACKAGE_CONTEXT_LIMIT` /
`GROQ_FAQ_CONTEXT_LIMIT` / `GROQ_HISTORY_MESSAGES`, or shorten the owner's
knowledge entries. Do **not** trim the security rules to buy throughput.

---

## 8. Surfaces

| Portal | Mounting point | Owner context |
|---|---|---|
| Client | `resources/views/client/booking-details.blade.php` (studio bookings) | the studio being viewed |
| Studio owner | `resources/views/layouts/owner/app.blade.php` (floating launcher) | the authenticated owner |
| Studio photographer | `resources/views/layouts/studio-photographer/app.blade.php` (floating launcher) | resolved from `tbl_studio_photographers.owner_id` |

All three include the same partial and hit the same endpoints, so behavior and
fallback copy are identical everywhere.

### Endpoints

| Method | Path | Name | Purpose |
|---|---|---|---|
| GET | `/chatbot/config` | `chatbot.config` | Display config (bot name, welcome copy, active flag) |
| POST | `/chatbot/start` | `chatbot.start` | Open a conversation |
| POST | `/chatbot/message` | `chatbot.message` | Send a message, get a guarded reply |
| POST | `/chatbot/end` | `chatbot.end` | Close a conversation |
| GET | `/chatbot/history` | `chatbot.history` | Transcript (owner of the session only) |
| POST | `/chatbot/helpful` · `/chatbot/not-helpful` | `chatbot.helpful` · `chatbot.not-helpful` | Feedback on the last reply |

---

## 9. Testing

```bash
php artisan test tests/Feature/ChatbotFeatureTest.php tests/Feature/ChatbotAiGuardrailsTest.php
```

Both suites use `Http::fake()` plus `Http::preventStrayRequests()`, so they need
no API key and never reach the network. Shared fixtures live in
`tests/Concerns/BuildsChatbotSchema.php`.

`ChatbotFeatureTest` covers the happy path: model-generated replies, prompt
assembly (security rules present, active packages only, knowledge wrapped as
untrusted data), package payload gating, moderation without provider calls, and
history replay.

Not covered by automated tests: the widget's in-browser behavior. It was verified
by rendering `partials.chatbot-widget` and asserting the modal markup, the
resolved `/chatbot/*` endpoints, launcher suppression, the empty-owner no-op, and
the absence of any credential or provider name in the output — but no automated
test drives the modal in a real browser session.

`ChatbotAiGuardrailsTest` covers security: twelve injection and credential-probe
variants (each asserting zero HTTP calls), the off-topic marker, credential and
internals leakage in output, instruction echo, provider errors, transport
timeouts, unusable payloads, request and per-user budget exhaustion, a missing
credential, session-ownership 403, credential absence from endpoint responses,
and that stored transcripts hold only guarded text.
