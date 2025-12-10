<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Sign Up — Smart E-Learning</title>
  <style>
    body { background: linear-gradient(180deg,#071028,#0b2540); color: #fff; font-family: Inter, Arial, sans-serif; margin:0; min-height:100vh; }
    .container { max-width: 420px; margin: 60px auto; background: rgba(255,255,255,0.04); border-radius: 16px; box-shadow: 0 8px 32px rgba(2,6,23,0.18); padding: 36px 28px; text-align: center; }
    .logo { width: 54px; height: 54px; border-radius: 14px; background: linear-gradient(135deg,#7c3aed,#06b6d4); display: flex; align-items: center; justify-content: center; font-size: 1.7rem; font-weight: 800; margin: 0 auto 16px; color: #fff; box-shadow: 0 8px 24px rgba(124,58,237,0.18); }
    h1 { font-size: 1.5rem; font-weight: 800; margin-bottom: 10px; }
    form { margin-top: 18px; }
    .form-group { margin-bottom: 16px; text-align:left; }
    label { display:block; margin-bottom:6px; font-weight:600; color:#cbd5e1; }
    input, select { width:100%; padding:10px 12px; border-radius:8px; border:1px solid #e6edf3; font-size:15px; margin-bottom:2px; }
    .btn { width:100%; padding:12px 0; border-radius:10px; border:0; font-size:1.1rem; font-weight:700; cursor:pointer; background: linear-gradient(90deg,#7c3aed,#06b6d4); color: #fff; transition: 0.2s; margin-top:10px; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(124,58,237,0.25); }
    .msg { margin: 12px 0; font-size: 1rem; }
    .link { color: #7c3aed; text-decoration: underline; cursor: pointer; }
    @media (max-width: 600px) { .container { padding: 18px 4px; } }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">EL</div>
    <h1>Create Your Account</h1>
    <form id="signupForm" autocomplete="off">
      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" required />
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required />
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="6" />
      </div>
      <div class="form-group">
        <label for="role">Role</label>
        <select id="role" name="role" required>
          <option value="student">Student</option>
          <option value="instructor">Instructor</option>
          <option value="Admin">Instructor</option>
          <option value="Office Manager">Instructor</option>
        </select>
      </div>
      <button type="submit" class="btn">Sign Up</button>
      <div class="msg" id="signupMsg"></div>
    </form>
    <div style="margin-top:18px; color:#cbd5e1; font-size:14px;">Already have an account? <a href="index.php" class="link">Login</a></div>
  </div>
  <script>
    document.getElementById('signupForm').addEventListener('submit', async function(e){
      e.preventDefault();
      const name = document.getElementById('name').value.trim();
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value;
      const role = document.getElementById('role').value;
      const msg = document.getElementById('signupMsg');
      msg.textContent = '';
      try {
        const res = await fetch('api/auth.php?action=register', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name, email, password, role })
        });
        const data = await res.json();
        if(data.success){
          msg.style.color = '#10b981';
          msg.textContent = 'Registration successful! You can now log in.';
          setTimeout(()=>{ window.location.href = 'index.php'; }, 1200);
        } else {
          msg.style.color = '#ef4444';
          msg.textContent = data.message || 'Registration failed.';
        }
      } catch(err){
        msg.style.color = '#ef4444';
        msg.textContent = 'Error: ' + err.message;
      }
    });
  </script>
</body>
</html>
