@extends('layouts/blankLayout')

@section('title', 'Login to DMS - Pages')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
<style>
  html, body {
    height: 100%;
    margin: 0;
    padding: 0;
    font-family: 'Public Sans', 'Segoe UI', sans-serif;
    background: #f5f5f9;
  }

  .auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    background: #f5f5f9;
  }

  .auth-card {
    display: flex;
    width: 90%;
    max-width: 950px;
    min-height: 560px;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 9px 45px rgba(58, 53, 65, .12);
    background: #fff;
  }

  /* ══ LEFT – Illustration only ══ */
  .auth-left {
    flex: 0 0 45%;
    background: #f0f2f8;
    display: flex;
    align-items: stretch;
    justify-content: center;
    padding: 0;
    position: relative;
    overflow: hidden;
  }
  .auth-left::before {
    content: '';
    position: absolute;
    width: 300px; height: 300px;
    border-radius: 50%;
    opacity: .07;
    bottom: -100px; left: -80px;
  }
  .auth-left::after {
    content: '';
    position: absolute;
    width: 180px; height: 180px;
    border-radius: 50%;
    opacity: .09;
    top: -50px; right: -40px;
  }
  .auth-illustration {
    position: absolute;
    z-index: 1;
    width: 98%;
    height: 98%;
    object-fit: contain;
    display: block;
  }

  /* ══ RIGHT – Logo + Form ══ */
  .auth-right {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 2.5rem;
    background: #fff;
  }

  .auth-form-inner {
    width: 100%;
    max-width: 300px;
    display: flex;
    flex-direction: column;
    align-items: center; /* center everything horizontally */
  }

  /* Logo – centered */
  .auth-brand {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    text-decoration: none;
    margin-bottom: 1rem;
  }
  .auth-brand-text {
    font-size: 3.25rem;
    font-weight: 700;
    color: #566a7f;
    letter-spacing: .3px;
  }

  /* Heading – centered */
  .auth-heading {
    font-size: 1.2rem;
    font-weight: 600;
    color: #566a7f;
    margin: 0 0 .25rem;
    text-align: center;
  }
  .auth-sub {
    color: #a1acb8;
    font-size: .875rem;
    margin-bottom: 1.75rem;
    text-align: center;
  }

  /* Form takes full width */
  .auth-form-inner form,
  .auth-divider,
  .auth-btn-google {
    width: 100%;
  }

  .auth-label {
    display: block;
    font-size: .82rem;
    font-weight: 500;
    color: #566a7f;
    margin-bottom: .35rem;
  }
  .auth-input-wrap { position: relative; }
  .auth-input {
    width: 100%;
    height: 42px;
    padding: 0 2.5rem 0 .875rem;
    border: 1px solid #d9dee3;
    border-radius: 6px;
    font-size: .875rem;
    color: #566a7f;
    background: #fff;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    box-sizing: border-box;
  }
  .auth-input::placeholder { color: #c4cdd4; }
  .auth-input:focus {
    border-color: #696cff;
    box-shadow: 0 0 0 3px rgba(105,108,255,.12);
  }
  .auth-input.is-invalid { border-color: #ff3e1d; }

  .auth-eye-btn {
    position: absolute;
    right: .75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    color: #b4bdc6;
    display: flex;
    align-items: center;
  }
  .auth-eye-btn:hover { color: #696cff; }

  .auth-btn-primary {
    display: block;
    width: 100%;
    height: 42px;
    background: #696cff;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: .9rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .2s, transform .1s;
    letter-spacing: .3px;
  }
  .auth-btn-primary:hover  { background: #5f61e6; }
  .auth-btn-primary:active { transform: scale(.98); }

  .auth-divider {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin: 1.4rem 0 1.2rem;
    color: #a1acb8;
    font-size: .78rem;
  }
  .auth-divider::before,
  .auth-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #ebebeb;
  }

  .auth-btn-google {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .6rem;
    height: 42px;
    border: 1px solid #d9dee3;
    border-radius: 6px;
    background: #fff;
    color: #566a7f;
    font-size: .875rem;
    font-weight: 500;
    text-decoration: none;
    transition: border-color .2s, box-shadow .2s;
    box-sizing: border-box;
  }
  .auth-btn-google:hover {
    border-color: #696cff;
    box-shadow: 0 2px 8px rgba(105,108,255,.15);
    color: #566a7f;
  }

  .auth-btn-google.is-loading {
    opacity: .75;
    cursor: wait;
  }

  .auth-btn-google .btn-loader {
    display: none;
    width: 14px;
    height: 14px;
    border: 2px solid #d0d7de;
    border-top-color: #696cff;
    border-radius: 50%;
    animation: auth-spin .7s linear infinite;
    flex-shrink: 0;
  }

  .auth-btn-google.is-loading .btn-loader {
    display: inline-block;
  }

  @keyframes auth-spin {
    to { transform: rotate(360deg); }
  }

  .invalid-feedback {
    font-size: .78rem;
    color: #ff3e1d;
    margin-top: .3rem;
    display: block;
  }

  @media (max-width: 720px) {
    .auth-card { flex-direction: column; max-width: 440px; }
    .auth-left { flex: none; height: 200px; }
    .auth-illustration { width: 100%; height: 100%; object-fit: contain; }
    .auth-right { padding: 2rem 1.5rem; }
  }
</style>
@endsection

@section('content')
<div class="auth-page">
  <div class="auth-card">

    {{-- ══ LEFT: Illustration only ══ --}}
<div class="auth-left">
  <img
    class="auth-illustration"
    src="{{ asset('assets/img/illustrations/help_us_king_fisher.png') }}"
    alt="DMS illustration"
  />
</div>

    {{-- ══ RIGHT: Logo + Form ══ --}}
    <div class="auth-right">
      <div class="auth-form-inner">

        {{-- Logo – centered --}}
        <a href="{{ route('auth-login-basic') }}" class="auth-brand">
          <div>@include('_partials.macros')</div>
          <span class="auth-brand-text">{{ config('variables.templateName') }}</span>
        </a>

        {{-- Heading – centered --}}
        <h4 class="auth-heading">Welcome to SLSU Document Management System</h4>
        <p class="auth-sub">Please sign-in using your institutional account</p>

        {{-- Form --}}
        <form action="{{ route('login.post') }}" method="POST">
          @csrf

        {{-- Google login --}}
        <button type="button" id="googlePopupBtn" class="auth-btn-google" aria-live="polite">
          <svg viewBox="0 0 24 24" width="18" height="18" style="flex-shrink:0">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
          <span id="googleBtnText">Sign in with Google</span>
          <span class="btn-loader" id="googleBtnLoader" aria-hidden="true"></span>
        </button>
        <small id="googleAuthError" class="invalid-feedback" style="display:none;text-align:center;"></small>

      </div>{{-- /.auth-form-inner --}}
    </div>{{-- /.auth-right --}}

  </div>{{-- /.auth-card --}}
</div>{{-- /.auth-page --}}

<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
  function togglePassword() {
    const input = document.getElementById('password');
    const slash = document.getElementById('eye-slash');
    if (input.type === 'password') {
      input.type = 'text';
      slash.style.display = 'none';
    } else {
      input.type = 'password';
      slash.style.display = '';
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('googlePopupBtn');
    const buttonText = document.getElementById('googleBtnText');
    const errorBox = document.getElementById('googleAuthError');
    const clientId = @json(config('services.google.client_id'));
    const popupEndpoint = @json(route('auth.google.popup'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const defaultGoogleText = 'Sign in with Google';
    const loadingGoogleText = 'Logging in...';

    if (!button) {
      return;
    }

    const showError = (message) => {
      if (!errorBox) {
        return;
      }
      errorBox.textContent = message;
      errorBox.style.display = 'block';
    };

    if (!clientId) {
      showError('Google sign-in is not configured.');
      button.disabled = true;
      return;
    }

    let tokenClient;
    let popupInProgress = false;
    let popupSafetyTimer;

    const setGoogleButtonLoading = (isLoading) => {
      button.disabled = isLoading;
      button.classList.toggle('is-loading', isLoading);
      if (buttonText) {
        buttonText.textContent = isLoading ? loadingGoogleText : defaultGoogleText;
      }
    };

    const resetGoogleButton = () => {
      popupInProgress = false;
      setGoogleButtonLoading(false);
      if (popupSafetyTimer) {
        clearTimeout(popupSafetyTimer);
        popupSafetyTimer = null;
      }
    };

    const initClient = () => {
      if (!window.google || !google.accounts || !google.accounts.oauth2) {
        return false;
      }

      tokenClient = google.accounts.oauth2.initTokenClient({
        client_id: clientId,
        scope: 'openid email profile',
        error_callback: (error) => {
          const reason = error?.type === 'popup_closed'
            ? 'Google sign-in was cancelled.'
            : 'Google sign-in failed. Please try again.';
          showError(reason);
          resetGoogleButton();
        },
        callback: async (tokenResponse) => {
          if (tokenResponse.error || !tokenResponse.access_token) {
            showError('Google sign-in was cancelled or failed.');
            resetGoogleButton();
            return;
          }

          try {
            const response = await fetch(popupEndpoint, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
              },
              body: JSON.stringify({
                access_token: tokenResponse.access_token,
              }),
            });

            const data = await response.json();

            if (!response.ok) {
              throw new Error(data.message || 'Google sign-in failed.');
            }

            window.location.href = data.redirect || '/dashboard';
          } catch (error) {
            showError(error.message || 'Google sign-in failed. Please try again.');
            resetGoogleButton();
          }
        },
      });

      return true;
    };

    button.addEventListener('click', function () {
      if (popupInProgress) {
        return;
      }

      if (errorBox) {
        errorBox.style.display = 'none';
      }

      popupInProgress = true;
      setGoogleButtonLoading(true);

      const ready = tokenClient || initClient();
      if (!ready) {
        showError('Google SDK is still loading. Please try again.');
        resetGoogleButton();
        return;
      }

      popupSafetyTimer = setTimeout(() => {
        if (popupInProgress) {
          showError('Google sign-in timed out or was closed. Please try again.');
          resetGoogleButton();
        }
      }, 15000);

      tokenClient.requestAccessToken({ prompt: 'select_account' });
    });
  });
</script>
@endsection