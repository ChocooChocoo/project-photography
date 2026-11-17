-- Renew the seeded studio subscriptions (FS-SUB-STU-%) that have run out.
-- Seeded monthly plans only had about ten days left when the database was seeded,
-- so most demo studios lost access to Edit Studio and new bookings.
-- Touches only seeded rows. Real subscriptions bought through the app are not changed.
-- Back up your database first. No table/column changes are required.

-- Preview the rows that will change.
SELECT id, studio_id, subscription_reference, status, payment_status, start_date, end_date
FROM tbl_studio_plans
WHERE subscription_reference LIKE 'FS-SUB-STU-%'
  AND payment_status = 'paid'
  AND end_date < CURDATE();

-- Start each again today for its own billing length (yearly plans 365 days, others 30).
UPDATE tbl_studio_plans
SET start_date = CURDATE(),
    end_date = DATE_ADD(CURDATE(), INTERVAL (
        CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(plan_snapshot, '$.plan_code')) LIKE '%YEARLY' THEN 365 ELSE 30 END
    ) DAY),
    next_billing_date = DATE_ADD(CURDATE(), INTERVAL (
        CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(plan_snapshot, '$.plan_code')) LIKE '%YEARLY' THEN 365 ELSE 30 END
    ) DAY),
    status = 'active',
    grace_ends_at = NULL,
    updated_at = NOW()
WHERE subscription_reference LIKE 'FS-SUB-STU-%'
  AND payment_status = 'paid'
  AND end_date < CURDATE();
