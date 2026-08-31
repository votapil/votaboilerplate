---
name: deploy-branches
description: Use when the user asks to put work on a staging stand, promote a feature to production, reset the staging branch, redeploy without a code change, or asks which branch deploys where. Триггеры — «залей на ревью», «выкати на стейдж», «промоуть в прод», «какая ветка куда деплоится», «пересобери стенд», «сбрось ветку на main», staging deploy, promote, workflow_dispatch. Это про окружения; создание фича-ветки внутри Spec Kit — speckit-git-feature.
---

# Branches and environments

## Configure this once per project

Fill this table in when the project gets its environments, and keep it as the single answer to
"which branch deploys where". Until a deploy pipeline exists, only the `main` row is true and this
skill is about the branch model, not about shipping.

| Branch | Environment | URL | Workflow | Trigger | Test gate |
|---|---|---|---|---|---|
| `<DEFAULT_BRANCH>` (`main`) | **production** | `<PROD_URL>` | `<PROD_WORKFLOW>` | push | ✅ suite must pass |
| `<STAGING_BRANCH>` (e.g. `review`) | staging | `<STAGING_URL>` | `<STAGING_WORKFLOW>` | push | ❌ none — WIP welcome |

Confirm the reality instead of trusting the table:

```bash
grep -rn "branches:" .github/workflows/*.yml
```

## The model: trunk-based, staging is disposable

`main` is production. The staging branch is **a pointer to "what is on the stand right now"** — you
reset it whenever you like. It is not a `develop` branch, nothing is based on it, and its history is
worthless.

⚠️ Never push work in progress to `main` "just to see it on the stand" — that ships it to users.

## Mirror staging to production (the common case)

Runs from anywhere; it uses the remote refs, not your working tree:

```bash
git fetch origin
git push origin origin/main:<STAGING_BRANCH> --force
```

If staging already equals `main`, git prints **"Everything up-to-date"** and **no deploy fires** —
there is no new commit to push. That is expected, not a failure.

## Redeploy the stand without a code change

A push of the same SHA is not a push event. To rebuild on the current commit, dispatch the workflow:

```bash
gh workflow run <STAGING_WORKFLOW> --ref <STAGING_BRANCH>
gh run watch "$(gh run list --workflow=<STAGING_WORKFLOW> --limit 1 --json databaseId -q '.[0].databaseId')"
```

## Stage one or more features on top of the production baseline

```bash
git fetch origin
git checkout -B <STAGING_BRANCH> origin/main      # reset the stand to prod
git merge --no-ff feat/a feat/b                   # layer the candidates on top
git push origin <STAGING_BRANCH> --force          # → deploys the combination
```

When the combination is green on the stand, **promote each feature branch into `main` individually**:

```bash
git checkout main && git pull
git merge --no-ff feat/a                          # only what actually passed
git push origin main                              # test gate → production deploy
```

Then rebuild the staging branch from `main` for the next batch.

### Why promote feature branches and not `staging → main`

- The staging branch usually holds **more than is ready** — a half-finished branch, debug commits.
  Merging it ships all of that.
- You cannot release `feat/a` if `feat/b` failed on the stand; merged together, they are glued.
- It pollutes `main` with throwaway integration merges, and diverges again the next time staging is
  force-pushed.

Full gitflow earns its overhead only with scheduled releases and a team. For continuous deploy,
trunk plus a disposable stand wins.

## Verify

```bash
gh run list --branch <STAGING_BRANCH> --limit 3
gh run list --branch main --limit 3
```

## Common mistakes

| Mistake | Reality |
|---|---|
| Push the same SHA and expect a deploy | No ref change → no push event → no deploy. Dispatch the workflow instead. |
| Push WIP to `main` to test it | `main` is production. That is what the stand is for. |
| Base long-lived work on the staging branch | It is force-pushed regularly. Work lives on a feature branch. |
| Expect a test gate on staging | There is none by design. The gate is on `main`. |
| Merge `staging → main` | See above — promote feature branches one by one. |
| Assume a green deploy means a working feature | Verify in a browser: skill `local-verify` before the push, the real URL after it. |
