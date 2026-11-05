<?php
require __DIR__.'/app.php';
header('Cache-Control: no-store');
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Allow: POST');http_response_code(405);exit('Please use the review form.');}
verify_csrf();
$reviewDestination=($_POST['return_to']??'')==='account'?'account.php#my-review':'index.php#reviews';
if(empty($_SESSION['user_id'])){header('Location: login.php',true,303);exit;}
$comment=trim((string)($_POST['comment']??''));$rating=filter_var($_POST['rating']??null,FILTER_VALIDATE_INT);
$_SESSION['review_draft']=['comment'=>substr($comment,0,2000),'rating'=>$rating];
if(!$rating||$rating<1||$rating>5||strlen($comment)<10||strlen($comment)>2000){$_SESSION['review_error']='Choose a rating and write a comment between 10 and 2,000 characters.';}
else try{
 $pdo=db();$pdo->beginTransaction();
 // Lock the account row to serialize repeated submissions for this customer.
 $stmt=$pdo->prepare('SELECT id FROM users WHERE id=? FOR UPDATE');$stmt->execute([$_SESSION['user_id']]);
 if(!$stmt->fetchColumn()){unset($_SESSION['user_id']);$pdo->rollBack();header('Location: login.php',true,303);exit;}
 $stmt=$pdo->prepare('SELECT TIMESTAMPDIFF(SECOND,updated_at,NOW()) FROM reviews WHERE user_id=?');$stmt->execute([$_SESSION['user_id']]);$elapsed=$stmt->fetchColumn();
 if($elapsed!==false&&(int)$elapsed<60){$pdo->rollBack();$_SESSION['review_error']='Please wait a minute before updating your review.';}
 else {
  $stmt=$pdo->prepare('INSERT INTO reviews(user_id,rating,comment) VALUES (?,?,?) ON DUPLICATE KEY UPDATE rating=?,comment=?,updated_at=CURRENT_TIMESTAMP');
  $stmt->execute([$_SESSION['user_id'],$rating,$comment,$rating,$comment]);$pdo->commit();
  unset($_SESSION['review_draft']);$_SESSION['review_success']='Thank you! Your review is now visible below.';
 }
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();error_log($e->getMessage());$_SESSION['review_error']='We couldn’t save your review. Please try again later.';}
header('Location: '.$reviewDestination,true,303);exit;
