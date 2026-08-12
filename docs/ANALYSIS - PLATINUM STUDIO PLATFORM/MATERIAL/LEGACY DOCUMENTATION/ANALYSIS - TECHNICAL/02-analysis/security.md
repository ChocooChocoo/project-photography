# Security

> **In plain terms:** Login roles restrict access, sensitive keys stay on the server, and the photography assistant filters unsafe requests and responses. These findings describe protections evidenced in the current project.

### ANL-005 — Chatbot guardrails

**Area:** AI integration and security.  
**Observation:** The photography assistant uses a server-side Groq client, input and output guardrails, cache-backed budgets, ownership checks for sessions, and safe fallback messages. The model and key come from service/environment configuration, not browser code.  
**Evidence:** `app/Services/ChatbotService.php`, `app/Services/Ai/`, `config/services.php`, `tests/Feature/ChatbotAiGuardrailsTest.php`.

### ANL-011 — Route and permission protection

**Area:** Authentication and authorization.  
**Observation:** Portal routes use role middleware and permission middleware. Cross-portal chatbot routes require authentication. Payment webhook routes validate provider signatures in their handlers.  
**Evidence:** `routes/web.php`, `bootstrap/app.php`, `tests/Feature/Payment/WebhookTest.php`.

No credential values are documented here. Runtime secrets belong only in protected environment configuration.
