<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app.php';require dirname(__DIR__).'/booking-sms.php';
if(sms_settings()['enabled']!==true){fwrite(STDERR,"SMS disabled. Configure your provider and enable sms in config.php first.\n");exit(1);}
$pdo=db();$ids=$pdo->query("SELECT booking_id FROM booking_sms WHERE status='pending' ORDER BY id LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
foreach($ids as $id)dispatch_booking_sms($pdo,(int)$id);
echo "Processed pending alerts. Accepted means provider acceptance, not confirmed handset delivery.\n";
