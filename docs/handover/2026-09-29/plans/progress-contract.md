@hussain4real the review of #175 `f1e99b16..3141615a` is posted on the PR. The server arithmetic is correct, and there's one P2 in each range:
- **P2-A1:** a candidate that always fails heads every `primary:expire-reservations` run, so no other overdue hold on the platform ever expires. The sibling `campaigns:expire` uses a cursor or skip, and this sweep needs the same.
- **P2-B1:** `lifecycle` is always `live`, including fully held, burned inventory, and past-deadline-with-commitment.

**The campaign progress contract needs a decision before we adapt the UI.** We rendered the real Business campaign page with your server JSON. Under the new semantics it shows figures that don't reconcile:
- **Unit and money totals don't add up.** "5 of 2,160 committed · 7 reserved · 2,133 available" leaves 15 units unaccounted for. Committed, reserved and left to raise sum to more than the target.
- **The status label is always "Raising · live".** It stays that way when every note is held, when the inventory is burned, at 100%, and after the deadline, alongside "Left to raise RWF 10,800,000" and a cancel button.
- **Our copy promises recycling.** It says "released after 5 minutes" and "go back on sale", which is no longer true.

Our proposal, for you to accept or change. The server stays the source of every figure, and the UI never subtracts:
1. **An explicit `units.unavailable`** for returned or overdue claims that are occupied but not live, so that committed + reserved + available + unavailable = total. Its copy would be "Not yet back on sale" until recycling exists.
2. **Money rows that reconcile to the target.** Either `remaining` = target − committed − live reserved, or keep your `remaining` and add `reserved_principal`, so the UI labels "Left to raise" as *excluding* live holds. Which one?
3. **Server lifecycle states** so the chip and notice are truthful:
   - `live`;
   - `fully_reserved` (available = 0 and reserved > 0);
   - `sold_out_pending_settlement` (committed = target);
   - `inventory_unavailable` (available = 0 with no live holds);
   - `closing_pending_settlement` (past the deadline with commitments, deferred by the sweep).

   The UI maps each one to copy. Cancel shows only when `can_cancel`, as it does today.
4. **Copy (ours, now).** We'll remove "go back on sale" and "released after 5 minutes" wherever they aren't guaranteed, and replace them with neutral wording. That part is copy only, as a separate draft for your review.

Also, **treat recycling as an activation blocker.** Two abandoned 50% checkouts make a raise permanently unsellable while it still reads as live.
