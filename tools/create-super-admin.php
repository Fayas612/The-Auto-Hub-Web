<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app.php';
try {
 $pdo=db();if(!$pdo->query("SELECT GET_LOCK('auto_hub_staff_bootstrap',10)")->fetchColumn())throw new RuntimeException('Another setup is running.');
 if((int)$pdo->query('SELECT COUNT(*) FROM staff')->fetchColumn()>0)throw new RuntimeException('Staff already exist. Use Super Admin > Staff management.');
 fwrite(STDOUT,"Full name: ");$name=trim(fgets(STDIN));
 fwrite(STDOUT,"Staff email: ");$email=strtolower(trim(fgets(STDIN)));
 fwrite(STDOUT,"Password (visible in terminal, 8–72 bytes): ");$password=rtrim(fgets(STDIN),"\r\n");
 if($name===''||strlen($name)>100||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254||strlen($password)<8||strlen($password)>72)throw new RuntimeException('Invalid name, email or password length.');
 $pdo->beginTransaction();$stmt=$pdo->prepare("INSERT INTO staff(name,email,password_hash,role) VALUES (?,?,?,'super_admin')");$stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
 $stmt=$pdo->prepare("INSERT INTO staff_activity(staff_id,action,detail) VALUES (?,'Staff setup','Initial super admin created')");$stmt->execute([$pdo->lastInsertId()]);$pdo->commit();echo "Super Admin created. Sign in at /admin.php.\n";
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
finally{if(isset($pdo))$pdo->query("SELECT RELEASE_LOCK('auto_hub_staff_bootstrap')");}

