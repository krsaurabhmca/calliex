<?php
require_once 'config/db.php';
// Deduplicate
mysqli_query($conn, "DELETE c1 FROM call_logs c1 INNER JOIN call_logs c2 ON c1.id > c2.id AND c1.mobile = c2.mobile AND c1.call_time = c2.call_time AND c1.executive_id = c2.executive_id");
// Add unique key
mysqli_query($conn, "ALTER TABLE call_logs ADD UNIQUE KEY unique_call (executive_id, mobile, call_time)");

// Fix blocks FK mistake
mysqli_query($conn, "ALTER TABLE blocks DROP FOREIGN KEY blocks_ibfk_1"); 
mysqli_query($conn, "ALTER TABLE blocks ADD CONSTRAINT fk_block_dist FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE");

echo "Schema fixes applied.";
?>
