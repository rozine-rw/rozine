@hussain4real: here is the **S3-B wallet binding target** for when you draft it. The UI for this is already on `dev` from the C3 scaffold. Matching it avoids the integration misses we hit on #141.

**Page:** `Inertia::render('investor/wallet', …)` with `C3InvestorWalletProps` (`resources/js/types/investor.ts`, "Wallet (v2 §2a)"). The smallest valid response is `resources/fixtures/ui/investor-wallet-live-minimal.json`, and the web tests render exactly that fixture. Anything beyond it is additive.

**Facts the UI relies on**
1. `allowed_actions` lists `wallet.deposit` only while a deposit can be sent. The form shows only with `funding.kind === 'deposit'`, a non-null `funding.policy` and at least one method. A null policy means no form, and a post is refused `POLICY_INPUT_REQUIRED`.
2. `wallet.total` = available + held + unissued committed, each counted once. `pending_deposits` sits **outside** `total` because it is not credited.
3. `deposits[]` lists pending/unknown first. Each has an immutable `intent_receipt` (DEPOSIT_INTENT_RECORDED). `credit_receipt` stays **null** until a verified success (DEPOSIT_CREDITED). The Investor never sees a provider reference.
4. The command posts to `actions.deposit` with `{request_id, identity_context_revision, amount, method_id}` and gets back the OperationResource `data: {receipt, current, next}` (`next` is nullable, per #143). On success the UI follows `next` and reloads `wallet, funding, deposits, history, allowed_actions`. It draws no optimistic balance.
5. `links.operation.url` must contain the **literal `{request_id}` token**, and it must resolve to the JSON operation lookup. The UI uses it after a lost answer (timeout, 5xx such as 503 `RETRYABLE_CONTENTION`) and never resends on its own. `OPERATION_NOT_FOUND` → facts refresh → an explicit same-key retry, only while `allowed_actions` still permits it. `IDEMPOTENCY_CONFLICT` is final.
6. Live responses omit `preview_outcome`, and every live URL stays off `/preview/`.

**Browser acceptance I'll run on your draft head** (a real-Chromium journey like #146/#153):
- Deposit → intent recorded (pending, not in total) → synthetic adapter success → credited (total and available rise, credit receipt opens).
- Lost answer: the post succeeds but the client drops the response, then the lookup by the same `request_id` resolves it with no second intent.
- Unknown and failed provider outcomes: shown as "not yet confirmed" and failed, with nothing credited.
- Restricted wallet: no form, and the restriction is named.

Push the draft as early as you like; I'll review at the exact head while CI runs. Meanwhile our lane continues with Phase 2 web states (#158–#161 today).
