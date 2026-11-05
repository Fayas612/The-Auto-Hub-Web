<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
ob_start();require dirname(__DIR__).'/staff.php';session_write_close();$pdo=db();$ids=[];$sessions=[];$bookingId=null;$newEmail='qa-'.bin2hex(random_bytes(8)).'@example.com';
function verify(bool $ok,string $label):void{if(!$ok)throw new RuntimeException('FAIL: '.$label);echo 'PASS: '.$label.PHP_EOL;}
function http_staff(string $path,string $sid,?array $body=null):array{
 $options=['method'=>$body===null?'GET':'POST','header'=>"Cookie: PHPSESSID=$sid\r\n",'ignore_errors'=>true,'follow_location'=>0,'timeout'=>15];
 if($body!==null){$options['header'].="Content-Type: application/x-www-form-urlencoded\r\n";$options['content']=http_build_query($body);}
 $content=file_get_contents('http://127.0.0.1:8080/'.$path,false,stream_context_create(['http'=>$options]));if($content===false)throw new RuntimeException('Local server not running.');return ['body'=>$content,'status'=>(int)explode(' ',$http_response_header[0])[1]];
}
$reviewUser=null;
try{
 foreach(['super_admin','admin'] as $role){
  $stmt=$pdo->prepare('INSERT INTO staff(name,email,password_hash,role) VALUES (?,?,?,?)');$stmt->execute(['QA '.$role,'qa-'.bin2hex(random_bytes(8)).'@example.com',password_hash('qa-password-for-testing',PASSWORD_DEFAULT),$role]);$ids[$role]=(int)$pdo->lastInsertId();
  session_id(bin2hex(random_bytes(16)));session_start();$_SESSION=['staff_id'=>$ids[$role],'staff_version'=>1,'csrf'=>bin2hex(random_bytes(32))];$sessions[$role]=['id'=>session_id(),'csrf'=>$_SESSION['csrf']];session_write_close();
 }
 $super=$sessions['super_admin'];$admin=$sessions['admin'];
 $stmt=$pdo->prepare('SELECT email FROM staff WHERE id=?');$stmt->execute([$ids['admin']]);$loginEmail=$stmt->fetchColumn();
 $loginResult=http_staff('admin.php',$admin['id'],['csrf'=>$admin['csrf'],'action'=>'login','email'=>$loginEmail,'password'=>'incorrect-password']);
 verify(str_contains($loginResult['body'],'Sign-in failed.'),'Incorrect staff password rejected');
 // A successful login rotates the session ID, so use a separate disposable session.
 session_id(bin2hex(random_bytes(16)));session_start();$_SESSION=['csrf'=>bin2hex(random_bytes(32))];$sessions['login']=['id'=>session_id(),'csrf'=>$_SESSION['csrf']];session_write_close();
 verify(http_staff('admin.php',$sessions['login']['id'],['csrf'=>$sessions['login']['csrf'],'action'=>'login','email'=>$loginEmail,'password'=>'qa-password-for-testing'])['status']===302,'Staff email and password login succeeds');
 verify(str_contains(http_staff('admin.php',$admin['id'])['body'],'A clear view of your day.'),'Admin dashboard renders');
 verify(str_contains(http_staff('admin.php?view=customers',$admin['id'])['body'],'Customer directory'),'Admin can view customers');
 $stmt=$pdo->prepare('INSERT INTO users(name,email,password_hash) VALUES (?,?,?)');$stmt->execute(['QA review customer','review-qa-'.bin2hex(random_bytes(8)).'@example.com',password_hash('qa-review-password',PASSWORD_DEFAULT)]);$reviewUser=(int)$pdo->lastInsertId();
 $reviewMarker='QA-review-'.bin2hex(random_bytes(6));$stmt=$pdo->prepare('INSERT INTO reviews(user_id,rating,comment) VALUES (?,?,?)');$stmt->execute([$reviewUser,2,$reviewMarker.' <script>unsafe</script>']);
 $adminReview=http_staff('admin.php?view=reviews&q='.urlencode($reviewMarker),$admin['id']);
 verify($adminReview['status']===200&&str_contains($adminReview['body'],$reviewMarker),'Admin can read customer reviews');
 verify(str_contains($adminReview['body'],'&lt;script&gt;unsafe&lt;/script&gt;'),'Review HTML safely escaped in staff page');
 verify(str_contains(http_staff('admin.php?view=reviews&q='.urlencode($reviewMarker).'&rating=5',$admin['id'])['body'],'No reviews match your filters.'),'Review star rating filter works');
 verify(str_contains(http_staff('admin.php?view=reviews&q='.urlencode($reviewMarker).'&rating=2',$super['id'])['body'],$reviewMarker),'Super Admin can view matching reviews');
 verify(http_staff('admin.php?view=staff',$admin['id'])['status']===403,'Admin denied staff management page');
 verify(http_staff('admin.php?view=activity',$admin['id'])['status']===403,'Admin denied activity log');
 $create=['action'=>'create_staff','name'=>'QA team member','email'=>$newEmail,'new_password'=>'qa-staff-password-2026','role'=>'admin'];
 verify(http_staff('admin.php',$admin['id'],['csrf'=>$admin['csrf'],...$create])['status']===403,'Forged staff creation blocked for Admin');
 verify(http_staff('admin.php',$super['id'],['csrf'=>'invalid',...$create])['status']===403,'Invalid CSRF blocked');
 verify(http_staff('admin.php',$super['id'],['csrf'=>$super['csrf'],...$create])['status']===302,'Super Admin creates staff');
 $stmt=$pdo->prepare('SELECT id,password_hash FROM staff WHERE email=?');$stmt->execute([$newEmail]);$created=$stmt->fetch();$ids['created']=(int)$created['id'];verify(password_verify('qa-staff-password-2026',$created['password_hash']),'New staff password stored as hash');
 verify(str_contains(http_staff('admin.php?view=staff',$super['id'])['body'],'QA team member'),'Super Admin staff editor renders');
 $stmt=$pdo->prepare("INSERT INTO bookings(name,email,phone,vehicle,service,preferred_date,notes) VALUES ('QA','qa@example.com','1234567890','QA car','nano',?,'QA notes')");$stmt->execute([date('Y-m-d',strtotime('+7 days'))]);$bookingId=(int)$pdo->lastInsertId();
 verify(http_staff('admin.php',$admin['id'],['csrf'=>$admin['csrf'],'action'=>'booking','id'=>$bookingId,'status'=>'confirmed'])['status']===302,'Admin updates appointment');
 $stmt=$pdo->prepare('SELECT status FROM bookings WHERE id=?');$stmt->execute([$bookingId]);verify($stmt->fetchColumn()==='confirmed','Status persisted');
 $stmt=$pdo->prepare("SELECT COUNT(*) FROM staff_activity WHERE staff_id=? AND action='Booking updated'");$stmt->execute([$ids['admin']]);verify((int)$stmt->fetchColumn()===1,'Booking change audited');
 $disable=['csrf'=>$super['csrf'],'action'=>'update_staff','id'=>$ids['admin'],'name'=>'QA admin','email'=>'qa-disabled-'.bin2hex(random_bytes(6)).'@example.com','role'=>'admin','active'=>'0','new_password'=>''];
 verify(http_staff('admin.php',$super['id'],$disable)['status']===302,'Super Admin deactivates staff');
 verify(str_contains(http_staff('admin.php',$admin['id'])['body'],'Sign in to staff portal'),'Deactivated session loses dashboard access');
 $self=$disable;$self['id']=$ids['super_admin'];$self['role']='admin';verify(str_contains(http_staff('admin.php',$super['id'],$self)['body'],'You cannot disable or demote your own account.'),'Self-demotion blocked');
 verify(str_contains(http_staff('admin.php?view=activity',$super['id'])['body'],'Staff updated'),'Super Admin activity log renders');
}finally{
 if($reviewUser){$stmt=$pdo->prepare('DELETE FROM users WHERE id=?');$stmt->execute([$reviewUser]);}
 if($bookingId){$stmt=$pdo->prepare('DELETE FROM bookings WHERE id=?');$stmt->execute([$bookingId]);}
 $stmt=$pdo->prepare('SELECT id FROM staff WHERE email=?');$stmt->execute([$newEmail]);$extra=$stmt->fetchColumn();if($extra)$ids['created']=(int)$extra;
 foreach(array_unique($ids) as $id){$stmt=$pdo->prepare('DELETE FROM staff_activity WHERE staff_id=?');$stmt->execute([$id]);$stmt=$pdo->prepare('DELETE FROM staff WHERE id=?');$stmt->execute([$id]);}
 foreach($sessions as $session){session_id($session['id']);session_start();$_SESSION=[];session_destroy();}
}
