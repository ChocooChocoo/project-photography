# Deploy runbook: gallery approval columns

## The cause in one line

The gallery approval columns are missing in the deployed database because
`2026_09_22_090100_add_gallery_approval_columns` has not run there.

There is no application code fix. A request that writes `approval_status` fails
with `Unknown column 'approval_status' in 'SET'` until the deployed database
has the columns.

## What decides the path

Step 2 (the pre-check) decides the path. Read it before you change anything.

- Nothing applied -> use Step 4 (preferred). Use Step 5 only if Step 4 cannot run.
- Partly applied -> use the SQL file in Step 5. Do not use `migrate`.
- Fully applied -> do not run anything. Go to Step 6 and confirm the action.

## Step 1: take a full backup

1. Open phpMyAdmin on the deployed host.
2. Select the database `u565877322_Project_00`.
3. Click **Export**.
4. Keep the default format **SQL**.
5. Click **Go** and save the file to a safe place.

Do not continue until the backup file is saved. You cannot undo a schema change
without it.

## Step 2: run the pre-check

Open the **SQL** tab on the deployed database. Copy section 0 of
`docs/sql/2026-09-22-t01-schema-additions.sql` and run it.

Section 0 returns four small result sets:

1. `present` for each expected column (one row per column).
2. `present` for the `sessions` table.
3. `present` for each of the four migration bookkeeping rows.
4. The last five applied batches, for context only.

`present = 1` means the object exists. `present = 0` means it is missing.

## Step 3: what to do for each pre-check result

### Nothing applied

All column rows show `present = 0`. No `sessions` table. No bookkeeping rows.

- Go to Step 4.

### Partly applied

Some rows show `present = 1` and some show `present = 0`.

Examples:

- `sessions` exists, but `approval_status` is missing.
- `approval_status` exists, but `rejected_at` is missing.
- The columns exist, but the foreign keys are missing.
- The columns exist, but the bookkeeping rows are missing.

- Do not use `php artisan migrate`. It would stop on the first object that
  already exists.
- Go to Step 5. The SQL file is statement-by-statement, so it adds only what is
  missing.

### Fully applied

All 17 column rows show `present = 1`, `sessions` shows `present = 1`, and all
four bookkeeping rows show `present = 1`.

- Do not run the SQL file and do not run `migrate`.
- Go to Step 6 to confirm the gallery approval action works.

## Step 4: preferred path, `php artisan migrate`

This is the preferred path. The application records the run correctly.

1. On the deployed application, run:

   ```
   php artisan migrate:status
   ```

   Read the list. A migration marked `Pending` has not run.

2. Then run:

   ```
   php artisan migrate --force
   ```

3. `--force` is required. The deployed `APP_ENV` is `production`, and Laravel
   asks for confirmation in production. Without `--force` the command waits and
   does nothing.

4. Stop when the command reports success. Then go to Step 6.

If the command fails with "duplicate column", the database is partly applied.
Go back to Step 3 and use Step 5.

## Step 5: fallback path, run the SQL file

Use this path when `migrate` cannot run, or when the database is partly applied.

Run the two files in this order. Use the phpMyAdmin **SQL** tab.

1. `docs/sql/2026-09-22-t01-schema-additions.sql`
   Run sections 1, then 2, then 3, then 4, then 6. Section 0 is the pre-check
   and section 7 is the post-check.
2. `docs/sql/2026-09-22-permission-string-repair.sql`
   Run 5.1 (preview), then 5.2 (repair), then 5.3 (duplicate check), then 5.4
   (bookkeeping).

Every section is safe to re-run. Each one checks the catalog first and changes
nothing when the object already exists. If you are not sure where you stopped,
run the whole file again.

If section 5.1 or 5.3 returns rows, read them:

- 5.1 lists stored permission values that do not match the canonical form.
  5.2 writes the canonical form.
- 5.3 lists two permission rows that resolve to the same string. Do not delete
  anything. Send the ids to the developer and wait for a decision.

## Step 6: post-check and confirm the action

Run section 7 of `docs/sql/2026-09-22-t01-schema-additions.sql`. Check the
result:

- `approval_status` is `enum('pending','approved','rejected','cancelled')` and
  is nullable.
- `rejection_reason` is `text`.
- `submitted_at`, `approved_at`, and `rejected_at` are `timestamp` and nullable.
- `submitted_by`, `approved_by`, and `rejected_by` are `bigint unsigned` and
  nullable.
- There are three foreign keys from each gallery table to `tbl_users`, each
  with `ON DELETE SET NULL`.
- The result of `SELECT migration, batch FROM migrations WHERE migration LIKE
  '2026_09_22%'` has four rows.

Then confirm the real action. In the owner portal, open an online gallery and
submit it for approval. The submit must succeed. A successful submit writes
`approval_status` and `submitted_at`, so it cannot work before the columns
exist.

## Other migrations that may be unapplied

The deploy lag may affect more than these four files. The deployed database may
be missing any migration that was added after the last deploy.

Find out with one query:

```sql
SELECT migration FROM migrations ORDER BY migration;
```

Then compare that list against the files in the `database/migrations/` folder.
Every file name without a matching row in the result has not run. Send the list
of missing file names to the developer before running anything else.

## Rollback: the gallery approval submit still fails

If the submit still fails after Step 4 or Step 5:

1. Re-run the section 0 pre-check. Confirm all 17 columns show `present = 1`
   and each foreign key is present. A missing foreign key does not stop the
   submit, but a missing column does.
2. Confirm the deployed application connects to the same database you changed.
   The host is `localhost` from the application server.
3. Confirm the table name in the error. The error names the exact column and
   table. If it names a column that is not in this runbook, the deploy lag
   touches more migrations. Use the query above.
4. Clear the application cache on the deployed host:

   ```
   php artisan optimize:clear
   ```

5. If the error persists, restore the backup from Step 1 and report the exact
   error text to the developer. Do not hand-edit the tables further.
