<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blue Arrow Books - Verify Email</title>
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

  /* Email icon circle */
  .email-icon-wrap {
    width: 64px;
    height: 64px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.25rem;
  }

  .email-icon-wrap svg {
    width: 28px;
    height: 28px;
    stroke: #2563eb;
    fill: none;
    stroke-width: 1.5;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .form-title {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 400;
    font-size: 1.9rem;
    color: #111827;
    margin-bottom: 0.3rem;
  }

  .form-subtitle {
    font-size: 0.82rem;
    color: #6b7a99;
    margin-bottom: 2rem;
    letter-spacing: 0.01em;
    line-height: 1.7;
  }

  .alert-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #15803d;
    font-size: 0.78rem;
    padding: 10px 14px;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    line-height: 1.5;
  }

  .btn-primary {
    width: 100%;
    padding: 11px;
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
    margin-bottom: 1rem;
  }

  .btn-primary:hover { opacity: 0.92; transform: translateY(-1px); }
  .btn-primary:active { transform: translateY(0); }

  .logout-row {
    text-align: center;
  }

  .logout-btn {
    background: none;
    border: none;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.78rem;
    color: #6b7a99;
    cursor: pointer;
    text-decoration: underline;
    text-underline-offset: 3px;
    transition: color 0.2s;
  }

  .logout-btn:hover { color: #1a2340; }

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

    <div class="email-icon-wrap">
      <svg viewBox="0 0 24 24">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
        <polyline points="22,6 12,13 2,6"/>
      </svg>
    </div>

    <h2 class="form-title">Check your inbox</h2>
    <p class="form-subtitle">
      Thanks for signing up. We've sent a verification link to your email address. Click the link to activate your account before continuing.
    </p>

    @if (session('status') == 'verification-link-sent')
      <div class="alert-success">
        A new verification link has been sent to your registered email address.
      </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
      @csrf
      <button type="submit" class="btn-primary">Resend Verification Email</button>
    </form>

    <div class="logout-row">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="logout-btn">Sign out of this account</button>
      </form>
    </div>

    <div class="form-footer">
      Blue Arrow Books v1.0 &nbsp;·&nbsp; Powered by BAMC
    </div>
  </div>
</div>

</body>
</html>