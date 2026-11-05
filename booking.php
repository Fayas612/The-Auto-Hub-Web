<?php
require __DIR__.'/app.php';
require __DIR__.'/booking-sms.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST required.']); exit; }
verify_csrf();
$data=[];
foreach (['name'=>100,'email'=>254,'phone'=>30,'vehicle'=>120,'service'=>40,'preferred_date'=>10,'notes'=>2000] as $key=>$max) {
 $data[$key]=trim((string)($_POST[$key]??''));
 if (strlen($data[$key])>$max || ($key!=='notes' && $data[$key]==='')) { http_response_code(422); echo json_encode(['error'=>'Please check all required fields and their lengths.']); exit; }
}
$date=DateTimeImmutable::createFromFormat('!Y-m-d',$data['preferred_date']);
if (!filter_var($data['email'],FILTER_VALIDATE_EMAIL) || !preg_match('/^[+0-9 ()-]{7,30}$/',$data['phone']) || !isset(SERVICES[$data['service']]) || !$date || $date->format('Y-m-d')!==$data['preferred_date'] || $date < new DateTimeImmutable('today')) {
 http_response_code(422); echo json_encode(['error'=>'Enter a valid email, phone number, service, and future booking date.']); exit;
}
if (time()-(int)($_SESSION['last_booking']??0)<60) { http_response_code(429); echo json_encode(['error'=>'Please wait a minute before submitting another request.']); exit; }
try {
 $pdo=db();$pdo->beginTransaction();$stmt=$pdo->prepare('INSERT INTO bookings (name,email,phone,vehicle,service,preferred_date,notes,user_id) VALUES (?,?,?,?,?,?,?,?)');
 $stmt->execute([...array_values($data),$_SESSION['user_id']??null]);$bookingId=(int)$pdo->lastInsertId();
 queue_booking_sms($pdo,$bookingId,$data['service'],$data['preferred_date']);$pdo->commit();$_SESSION['last_booking']=time();
} catch (Throwable $e) { if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();error_log($e->getMessage()); http_response_code(503); echo json_encode(['error'=>'Booking is temporarily unavailable. Please try again later.']);exit; }
// A provider/network failure must not turn a saved booking into a failed request.
try{dispatch_booking_sms($pdo,$bookingId);}catch(Throwable $e){error_log('SMS dispatch unavailable; appointment remains saved.');}
echo json_encode(['message'=>'Your request has been received. Our team will contact you to confirm availability.','reference'=>(string)$bookingId,'account_url'=>!empty($_SESSION['user_id'])?'account.php#appointments':null]);
