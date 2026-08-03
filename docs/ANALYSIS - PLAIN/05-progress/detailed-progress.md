# Detailed Progress in Plain Language

> **In plain terms:** This is the complete plain-language progress history, retained in the current workflow.
>
> **Status:** Historical detail retained in the new System Analysis format. For the current normalized status, see the progress tracker.

---


# Capstone B Roadmap — Progress, Plain Language (Phases 1, 2, 3, 8, 9 & 10)

> Non-technical companion to `ROADMAP PROGRESS.md`. Same work, same structure, explained in
> everyday language — what was broken, what was missing, and what now works, without code terms.
> Database table names are mentioned occasionally since they double as plain labels (e.g. "the
> bookings list," "the packages list").
>
> This work has since been merged into the main project. The "check it in a real browser first"
> cautions noted below were never carried out before that merge, so they remain open items.
>
> Phases 4 to 7 have not been started. Phase 8 (the AI assistant) was done ahead of them because it
> came from a separate request and doesn't touch bookings, payments, or payroll at all. **Phases 9 and
> 10 are written up but not built** — both were deliberately research exercises. Phase 9 waits on a
> business decision; Phase 10's first three items don't wait on anything.

Legend: ✅ Done this pass | ✔️ Already fixed prior to this pass (checked, no change needed) | ⚠️ Partial — see note | 📋 Written up — nothing built yet

---

## Phase 11 — Planned first page

| Item | Status | Notes |
|---|---|---|
| Bootstrap landing-page plan | Written up | The future first page, login and registration buttons, and responsive Bootstrap layout are documented in [the plan](../03-planning/landing-page.md). No website code, route, login behavior, or system testing changed; the website still opens on login. |

---

## Phase 12 — Planned studio-management improvements

| Item | Status | Notes |
|---|---|---|
| Core studio-management requirements | Written up | Future registration, security, permit, administration, employee, role, attendance, client, and pricing requirements are documented in [the plan](../03-planning/core-studio-management.md). No implementation order or website change is approved. |

---

## Phase 1 — Stabilize

| # | Item | Status | Notes |
|---|---|---|---|
| 1.1 | Studio registration location bug | ✔️ | Already had a safety check in place for broken location data — no change needed. |
| 1.2 | Online gallery images not showing | ✅ | Gallery photos now check the file actually exists before trying to show it — a placeholder appears instead of a broken image icon if it's missing. *(Update 25 Jul 2026: that turned out to be treating the symptom. The real problem affected the whole system — photos were being saved into one folder while the website was looking for them in a different one, so **anything uploaded after the site went live simply never appeared**. Older photos worked, which is why it went unnoticed. Both folders are now the same one. Details in `prompt/output/05.md`.)* |
| 1.3 | UI misalignment & text overflow | ⚠️ | Long studio/freelancer names on the client dashboard and booking history now get trimmed with "..." instead of overflowing their box. Icons were already loading fine. Targeted fix, not a full visual sweep — a real look-through of every page is still worth doing before final sign-off. |
| 1.4 | Owner profile photo required | ✅ | The owner's profile photo was completely optional despite the form suggesting otherwise — it's now actually required, with the "(optional)" label removed and a required mark added. |
| 1.5 | Payment verification / lost payments | ✅ | **The most important fix.** If a customer paid and closed their browser before returning to the site, the payment used to get "lost" — the system had no way to confirm it succeeded. Now the payment providers (PayMongo and Stripe) directly notify the platform the instant a payment succeeds or fails, so nothing depends on the customer coming back. Failed payments now also notify the customer. A couple of related record-keeping gaps were fixed alongside this. |
| 1.6 | Purchase request escalation reminder | ✔️ | The automatic overdue-purchase-request reminder was already scheduled to run — no change needed. |

---

## Phase 2 — Complete

| # | Item | Status | Notes |
|---|---|---|---|
| 2.1 | Starting price on services | ✅ | Studios and freelancers can now show a starting price on each service, so customers see a price hint before they start booking. Also fixed a page that was crashing for freelancers editing their services. |
| 2.2 | Owner income report | ✅ | Owners can now see income broken down by service category — bookings, revenue, platform fee, net income — right on their dashboard, and export it the same way as their other reports. |
| 2.3 | Photographer assignment deadline | ✅ | When an owner assigns a photographer, there's now a 24-hour deadline for the photographer to respond. Both sides can see it, and overdue ones are flagged. |
| 2.4 | Visual booking calendar | ✅ | Clients now see an actual color-coded calendar when picking a date — green for available, red for fully booked, grey for closed days — instead of a blank date field. |
| 2.5 | Studio-side booking cancellation | ✅ | Studios can now cancel a confirmed booking themselves with a required reason. The client is notified, and the payment gets flagged for manual refund review. |
| 2.6 | Portfolio gallery (no booking needed) | ⚠️ | Studios and freelancers can now upload portfolio photos that aren't tied to any booking — useful for brand-new studios with nothing completed yet to show. A "Past Work" tab showing completed-booking galleries alongside the portfolio was **not** built this round. |
| 2.7 | Pending booking auto-expiry | ✅ | Bookings left unconfirmed for 48 hours now automatically cancel themselves, notify both sides, and show the client a countdown before that happens. |
| 2.8 | Expand notification coverage | ✅ | Several important events now actually send a notification that didn't before: booking completed, photographer assigned, review received. A few more (gallery published, assignment-deadline warnings, subscription-expiry reminders) were built into the message system but not yet triggered anywhere — some of those got hooked up as part of Phase 3. |
| 2.9 | Connect budget to booking payments | ✅ | A client's personal budget tool now actually updates itself as real booking payments happen, instead of being a manual planner that never reflected real spending. Clients get warned if they go over budget. |
| 2.10 | Review moderation + rating totals | ✅ | An admin can now flag or remove a bad-faith review. Star ratings are now calculated once and stored, instead of being recalculated from scratch every single time someone views a profile — and only approved reviews are shown to customers. |

---

## Verification performed (Phase 1 & 2)

- The full automated test suite passed after every item.
- Every changed file was checked for basic coding mistakes — all clean.
- Every changed page was confirmed to load without errors.
- All database changes were applied cleanly to the real working database.
- The payment-notification fix and the new calendar were additionally checked against the live site directly, since the sandboxed preview tool couldn't reach it properly this session.
- A full manual click-through of all 16 Phase 1–2 items across every user type was **not** done. It was recommended before going live, and hasn't happened yet — still open.

## Known follow-ups (not fixed in this pass)

1. **A pre-existing database setup bug** blocks building the entire database completely from scratch in the lightweight test environment. Unrelated to this work; flagged separately.
2. **A pre-existing double-click bug** on the studio's "edit service" button fires the save action twice. Flagged separately.
3. **A settings file with live-looking payment test keys is stored in version history** — pre-existing, unrelated to this work, worth rotating out.
4. **2.6** — the "Past Work" tab (showing completed-booking galleries next to the portfolio) was not built.
5. **A payment success page still doesn't record revenue** the way the newer instant-notification path does (pre-existing gap, separate from the 1.5 fix).

---

## Phase 3 — Core New Features

| # | Item | Status | Notes |
|---|---|---|---|
| 3.1 | Photos on packages, not just text | ⚠️ | Studio owners and freelancers can now attach up to 5 sample photos to each package. Customers see a preview photo right on the package card while choosing, on both places that matter (the booking package picker and the booking details screen). Along the way, a gap was found and fixed: there was previously no way to *edit* a package at all after creating it, only create/view/delete — editing was added since photos need to be replaceable. This platform doesn't have a dedicated public "studio profile" page today, so photo previews were kept to the two package-picking screens where they're actually needed, rather than inventing a new page. |
| 3.2 | Photos get reviewed before the customer sees them | ✅ | Previously, the moment a photographer uploaded photos, the customer could see them immediately — no quality check at all. Now studio uploads (from the owner or their photographer) land as "pending" first, and the owner has to click "Publish to Client" before the customer can see them — the customer also gets notified at that moment. **Freelancers are the exception**: since a freelancer is their own photographer with no one else to review the work, their uploads still publish immediately, same as before. |
| 3.3 | Customers can ask for a photo redo | ✅ | Once a booking is completed, the customer now has 7 days to hit a "Request Revision" button instead of only being able to leave a star rating. Doing so notifies the studio owner and the assigned photographer, and reopens the gallery for new photos — which then goes back through the same review-before-publish step from 3.2. After 7 days the option disappears and the booking is final. A related gap got closed too: nothing previously stopped a photographer from quietly re-uploading over photos the customer had already seen — now that's only allowed once a revision has actually been requested. |
| 3.4 | Free trial for studio subscription plans | ⚠️ | The platform admin can now set a number of free trial days on any subscription plan. If a studio owner subscribes to a plan with a trial, it activates immediately for free, no payment screen at all, and a reminder goes out before the trial ends. **Marked partial, not done:** the plan also called for automatically downgrading an owner who doesn't add a payment method within 48 hours of the trial ending — that half was **not** built, since it needs card-on-file support the platform doesn't have yet. What works today is "start a free trial and give a heads-up." |

### Verification performed (Phase 3)

- Every changed file was checked for basic coding mistakes — all clean.
- Every changed or new page was confirmed to load without errors.
- The full automated test suite still passes, nothing broke.
- The four new pieces of database structure were each tested on their own to confirm the new fields get added correctly and can be cleanly undone if needed.
- **Could not verify against the real, full database this pass** — the local database server on this machine wouldn't start due to unrelated file corruption, and a separate already-known pre-existing bug (see Phase 1/2 follow-up #1) blocks building the whole database from scratch in the lightweight test environment. Neither was caused by this work.
- A full manual click-through in a real browser (publishing a gallery, requesting a revision, uploading package photos, starting a free trial) was **not** done this pass. Recommended before going live, and still not done — same caution as Phases 1 and 2.

### Known follow-ups (Phase 3)

1. **The local database server problem** (see above) blocks real-database testing until it's repaired — this is a machine/environment issue, not something this work caused.
2. **The admin page for editing an existing subscription plan doesn't actually exist yet** — only creating a new plan works today (pre-existing gap, not introduced this pass). The free-trial setting was still wired into the save logic so it'll work the moment that missing page gets built.
3. **The package edit screens repeat a lot of the create-screen's design** since there was no shared building block to reuse — a future cleanup could combine them, but that wasn't part of this pass's job.

---

## Phase 8 — The AI Assistant

> Done 2026-07-25. The old chat box has been replaced with a real AI assistant that only talks about
> photography services. Safety was the top priority throughout, ahead of making it chatty.

| # | Item | Status | Notes |
|---|---|---|---|
| 8.1 | Replace the old scripted chat with a real AI | ✅ | The old chat box only worked by keyword-matching: the studio owner typed out answers in advance, and the box repeated them word for word if a customer happened to use a matching word. It now writes its own answers using a real AI service (Groq), drawing on the studio's actual current packages and prices. **Something odd was discovered along the way:** the system had a chatbot toolkit installed and listed as a dependency, but it was never actually used for anything — the "chatbot" was entirely hand-written keyword matching. That unused toolkit has been removed. No database changes were needed. |
| 8.2 | Keep it strictly to photography | ✅ | The assistant will only discuss photography services — bookings, packages, prices, what's included, services, schedules, availability, and how to reach the team. Ask it anything else and it politely says it can only help with photography questions. Its rules live in the application's code, not in a settings screen, so nobody can accidentally (or deliberately) weaken them from the admin area, and the rules are re-sent with every single message so a long conversation can't gradually talk it out of them. It's also told to only state facts it has actually been given — it must never invent a price or a date. |
| 8.3 | Safety checks on the way in and on the way out | ✅ | Before a message reaches the AI, the system screens out rude language, spam, and — new this round — attempts to trick the assistant into breaking its rules or revealing private technical information. Those messages are answered locally and never sent onward. After the AI replies, the answer is checked *again* before anyone sees it: if it has drifted off-topic, quoted its own instructions, or contains anything resembling a password or internal setting, the whole reply is thrown away and a safe standard message is shown instead. Discarded replies are never saved or recorded anywhere. The system also never reveals *which* check a message tripped, so the safeguards can't be probed and mapped. |
| 8.4 | Protecting the key, handling failures, staying within the usage allowance | ✅ | The AI service key stays on the server and is read in exactly one place in the code. It never reaches a browser, a page, a log, an error message, or any document. The AI service also has a usage allowance (so many messages and so much text per minute and per day), so the system counts its own usage and stops itself just short of the limit rather than being cut off by the provider. Individual accounts have their own smaller cap so one person can't use up the whole studio's daily allowance. If anything goes wrong — the service is down, slow, or misconfigured — users see a short "temporarily unavailable" note and nothing more. No technical details are ever shown. |
| 8.5 | Available everywhere, and documented | ✅ | The chat window used to exist on exactly one customer page, written directly into that page. It's now a single reusable component available to customers, studio owners, and studio photographers alike. The owner's admin screen was relabelled to match how it now works: what used to be "chatbot answers" are now "studio knowledge" facts the assistant can draw on, with a clear note that replies are AI-written and that the safety rules can't be changed from that screen. A new reference document explains the whole setup, and every older document that still described the old chat box was updated. |

### Problems found and fixed along the way (not part of the plan)

Three genuine security problems already present in the old chat code, all fixed:

1. **Anyone logged in could read anyone else's chat history.** The system never checked that a conversation actually belonged to the person asking for it. Now it does, and there's an automated test to make sure it stays that way. This was the most serious of the three.
2. **Internal error text was being shown to users.** When something went wrong, the raw technical error was sent straight to the browser. Users now see a short, plain message; the technical detail goes to the private log only.
3. **One studio owner could edit another studio owner's chat answers.** The edit, delete, and enable/disable actions never checked who owned the entry. They do now.

### Verification performed (Phase 8)

- The full automated test suite passes — 62 checks, 39 of them about the assistant, including 25 new ones dedicated purely to security.
- The security tests deliberately stand in for the real AI service, so they cost nothing to run, need no key, and never contact the outside world.
- What those tests prove: twelve different trick-question and password-fishing attempts are all refused **without the message ever leaving the building**; off-topic answers are swapped for the standard reply; anything password-like in an answer is thrown away; the service being down, slow, or misconfigured all produce the same harmless message; the usage allowance genuinely stops requests; one user can't read another's conversation; and the key never appears in anything sent to a browser.
- Unlike earlier phases, this one **could** be tested against the real outside service — and was. Real questions about wedding packages came back with the studio's actual prices, and attempts to extract the assistant's instructions or its key were refused.
- Every changed page was confirmed to load without errors, and the new chat component was checked to confirm it contains no key and doesn't even mention which AI service is being used.
- **A manual click-through while logged in was not done.** Reaching the owner or customer area means signing into a real account, which was outside what could be done this pass. The underlying request handling was tested automatically instead. Same standing caution as the earlier phases.

### Known follow-ups (Phase 8)

1. **The settings file with live keys is still stored in version history — the keys need replacing.** This was already flagged back in Phase 1/2 (follow-up #3) for payment test keys. It is now more serious, because a live AI service key was added to that same file. The file needs to be removed from version tracking, and then **every key that has ever been in it must be replaced with a fresh one** — removing the file stops it being saved in future but does not erase the copies already in the project's history. This was deliberately left for you to do, because replacing the keys has to happen at the same time.
2. **The assistant can only handle roughly 3 to 5 messages per minute across the entire platform.** This is a limit of the current (free-tier) AI service plan, not a flaw in the build: the safety rules that go with every message are themselves substantial, and the plan allows only so much text per minute. Beyond that, users briefly see "the assistant is busy, try again in a moment." The build already minimises this as far as it sensibly can — full package details are only attached when someone actually asks about prices, and the conversation history sent along is kept short. If more capacity is needed, upgrade the AI service plan. Trimming the safety rules to buy speed is not an acceptable trade.
3. **This particular AI model needed a setting changed to be usable.** It's a "thinking out loud" model: by default it wrote its reasoning into the reply, which used fifteen times more of the usage allowance, cut off the real answer partway, and repeated the safety rules back into the reply — which the safety check then correctly rejected. The first live test therefore refused a perfectly normal question about wedding packages. A configuration setting now suppresses that, and it works correctly. Worth re-checking if the AI model is ever swapped for a different one.
4. **A leftover unused screen and an unfinished page** sit in the same admin menu as the assistant: an old chat-history component that isn't reachable from anywhere, and an "Inquiries" page that's still an empty template. Both pre-date this work and were left alone.
5. **The payment services still record more detail in their logs than they should.** The assistant now follows a strict rule of never logging message content. The two payment integrations were not brought up to that standard this round — worth a separate pass.
6. **There's no "was this helpful?" button on assistant replies yet.** The behind-the-scenes support for it exists (and is now properly secured), but nothing in the interface uses it. A small future addition.

---

## Phase 9 — What happens when a photographer cancels a booking the customer already paid for

> Written up 26 Jul 2026. **Nothing was built this round, on purpose.** The request was to study the
> problem, lay out several possible answers rather than pick one, and list the decisions the business
> has to make before anyone writes code. Full write-up in the planning folder.

**The situation.** A customer books a shoot and pays. The studio owner assigns a photographer. The
photographer accepts, then later backs out — illness, an emergency, a clash, or simply changing their
mind. The customer's money is with the platform, the event date is often one that cannot move, and the
customer never chose that photographer in the first place.

**What happens today: almost nothing.** The photographer's assignment is marked cancelled and that is
the end of it. The booking itself still says the shoot is in progress. The owner isn't told — they only
find out if they happen to open that booking. The customer is never told at all. No decision is made
about the money.


**And there's a trap in it.** Because a photographer accepting a job switches the booking to "in
progress," and because "in progress" is exactly the state that stops an owner from swapping a
photographer or removing one, the owner is locked out of both of the obvious fixes. The only way out is
to cancel the whole booking on a customer who did nothing wrong — and even that only sets an internal
"needs a refund" marker that nothing in the system actually acts on.

| # | Item | Status | Notes |
|---|---|---|---|
| 9.1 | Let the owner rescue a stuck booking | 📋 | **Doesn't depend on any decision — this one should be done first.** Undo the lock described above, so an owner can bring in another photographer instead of scrapping the booking. |
| 9.2 | Tell somebody when it happens | 📋 | **Doesn't depend on any decision either.** Notify the owner immediately and the customer promptly, and put a clock on it so an unresolved cancellation is chased rather than forgotten. The messaging system already supports this — it simply is not being called. |
| 9.3 | Send a different photographer, same date and time | 📋 | Needs a decision on who chooses, and whether the customer may say no to the replacement. The best outcome by far: the shoot still happens, on the day, and no money has to move. The system can already work out which photographers are genuinely free. |
| 9.4 | Move the booking to another date | 📋 | Needs a decision on who chooses. There is currently no way to change a booking's date at all — that would have to be built. Useless for a wedding or a graduation, which cannot be moved. |
| 9.5 | Give the money back | 📋 | Needs decisions on automatic versus manual refunds, and who absorbs the platform's fee. **The platform currently cannot issue a refund at all** — neither payment provider connection has that ability, and the existing "needs a refund" marker is read by nothing. This is the single biggest piece of work in the phase. |
| 9.6 | Offer credit toward a future shoot instead | 📋 | Needs a decision on whether credit is ever offered in place of cash. There's no such thing as a customer credit balance in the system today; building one is the largest job here and creates a real financial obligation to track. It should never be the *only* option offered when the cancellation was the studio's fault. |
| 9.7 | Keep a record against the photographer | 📋 | Needs a decision on whether it carries any consequence. Right now a photographer who cancels constantly looks exactly the same as one who never has — the reason they give is stored and then never looked at again. |
| 9.8 | Look outside the studio for a replacement | 📋 | Needs a decision on how far to look. If nobody at the studio is free, check off-duty staff on overtime, then **freelancers already on the platform** in the same city and category. This is the biggest missed opportunity in the whole phase — the platform is already full of freelancers with their own calendars, and none of them has ever been reachable when a studio is stuck. Freelancers would opt in, never be volunteered. |
| 9.9 | Refund the difference if the replacement is a step down | 📋 | Needs the refund ability (9.5) to exist first. A ten-year lead photographer swapped for a first-year assistant is the same booking on paper and a different thing in reality. Same or better → no change. Worse → the difference comes back automatically, without the customer having to ask. |
| 9.10 | Make last-minute cancelling harder | 📋 | Needs a decision on where the line sits. Today a photographer can quit with one click at any moment right up until they arrive — cancelling three weeks ahead and cancelling twelve hours ahead use the exact same button. **Cheapest change here and the one that helps most:** by the morning of the event every available answer is a bad one, so the real win is fewer last-minute cancellations in the first place. |
| 9.11 | Name a backup photographer on big bookings | 📋 | **Doesn't depend on any decision.** A named second photographer attached from the start, who steps up automatically if the first one quits. Costs nothing until it's needed. |

**Nine possible answers were written up**, not one: send a substitute from the studio, look outside the
studio for one, move the date with the same photographer, move it with a different one, refund
everything, refund part of it, refund just the difference if the replacement is a step down, give credit
toward a future shoot, or simply handle it by hand within a strict deadline. Each is described in terms
of what the customer experiences, what happens to their money, whether the shoot still goes ahead, and
what would have to be built.

**Nine decisions have to be made before building anything**, and they are business questions, not
technical ones: who picks the remedy, whether the customer can refuse a substitute, whether refunds are
automatic, who absorbs the platform's fee, whether credit is ever offered instead of cash, whether
holding back part of a payment is ever fair when the *studio* cancelled, what happens to a photographer
who does this repeatedly, how far outside the studio to look for a replacement, and whether last-minute
cancelling should be restricted.

---

### The case that really matters: the event is tomorrow and it cannot be moved

The wedding is booked, the guests are invited, the venue is paid for. It happens **on that day**. The
photographer quits with days — or hours — to go.

In that situation most of the answers above stop being useful:

- **Moving the date** — impossible. That's the whole problem.
- **A refund** — correct on paper, useless in practice. Money back at 7am on a Saturday does not get
  anyone photographed, and nobody rebooks a wedding photographer that morning.
- **Credit toward a future shoot** — worse. It offers a future occasion to replace one that only happens
  once.

**Only finding a replacement helps.** So the real question isn't "which remedy" — it's **how far do we
look, and how fast.**

**The search should widen one step at a time**, each step only when the last comes back empty: another
photographer at the same studio → the studio's off-duty staff offered overtime → **freelancers already on
the platform** → another studio → and finally, if genuinely nobody can be found, a full refund with
something extra on top.

**How much notice there is changes what the customer is owed.** Weeks out, they get a choice — see the
replacement's profile, say no, take a refund and rebook elsewhere. Days out, that choice narrows. Hours
out, there is no time to ask: the owner sends whoever is qualified and available and tells the customer
who is coming. At that point the customer's protection stops being choice and becomes money — which is
exactly what item 9.9 is for.

**And a replacement alone isn't always enough.** If the stand-in is less experienced than the person
booked, the customer should get the difference back automatically. If nobody at all can be found, that
should be a designed outcome — full refund, compensation on top, a real apology, and a mark against the
photographer who caused it — not something the business improvises on the day.

**Prevention beats all of it.** By the morning of the event every option is a bad one. Making last-minute
cancellation harder (9.10) and naming a backup on big bookings (9.11) are both cheap, and both stop the
emergency happening at all.

---

### What we'd actually recommend building

Written up as a **recommendation, not a decision** — the request was to lay out options, not pick one.
**Nine things, in three groups:**

**Must build — four.** Without these the system is broken, not just limited.
1. Unlock the owner so they can rescue a stuck booking (9.1)
2. Tell the owner and customer, on a clock (9.2)
3. Send a replacement from the studio (9.3)
4. Be able to issue refunds at all (9.5)

Items 1 and 2 need no decision from anybody and could start tomorrow. Item 4 is the one genuinely big
project — the platform currently cannot refund anyone.

**Build next — three.** Turns "not broken" into "actually good": look outside the studio for a
replacement (9.8), refund the difference on a downgrade (9.9), and track photographers who cancel
repeatedly (9.7).

**Prevention — two.** Cheap, and worth more than any remedy: restrict last-minute cancelling (9.10) and
name a backup on high-value bookings (9.11).

**Don't build — two.**
- **Credit toward a future shoot (9.6).** The largest job of the lot, it creates a real ongoing financial
  obligation to track, and offering credit instead of cash when the *studio* cancelled looks like holding
  the customer's money hostage. Refunds cover the same ground honestly.
- **Changing a booking's date (9.4).** It only helps when the date can move — which is exactly the
  situation that was never an emergency. Worth building if customers want to reschedule in general, but
  not as an answer to this problem.

**Other problems noticed while writing this up** (all left alone, none of them new):

1. When a customer cancels their own booking, the system wipes the record that they had paid. That will
   conflict with anything built here.
2. Freelancer bookings are a different problem entirely — a freelancer *is* the photographer, so there
   is nobody to substitute. That deserves its own study.
3. Bookings needing several photographers aren't covered by any of the nine options — one of four
   dropping out is a short-staffing problem, not a cancellation.
4. Freelancers are proposed as the rescue for studio bookings, but have no rescue of their own — if a
   freelancer cancels on their own client, there is nobody to substitute. That needs its own study.

---

## Phase 10 — What happens when a studio's subscription runs out

*Written up on 27 July 2026. Nothing was built. Full write-up:
`docs/04-REFERENCE/SUBSCRIPTION LIFECYCLE.md`.*

Studio owners pay the platform a monthly or yearly subscription, and some plans come with a free trial.
The request was to check whether that whole arrangement — starting a trial, the trial running out,
paying, renewing, cancelling, and what happens to the account afterwards — is properly thought through
and properly written down.

It is not. Two things came out of the review, and both are larger than a documentation problem.

**A free trial never ends.** When an owner starts a 14-day trial, the system correctly notes the date
the trial should finish — and then separately gives the account a full month of access anyway. Nothing
ever checks the trial's finish date to actually stop anything. So a 14-day trial is really 30 free days,
and a 30-day trial on the yearly plan is really a free year. The system also never asks for a card when
the trial starts, so even if the trial did end, there would be nothing to charge. The reminder email the
owner receives says "add a payment method to keep your plan active" — and there is no screen anywhere in
the platform where they could do that.

**Letting a subscription lapse costs the owner nothing.** There is no check anywhere that asks "is this
studio actually paying?" before letting the owner use the platform. An owner whose subscription expired
a year ago — or who never subscribed at all — still has a studio listed in the marketplace, still takes
bookings, still runs payroll, still uses everything. The one exception is registering a *second* studio,
which does require a subscription. The first one is free. In other words, **the platform sells
subscriptions and nothing about the platform depends on having one.**

| # | Item | Status | Notes |
|---|---|---|---|
| 10.1 | Make a trial actually last as long as it says | 📋 | **Doesn't depend on any decision — do this first.** A 14-day trial should be 14 days, not 30. Right now the platform gives away a full billing period with every trial signup. |
| 10.2 | End trials when they end | 📋 | **Doesn't depend on any decision.** Something has to check the trial's finish date and act on it. Today one reminder goes out beforehand and then nothing ever happens. Also needs somewhere for the owner to actually add a card, which doesn't exist yet. |
| 10.3 | Record when a subscription has expired | 📋 | **Doesn't depend on any decision.** The system has a place to record "expired" and has never once written it. A subscription that ran out a year ago still shows as active everywhere. |
| 10.4 | Add a short grace period for a failed payment | 📋 | Needs a decision on how long. Today a failed payment kills the subscription instantly, with no retry and no warning — and a card being declined is usually the bank's doing, not the owner deciding to leave. |

| 10.5 | Actually restrict what an unpaid studio can do | 📋 | Needs four decisions, and item 10.3 first. **The biggest piece of work here, and the reason the rest matters.** The recommendation is to hide the studio from the marketplace and stop new bookings, while the owner keeps their login, their studio, and every record they've ever had. |
| 10.6 | Warn the owner before anything changes | 📋 | Needs a decision on the grace period. A reminder message for expiring subscriptions was built two phases ago and has never been switched on. Nothing about subscriptions is sent by email at all today — only inside the site, so an owner who doesn't log in hears nothing. |
| 10.7 | Let an owner come back | 📋 | Needs a decision on how subscriptions are counted. There is currently no way to restart a cancelled or expired subscription. Nothing would need restoring — the recommendation removes access, never data. |
| 10.8 | Charge the card automatically each period | 📋 | Needs a decision on whether trials require a card, and depends on earlier payment work. There is no automatic renewal at all today: each subscription is a single manual payment, and the card is never kept on file. |
| 10.9 | Let owners cancel or change plan after the first three days | 📋 | Owners can only cancel within three days of paying. **After that there is no way to cancel at all**, and no way to upgrade or downgrade either — an owner on a yearly plan is stuck for the year. Cancelling also marks the money as refunded in the platform's own records without actually refunding anything. |

### What we'd actually recommend

Unlike the last phase, this one recommends **one** approach rather than a menu — the request was for a
process, not options.

A subscription should move through clearly named stages that the owner can always see: **on trial →
paying → payment late → grace period → expired**, with cancelling meaning "stops at the end of what
I've already paid for," and a one-click way back at any point. Every stage change is announced before it
happens, by email as well as on the site.

Three principles behind it:

- A trial lasts exactly as long as it says it does.
- "Expired" is something the system records and tells the owner about, not something that quietly
  becomes true when a date slips past.
- Cancelling never takes away time already paid for.

### What should happen when it expires

**Restrict, never delete.** An expired subscription is a billing matter, not a punishment. Deleting the
account would destroy other people's records too — customers' booking history, employees' payroll, the
platform's own revenue figures — all to penalise a card that stopped working.

So: the owner keeps their login, their studio, their staff list, and every booking, payment, gallery and
payroll record they've ever had, and can still read all of it. What stops is the part they were paying
for — the studio disappears from the customer-facing marketplace and can't take new bookings.

**Bookings already paid for are honoured to the end, whatever the subscription says.** A customer who
paid for a shoot in three weeks should never be affected by a billing argument between the studio and
the platform.

### Decisions still needed

Six, all business questions rather than technical ones: how long a grace period should be · whether a
card is required to start a free trial · exactly what an expired owner can still do · whether a
subscription belongs to a studio or to the owner (the system currently answers both ways) · whether
already-paid bookings continue after expiry · and whether there should be a free tier at all — an
earlier plan assumed one existed, and none does.

The first three items (10.1, 10.2, 10.3) need none of these answers and could start immediately.

### Things that were written down wrongly, now fixed

- Two documents said an owner needs a paid subscription before they can create a studio. **The first
  studio is free** — the requirement only kicks in from the second one.
- An earlier plan said that when a subscription expires the studio should "drop to the free tier."
  **There is no free tier.** All eight plans on sale cost money.

### Other things noticed while writing this up (all left alone)

1. **Freelancer subscriptions barely exist.** The platform sells four freelancer plans, two with free
   trials, and there is no way for a freelancer to subscribe to any of them.
2. **The admin can create a subscription plan but not edit one** — the edit page was never built, so a
   trial length can't be changed after the fact.
3. **Every plan in the sample data allows exactly one studio**, which means the one subscription check
   that does exist could never let anyone past it anyway.
4. **The platform still earns its commission on every booking whether the studio subscribes or not** —
   nobody has decided whether that's intended.
5. **Studio staff — HR, finance, photographers — have their own logins**, and no document says whether
   they lose access when the owner's subscription lapses.
