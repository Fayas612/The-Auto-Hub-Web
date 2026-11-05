<?php
require __DIR__.'/app.php';
header('Cache-Control: no-store');
if(!empty($_SESSION['user_id'])) { header('Location: account.php'); exit; }
$register=($_GET['mode']??'')==='register'; $error=''; $name=''; $email='';
if($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf();
 $name=trim((string)($_POST['name']??'')); $email=strtolower(trim((string)($_POST['email']??''))); $password=(string)($_POST['password']??'');
 $attempts=array_filter($_SESSION['user_attempts']??[],fn($time)=>$time>time()-900);
 if(count($attempts)>=8) $error='Too many attempts. Please try again in 15 minutes.';
 elseif(!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>254 || strlen($password)>72 || strlen($password)<($register?12:1) || ($register && ($name==='' || strlen($name)>100))) $error='Enter a valid name, email and password. New passwords must be 12–72 bytes long.';
 else {
  $attempts[]=time(); $_SESSION['user_attempts']=$attempts;
  try {
   $pdo=db();
   if($register) {
    if($password!==(string)($_POST['confirm_password']??'')) $error='Your passwords do not match.';
    else {
     $stmt=$pdo->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)');
     $stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
     session_regenerate_id(true); $_SESSION['user_id']=(int)$pdo->lastInsertId(); $_SESSION['user_attempts']=[];
     header('Location: account.php'); exit;
    }
   } else {
    $stmt=$pdo->prepare('SELECT id,password_hash FROM users WHERE email=?'); $stmt->execute([$email]); $user=$stmt->fetch();
    $hash=$user['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
    if(password_verify($password,$hash) && $user) {
     session_regenerate_id(true); $_SESSION['user_id']=(int)$user['id']; $_SESSION['user_attempts']=[]; header('Location: account.php'); exit;
    }
    $error='Email or password is incorrect.';
   }
  }catch(PDOException $e) { error_log($e->getMessage()); $error=$e->getCode()==='23000'?'Unable to create this account. Try signing in or use another email.':'Accounts are temporarily unavailable. Please try again later.'; }
  catch(Throwable $e) { error_log($e->getMessage()); $error='Accounts are temporarily unavailable. Please try again later.'; }
 }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=$register?'Create account':'Customer login'?> • The Auto Hub</title><link rel="stylesheet" href="assets/style.css"><link rel="stylesheet" href="assets/auth.css"><link rel="stylesheet" href="assets/branding.css"><link rel="icon" type="image/jpeg" href="assets/auto-hub-logo.jpeg"><link rel="stylesheet" href="assets/theme.css"><script src="assets/theme.js"></script></head><body><main class="auth-layout"><aside class="auth-story"><a class="brand" href="index.php"><img class="brand-logo" src="assets/auto-hub-logo.jpeg" width="64" height="64" alt="The Auto Hub — established 2021"><span class="brand-copy">THE AUTO HUB<small>DETAILING SERVICES</small></span></a><nav class="auth-nav" aria-label="Main navigation"><a href="index.php">Home</a><a href="index.php#services">Our services</a><a href="index.php#approach">The process</a><a class="auth-book" href="index.php#booking">Book a detail ↗</a></nav><div><p class="eyebrow">CARE BEYOND THE SURFACE</p><h1>Your Car. Your Care.<br><em>Your Auto Hub.</em></h1><p>A fresh finish starts here. Connect with The Auto Hub for nano coating, windscreen polishing and headlight polishing.</p></div><small>THE AUTO HUB DETAILING SERVICES</small></aside><section class="auth-panel"><a class="back" href="index.php">← Back to website</a><div class="auth-card"><span class="auth-icon" aria-hidden="true">↗</span><p class="eyebrow">YOUR AUTO HUB ACCOUNT</p><h2><?=$register?'Let’s get you started.':'Welcome back.'?></h2><p><?=$register?'Create your account and give your vehicle the care it deserves.':'Sign in to your personal detailing account.'?></p><div class="auth-tabs"><a href="login.php" <?=!$register?'aria-current="page"':''?>>Sign in</a><a href="login.php?mode=register" <?=$register?'aria-current="page"':''?>>Create account</a></div><?php if($error): ?><p class="auth-error" role="alert"><?=h($error)?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><?php if($register): ?><label>Full name<input name="name" autocomplete="name" maxlength="100" value="<?=h($name)?>" required></label><?php endif; ?><label>Email address<input type="email" name="email" autocomplete="email" maxlength="254" value="<?=h($email)?>" placeholder="you@example.com" required></label><label>Password<input type="password" name="password" autocomplete="<?=$register?'new-password':'current-password'?>" <?=$register?'minlength="12"':''?> maxlength="72" required></label><?php if($register): ?><p class="fine">Use at least 12 characters. Maximum 72 bytes.</p><label>Confirm password<input type="password" name="confirm_password" autocomplete="new-password" minlength="12" maxlength="72" required></label><?php endif; ?><button class="password-visibility" type="button" aria-pressed="false">Show passwords</button><button class="button" type="submit"><?=$register?'Create account':'Sign in'?> <span>↗</span></button></form><p class="auth-footer"><?=$register?'Already have an account?':'New to The Auto Hub?'?> <a href="<?=$register?'login.php':'login.php?mode=register'?>"><?=$register?'Sign in':'Create an account'?></a></p></div><small class="auth-bottom">Secure access · Thoughtful care · The Auto Hub</small></section></main><script src="assets/auth.js"></script></body></html>





