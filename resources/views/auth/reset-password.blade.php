<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blue Arrow Books - Set New Password</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background-color: #f5f6fa;
    min-height: 100vh;
    display: flex;
    align-items: stretch;
    color: #1a2340;
  }

  .panel-left {
    width: 52%;
    background: #1a3a6b;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    position: relative;
    overflow: hidden;
  }

  .panel-left::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
      linear-gradient(rgba(255,255,255,0.06) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255,255,255,0.06) 1px, transparent 1px);
    background-size: 40px 40px;
  }

  .logo-glow {
    position: absolute;
    width: 420px;
    height: 420px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
  }

  .brand-block {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .logo-img {
    width: 180px;
    height: 180px;
    object-fit: contain;
    margin-bottom: 0.5rem;
  }

  .brand-name {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 300;
    font-size: 2.6rem;
    letter-spacing: 0.08em;
    color: #ffffff;
    line-height: 1.1;
    margin-bottom: 0.3rem;
  }

  .brand-name span { color: #7ec8ff; }

  .brand-sub {
    font-size: 0.75rem;
    font-weight: 400;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.5);
    margin-bottom: 2.5rem;
  }

  .icons-row {
    display: flex;
    gap: 2rem;
    align-items: center;
    margin-bottom: 2.5rem;
  }

  .icon-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.4rem;
    opacity: 0.8;
  }

  .icon-badge svg {
    width: 28px;
    height: 28px;
    stroke: rgba(255,255,255,0.75);
    fill: none;
    stroke-width: 1.4;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .icon-badge span {
    font-size: 0.62rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.45);
  }

  .divider-line {
    width: 1px;
    height: 36px;
    background: rgba(255,255,255,0.2);
  }

  .feature-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
    max-width: 360px;
  }

  .pill {
    font-size: 0.7rem;
    letter-spacing: 0.05em;
    padding: 5px 12px;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.2);
    color: rgba(255,255,255,0.65);
    background: rgba(255,255,255,0.08);
  }

  .panel-footer {
    position: absolute;
    bottom: 1.5rem;
    font-size: 0.68rem;
    color: rgba(255,255,255,0.3);
    letter-spacing: 0.05em;
  }

  .panel-right {
    width: 48%;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 4rem;
    border-left: 1px solid #e8ecf4;
  }

  .form-wrapper { width: 100%; max-width: 360px; }

  .form-title {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 400;
    font-size: 1.9rem;
    color: #111827;
    margin-bottom: 0.3rem;
  }

  .form-subtitle {
    font-size: 0.8rem;
    color: #6b7a99;
    margin-bottom: 2rem;
    letter-spacing: 0.02em;
    line-height: 1.6;
  }

  .alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
    font-size: 0.78rem;
    padding: 10px 14px;
    border-radius: 8px;
    margin-bottom: 1.2rem;
  }

  .alert-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #15803d;
    font-size: 0.78rem;
    padding: 10px 14px;
    border-radius: 8px;
    margin-bottom: 1.2rem;
  }

  .form-group { margin-bottom: 1.1rem; }

  .form-label {
    display: block;
    font-size: 0.72rem;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #6b7a99;
    margin-bottom: 0.45rem;
  }

  .form-input {
    width: 100%;
    padding: 10px 14px;
    background: #f8f9fc;
    border: 1px solid #dde2ef;
    border-radius: 8px;
    color: #1a2340;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.2s, background 0.2s;
  }

  .form-input:focus {
    border-color: #3b82f6;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
  }

  .form-input::placeholder { color: #b0bbd4; }

  .field-error {
    font-size: 0.72rem;
    color: #dc2626;
    margin-top: 0.3rem;
  }

  .form-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 1.5rem;
  }

  .back-link {
    font-size: 0.78rem;
    color: #2563eb;
    text-decoration: none;
    opacity: 0.8;
  }

  .back-link:hover { opacity: 1; text-decoration: underline; }

  .btn-primary {
    padding: 10px 28px;
    background: linear-gradient(135deg, #1d4ed8, #3b82f6);
    border: none;
    border-radius: 8px;
    color: #ffffff;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.88rem;
    font-weight: 500;
    letter-spacing: 0.06em;
    cursor: pointer;
    transition: opacity 0.2s, transform 0.15s;
  }

  .btn-primary:hover { opacity: 0.92; transform: translateY(-1px); }
  .btn-primary:active { transform: translateY(0); }

  .form-footer {
    margin-top: 2rem;
    text-align: center;
    font-size: 0.7rem;
    color: #9aa5be;
    letter-spacing: 0.04em;
  }

  @media (max-width: 768px) {
    body { flex-direction: column; }
    .panel-left { width: 100%; padding: 2.5rem 2rem 1.5rem; min-height: auto; }
    .panel-right { width: 100%; padding: 2rem 1.5rem 3rem; border-left: none; border-top: 1px solid #e8ecf4; }
    .logo-img { width: 120px; height: 120px; }
    .brand-name { font-size: 2rem; }
  }
</style>
</head>
<body>

<div class="panel-left">
  <div class="logo-glow"></div>
  <div class="brand-block">
    <img class="logo-img" src="{{ asset('images/bamc-3d.png') }}" alt="Blue Arrow Books">
    <h1 class="brand-name">Blue Arrow <span>Books</span></h1>
    <p class="brand-sub">Blue Arrow Management Consultants</p>

    <div class="icons-row">
      <div class="icon-badge">
        <svg viewBox="0 0 24 24"><rect x="4" y="2" width="14" height="20" rx="2"/><line x1="8" y1="7" x2="14" y2="7"/><line x1="8" y1="11" x2="16" y2="11"/><line x1="8" y1="15" x2="13" y2="15"/><line x1="4" y1="7" x2="2" y2="7"/><line x1="4" y1="11" x2="2" y2="11"/><line x1="4" y1="15" x2="2" y2="15"/></svg>
        <span>Journal</span>
      </div>
      <div class="divider-line"></div>
      <div class="icon-badge">
        <svg viewBox="0 0 24 24"><line x1="12" y1="3" x2="12" y2="21"/><path d="M5 8l-2 5h4L5 8z"/><path d="M19 8l-2 5h4L19 8z"/><line x1="5" y1="8" x2="19" y2="8"/><line x1="8" y1="21" x2="16" y2="21"/></svg>
        <span>Balance</span>
      </div>
      <div class="divider-line"></div>
      <div class="icon-badge">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg>
        <span>Invoicing</span>
      </div>
      <div class="divider-line"></div>
      <div class="icon-badge">
        <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        <span>Reports</span>
      </div>
    </div>

    <div class="feature-pills">
      <span class="pill">Multi-Company</span>
      <span class="pill">VAT Ready</span>
      <span class="pill">Payroll</span>
      <span class="pill">Fixed Assets</span>
      <span class="pill">Inventory</span>
      <span class="pill">UAE Compliant</span>
    </div>
  </div>
  <p class="panel-footer">&copy; {{ date('Y') }} Blue Arrow Management Consultants &nbsp;·&nbsp; All rights reserved</p>
</div>

<div class="panel-right">
  <div class="form-wrapper">

    <h2 class="form-title">Set new password</h2>
    <p class="form-subtitle">Choose a strong password of at least 8 characters.</p>

    @if (session('status'))
      <div class="alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
      <div class="alert-error">
        @foreach ($errors->all() as $error)
          <div>{{ $error }}</div>
        @endforeach
      </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input class="form-input" type="email" id="email" name="email"
               value="{{ old('email', request()->email) }}" required autocomplete="email"
               placeholder="you@example.com">
        @error('email')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-group">
        <label class="form-label" for="password">New Password</label>
        <input class="form-input" type="password" id="password" name="password"
               required autocomplete="new-password" minlength="8"
               placeholder="Min. 8 characters">
        @error('password')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-group">
        <label class="form-label" for="password_confirmation">Confirm New Password</label>
        <input class="form-input" type="password" id="password_confirmation"
               name="password_confirmation" required autocomplete="new-password"
               placeholder="••••••••">
        @error('password_confirmation')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-actions">
        <a class="back-link" href="{{ route('login') }}">← Back to login</a>
        <button type="submit" class="btn-primary">Set Password</button>
      </div>
    </form>

    <div class="form-footer">
      Blue Arrow Books v1.0 &nbsp;·&nbsp; Powered by BAMC
    </div>
  </div>
</div>

</body>
</html>