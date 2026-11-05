<?php
// Loaded by booking.php after app.php. Never exposes provider secrets to the client.
function sms_settings():array {
 return array_merge(['enabled'=>false,'provider'=>'twilio','recipient'=>'+94777278078','account_sid'=>'','auth_token'=>'','from'=>''],config()['sms']??[]);
}
function queue_booking_sms(PDO $pdo,int $bookingId,string $service,string $date):void {
 $settings=sms_settings();
 $message='The Auto Hub: New booking #'.$bookingId.'. '.(SERVICES[$service]??$service).'. Requested date: '.$date.'. Sign in to the staff portal to review and confirm.';
 $stmt=$pdo->prepare('INSERT INTO booking_sms(booking_id,recipient,message) VALUES (?,?,?)');
 $stmt->execute([$bookingId,$settings['recipient'],$message]);
}
function dispatch_booking_sms(PDO $pdo,int $bookingId):void {
 $settings=sms_settings();
 if($settings['enabled']!==true)return;
 if($settings['provider']!=='twilio'||!preg_match('/^AC[0-9a-fA-F]{32}$/',$settings['account_sid'])||$settings['auth_token']===''||!preg_match('/^\+[1-9][0-9]{7,14}$/',$settings['from'])||!function_exists('curl_init')){
  error_log('Booking SMS configuration is incomplete; alert remains pending.');return;
 }
 $stmt=$pdo->prepare("UPDATE booking_sms SET status='processing',error=NULL WHERE booking_id=? AND status='pending'");$stmt->execute([$bookingId]);if($stmt->rowCount()!==1)return;
 $stmt=$pdo->prepare('SELECT recipient,message FROM booking_sms WHERE booking_id=?');$stmt->execute([$bookingId]);$alert=$stmt->fetch();
 $state='uncertain';$providerId=null;$error='Request interrupted; check provider logs before retrying.';
 try {
  $curl=curl_init('https://api.twilio.com/2010-04-01/Accounts/'.$settings['account_sid'].'/Messages.json');
  curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query(['To'=>$alert['recipient'],'From'=>$settings['from'],'Body'=>$alert['message']]),CURLOPT_USERPWD=>$settings['account_sid'].':'.$settings['auth_token'],CURLOPT_HTTPAUTH=>CURLAUTH_BASIC,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>12,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_FOLLOWLOCATION=>false]);
  $response=curl_exec($curl);$http=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);
  if($response!==false){
   $data=json_decode($response,true);
   if($http===201&&!empty($data['sid'])&&preg_match('/^SM[0-9a-fA-F]{32}$/',$data['sid'])){$state='accepted';$providerId=$data['sid'];$error=null;}
   elseif($http>=400&&$http<500){$state='failed';$error='SMS provider rejected the request (HTTP '.$http.'). Check provider settings and credit.';}
  }
 }catch(Throwable $e){error_log('Booking SMS request could not be completed.');}
 $stmt=$pdo->prepare('UPDATE booking_sms SET status=?,provider_id=?,error=? WHERE booking_id=?');$stmt->execute([$state,$providerId,$error,$bookingId]);
}
