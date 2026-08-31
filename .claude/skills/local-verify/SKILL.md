---
name: local-verify
description: Use BEFORE pushing or deploying any change to a screen or an endpoint, and whenever the user asks whether something actually works. Brings the stack up, walks the changed scenario in a real browser, screenshots it and looks at the screenshot. Триггеры — «проверь локально», «работает?», «посмотри глазами», «покажи как выглядит», «перед пушем проверь», «залей» (сначала проверка, потом пуш).
---

# Verify it locally before it leaves the machine

Green tests prove the code does what the test says. They do not prove a screen renders, a button is
reachable with a thumb, or that the field is not off the bottom of the viewport. **Anything the user
will look at gets looked at first — here, not on the stand.**

## 1. Stack up

```bash
make up
docker compose ps                    # every service Up / healthy
curl -sS http://localhost:8080/healthz | head -c 300
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:3000
```

Confirm the ports against `docker-compose.yml` rather than trusting this file. Anything not healthy is
an environment problem, not a code problem — skill `env-doctor`.

## 2. Pick the scenario from the diff, not from memory

```bash
git diff --stat
git diff --name-only | grep -E '^webapp/|routes/|app/Http/'
```

For each changed screen or endpoint write down the path a user takes: from where they start, what
they tap, what they should see. That list is the test plan; it is usually three to five steps.

## 3. Walk it in a browser

Drive it with whatever browser automation this session actually has (Playwright, a browser tool).
The minimum walk:

1. log in as a normal, **non-admin** user;
2. reach the changed screen the way a user reaches it — through the navigation, not by typing the URL;
3. perform the action with real input, including one invalid input;
4. screenshot at a phone-sized viewport (≈390×844) and at a desktop one.

If no browser automation is available in this session, say so plainly and give the user the exact URL
and steps to click — do not silently downgrade to "tests pass, looks fine".

## 4. Actually look at the screenshot

Open the image and read it. Things that only the eye catches:

- text clipped, wrapped or overflowing its container; a label truncated in one language and not another;
- a control below the fold, or under the keyboard, on a phone-sized viewport;
- the empty state and the loading state — did you ever see them, or only the happy full list?
- an untranslated key rendered as `some.key.name`;
- contrast and hierarchy: is the primary action obviously the primary action?

## 5. Check the console and the network

Browser console errors and failed requests are findings even when the page "works". A 500 that the UI
swallows is a bug that will surface in production as an alert.

## 6. Then push

Only now: skill `git-conventional-commits`, then skill `deploy-branches`. Report to the user what you
walked, with the screenshot attached — "verified" without evidence means nothing.

## Rules

- No push of a UI change that was never rendered. Not "it is a small change", not "the test covers it".
- No demo data seeded into anything but the local database.
- If a check cannot be done locally, name the gap out loud instead of skipping it quietly.
