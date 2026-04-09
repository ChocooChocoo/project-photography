Objective: Resolve Client Booking Payment Error

    1). On the client side, an error appears when clients proceed with the booking payment process.

Sample Error Message:
    Error
    Booking creation failed: Failed to create booking: Array to string conversion
    (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: platinum, SQL:
    insert into `tbl_booking_packages`
    (`booking_id`, `package_id`, `package_type`, `package_name`, `package_price`, `package_inclusions`, `duration`, `maximum_edited_photos`, `coverage_scope`, `updated_at`, `created_at`)
    values
    (1, 5, studio, Birthday Celebration - Family Package, 22000.00, "[\"5 hours of event coverage\",\"1 main photographer\",\"300+ edited high-resolution photos\",\"Preparation shots (before party)\",\"Cake cutting ceremony\",\"Candid guest interactions\",\"Family portraits\",\"Online gallery with download access\",\"USB drive with selected photos\",\"10 printed 4x6 photos in keepsake box\"]", 5, 300, ?, 2026-04-09 18:57:05, 2026-04-09 18:57:05))

Description:
    The booking creation fails during payment processing because of an “Array to string conversion” error in the database insert query. Based on the error log, the issue appears to be related to the coverage_scope field, which is being passed as an array or invalid value instead of a string format expected by MySQL.