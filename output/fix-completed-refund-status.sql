-- Run in phpMyAdmin only after uploading the refund-status code fix.
-- Back up your database first. No table/column changes are required.
-- Only cancelled bookings incorrectly left pending after a resolved refund are updated.

-- Preview affected bookings before running UPDATE.
SELECT b.id, b.booking_reference, b.payment_status,
       p.total_paid, p.total_refunded,
       CASE
           WHEN p.total_refunded >= p.total_paid - 0.001 THEN 'refunded'
           WHEN p.total_refunded > 0 THEN 'partially_refunded'
           ELSE 'paid'
       END AS corrected_payment_status
FROM tbl_bookings b
JOIN (
    SELECT booking_id, SUM(amount) AS total_paid,
           SUM(COALESCE(refunded_amount, CASE WHEN status = 'refunded' THEN amount ELSE 0 END)) AS total_refunded
    FROM tbl_payments
    WHERE status IN ('succeeded', 'refunded', 'partially_refunded')
    GROUP BY booking_id
) p ON p.booking_id = b.id
WHERE b.status = 'cancelled'
  AND b.payment_status = 'refund_pending'
  AND p.total_paid > 0
  AND EXISTS (
      SELECT 1 FROM tbl_booking_cancellation_recoveries r
      WHERE r.booking_id = b.id AND r.status = 'refunded' AND r.resolved_at IS NOT NULL
  )
  AND NOT EXISTS (
      SELECT 1 FROM tbl_booking_cancellation_recoveries r
      WHERE r.booking_id = b.id AND r.status = 'refund_pending'
  );

-- Repair those same records without changing payment amounts or refund evidence.
UPDATE tbl_bookings b
JOIN (
    SELECT booking_id, SUM(amount) AS total_paid,
           SUM(COALESCE(refunded_amount, CASE WHEN status = 'refunded' THEN amount ELSE 0 END)) AS total_refunded
    FROM tbl_payments
    WHERE status IN ('succeeded', 'refunded', 'partially_refunded')
    GROUP BY booking_id
) p ON p.booking_id = b.id
SET b.payment_status = CASE
        WHEN p.total_refunded >= p.total_paid - 0.001 THEN 'refunded'
        WHEN p.total_refunded > 0 THEN 'partially_refunded'
        ELSE 'paid'
    END,
    b.updated_at = CURRENT_TIMESTAMP
WHERE b.status = 'cancelled'
  AND b.payment_status = 'refund_pending'
  AND p.total_paid > 0
  AND EXISTS (
      SELECT 1 FROM tbl_booking_cancellation_recoveries r
      WHERE r.booking_id = b.id AND r.status = 'refunded' AND r.resolved_at IS NOT NULL
  )
  AND NOT EXISTS (
      SELECT 1 FROM tbl_booking_cancellation_recoveries r
      WHERE r.booking_id = b.id AND r.status = 'refund_pending'
  );
