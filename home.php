<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Smart E-Learning Platform</title>
  <style>
    body { background: linear-gradient(180deg,#071028,#0b2540); color: #fff; font-family: Inter, Arial, sans-serif; margin:0; min-height:100vh; }
    .container { max-width: 600px; margin: 80px auto; background: rgba(255,255,255,0.04); border-radius: 18px; box-shadow: 0 8px 32px rgba(2,6,23,0.25); padding: 40px 32px; text-align: center; }
    .logo { width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg,#7c3aed,#06b6d4); display: flex; align-items: center; justify-content: center; font-size: 2.2rem; font-weight: 800; margin: 0 auto 18px; color: #fff; box-shadow: 0 8px 24px rgba(124,58,237,0.18); }
    h1 { font-size: 2.1rem; font-weight: 800; margin-bottom: 10px; }
    p { color: #cbd5e1; margin-bottom: 32px; }
    .btn-row { display: flex; gap: 18px; justify-content: center; margin-top: 32px; }
    .btn { padding: 14px 32px; border-radius: 10px; border: 0; font-size: 1.1rem; font-weight: 700; cursor: pointer; background: linear-gradient(90deg,#7c3aed,#06b6d4); color: #fff; transition: 0.2s; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(124,58,237,0.25); }
    .btn-outline { background: transparent; border: 2px solid #7c3aed; color: #7c3aed; }
    @media (max-width: 700px) { .container { padding: 24px 8px; } }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">EL</div>
    <h1>Smart E-Learning Platform</h1>
    <p>Welcome to the Smart E-Learning Management System. Please sign up for a new account or log in to your dashboard.</p>
    <div class="btn-row">
      <a href="signup.php" class="btn">Sign Up</a>
      <a href="index.php" class="btn btn-outline">Login</a>
    </div>
    <div style="margin-top:32px; color:#94a3b8; font-size:13px;">Demo logins available: <code>student@demo</code> / <code>demo</code></div>
  </div>
</body>
</html>
