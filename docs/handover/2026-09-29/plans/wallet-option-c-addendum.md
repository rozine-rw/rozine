# Option C design addendum, revision 2: Hussain's four points and his review

**Status:** design only. No code, migration or commit. This revises 5874750331 to follow Hussain's review (5874790523). It is not approval to implement M1 or M2.

**Bases:**
- `origin/dev` at `f1428e12` (#172 merged).
- #175 at `85b68691` (`origin/pr175`, marked **#175**).
- #176 at `8a73d686` (`origin/feat/s3d-disbursement`, marked **#176**).
- CI runs PostgreSQL 17 as user `postgres` (`.github/workflows/tests.yml:51,53`).

Every constraint restated below, and every constraint M1 or M2 replaces, **is restated again from the integrated #175 and #176 heads at integration time**. The SQL here shows intent, not the final text.

## Changelog since 5874750331
1. **Resolved defaults.** Hussain's answers to Q1–Q10 are folded into the body. §6 is now a resolved-defaults table plus four new questions.
   - The Investor `deposit_credit` shape guard is out of scope. It is a separate tracked fix, and no existing test is claimed to prove it.
   - No S4-C kinds are mapped or allowed.
   - The registry covers collections only.
2. **Migration split.**
   - M1 is now Investor-effect-only: the supertype, the backfill, the ledger FK swap, the registry and the provider binding.
   - M2 carries everything Business: `business_wallets`, the Business deposit tables, the Business kinds, and cash conservation.
   - No Business kind is allowed until its conservation triggers exist in the same transaction.
   - The separate "shape" trigger of revision 1 is replaced by a full conservation check.
3. **Provider binding (§4).** The registry's `provider` is bound both ways by composite FKs: registry → intent, and intent → registry. Events are bound to `(intent_id, provider)`. A wrong-provider forgery test is added.
4. **Cash conservation (§3).** For Business deposit entries, the database requires at commit:
   - clearing debit = intent gross;
   - available credit = intent net;
   - fee credit = intent fee;
   - an applied-success credit binding.

   The check is deferred and queued on entries, lines and credits. Tests cover both insertion orders and `SET CONSTRAINTS ALL IMMEDIATE`.
5. **Lock graph (§3(c)), rewritten.**
   - The callback writes `wallet_provider_events` before its ledger entry (`WS:149-156`), and reads `wallet_deposit_intents` before it locks the wallet (`WS:131-134`).
   - FK parents touched by DDL are included, with `business_profiles` first for M2.
   - The document no longer claims the lock list prevents deadlock. Atomic abort is the guarantee, and it is tested.
6. **Current bases.**
   - #175 now authorizes checkout Business-first (`EloquentPrimaryCheckout.php:100-105`).
   - #175 adds `primary_commitment_source_unavailable` (`165949:16-17`), which M1 and M2 preserve.
   - The rollback dependencies are re-derived, and `WalletSchemaTest`'s round-trip list is a newly found dependency.
7. **Wallet tier.**
   - Business wallets in `business_id` order, then Investor wallets in Party-id order, on every multi-wallet path.
   - A callback never takes Business, authority or campaign locks after its wallet lock. Database, SQL-log and architecture tests enforce this.

**Citation shorthand** (`file:line` on dev unless marked):

| Short | File |
|---|---|
| `J` | `app/Infrastructure/Operations/EloquentOperationJournal.php` |
| `OPS` | `database/migrations/2026_09_24_052148_create_command_operations_table.php` |
| `104818` / `104821` / `112500` / `112902` / `140000` | `database/migrations/2026_09_28_<n>_*.php` (dev) |
| `161335` / `163057` / `165949` | #175 `database/migrations/2026_09_28_<n>_*.php` |
| `100200` / `100300` | #176 `database/migrations/2026_09_29_<n>_*.php` |
| `WS` / `WP` / `SDP` | `app/Infrastructure/Wallet/EloquentWalletStore.php`, `EloquentWalletPostings.php`, `SyntheticDepositProvider.php` |
| `BAS` / `BCS` / `IAS` | `EloquentBusinessAuthorityStore.php`, `EloquentBusinessCampaignStore.php`, `EloquentIdentityAccessStore.php` |
| `PCK` | #175 `app/Infrastructure/Primary/EloquentPrimaryCheckout.php` |

**Corrections to the original Option C post** (unchanged from revision 1, extended):
1. It proposed a `business:<id>` journal key. `J:97` refuses that key, and Business commands already use `party:<acting Party>` (`BCS:94,236`).
2. It said "`investor_wallets` unchanged" and "no S3-B PHP". Both are false:
   - `investor_wallets` and `wallet_deposit_intents` each gain a constant `owner` column (no PHP writes it).
   - `wallet_provider_events.intent_id` gets a new FK target.
   - `ApplyProviderOutcome` routes by owner.
   - `WS` and `WP` are unchanged.
3. The subtype→supertype FK must be deferred. The internal `RI_ConstraintTrigger_c_*` sorts before the lower-case supertype trigger.
4. Stored generated columns are not readable in BEFORE triggers, so `protect_ledger_entry` reads `wallets.owner`. `LIKE`-prefix owner derivation is not an exhaustive mapping.
5. `down()` must refuse on Business evidence, not on any `wallets` row.

---

## 1. Journal identity for Business commands

### 1(a) Current behaviour
- `validateIdentity` (`J:95-102`) accepts only `party:<ULID>` or `staff:<int>`, at most 80 characters (`J:97`). `execute` (`J:29`) and `find` (`J:82`) both call it first.
- Columns (`OPS`): `actor_key varchar(80)` (`:16`), `actor_user_id` (`:17`), `target_type`/`target_id` (`:21-22`). Unique `(actor_key, command, request_id)` (`:26`); rows are immutable (`:35-37`).
- The hash covers target and input (`J:33`), so a mismatch gives `IDEMPOTENCY_CONFLICT` (`J:40-41`). The advisory key is `actor|command|request` (`J:37`).
- Business convention: `WithBusinessAuthority` locks `business_profiles` (`BAS:128`), then the User (`IAS:477`), then the Parties (`IAS:483`), then checks the permission (`BAS:144`). The journal key is `'party:'.$identity['party']['id']` (`BCS:94,236`).
- Lookup convention: `BCS:198-215` resolves the caller's Party, calls `find`, and re-enters authority for the target's Business with `business.view`, requiring the same Party.
- #175 checkout follows the same pattern: `party:` key, Business-first (`PCK:26-30,100-105`).

### 1(b) Contract (resolved defaults Q1, Q2, Q8)
**No change to `validateIdentity`, `OperationJournal` or `command_operations`.** A Business identity never stands in for the acting human.

| Field | Business deposit |
|---|---|
| `actor_key` | `party:<authenticated person's Party>`, resolved server-side inside `WithBusinessAuthority` |
| `actor_user_id` | the authenticated user |
| `command` | `business.wallet.deposit` (distinct from `wallet.deposit`, so a sole trader's two deposits never collide under `OPS:26`) |
| `target_type` / `target_id` | `business_wallet` / the supertype wallet id |
| permitted input | `identity_context_revision`, `business_id`, `amount`, `method_id` |
| execute and replay | `WithBusinessAuthority::handle(..., 'business.wallet.deposit', ...)`; replay needs the **current** permission (Q1) |
| lookup | `findBusinessDeposit`: the original acting Party (via the key), `type === 'business_wallet'` with the exact wallet id, and current `business.view` for that wallet's Business; otherwise 404 (Q1) |
| permission grant | `business.wallet.deposit` is added to `MandateAuthority::PERMISSIONS` (`MandateAuthority.php:18`). It is granted only by a new, reviewed mandate version. It is not inferred from `application.sign`, and there is no automatic sole-trader grant; existing mandates are unchanged (Q2). |
| idempotency | a per-acting-Party namespace (Q8). `business_deposit_intents` is unique on `(wallet_id, actor_party_id, request_id)`. This never substitutes for provider deduplication, which stays with the event identity, the registry, one final outcome per intent and one entry per source (§4). |

New port: `BusinessWalletStore`, with `page`, `deposit`, `findDeposit`, `claimDispatches` and `recordDispatch`. Callbacks live in a separate class (§4(b), §5).

### 1(c) Migration
None for the journal. The permission constant is code-only, and there is no backfill.

### 1(d) Tests
- `OperationJournalTest`: `it('keeps refusing Business-scoped actor keys on execute and find')` with `business:<ulid>`, `business:1` and `business:`. Expect `OPERATION_INPUT_INVALID` and zero rows.
- `tests/Feature/BusinessWalletDepositCommandTest.php`:
  - `it('journals a Business deposit under the acting person and targets the Business wallet')`
  - `it('keeps a sole trader\'s Investor and Business deposits apart under one request id')`
  - `it('treats the same request id from a second signatory as its own operation')`
  - `it('refuses a replay that names another Business under the same request id')` → `IDEMPOTENCY_CONFLICT`
  - `it('requires current business.wallet.deposit to deposit or replay')`
  - `it('refuses a sole trader whose current mandate lacks the explicit grant')`
  - `it('finds a Business deposit only for the original acting person with current business.view')`
  - `it('never reads Investor restrictions or methods for a Business deposit')`

---

## 2. Exhaustive kind→owner mapping, both directions

### 2(a) Current behaviour
- `ledger_accounts.wallet_id` is nullable, with an FK to `investor_wallets` (`104818:28`). `ledger_entries.wallet_id` is NOT NULL, with an FK to `investor_wallets` (`104818:35`).
- The `ledger_account_owner` CHECK ties kind to wallet presence only (`104818:58-60`; #176 `100200:25-28`).
- `ledger_entry_source` enumerates the entry kinds (#176 `100200:31-38`).
- #175 adds a **separate** CHECK, `primary_commitment_source_unavailable` (`source_type <> 'primary_commitment'`, `165949:16-17`).
- System accounts are unique per kind where `wallet_id IS NULL` (`104818:62`).

### 2(b) Contract
**M1 (Investor-effect only).** No `business` value is admitted anywhere:
```sql
CREATE TABLE wallets (id char(26) PRIMARY KEY,
  owner varchar(10) NOT NULL CONSTRAINT wallet_owner_kind CHECK (owner = 'investor'),   -- M2 widens to ('investor','business')
  created_at timestamptz NOT NULL, CONSTRAINT wallets_id_owner UNIQUE (id, owner));
-- wallets_immutable: BEFORE UPDATE OR DELETE → reject_wallet_ledger_mutation() (104818:69-73)

ALTER TABLE investor_wallets ADD COLUMN owner varchar(10) NOT NULL DEFAULT 'investor',
  ADD CONSTRAINT investor_wallet_owner CHECK (owner = 'investor');   -- raw INSERTs at WS:327 / WP:36 take the default
-- backfill (2(c)), then:
ALTER TABLE investor_wallets ADD CONSTRAINT investor_wallets_supertype_fk
  FOREIGN KEY (id, owner) REFERENCES wallets (id, owner) DEFERRABLE INITIALLY DEFERRED;
-- investor_wallets_supertype: AFTER INSERT → plain INSERT INTO wallets (fires only for inserted rows, so ON CONFLICT DO NOTHING is unchanged)
-- wallets_created_by_subtype: BEFORE INSERT ON wallets, refuse when pg_trigger_depth() < 2 (created after the backfill)
-- wallets_have_subtype: deferred constraint trigger. M1 checks the investor branch only; M2 replaces it with both branches.

ALTER TABLE ledger_accounts ADD COLUMN wallet_owner varchar(10) GENERATED ALWAYS AS (CASE kind
  WHEN 'investor_available' THEN 'investor' WHEN 'investor_held' THEN 'investor' WHEN 'investor_committed' THEN 'investor'
  WHEN 'deposit_clearing' THEN NULL WHEN 'deposit_fee_revenue' THEN NULL WHEN 'disbursement_settlement' THEN NULL END) STORED;
ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_account_owner, ADD CONSTRAINT ledger_account_owner CHECK (COALESCE(
     (kind IN ('investor_available','investor_held','investor_committed') AND wallet_id IS NOT NULL AND wallet_owner = 'investor')
  OR (kind IN ('deposit_clearing','deposit_fee_revenue','disbursement_settlement') AND wallet_id IS NULL AND wallet_owner IS NULL),
  false));                                            -- a CHECK passes on NULL; COALESCE closes that (the 100200:38 pattern)
ALTER TABLE ledger_accounts DROP CONSTRAINT ledger_accounts_wallet_id_foreign,
  ADD CONSTRAINT ledger_accounts_wallet_owner FOREIGN KEY (wallet_id, wallet_owner) REFERENCES wallets (id, owner) MATCH FULL;

ALTER TABLE ledger_entries ADD COLUMN wallet_owner varchar(10) GENERATED ALWAYS AS (CASE kind
  WHEN 'deposit_credit' THEN 'investor' WHEN 'primary_hold' THEN 'investor' WHEN 'primary_commit' THEN 'investor'
  WHEN 'primary_release' THEN 'investor' WHEN 'primary_refund' THEN 'investor' WHEN 'primary_issue' THEN 'investor' END) STORED;
ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entry_wallet_owner CHECK (wallet_owner IS NOT NULL);
ALTER TABLE ledger_entries DROP CONSTRAINT ledger_entries_wallet_id_foreign,
  ADD CONSTRAINT ledger_entries_wallet_owner FOREIGN KEY (wallet_id, wallet_owner) REFERENCES wallets (id, owner) MATCH FULL;
-- ledger_entry_source: not touched by M1. primary_commitment_source_unavailable: never touched by M1 or M2.
```

**M2 (Business).** All of this lands atomically with §3's conservation triggers:
- `wallet_owner_kind` becomes `IN ('investor','business')`.
- `business_wallets(id, owner = 'business', business_id UNIQUE → business_profiles, currency = 'RWF', created_at)` with the deferred FK `(id, owner)` → `wallets`, immutable, and its own supertype trigger.
- `wallets_have_subtype` is replaced with both branches.
- `ALTER TABLE ledger_accounts ALTER COLUMN wallet_owner SET EXPRESSION AS (<M1 CASE> + WHEN 'business_available' THEN 'business')` (PostgreSQL 17; rewrites the table).
- `ledger_account_owner` gains `(kind = 'business_available' AND wallet_id IS NOT NULL AND wallet_owner = 'business')`.
- `ALTER TABLE ledger_entries ALTER COLUMN wallet_owner SET EXPRESSION AS (<M1 CASE> + WHEN 'business_deposit_credit' THEN 'business')`.
- `ledger_entry_source` is replaced with **the integrated #175/#176 definition restated verbatim**, plus one disjunct: `(kind = 'business_deposit_credit' AND source_type = 'business_deposit_intent' AND origin_operation_id IS NOT NULL AND cause_type IS NULL AND cause_id IS NULL)`.
- `primary_commitment_source_unavailable` stays as #175's separate CHECK. M2 neither drops nor restates it.
- No S4-C kind (for example `business_repayment_debit`) is mapped or admitted. Those arrive with their own implementation and constraints (Q9).

**The NULL rules:**
- System accounts keep `wallet_id` and `wallet_owner` both NULL. That all-NULL key is exempt under `MATCH FULL`, so `ledger_accounts_system_kind` and the `WP` system-account upsert (#176 `WP:153`) are unchanged.
- A wallet-bearing row with a NULL owner is refused three ways: by the `COALESCE` CHECK, by `MATCH FULL` (a partly NULL key is a violation), and, for entries, by `ledger_entry_wallet_owner`.

**Cross-owner consequences, with no new logic:**
- `protect_ledger_line` compares account and entry wallets (`112500:72`). Wallet ids are unique across owners through `wallets.id`, so no cross-owner line is possible.
- The overdraw check covers every wallet account (#176 `100200:119-124`).
- `primary_*` kinds map to `investor`, so they cannot name a Business wallet. The #175 hold binding joins `investor_wallets` (`161335:28,37`) and stays owner-correct.

### 2(c) Backfill and ordering
- In M1, after `investor_wallets.owner` is added and before the FK, triggers and guard:
  ```sql
  INSERT INTO wallets SELECT id, 'investor', created_at FROM investor_wallets;
  ```
- Parity is asserted in the same transaction: the counts are equal, and every wallet-bearing ledger row has `wallet_owner = 'investor'`.
- M2 needs no backfill.

### 2(d) Tests
`tests/Feature/WalletOwnerSchemaTest.php` uses raw SQL through `walletSchemaRejects` (`WalletSchemaTest.php:23-29`). Each test asserts the SQLSTATE and the constraint:

| Test | Forgery | Expect |
|---|---|---|
| `it('refuses an Investor subtype row that claims another owner')` | `investor_wallets.owner = 'business'` | 23514 `investor_wallet_owner` |
| `it('refuses a Business wallet that reuses an Investor wallet id')` (M2) | `business_wallets.id` equal to an Investor id | 23505 `wallets_pkey` |
| `it('refuses a supertype row inserted on its own')` | direct `INSERT INTO wallets` | 23514 guard |
| `it('refuses an orphan subtype at commit')` | disable `investor_wallets_supertype` in the transaction, then insert | 23503 `investor_wallets_supertype_fk` |
| `it('refuses an orphan supertype at commit')` | disable the guard, then insert `wallets` | 23514 `wallets_have_subtype` |
| `it('refuses owner-crossed ledger accounts')` (M2) | `business_available` on an Investor wallet; `investor_available` on a Business wallet | 23503 `ledger_accounts_wallet_owner` |
| `it('never lets an unmapped account kind bypass the wallet FK')` | `kind = 'business_reserve'` with a wallet | 23514 `ledger_account_owner` |
| `it('refuses owner-crossed entries')` (M2) | `deposit_credit` and every `primary_*` on a Business wallet; `business_deposit_credit` on an Investor wallet | 23503 `ledger_entries_wallet_owner` |
| `it('declares both wallet-owner FKs MATCH FULL')` | `pg_constraint.confmatchtype` | `'f'` |
| `it('maps every allowed account and entry kind to exactly one owner')` | parse the kinds from `pg_get_constraintdef`; probe each one | complete; system kinds NULL; no kind outside the CHECK is mapped |
| `it('admits no Business value before M2')` | after M1 only: a `wallets.owner = 'business'` row; the `business_available` kind | 23514 |
| `it('keeps rejecting unbound primary_commitment sources after M1 and M2')` | `primary_hold` with `source_type = 'primary_commitment'` | 23514 `primary_commitment_source_unavailable`; the constraint exists before and after each round trip |

---

## 3. Locking, conservation and the atomic migrations

### 3(a) Current behaviour
- `protect_ledger_entry` is a BEFORE trigger (`104818:79-90`). It locks `investor_wallets` for `NEW.wallet_id` (`:84`), does not refuse a missing wallet, and stamps `created_xid` (`:85`).
- The PHP paths lock the same row first: `WS:330`, `WS:134`, `WP:38,66` (#176 `WP:39,72`).
- The checks that must stay intact:
  - the seal (`112500:65-70,35-36`) and per-line deferred balance (`112500:53-54`);
  - the lifecycle, including issue/refund exclusion (#176 `100200:40-66`, `:60-63`);
  - the anchor and bucket shape (#176 `100200:68-117`);
  - the overdraw check (#176 `:119-124`);
  - the #175 hold binding (`161335:18-84`) and completed-outcome binding (`163057:29-49`);
  - the commitment-source prohibition (`165949:16-17`).
- **Deposit conservation today.** `protect_wallet_deposit_credit` (`104821:143-162`) compares the credit row's amount with `intent.credited` and checks that an entry exists. It does **not** compare the entry's line amounts with the intent. That Investor gap is tracked separately (Q7) and is not claimed as covered here.

### 3(b) Contract
**`protect_ledger_entry`.**
- M1 version: `SELECT owner FROM wallets WHERE id = NEW.wallet_id`, then `PERFORM 1 FROM investor_wallets … FOR UPDATE`. If the owner is missing or the subtype is not found, it raises 23503. It stamps `created_xid` as before.
- M2 replaces it with the two-branch body from revision 1: `investor` locks `investor_wallets`, `business` locks `business_wallets`, and anything else raises 23503.
- It locks the **subtype** row because every PHP path locks that row. The supertype is only ever share-locked by FK checks.
- M1 and M2 do not `CREATE OR REPLACE` any of `ledger_entry_balance_check`, `assert_ledger_entry_balanced`, `assert_ledger_line_entry_balanced`, `protect_ledger_line`, `protect_primary_posting`, `check_primary_hold_binding`, `require_primary_hold_binding` or `require_completed_primary_outcome`.

**Business deposit conservation (M2).** It is deferred and queued from all three inserting tables, so insertion order and early flushing cannot skip it:
```sql
CREATE FUNCTION business_deposit_entry_check(checked_entry varchar) RETURNS void LANGUAGE plpgsql AS $$
DECLARE e ledger_entries%ROWTYPE; i business_deposit_intents%ROWTYPE; c business_deposit_credits%ROWTYPE;
  n int; gross_lines int; net_lines int; fee_lines int; gross numeric; net numeric; fee numeric;
BEGIN
  SELECT * INTO e FROM ledger_entries WHERE id = checked_entry;
  IF e.id IS NULL OR e.wallet_owner IS DISTINCT FROM 'business' THEN RETURN; END IF;            -- Investor entries: unchanged rules
  SELECT * INTO i FROM business_deposit_intents WHERE id = e.source_id AND wallet_id = e.wallet_id;
  IF i.id IS NULL OR e.kind <> 'business_deposit_credit' OR e.source_type <> 'business_deposit_intent'
     OR e.origin_operation_id IS DISTINCT FROM i.operation_id THEN
    RAISE EXCEPTION 'Business deposit entry % must settle its own intent on its wallet', checked_entry USING ERRCODE = '23514';
  END IF;
  SELECT count(*),
    count(*) FILTER (WHERE l.direction = 'debit'  AND a.wallet_id IS NULL AND a.kind = 'deposit_clearing'),
    count(*) FILTER (WHERE l.direction = 'credit' AND a.wallet_id = e.wallet_id AND a.kind = 'business_available'),
    count(*) FILTER (WHERE l.direction = 'credit' AND a.wallet_id IS NULL AND a.kind = 'deposit_fee_revenue'),
    sum(l.amount) FILTER (WHERE l.direction = 'debit'  AND a.kind = 'deposit_clearing'),
    sum(l.amount) FILTER (WHERE l.direction = 'credit' AND a.kind = 'business_available'),
    coalesce(sum(l.amount) FILTER (WHERE l.direction = 'credit' AND a.kind = 'deposit_fee_revenue'), 0)
  INTO n, gross_lines, net_lines, fee_lines, gross, net, fee
  FROM ledger_lines l JOIN ledger_accounts a ON a.id = l.account_id WHERE l.entry_id = checked_entry;
  IF gross_lines <> 1 OR net_lines <> 1 OR fee_lines > 1 OR n <> 2 + fee_lines OR (fee_lines = 1) <> (i.fee > 0)
     OR gross IS DISTINCT FROM i.amount OR net IS DISTINCT FROM i.credited OR fee IS DISTINCT FROM i.fee THEN
    RAISE EXCEPTION 'Business deposit entry % must move exactly its intent gross, net and fee', checked_entry USING ERRCODE = '23514';
  END IF;
  SELECT * INTO c FROM business_deposit_credits WHERE ledger_entry_id = checked_entry;
  IF c.id IS NULL OR c.intent_id <> i.id OR c.wallet_id <> e.wallet_id OR c.amount <> i.credited
     OR c.operation_id <> i.operation_id OR NOT EXISTS (SELECT 1 FROM wallet_provider_events ev
        WHERE ev.id = c.provider_event_id AND ev.intent_id = i.id AND ev.provider = i.provider
          AND ev.disposition = 'applied' AND ev.state = 'succeeded') THEN
    RAISE EXCEPTION 'Business deposit entry % needs its applied-success credit binding', checked_entry USING ERRCODE = '23514';
  END IF;
END $$;
-- Three CONSTRAINT TRIGGERs, all DEFERRABLE INITIALLY DEFERRED, each calling the function:
--   business_deposit_entry_conserved  AFTER INSERT ON ledger_entries  WHEN (NEW.kind = 'business_deposit_credit') → (NEW.id)
--   business_deposit_line_conserved   AFTER INSERT ON ledger_lines                                              → (NEW.entry_id)
--   business_deposit_credit_conserved AFTER INSERT ON business_deposit_credits                                  → (NEW.ledger_entry_id)
```
What this rules out:
- **Orphans.** A balanced, correctly shaped `business_deposit_credit` entry with no credit row, or with a credit that is not an applied success, fails at commit.
- **Mispricing.** Any gross, net or fee that differs from the intent fails at commit.
- **Order dependence.** Every insert re-queues the check. A line appended after an early flush of only these triggers queues a fresh check. After a full `SET CONSTRAINTS ALL IMMEDIATE`, the #172 seal refuses the line (`112500:68-70`).

What the credit row carries:
- `business_deposit_credits` keeps a BEFORE INSERT check: the intent exists, the event is an applied success for the intent, and the entry exists with the matching kind, source and wallet.
- It does **not** check line amounts, so the "entry, credit, then lines" order is possible; the deferred check covers the amounts.
- Unique constraints: `ledger_entry_id`, `intent_id` and `provider_event_id`. One final per intent is already enforced (`104821:97-98`), and so is one entry per `(kind, source)` (`104818:43`).

Flushing:
- The Business adapter writes the entry, lines and credit, **then** flushes `ledger_entries_balanced, ledger_lines_entry_balanced, business_deposit_*_conserved` (the `WS:432-433` pattern).
- A `SET CONSTRAINTS ALL IMMEDIATE` before the credit row exists fails closed. That matches the #172 semantics (`112500:16-17`).

Accounts and scope:
- Clearing and fee reuse `deposit_clearing`/`deposit_fee_revenue`, for the synthetic provider only (Q4). Real-provider reconciliation and account mapping is a separate gate.
- The Investor `deposit_credit` path is unchanged (Q7).

### 3(c) Migration lock graph, ordering and rollback

**Runtime acquisition orders that meet the migrations** (table-level modes in brackets):

| Path | Order |
|---|---|
| Investor deposit | users, parties [ROW SHARE] → `investor_wallets` [ROW EXCLUSIVE then ROW SHARE, `WS:327,330`] → policy, method and restriction reads → `wallet_deposit_intents` [ROW EXCLUSIVE, `WS:391`], with its FKs share-locking the parent rows → `wallet_deposit_dispatches` → `command_operations` [ROW EXCLUSIVE, `J:69`] |
| Investor callback | advisory → **`wallet_deposit_intents` read [ACCESS SHARE, `WS:131`]** → `investor_wallets` [ROW SHARE, `WS:134`] → intent FOR UPDATE (`WS:135`) → **`wallet_provider_events` insert [ROW EXCLUSIVE, `WS:153`]** → `ledger_entries` → `ledger_accounts` → `ledger_lines` (`WS:418-429`) → `wallet_deposit_credits` (`WS:441`) |
| #175 checkout | `business_profiles` [ROW SHARE, `PCK:103`] → users, parties (`PCK:105`) → `business_campaigns` → `primary_reservations` … → `investor_wallets` → ledger (`EloquentPrimaryReservations.php:43-83`) → `command_operations` |
| #176 disbursement and reconciler | `business_profiles` → staff users → campaign → disbursement → proof → Primary → `investor_wallets` → ledger (#176 `EloquentDisbursementStore.php:53-59,341-352`) |
| Business authority configure | advisory → `business_profiles` [ROW EXCLUSIVE, `BAS:83-106`] → `business_mandates`. Never touches wallet or ledger tables. |

**M1** (Investor-effect only). It touches no Business table and no new FK parent outside the wallet and ledger set. It runs in Laravel's per-migration transaction:
1. `SET LOCAL lock_timeout = '10s'`.
2. `LOCK TABLE investor_wallets, wallet_deposit_intents, wallet_provider_events, ledger_entries, ledger_accounts, ledger_lines IN ACCESS EXCLUSIVE MODE`.
   - This follows the majority writer order: wallet → intent → event → ledger.
   - `wallet_deposit_intents` is also the referenced parent of the dropped events FK.
3. Create the supertype, backfill it, and add the subtype FK and triggers.
4. Swap the ledger columns, CHECKs and FKs.
5. Replace `protect_ledger_entry`.
6. Create the registry, bind the provider, swap the events FK (§4), and assert parity.

**M2** (Business):
1. `SET LOCAL lock_timeout = '10s'`.
2. **`LOCK TABLE business_profiles IN SHARE ROW EXCLUSIVE MODE` first**, the Business-first position. This is the mode `CREATE TABLE … REFERENCES business_profiles` needs anyway.
   - It does **not** conflict with the ROW SHARE (FOR UPDATE) held by in-flight Business-first paths (`BAS:128`, `PCK:103`, #176 `lockBusiness`). Those paths never block M2 here.
   - It **does** wait for an in-flight `configure` (ROW EXCLUSIVE). `configure` never touches wallet or ledger tables, so it cannot close a cycle through M2.
3. `LOCK TABLE parties, deposit_policies, command_operations IN SHARE ROW EXCLUSIVE MODE`. These are the FK parents of the new Business deposit tables.
   - `command_operations` waits for any in-flight journaled command that has already inserted its result.
   - Such a command is at its end and needs nothing M2 holds, except in the case (b) below.
4. `LOCK TABLE wallets, deposit_references, wallet_provider_events, ledger_entries, ledger_accounts, ledger_lines IN ACCESS EXCLUSIVE MODE`. This covers the rewrites, the triggers, and the parent of `business_deposit_credits.provider_event_id`.
5. Create the tables, widen the CHECKs, set the new expressions, add the conservation triggers, and assert.

**Residual cycles (named, not denied).** The ordered lists shrink the window; they do not prevent deadlock:
- **(a) M1 against an Investor callback.**
  1. The callback holds ACCESS SHARE on `wallet_deposit_intents` (from `WS:131`) before it requests `investor_wallets` (`WS:134`).
  2. M1 acquires `investor_wallets` and then waits for `wallet_deposit_intents`.
  3. Reordering the list only moves the partner to the Investor deposit command, which locks the wallet (`WS:330`) before writing the intent (`WS:391`).
- **(b) M2 against an Investor first deposit.**
  1. The deposit creates its wallet (`WS:327`), and the M1 supertype trigger inserts into `wallets` [ROW EXCLUSIVE].
  2. M2 then takes `command_operations` [SHARE ROW EXCLUSIVE] and waits for `wallets` [ACCESS EXCLUSIVE].
  3. The deposit reaches its journal insert (`J:69`) and waits for `command_operations`.
  4. Swapping steps 3 and 4 of M2 moves the partner to any command that inserts `command_operations` before it creates a wallet.

**The guarantee is atomic failure with a bounded wait.**
- PostgreSQL's deadlock detector (40P01) or `lock_timeout` (55P03) aborts one side.
- If the migration is the victim, the transactional DDL leaves **no partial schema** (proved in 3(d)).
- If the runtime transaction is the victim, the journal and the callback retry: `DB::transaction(..., 3)` at `J:72` and `WS:160`.
- The production run needs change-management approval, runs in a quiet window, and re-runs on 40P01/55P03. It is never forced.

**Rollback.**
- `M2.down()` takes M2's lock sequence. It refuses (23514) once any Business evidence exists: a `business_wallets` row, a Business deposit row, a `wallet_owner = 'business'` ledger row, or an `owner = 'business'` registry row. Otherwise it restores M1's exact state, including the M1 expressions, CHECKs and single-branch functions.
- `M1.down()` takes M1's lock sequence and restores the exact pre-M1 definitions from the integrated heads:
  - `wallet_provider_events_intent_id_foreign`;
  - `protect_ledger_entry` as in `104818:79-88`;
  - `ledger_accounts_wallet_id_foreign` and `ledger_entries_wallet_id_foreign`;
  - `ledger_account_owner` as in #176 `100200:25-28`.

  It then drops the registry, the columns, the triggers and `wallets`. Investor data is untouched (Q10, conditional on the snapshot tests below).
- Neither `down()` touches `primary_commitment_source_unavailable`.
- #175's `165949::down()` refuses while any entry kind other than `deposit_credit` exists (`165949:29`). Business entries therefore also pin it, which is consistent.

**Rollback-list dependencies at the current heads** (re-derived at integration):
- **`PostgreSqlConfigurationTest.php`** needs M2 and M1 downed **first** and re-upped **last**:
  - At #176 (`:88-96`): `100300` `:90-91`, `100200` `:92-93`, holdings `:95`, disbursements `:96`, wallet downs `:98-104`, `$businessMigration->down()` `:135`.
  - At #175 (`:82-89`): Primary downs `:86-89` before wallet downs `:96-102`; `$businessMigration->down()` `:133`; ups `:183-192`.
  - M2 must precede `$businessMigration->down()` because `business_wallets` references `business_profiles`.
- **`WalletSchemaTest.php` "rolls wallet migrations back and forward"**. This dependency is new, and neither of us listed it:
  - #175 lists at `:228` (starting with `165949`) and `:234` (ending with it); #176 at `:228`/`:233`.
  - M2 and M1 must be prepended to the down list and appended to the up list. Otherwise `104818::down()` drops `investor_wallets` under M1's objects.
- **`AuditReportPersistenceTest.php`** needs **no entry** at #175 `85b68691`:
  - It downs and re-ups the Primary migrations with `161335`/`163057` (`:208-219`, `:248-253`).
  - `161335::up()` locks `business_profiles, business_campaigns, primary_reservations, investor_wallets, ledger_*` (`161335:15-16`), and all of those still exist under M1/M2.
  - Neither M1 nor M2 depends on a Primary, campaign or closure table.
  - This must be re-checked if the integrated heads change.
- `PrimaryHoldBindingTest.php:127,138`, `PrimaryReservationSchemaTest.php:200-201` and `PrimaryCommandOutcomeTest.php:78,90` round-trip single #175 migrations only. M1/M2 are independent of them, and we re-verify at integration.
- `docs/phase-0/baseline-inventory.md` is regenerated.

### 3(d) Tests
`tests/Concurrency/LedgerOwnerLockTest.php` uses the second-connection `lock_timeout` probe of `WalletDepositConcurrencyTest.php:126-147`:
- `it('locks the Investor subtype row, and only it, while an Investor entry is written')`
- `it('locks the Business subtype row, and only it, while a Business entry is written')`
- `it('locks the subtype for a raw SQL entry with no PHP lock')`
- `it('refuses an entry whose wallet has no subtype row')` → 23503
- `it('runs a sole trader\'s Business deposit, Investor deposit and Business-first checkout concurrently without deadlock')`: forked; all exit 0; each wallet moves only by its own entries.

`tests/Feature/BusinessDepositConservationTest.php` (raw SQL, then `SET CONSTRAINTS ALL IMMEDIATE`, or commit in a forked child):

| Test | Asserts |
|---|---|
| `it('accepts a Business credit written entry, lines, credit')` | commits; one entry, one credit |
| `it('accepts a Business credit written entry, credit, lines')` | commits (the other insertion order) |
| `it('refuses a balanced, correctly shaped orphan entry at commit')` | no credit row → 23514 "needs its applied-success credit binding" |
| `it('refuses mispriced gross, net or fee in either insertion order')` | dataset {gross+1/net+1, fee moved to net, fee line when the fee is 0, missing fee line} × {both orders} → 23514 "exactly its intent gross, net and fee" |
| `it('refuses a credit bound to a non-success or another intent\'s event')` | dataset {pending, failed, key_conflict, another intent} → 23514 |
| `it('fails closed on SET CONSTRAINTS ALL IMMEDIATE before the credit exists')` | 23514; nothing kept |
| `it('re-checks after an early flush of only the conservation triggers')` | flush `business_deposit_*_conserved` IMMEDIATE, then append a line → 23514 at commit |
| `it('refuses a line appended after a full early flush')` | `ALL IMMEDIATE` after a complete write, then a line → the #172 seal message |
| `it('leaves the #172, #175 and #176 ledger functions byte-identical')` | `md5(pg_get_functiondef)` of the functions listed in 3(b), before and after M1 and M2 |

`tests/Feature/WalletSupertypeMigrationTest.php`, with a helper `walletSchemaSnapshot()`. The snapshot records `pg_class` names, `pg_attribute` (including `attgenerated` and expressions), `pg_get_constraintdef`, `pg_get_triggerdef`, `md5(pg_get_functiondef)` and `pg_indexes` for every wallet, ledger, registry and Business table.
- `it('backfills one Investor supertype row per wallet and keeps every ledger row byte-identical')`
- `it('round-trips M1 with Investor data and restores the exact pre-M1 snapshot')`
- `it('round-trips M2 with Investor data and restores the exact M1 snapshot')`
- `it('refuses M1 and M2 rollback once any Business evidence exists')`

`tests/Concurrency/WalletMigrationLockTest.php` (forked; the migration runs in its own child):
- `it('completes M1 after an in-flight Investor callback that has written its provider event but not its ledger entry')`: the child pauses on `eloquent.creating: LedgerEntry`. M1 blocks, then completes once the child commits. The credit entry has `wallet_owner = 'investor'`.
- `it('completes M1 and M2 after an in-flight Business-first Primary reservation commits')`: #175 `PrimaryCheckout::reserve` pauses after its hold. M1 and M2 wait, then complete, and the hold is covered.
- `it('completes M2 after an in-flight Business authority configure commits')`: `configure` holds ROW EXCLUSIVE on `business_profiles`.
- `it('aborts M1 atomically when it deadlocks with an Investor callback')`:
  - Setup: the callback pauses right after the `WS:131` read (a `DB::listen` barrier). M1 takes `investor_wallets` and then waits. The barrier is released.
  - Asserts: exactly one 40P01. The victim is forced to be M1 by `SET deadlock_timeout = '100ms'` on M1's session; this is a superuser setting (see N1). `walletSchemaSnapshot()` equals the pre-M1 snapshot. The callback succeeds on its retry.
- `it('aborts M1 atomically on lock_timeout with no partial DDL')`: an idle-in-transaction reader holds `ledger_lines`, M1 fails with 55P03, and the snapshot is unchanged.
- `it('aborts M2 atomically on lock_timeout while configure holds business_profiles')`: fails with 55P03; the snapshot equals M1's.
- `it('aborts M2 atomically when it deadlocks with an Investor first deposit')`: residual cycle (b). One 40P01; if M2 is the victim, the snapshot equals M1's; the deposit commits after its retry.

---

## 4. Cross-owner provider-reference routing (collections only)

### 4(a) Current behaviour
- References:
  - Rozine issues each reference before dispatch (`WS:379`).
  - The reference hash is unique within `wallet_deposit_intents` only (`104821:35`).
  - The intent carries `provider` (`104821:33`), but nothing binds an event's `provider` to its intent's.
- Events:
  - identity unique `(provider, provider_event_id) WHERE disposition <> 'key_conflict'` (`104821:96`);
  - content unique (`:67`);
  - one applied final per intent (`:97-98`);
  - `intent_id` FK → `wallet_deposit_intents` (`:57`).
- Signed verification (`SDP`):
  - The MAC covers exactly `FIELDS` (`:26`), which includes `provider` and `reference`.
  - Any extra or missing key, or a bad MAC, gives `PROVIDER_EVENT_UNVERIFIED` with no record (`:67-72`).
  - The key is derived from `app.key` (`:86-89`).
- There is no HTTP callback route on dev (`routes/api.php:136-138`). The only entry point is the local `PrepareSyntheticWallet` command → `ApplyProviderOutcome::handle` (`ApplyProviderOutcome.php:25-28`).
- Resolution (`WS:127-161`):
  1. advisory lock (`:130`)
  2. intent `wallet_id` by `(provider, sha)` (`:131-133`), or 404 with no write
  3. wallet FOR UPDATE (`:134`)
  4. intent FOR UPDATE by sha (`:135`)
  5. same identity with other content → `key_conflict` (`:137-145`)
  6. facts that do not match the intent → `mismatch` (`:146`)
- Payouts: #176 keeps a separate `disbursement_intents` reference uniqueness and its own route. That stays isolated (Q6).

### 4(b) Contract
The registry and the provider binding go in M1. The Business half goes in M2.
```sql
ALTER TABLE wallet_deposit_intents ADD COLUMN owner varchar(10) NOT NULL DEFAULT 'investor',
  ADD CONSTRAINT deposit_intent_owner CHECK (owner = 'investor');
CREATE UNIQUE INDEX wallet_deposit_intents_reference_binding
  ON wallet_deposit_intents (id, wallet_id, provider, provider_reference_sha256);
CREATE TABLE deposit_references (
  reference_sha256 char(64) PRIMARY KEY,                   -- global across owners
  provider varchar(40) NOT NULL,
  owner varchar(10) NOT NULL CONSTRAINT deposit_reference_owner CHECK (owner = 'investor'),   -- M2: IN ('investor','business')
  intent_id char(26) NOT NULL UNIQUE,
  wallet_id char(26) NOT NULL,
  investor_intent_id char(26) GENERATED ALWAYS AS (CASE owner WHEN 'investor' THEN intent_id END) STORED,
  business_intent_id char(26) GENERATED ALWAYS AS (CASE owner WHEN 'business' THEN intent_id END) STORED,
  created_at timestamptz NOT NULL,
  CONSTRAINT deposit_reference_one_owner CHECK (num_nonnulls(investor_intent_id, business_intent_id) = 1),
  CONSTRAINT deposit_reference_binding UNIQUE (reference_sha256, intent_id, provider, wallet_id, owner),
  CONSTRAINT deposit_reference_event_key UNIQUE (intent_id, provider),
  CONSTRAINT deposit_reference_wallet FOREIGN KEY (wallet_id, owner) REFERENCES wallets (id, owner),
  -- registry → intent: this owner's intent, with this provider, reference and wallet
  CONSTRAINT deposit_reference_investor_intent FOREIGN KEY (investor_intent_id, wallet_id, provider, reference_sha256)
    REFERENCES wallet_deposit_intents (id, wallet_id, provider, provider_reference_sha256) DEFERRABLE INITIALLY DEFERRED);
  -- M2 adds deposit_reference_business_intent → business_deposit_intents (id, wallet_id, provider, provider_reference_sha256)
-- backfill: INSERT INTO deposit_references (reference_sha256, provider, owner, intent_id, wallet_id, created_at)
--           SELECT provider_reference_sha256, provider, 'investor', id, wallet_id, created_at FROM wallet_deposit_intents;
-- intent → registry: a registry row naming this intent, provider, wallet and owner
ALTER TABLE wallet_deposit_intents ADD CONSTRAINT deposit_intent_reference
  FOREIGN KEY (provider_reference_sha256, id, provider, wallet_id, owner)
  REFERENCES deposit_references (reference_sha256, intent_id, provider, wallet_id, owner) DEFERRABLE INITIALLY DEFERRED;
-- events: bound to their intent's registered provider
ALTER TABLE wallet_provider_events DROP CONSTRAINT wallet_provider_events_intent_id_foreign,
  ADD CONSTRAINT wallet_provider_events_intent_reference
  FOREIGN KEY (intent_id, provider) REFERENCES deposit_references (intent_id, provider);
-- wallet_deposit_intents_registered: AFTER INSERT → writes the registry row. deposit_references is immutable,
-- with a depth guard. The writer is only a convenience; the two FKs above are the proof.
```
- The provider, owner, wallet and reference are bound **in both directions** by declarative FKs.
- A trigger-populated value that disagrees with the intent fails at commit, and so does a forged row that bypasses the trigger (tests below).
- `business_deposit_intents` (M2) mirrors the intent columns, with a constant `owner = 'business'`, `deposit_intent_reference`, and its own registry writer.
- Every S3-B event index (`104821:67,96-98`) spans both owners unchanged (Q3), and `WS:137` reads the shared table.

**Signed binding:**
- Routing uses only the signed `(provider, reference)` (`SDP:26`). The owner and the intent are never read from the message, and `SDP:70` rejects an unsigned hint.
- The registry row commits with its intent and outbox row, before any dispatch (`WS:376-394`).

**PHP:**
- `DepositReferences::resolve(provider, reference): DepositRoute{owner, intentId, walletId}` is a PK read of the immutable registry. An unknown reference or a provider mismatch gives 404 `DEPOSIT_REFERENCE_UNKNOWN` with no write.
- `ApplyProviderOutcome::handle`: `verify` → `resolve` → the Investor `WalletStore::applyOutcome` (unchanged) or `BusinessDepositOutcomes::apply($event, $env, $route)`.
- `BusinessDepositOutcomes` is a separate class. It uses the `WS:130` advisory key, and it re-checks its locked intent against `$route`, failing with `DEPOSIT_REFERENCE_INTEGRITY_FAILED` and no write.
- **Scope:** collections only. Payouts keep their isolated route and verified instruction binding. A shared collection/payout callback needs a directional registry contract and collision tests first (Q6).

### 4(c) Ordering and rollback
- M1 steps: owner column → index → registry → backfill with parity → intent FK → writer trigger and guard → events FK swap.
- M2 widens `deposit_reference_owner` and adds `deposit_reference_business_intent`.
- Rollback is in §3(c). `M1.down()` restores `wallet_provider_events_intent_id_foreign`, then drops the registry, the index, `deposit_intent_reference` and the owner column.

### 4(d) Tests
`tests/Feature/DepositReferenceRoutingTest.php`:

| Test | Asserts |
|---|---|
| `it('routes a verified event to the one intent and owner its reference is registered to')` | Both owners on a sole trader; only the matching wallet moves |
| `it('refuses an unregistered or wrong-provider reference without recording anything')` | 404; zero events |
| `it('refuses a Business intent that reuses an Investor reference, and the reverse')` (M2) | 23505 `deposit_references_pkey` |
| `it('refuses a registry row whose provider differs from its intent')` | Writer trigger and guard disabled in the transaction; registry row with `provider = 'forged'` for a `synthetic` intent → 23503 `deposit_reference_investor_intent` and `deposit_intent_reference` at commit. The same for Business in M2. |
| `it('refuses a registry row whose wallet or owner differs from its intent')` | Wallet swapped → 23503; `owner = 'business'` on an Investor intent (M2) → 23503 `deposit_reference_business_intent` |
| `it('refuses an intent with no registry row at commit')` | Writer disabled → 23503 `deposit_intent_reference` |
| `it('refuses a provider event whose provider differs from its intent\'s')` | 23503 `wallet_provider_events_intent_reference` |
| `it('records a reused event id across owners as a key conflict and credits nothing')` | `key_conflict`; no Business credit |
| `it('replays an identical event through the router with its recorded disposition')` | `replayed: true`; one credit |
| `it('refuses a message that adds an owner or intent hint or alters the reference')` | 401 `PROVIDER_EVENT_UNVERIFIED`; nothing recorded |
| `it('fails closed when the route and the owner table disagree')` | `DEPOSIT_REFERENCE_INTEGRITY_FAILED`; no event, entry or credit |

`tests/Concurrency/DepositReferenceConcurrencyTest.php`:
- `it('applies exactly one of two simultaneous events sharing an identity across owners')`
- `it('credits once when the same Business success is delivered simultaneously')`
- `it('records one reference when two intents race with the same reference across owners')`

---

## 5. Lock order

**Global order:**
Business → staff users (ascending id) → campaign → disbursement → proof → Primary (stable id) → **wallets: Business wallets in `business_id` order, then Investor wallets in Party-id order** → ledger (Q5).

**Rules:**
- Every multi-wallet path takes its wallet locks through one ordering helper (`WalletLockOrder::sort`), and it takes them before any posting.
- A deposit callback locks only its own wallet, and it never later acquires a Business, authority (users, Parties, mandates) or campaign lock.
- Identity locks (User → Parties) sit in the staff-users slot. That is the existing cancel path (`BCS:220-227`) and the #175 checkout path (`PCK:100-105`).

| Path | Order | Business wallet? |
|---|---|---|
| Business deposit command (M2) | `business_profiles` FOR UPDATE (`BAS:128`) → User (`IAS:477`) → Parties (`IAS:483`) → `business_wallets` (upsert, then FOR UPDATE) → journal advisory (`J:37`) → intent (its trigger re-locks the same wallet and writes the registry) → outbox | Yes |
| Business deposit callback (M2) | Event advisory (the `WS:130` key) → registry read (immutable) → `business_wallets` FOR UPDATE → intent FOR UPDATE → **`wallet_provider_events` insert** → `ledger_entries` (`protect_ledger_entry` re-locks the same wallet) → accounts, lines → `business_deposit_credits` → commit. No Business, authority or campaign lock at any point. | Yes |
| Business dispatch worker | Dispatch rows `SKIP LOCKED` only (like `WS:164-183`) | No wallet lock |
| Investor callback | Advisory → registry read → `investor_wallets` (`WS:134`) → intent → event → ledger → credit | No |
| #175 checkout | Business (`PCK:103`) → User → Party (`PCK:105`) → campaign → reservations → Investor wallet → ledger | No |
| #176 authorize, approve and reconciler; `primary_refund`; `primary_issue` | Business → (staff) → campaign → disbursement → proof → Primary → Investor wallets (Party id) → ledger (#176 `EloquentDisbursementStore.php:53-59,341-352`; `FundedCampaigns.php:20-22`) | **Never.** `primary_*` maps to `investor`; the payout goes to an external destination. |
| Future multi-wallet paths (for example S4-C) | Not designed here. They must use the tier order through `WalletLockOrder`. | — |

Why the Business deposit cannot deadlock with a sole trader's checkout or cancel: all three take the Business first, then User → Party. The deposit command and its callback use disjoint advisory keys.

**Tests:**
- `it('settles a Business deposit callback while its Business, mandate users, Parties and campaigns are locked elsewhere')` in `tests/Concurrency/DepositCallbackLockScopeTest.php`: a holder connection has those rows locked FOR UPDATE; the callback runs with `lock_timeout = '500ms'` and succeeds. There is an Investor twin.
- `it('never queries authority or campaign tables during a deposit callback')`: `DB::listen` over both callbacks. No statement references `business_profiles`, `business_mandates`, `users`, `parties`, `business_campaigns`, `primary_*` or `command_operations`.
- `arch('deposit callbacks take no authority dependency')`: `expect('App\Infrastructure\Wallet\EloquentBusinessDepositOutcomes')->not->toUse([WithBusinessAuthority::class, BusinessAuthorityStore::class, AuthorizeActiveRole::class, PrimaryCampaignSource::class, FundedCampaigns::class, OperationJournal::class])`.
- `it('orders wallet locks Business by business_id, then Investor by Party id')`: a unit test of `WalletLockOrder::sort`, including a sole trader whose two wallets share one Party.

---

## 6. Resolved defaults (Hussain, 5874790523) and new questions

| # | Topic | Default adopted | Where |
|---|---|---|---|
| Q1 | Lookup | `business.view` + the original acting Party + the exact Business-wallet target; deposit and replay need current `business.wallet.deposit` | §1(b) |
| Q2 | Permission | Explicit `business.wallet.deposit`, granted only by a new reviewed mandate version; never inferred; no automatic sole-trader grant | §1(b) |
| Q3 | Events | The shared `wallet_provider_events` table, with intent ownership and event-reference binding enforced at the database | §4(b) |
| Q4 | Clearing | Reuse `deposit_clearing`/`deposit_fee_revenue` for the synthetic provider only, with exact per-intent conservation; real-provider mapping is a separate gate | §3(b) |
| Q5 | Wallet tier | Business wallets by `business_id`, then Investor wallets by Party id, on every multi-wallet path; callbacks never escalate | §5 |
| Q6 | Registry scope | Collections only; payouts stay isolated | §4 |
| Q7 | Investor `deposit_credit` shape | **Out of scope.** A separate tracked fix with its own review; not claimed as proven by existing tests | §3(a) |
| Q8 | Idempotency | Per acting Party; not a substitute for provider deduplication | §1(b) |
| Q9 | S4-C kinds | Not mapped or admitted; they come with their own implementation | §2(b) |
| Q10 | Rollback | Refuse after any Business evidence; exact Investor-only round trip, conditional on the snapshot tests and dependency ordering | §3(c)-(d) |

**New open questions:**
- **N1.** The deadlock test forces M1 to be the victim with a per-session `deadlock_timeout`, which is a superuser setting. CI runs as `postgres` (`tests.yml:53`). Is that acceptable, or should the test accept either victim and assert atomicity for whichever side aborted?
- **N2.** M2 rewrites the ledger tables a second time (`SET EXPRESSION`), after M1's column add. Should we keep the M1/M2 split, which is Investor-only first with no dormant Business values, or accept one combined migration with a larger lock set?
- **N3.** `business_deposit_line_conserved` fires for every ledger line, and does one PK read for an Investor line. Is that acceptable, or should lines carry a copied owner column?
- **N4.** Production run of M1/M2: who approves the change window and the retry policy under change management? The migrations never force.

## 7. Sequencing and scope
- Implementation is sequenced **after the #176 S3-D corrections and #175 have merged to dev**. M1 and M2 are timestamped after both heads' last migrations.
- Every replaced or restored constraint and function is restated from the integrated heads at that point. That includes #175's `primary_commitment_source_unavailable`, which is preserved untouched.
- **No shared constraint changes before Hussain approves.** That covers the `investor_wallets`, `ledger_*`, `wallet_deposit_intents` and `wallet_provider_events` definitions, `protect_ledger_entry`, `OperationJournal` and `MandateAuthority::PERMISSIONS`.
- After approval, M1 and M2 arrive as one draft PR against `dev`, with every test named here. It goes through the normal dev → uat → main promotion.
- This revision does not accept the broader C4 ownership split, and it does not interrupt the assigned #176 corrections or the UI work.
