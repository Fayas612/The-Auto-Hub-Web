<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') {http_response_code(404);exit;}
$file=dirname(__DIR__).'/config.php';
if(!is_file($file)){fwrite(STDERR,"Copy config.example.php to config.php and enter your local MySQL settings first.\n");exit(1);}
$config=require $file;
try {
 if(!str_starts_with($config['dsn'],'mysql:') || !preg_match('/(?:^|;)dbname=auto_hub(?:;|$)/',substr($config['dsn'],6))) throw new RuntimeException('Setup requires a MySQL DSN with dbname=auto_hub.');
 $serverDsn=preg_replace('/;dbname=auto_hub(?=;|$)/','',$config['dsn']);
 $pdo=new PDO($serverDsn,$config['user'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $sql=file_get_contents(dirname(__DIR__).'/database.sql');
 foreach(explode(';',$sql) as $statement){if(trim($statement)!=='')$pdo->exec($statement);}
 if(!$pdo->query("SHOW COLUMNS FROM bookings LIKE 'user_id'")->fetch()) {
  $pdo->exec('ALTER TABLE bookings ADD COLUMN user_id BIGINT UNSIGNED NULL, ADD INDEX (user_id), ADD CONSTRAINT bookings_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
 }
 echo "Created/verified auto_hub database, users table and bookings table. Existing records preserved.\n";
}catch(Throwable $e){fwrite(STDERR,"Database setup failed. Check that MySQL is running and your config.php credentials have CREATE privileges.\n".$e->getMessage()."\n");exit(1);}
