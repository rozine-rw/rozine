@hussain4real: the Business wallet compatibility design you asked for (5874329856). It's **design only; no schema or code has changed**, and it needs your agreement before anything is built.

## S4-A1 Business servicing wallet: design comparison for the sole-trader collision

**Basis:** `origin/dev` `f1428e12`, #175 `124eec88`, #176 `ef065d99`, PostgreSQL 17 (`.github/workflows/tests.yml:51`). This is design only; no code or schema has changed. It answers 5874329856.

## 0. Summary
Hussain is right about our first proposal: a shared table breaks S3-B's assumptions. I recommend **Option C**. Business wallets go in their own `business_wallets` table, and `investor_wallets` stays exactly as it is. A new `wallets` supertype gives both kinds one shared ledger, and the database ties each ledger kind to the matching wallet owner. The only S3-B change is the ledger's constraints. No PHP in S3-B, #175 or #176 changes.

## 1. Inventory: what assumes one wallet per Party (file:line on dev unless marked)

| # | Item | Location | Assumption |
|---|---|---|---|
| 1 | Global unique on `party_id`; `(id, party_id)` unique | `104818:21,24` | one row per Party |
| 2 | `lockWallet`: `ON CONFLICT (party_id)`, then `where('party_id')->sole()` | `EloquentWalletStore.php:327,330` | conflict inference and the read |
| 3 | `lockForParty`: the same pair | `EloquentWalletPostings.php:36,38`; the #176 copy at `:37,39` | same |
| 4 | Posting re-lock with `whereKey()->where('party_id')` | `EloquentWalletPostings.php:66`; #176 `:72` | wallet ↔ Party only, no owner |
| 5 | `page()` uses `where('party_id')->first()` | `EloquentWalletStore.php:92` | picks either row without error |
| 6 | `findDeposit()` uses `where('party_id')->value('id')` | `EloquentWalletStore.php:118` | picks either row without error |
| 7 | `applyOutcome` locks `InvestorWallet` by the intent's wallet | `EloquentWalletStore.php:131-134` | the intent always names an Investor wallet |
| 8 | Deposit FKs: `deposit_intent_wallet_party` → `investor_wallets(id, party_id)`, `deposit_intent_method_party` → `investor_funding_methods` | `104821:42-43` | either owner's wallet would pass |
| 9 | Intent trigger locks `investor_wallets` and checks the method by `party_id` | `104821:112-113` | Investor only |
| 10 | `ledger_accounts.wallet_id` and `ledger_entries.wallet_id` FKs → `investor_wallets` | `104818:28,35` | every ledger wallet is an Investor wallet |
| 11 | `ledger_account_owner` CHECK (kind vs NULL, not kind vs owner) | `104818:58-60`; #176 `100200:24-27` | investor kinds allowed on any wallet |
| 12 | `protect_ledger_entry` locks `investor_wallets` | `104818:84` | |
| 13 | `protect_ledger_line`: `account.wallet_id <> entry.wallet_id` | `112500:72` | both sides non-NULL |
| 14 | Overdraw check: `account.wallet_id = entry_wallet` | `140000:60-63`; #176 `:115-118` | |
| 15 | #172 anchor and bucket shape (`investor_*` kinds) | `140000:38-57` | |
| 16 | #175 hold binding joins `investor_wallets` on `wallet.party_id = reservation.party_id` | #175 `161335:27-30,36-42` | a sole trader's Business row would match |
| 17 | #175 caller | #175 `EloquentPrimaryReservations.php:83` | |
| 18 | #176 `primary_issue` shape; source CHECK | #176 `100200:30-37,85-112` | kind-based only |
| 19 | Methods and restrictions keyed by `party_id` | `104819:33,47`; `EloquentWalletStore.php:351-372`; `EloquentSyntheticWalletFixtures.php:27` | Investor-scoped cases |
| 20 | Allowlists | `JournalLine.php:10`, `PrimaryPosting.php:24` | |
| 21 | Mandate permissions have no wallet or repayment entry | `MandateAuthority.php:18` | S4-A1 has to add one |
| 22 | Tests and inventory | `WalletSchemaTest.php:78` (`investor_wallets_party_id_unique`), `WalletPostingsTest.php:150-152` (`sole()`), `WalletHttpTest.php:65,282`, factories (`WalletDepositIntentFactory.php:24-27`), `baseline-inventory.md:803` | |

Two points about the current plan:
- The collision is confirmed at `MandateAuthority.php:83`: for a sole trader, the only person is `entityPartyId`.
- Item 8 means the plan's "reuse `RecordDepositIntent`/`ApplyProviderOutcome` unchanged" (c4-plan.md:33) is false under **every** option. The deposit tables are Investor-typed by their FKs.

## 2. Option A: shared table with an `owner` column

**Schema (expand/contract, forward-only):**
- **M1 (expand):**
  - `investor_wallets`: add `owner varchar(10) NOT NULL DEFAULT 'investor'` and `business_id` (FK → `business_profiles`).
  - Add CHECKs `owner IN ('investor','business')` and `(owner='investor' AND business_id IS NULL) OR (owner='business' AND business_id IS NOT NULL)`.
  - Add unique `(id, party_id, owner)`, partial unique `(party_id) WHERE owner='investor'`, and partial unique `(business_id) WHERE owner='business'`.
  - Keep the global unique for now.
  - No UPDATE is needed, so the immutable trigger (`104818:74`) does not fire. A constant `ADD COLUMN DEFAULT` changes metadata only.
- **Deposit binding:** add `wallet_deposit_intents.wallet_owner NOT NULL DEFAULT 'investor' CHECK (='investor')`. Replace item 8's wallet FK with `(wallet_id, party_id, wallet_owner)` → `investor_wallets(id, party_id, owner)`.
- **Ledger binding:**
  - `ledger_accounts.wallet_owner`: `GENERATED ALWAYS AS (CASE WHEN kind LIKE 'investor\_%' THEN 'investor' WHEN kind LIKE 'business\_%' THEN 'business' END) STORED`.
  - `ledger_entries.wallet_owner`: `business` when `kind LIKE 'business\_%'`, otherwise `investor`.
  - Composite FKs `(wallet_id, wallet_owner)` → `investor_wallets(id, owner)`.
  - This binds #175 holds (item 16) and #176 issues (item 18) to Investor wallets without editing either PR. MATCH SIMPLE skips system accounts, whose owner is NULL.
- **Code:**
  - Items 2 and 3 become `ON CONFLICT (party_id) WHERE owner = 'investor'`.
  - Items 2–6 and the #176 copy add `where('owner','investor')`.
  - Add a global scope to `InvestorWallet`; it does not cover raw SQL.
  - Factories set `owner`.
- **Rollout order:**
  1. M1.
  2. Deploy the code. With the predicate, the global unique still satisfies inference; `index_predicate` can match a non-partial index.
  3. M2 (contract): drop `investor_wallets_party_id_unique`, add the Business account and entry kinds, and allow Business rows.

  If M2 ran before step 2, the old `ON CONFLICT (party_id)` would fail with 42P10. It fails loudly, but S3-B would be down.
- **Missed-caller risk (the deciding flaw):**
  - A missed `sole()` returns a 500 only for sole traders.
  - A missed `first()` or `value()` (items 5 and 6) silently shows Business cash to the Investor.
  - Every future Investor caller has to remember the filter.
  - The shared account and Primary tables (#175, #176, S4-B) all touch this one table.

## 3. Option B: separate `business_wallets` table
`business_wallets(id, business_id UNIQUE FK, currency, created_at)` is immutable, plus `business_funding_methods` (append-only, synthetic). `investor_wallets` is untouched, so items 1–7, 16 and 17 need no change. The open question is how the ledger's `wallet_id` handles two wallet tables:
- **B1 (polymorphic `owner_type`/`owner_id`, no FK):** rejected. It loses referential integrity and still rewrites items 12–14.
- **B2 (separate ownership columns):**
  - Add `business_wallet_id` to `ledger_accounts` and `ledger_entries`, drop NOT NULL on `wallet_id`, and add an exactly-one CHECK.
  - This opens a NULL hole: at item 13, `account.wallet_id <> NULL` evaluates to NULL, so an Investor account line on a Business entry **passes**.
  - Items 12–14 and #176's balance function would all need rewriting. The blast radius is high.
- **B3 (a separate Business ledger):**
  - Adds `business_ledger_accounts`, `entries` and `lines`, with their own copies of the balance, seal and overdraw triggers.
  - Changes nothing in S3-B, #175 or #176.
  - The cost: S4-C's `servicing_clearing` spans two ledgers. The payout conservation check becomes a cross-ledger deferred query, with duplicate trigger code to maintain.

## 4. Option C (recommended): supertype `wallets` with investor and business subtypes
- **Tables:**
  - `wallets(id PK, owner, created_at)`, immutable, with unique `(id, owner)`.
  - `business_wallets(id, owner CHECK(='business'), business_id UNIQUE → business_profiles, currency)`, with FK `(id, owner)` → `wallets`.
- **Backfill and supertype rows:**
  - Backfill: `INSERT INTO wallets SELECT id,'investor',created_at FROM investor_wallets`, under `LOCK TABLE investor_wallets IN SHARE ROW EXCLUSIVE MODE`.
  - An AFTER INSERT trigger on `investor_wallets` inserts the supertype row. AFTER row triggers do not fire on `ON CONFLICT DO NOTHING`, so items 2 and 3 are unchanged.
  - A deferred trigger on `wallets` requires exactly one subtype row.
- **Ledger:**
  - Add the §2 generated `wallet_owner` columns on `ledger_accounts` and `ledger_entries`.
  - Swap the item 10 FKs to `(wallet_id, wallet_owner)` → `wallets(id, owner)`. `wallet_id` stays NOT NULL, so there is no NULL hole.
  - Wallet ids are globally unique, so item 13 already stops cross-owner lines. Item 14 already stops a Business overdraw. Items 15, 16 and 18 still apply and are now owner-bound by construction: `primary_*` kinds can only reach Investor wallets.
  - `protect_ledger_entry` (item 12) also locks `business_wallets WHERE id = NEW.wallet_id`.
  - Add `business_available` and `business_deposit_credit` (and S4-C's `business_repayment_debit`) to `ledger_account_owner` and `ledger_entry_source`. The Business shape checks go in a **separate** deferred constraint trigger, so `ledger_entry_balance_check` is not redefined again.
- **Deposits:**
  - New `business_deposit_intents`, `business_deposit_dispatches` and `business_deposit_credits` tables, with FKs to `business_wallets` and `business_funding_methods`.
  - Reuse the domain (`DepositOutcome`, `DepositPolicyTerms`) and the dispatch and apply logic behind an owner-specific store port.
  - Open item: provider references must be unique across both intent tables. For synthetic C4, a lookup routed by owner is enough; a real provider needs a shared reference registry.
- **Authority:** `WithBusinessAuthority` → `business_profiles` lock → `requirePermission('business.wallet.deposit')`. This needs adding to `MandateAuthority::PERMISSIONS`, and existing mandates stay valid. The journal key is `business:<id>`. Investor restrictions (item 19) are never read.
- **Rollout:**
  - One forward migration, sequenced after #176's `100200`, because it replaces the same two CHECKs.
  - `down()` refuses once any `wallets` or `business_wallets` rows exist.
  - The PHP adapter lands after the migration. There is no ordering hazard, because Investor code never references the new tables.

## 5. Tests (all options; Option C in brackets where it differs)
**App level:**
1. A sole trader with both roles gets two distinct wallets. The Investor `page`, `findDeposit` and `lockForParty` return only the Investor id [trivially true in C].
2. An Investor deposit, provider success and replay credit only `investor_available`; `business_available` is unchanged.
3. A Business deposit credits only `business_available`; the Investor wallet revision and balances are unchanged.
4. A #175 reservation hold for the sole trader moves Investor cash only. With Investor available = 0 and Business = 50,000, it returns `INSUFFICIENT_AVAILABLE_FUNDS`.
5. A #176 issue against the sole trader stays Investor-only.
6. S4-C `repay` never debits Investor buckets, and payout credits never reach the Business wallet.
7. A forked concurrent Investor hold and Business deposit on the same Party: no deadlock, and each wallet stays independent.

**Raw SQL, each expecting SQLSTATE 23514 or 23503:**
1. Insert a `primary_hold` on a Business wallet.
2. Insert a line from an `investor_*` account on a Business entry, and the reverse.
3. Insert an `investor_available` account on a Business wallet.
4. Insert a deposit intent naming a Business wallet or a Business method.
5. Insert a `business_*` entry on an Investor wallet.
6. Overdraw `business_available`.
7. Insert a `wallets` row with no subtype, or with two.
8. [A only] A second `owner='investor'` row for the same Party.

**Migration tests:**
1. Backfill parity: supertype count equals `investor_wallets` count.
2. `down()` refuses once rows exist.
3. The existing S3-B, #175 and #176 suites pass **unmodified** [C].
4. `WalletSchemaTest.php:78` passes unmodified [C]; under A it has to be rewritten.
5. `baseline-inventory.md` is regenerated.

## 6. Recommendation: Option C

| | A | B2 | B3 | **C** |
|---|---|---|---|---|
| S3-B PHP changes | items 2–6 | none | none | **none** |
| S3-B schema | unique swap, deposit and ledger FKs | NOT NULL drop, 3 triggers | none | 2 FK swaps, 1 trigger, 2 CHECKs |
| #175 / #176 edits | #176 copy of `lockForParty` | #176 balance function | none | **none** (sequenced after #176) |
| Missed-caller failure | silent wrong wallet | NULL-comparison hole | none | **impossible**: Investor tables hold no Business rows |
| Rollout | 3 steps, ordering hazard | 1 step | 1 step | 1 step |
| S4-C fit | single ledger | single ledger | cross-ledger clearing | **single ledger** |

Reasoning:
- **Blast radius.** C leaves every Investor entry point untouched. #175's party join becomes owner-safe with no edit, because `investor_wallets` holds only Investor rows and `primary_*` entries can only reference Investor-owner wallets.
- **Forward-only risk.** C has a single migration with no expand/contract phase, and nothing at runtime depends on deploy order.
- **Fit with S4-C.** The payout path (Business `business_repayment_debit` → system `servicing_clearing` → Investor `payout_credit`) lives in one ledger. The per-repayment conservation check is a single-ledger deferred query. The existing overdraw guard (item 14) covers `business_available` for free. The payout worker's lock order (entitlement → Investor wallet → ledger) is unchanged.
- **Cost.** The Business deposit tables run parallel to the Investor ones, and provider-reference uniqueness across the two needs deciding before a real provider is used.

What I'm asking Hussain to agree now is the Option C schema direction only; implementing it is separate. It touches the shared ledger constraints, so it needs his sign-off and has to land after #176.

**What I'm asking:** agreement on the Option C *direction* only. Implementation would come as its own draft PR, sequenced after #176, with the full test list above. It changes nothing in S3-B, #175 or #176 PHP.

🤖 Generated with [Claude Code](https://claude.com/claude-code)