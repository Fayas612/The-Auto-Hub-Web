<?php
require __DIR__.'/app.php';
header('Cache-Control: no-store');
const STAFF_ROLES=['admin'=>'Admin','super_admin'=>'Super Admin'];
const BOOKING_STATUSES=['pending','confirmed','completed','cancelled'];
function current_staff(PDO $pdo): ?array {
 if(empty($_SESSION['staff_id']))return null;
 $stmt=$pdo->prepare('SELECT * FROM staff WHERE id=? AND active=1');$stmt->execute([$_SESSION['staff_id']]);$row=$stmt->fetch();
 if(!$row||(int)$row['session_version']!==(int)($_SESSION['staff_version']??0)){unset($_SESSION['staff_id'],$_SESSION['staff_version']);return null;}return $row;
}
function staff_audit(PDO $pdo,int $id,string $action,string $detail):void {$stmt=$pdo->prepare('INSERT INTO staff_activity(staff_id,action,detail) VALUES (?,?,?)');$stmt->execute([$id,$action,$detail]);}
function staff_action(PDO $pdo,array $staff,array $input):string {
 $action=(string)($input['action']??'');
 if($action==='booking') {
  if(!filter_var($input['id']??null,FILTER_VALIDATE_INT)||!in_array($input['status']??'',BOOKING_STATUSES,true))throw new InvalidArgumentException('Choose a valid booking and status.');
  $pdo->beginTransaction();try{
   $stmt=$pdo->prepare('SELECT status FROM bookings WHERE id=? FOR UPDATE');$stmt->execute([$input['id']]);$old=$stmt->fetchColumn();if($old===false)throw new InvalidArgumentException('Booking not found.');
   $stmt=$pdo->prepare('UPDATE bookings SET status=? WHERE id=?');$stmt->execute([$input['status'],$input['id']]);staff_audit($pdo,(int)$staff['id'],'Booking updated','#'.$input['id'].': '.$old.' → '.$input['status']);$pdo->commit();return 'Appointment updated. The customer can see the new status.';
  }catch(Throwable $e){$pdo->rollBack();throw $e;}
 }
 if($staff['role']!=='super_admin'){http_response_code(403);throw new InvalidArgumentException('Only a Super Admin can manage staff.');}
 if(!in_array($action,['create_staff','update_staff'],true))throw new InvalidArgumentException('Unknown action.');
 $name=trim((string)($input['name']??''));$email=strtolower(trim((string)($input['email']??'')));$role=(string)($input['role']??'');$password=(string)($input['new_password']??'');
 if($name===''||strlen($name)>100||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254||!isset(STAFF_ROLES[$role]))throw new InvalidArgumentException('Enter a valid name, email and role.');
 if(($action==='create_staff'||$password!=='')&&(strlen($password)<8||strlen($password)>72))throw new InvalidArgumentException('Use a password of 8–72 bytes.');
 $pdo->beginTransaction();try{
  $all=$pdo->query('SELECT id,role,active FROM staff ORDER BY id FOR UPDATE')->fetchAll();
  if($action==='create_staff'){$stmt=$pdo->prepare('INSERT INTO staff(name,email,password_hash,role) VALUES (?,?,?,?)');$stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role]);$id=(int)$pdo->lastInsertId();}
  else{
   $id=filter_var($input['id']??null,FILTER_VALIDATE_INT);$active=($input['active']??'')==='1'?1:0;$target=null;foreach($all as $row)if((int)$row['id']===$id)$target=$row;
   if(!$target)throw new InvalidArgumentException('Staff account not found.');
   if($id===(int)$staff['id']&&($role!=='super_admin'||!$active))throw new InvalidArgumentException('You cannot disable or demote your own account.');
   $remaining=array_filter($all,fn($row)=>(int)$row['id']!==$id&&$row['role']==='super_admin'&&(int)$row['active']===1);
   if(!$remaining&&($role!=='super_admin'||!$active))throw new InvalidArgumentException('Keep at least one active Super Admin.');
   $sql='UPDATE staff SET name=?,email=?,role=?,active=?,session_version=session_version+1';$values=[$name,$email,$role,$active];
   if($password!==''){$sql.=',password_hash=?,failed_attempts=0,locked_until=NULL';$values[]=password_hash($password,PASSWORD_DEFAULT);}
   $sql.=' WHERE id=?';$values[]=$id;$stmt=$pdo->prepare($sql);$stmt->execute($values);
   if($id===(int)$staff['id'])$_SESSION['staff_version']=(int)$staff['session_version']+1;
  }
  staff_audit($pdo,(int)$staff['id'],$action==='create_staff'?'Staff created':'Staff updated','Staff #'.$id.' / '.$role);$pdo->commit();return 'Staff account saved.';
 }catch(Throwable $e){$pdo->rollBack();throw $e;}
}

