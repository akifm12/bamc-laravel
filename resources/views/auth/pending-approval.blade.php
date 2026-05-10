<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blue Arrow Books — Registration Received</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'DM Sans', sans-serif;
    background-color: #f5f6fa;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1a2340;
  }
  .card {
    background: #ffffff;
    border: 1px solid #e8ecf4;
    border-radius: 16px;
    padding: 3rem;
    max-width: 440px;
    width: 100%;
    text-align: center;
    box-shadow: 0 4px 24px rgba(26,58,107,0.07);
  }
  .icon-wrap {
    width: 72px; height: 72px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.5rem;
  }
  .icon-wrap svg {
    width: 32px; height: 32px;
    stroke: #16a34a; fill: none;
    stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round;
  }
  h2 {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 400;
    font-size: 1.9rem;
    color: #111827;
    margin-bottom: 0.75rem;
  }
  p {
    font-size: 0.85rem;
    color: #6b7a99;
    line-height: 1.7;
    margin-bottom: 2rem;
  }
  .btn {
    display: inline-block;
    padding: 10px 28px;
    background: linear-gradient(135deg, #1d4ed8, #3b82f6);
    border-radius: 8px;
    color: #ffffff;
    font-size: 0.88rem;
    font-weight: 500;
    text-decoration: none;
    letter-spacing: 0.05em;
  }
  .footer {
    margin-top: 2rem;
    font-size: 0.7rem;
    color: #9aa5be;
  }
</style>
</head>
<body>
  <div class="card">
    <div class="icon-wrap">
      <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </div>
    <h2>Registration received</h2>
    <p>Your account has been created and is awaiting admin approval. You will be able to log in once your account has been activated. This usually takes less than 24 hours.</p>
    <a class="btn" href="{{ route('login') }}">Back to Login</a>
    <div class="footer">Blue Arrow Books v1.0 &nbsp;·&nbsp; Powered by BAMC</div>
  </div>
</body>
</html>