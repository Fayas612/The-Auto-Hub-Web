<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
ob_start();
require dirname(__DIR__).'/app.php';
session_write_close();$pdo=db();$ids=[];$sessions=[];
function check(bool $ok,string $label):void {if(!$ok)throw new RuntimeException('FAIL: '.$label);echo 'PASS: '.$label.PHP_EOL;}
function request(string $path,string $sid,?array $body=null):string {
 $options=['method'=>$body===null?'GET':'POST','header'=>"Cookie: PHPSESSID=$sid\r\n",'ignore_errors'=>true,'timeout'=>15];
 if($body!==null){$options['header'].="Content-Type: application/x-www-form-urlencoded\r\n";$options['content']=http_build_query($body);}
 $result=file_get_contents('http://127.0.0.1:8080/'.$path,false,stream_context_create(['http'=>$options]));
 if($result===false)throw new RuntimeException('Local server unavailable.');return $result;
}
try {
 foreach(['A','B'] as $label){
  $stmt=$pdo->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)');
  $stmt->execute(['Appointment QA '.$label,'qa-'.bin2hex(random_bytes(10)).'@example.com',password_hash(bin2hex(random_bytes(20)),PASSWORD_DEFAULT)]);
  $ids[$label]=(int)$pdo->lastInsertId();
  session_id(bin2hex(random_bytes(16)));session_start();$_SESSION=['user_id'=>$ids[$label],'csrf'=>bin2hex(random_bytes(32))];
  $sessions[$label]=['id'=>session_id(),'csrf'=>$_SESSION['csrf']];session_write_close();
 }
 $body=['csrf'=>$sessions['A']['csrf'],'name'=>'Appointment QA','email'=>'qa@example.com','phone'=>'1234567890','vehicle'=>'QA Vehicle','service'=>'nano','preferred_date'=>date('Y-m-d',strtotime('+7 days')),'notes'=>'<script>qa-only</script>','user_id'=>$ids['B']];
 $result=json_decode(request('booking.php',$sessions['A']['id'],$body),true,512,JSON_THROW_ON_ERROR);
 check(isset($result['reference']),'Booking accepted');$reference=(int)$result['reference'];
 $smsStmt=$pdo->prepare('SELECT recipient,status FROM booking_sms WHERE booking_id=?');$smsStmt->execute([$reference]);$smsAlert=$smsStmt->fetch();
 check($smsAlert&&$smsAlert['recipient']==='+94777278078','Booking alert queued for business phone');
 $stmt=$pdo->prepare('SELECT user_id FROM bookings WHERE id=?');$stmt->execute([$reference]);
 check((int)$stmt->fetchColumn()===$ids['A'],'Ownership uses session, not submitted user_id');
 $page=request('account.php',$sessions['A']['id']);
 check(str_contains($page,'REQUEST #'.$reference),'Owner sees appointment');
 check(str_contains($page,'&lt;script&gt;qa-only&lt;/script&gt;'),'Appointment notes are escaped');
 $badReview=request('submit-review.php',$sessions['A']['id'],['csrf'=>$sessions['A']['csrf'],'rating'=>7,'comment'=>'QA review validation']);
 check(str_contains($badReview,'Choose a rating and write a comment'),'Invalid review rating rejected');
 $reviewPage=request('submit-review.php',$sessions['A']['id'],['csrf'=>$sessions['A']['csrf'],'rating'=>5,'comment'=>'QA review <script>review-only</script>','user_id'=>$ids['B'],'name'=>'Forged name']);
 check(str_contains($reviewPage,'Thank you! Your review is now visible below.'),'Review publishes successfully');
 check(str_contains($reviewPage,'&lt;script&gt;review-only&lt;/script&gt;'),'Review comment is escaped');
 $reviewStmt=$pdo->prepare('SELECT user_id FROM reviews WHERE comment=?');$reviewStmt->execute(['QA review <script>review-only</script>']);
 check((int)$reviewStmt->fetchColumn()===$ids['A'],'Review belongs to authenticated user');
 check(str_contains(request('submit-review.php',$sessions['A']['id'],['csrf'=>'invalid','rating'=>5,'comment'=>'QA review invalid CSRF']),'Session expired'),'Review requires valid CSRF');
 $reviewDashboard=request('account.php',$sessions['A']['id']);
 check(str_contains($reviewDashboard,'id="my-review"')&&str_contains($reviewDashboard,'QA review &lt;script&gt;review-only&lt;/script&gt;'),'Dashboard shows the customer review');
 check(!str_contains(request('account.php',$sessions['B']['id']),'QA review &lt;script&gt;review-only&lt;/script&gt;'),'Another dashboard does not show this personal review');
 $reviewStmt=$pdo->prepare('UPDATE reviews SET updated_at=DATE_SUB(NOW(),INTERVAL 2 MINUTE) WHERE user_id=?');$reviewStmt->execute([$ids['A']]);
 $updatedReview=request('submit-review.php',$sessions['A']['id'],['csrf'=>$sessions['A']['csrf'],'rating'=>4,'comment'=>'Updated QA review from dashboard','return_to'=>'account','user_id'=>$ids['B']]);
 check(str_contains($updatedReview,'id="my-review"')&&str_contains($updatedReview,'Updated QA review from dashboard'),'Review update returns to dashboard');
 $reviewStmt=$pdo->prepare('SELECT COUNT(*) AS n,MAX(rating) AS rating FROM reviews WHERE user_id=?');$reviewStmt->execute([$ids['A']]);$savedReview=$reviewStmt->fetch();
 check((int)$savedReview['n']===1&&(int)$savedReview['rating']===4,'Updating replaces own review without duplicates');
 check(!str_contains(request('account.php',$sessions['B']['id']),'REQUEST #'.$reference),'Another account cannot see appointment');
 $stmt=$pdo->prepare("UPDATE bookings SET status='confirmed' WHERE id=?");$stmt->execute([$reference]);
 check(str_contains(request('account.php',$sessions['A']['id']),'status-confirmed'),'Staff status changes appear on dashboard');
 check(str_contains(request('index.php',$sessions['A']['id']),'class="nav-signout"'),'Signed-in navbar shows Sign out');
 check(str_contains(request('account.php',$sessions['A']['id']),'class="header-signout"'),'Customer dashboard header shows Sign out');
 check(str_contains(request('logout.php',$sessions['A']['id'],['csrf'=>'invalid']),'Session expired'),'Logout rejects invalid CSRF');
 check(str_contains(request('logout.php',$sessions['A']['id'],['csrf'=>$sessions['A']['csrf']]),'Welcome back.'),'Navbar sign-out returns to login');
 check(!str_contains(request('index.php',$sessions['A']['id']),'class="nav-signout"'),'Signed-out navbar hides Sign out');
 check(str_contains(request('account.php',$sessions['A']['id']),'Welcome back.'),'Signed-out customer cannot open dashboard');
}finally{
 foreach($ids as $id){$stmt=$pdo->prepare('DELETE FROM bookings WHERE user_id=?');$stmt->execute([$id]);$stmt=$pdo->prepare('DELETE FROM users WHERE id=?');$stmt->execute([$id]);}
 foreach($sessions as $session){session_id($session['id']);session_start();$_SESSION=[];session_destroy();}
}
