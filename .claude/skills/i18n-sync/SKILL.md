---
name: i18n-sync
description: Use when adding or changing any user-visible string, and when a translation is missing, shows a raw key, or appears in the wrong language. Checks key parity across locales, hunts hardcoded strings in API responses and components, and clears the translation caches. Триггеры — «нет перевода», «добавь строку», «показывает ключ вместо текста», «переведи интерфейс», «проверь локали», «на английском вместо русского», i18n.
---

# Keeping translations in sync

Two independent stores, and a string can be missing from either:

| Side | Files | Used as |
|---|---|---|
| Backend | `lang/<locale>/*.php`, `lang/<locale>.json` | `__('key')` / `trans('key', [], $locale)` |
| Frontend | `webapp/i18n/locales/<locale>.json` | `$t('key')` |

Confirm the actual locale list before assuming it (`ls lang/`, `ls webapp/i18n/locales/`) — projects
gain languages, and a locale added to one side only is the most common cause of "it works in English".

## 1. Key parity — no locale may be short a key

```bash
cd webapp/i18n/locales
for f in *.json; do jq -r 'paths(scalars) | join(".")' "$f" | sort > "/tmp/keys.$f.txt"; done
diff /tmp/keys.en.json.txt /tmp/keys.ru.json.txt        # < only in en, > only in ru
```

Every line of output is a bug: `<` is an untranslated string the user will see as English or as a raw
key, `>` is a leftover from a deleted feature.

The **backend** side has an automated guard — `tests/Feature/Structure/LocaleParityTest.php` compares
every `lang/<locale>` directory against `lang/en` and also fails on empty values (an empty string
renders nothing and fails nothing, so a button loses its label in one language only):

```bash
make test args="--filter=LocaleParity"
```

There is no equivalent guard for `webapp/i18n/locales/` yet, so the frontend diff above is a manual
step. Run it before every commit that touched a `.vue` or a store.

## 2. Unused and missing keys

```bash
# keys referenced in code but absent from the locale files
grep -rhoE "\\\$?t\(\s*['\"]([a-zA-Z0-9_.]+)['\"]" webapp/app --include=*.vue --include=*.ts \
  | sed -E "s/.*['\"]([a-zA-Z0-9_.]+)['\"].*/\1/" | sort -u > /tmp/used.txt
comm -23 /tmp/used.txt /tmp/keys.en.json.txt      # referenced, never defined → renders as the key
```

A dynamic key (`t('status.' + row.status)`) will not be found by that grep and will not be found by
your eyes either — write those key families out in a comment next to the call.

## 3. Hardcoded strings — the ones the checker cannot see

```bash
grep -rnE "abort\([0-9]{3}, *['\"]" app/
grep -rnE "'message' *=> *['\"][A-Z]" app/
grep -rn ">[A-Za-zА-Яа-я][^<>{]\{3,\}<" webapp/app --include=*.vue | grep -v '{{'
```

Rules that produce those findings:

- Every string a user can read goes through the translation helper — validation messages, exception
  messages, flash messages, notification and email bodies, button labels, empty states.
- Messages sent **to** a user out of band (mail, bot, push) are rendered in **that user's** locale,
  explicitly: `__('key', [...], $user->locale)`. The queue worker's locale is not theirs.
- Domain exceptions carry a translation **key**, not a translated string — the renderer translates
  once, at the edge. Translating in the constructor and again in the renderer produces a key echoed
  back to the user.

## 4. Nothing changed but the translation is still wrong

Almost always a cache:

```bash
make artisan args="optimize:clear"      # config + cached translations on the backend
```

For the frontend, a stale build cache: stop the dev server, remove `webapp/.nuxt` and
`webapp/node_modules/.cache`, start it again. If the raw key is still rendered, the key genuinely is
not in that locale file — go back to step 1.

## 5. Adding a string, the whole loop

1. Add the key to **every** locale file, both sides if both sides use it.
2. Nest by feature, not by page (`things.form.name_label`, not `page3.label7`).
3. Write the other-language values yourself; do not leave a copy of the English string as a
   placeholder — it looks translated and never gets revisited.
4. Re-run step 1, then look at the screen in each language — see skill `local-verify`. Text length
   differs by language, and a label that fits in English can break the layout elsewhere.
