<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Connexion — École Supérieure EMSI</title>

    <!-- Tailwind -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            margin: 0;
            overflow-x: hidden;
        }

        .login-page {
            min-height: 100vh;
            display: flex;
            background: #f8fafc;
        }

        /* =========================
           PARTIE GAUCHE
        ========================== */

        .login-left {
            position: relative;
            width: 46%;
            min-height: 100vh;
            overflow: hidden;

            background:
                linear-gradient(
                    135deg,
                    rgba(20, 45, 120, 0.94),
                    rgba(43, 40, 155, 0.82),
                    rgba(76, 29, 149, 0.80)
                ),
                url('{{ asset('images/emsi-campus.jpg') }}');

            background-size: cover;
            background-position: center;
        }

        .login-left::before {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            top: -180px;
            right: -180px;
        }

        .login-left::after {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            bottom: -130px;
            left: -130px;
        }

        .left-content {
            position: relative;
            z-index: 2;

            min-height: 100vh;
            padding: 55px 60px;

            display: flex;
            flex-direction: column;
            justify-content: space-between;

            color: white;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .brand-icon {
            width: 54px;
            height: 54px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);

            border-radius: 16px;

            backdrop-filter: blur(10px);
        }

        .brand-icon svg {
            width: 29px;
            height: 29px;
        }

        .brand-title {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .brand-subtitle {
            font-size: 12px;
            opacity: 0.75;
            margin-top: 3px;
        }

        .hero-text {
            max-width: 490px;
        }

        .hero-text .badge {
            display: inline-flex;
            align-items: center;

            padding: 8px 13px;

            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);

            border-radius: 999px;

            font-size: 12px;
            font-weight: 500;

            backdrop-filter: blur(10px);

            margin-bottom: 22px;
        }

        .hero-text h1 {
            font-size: clamp(35px, 4vw, 58px);
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -2px;
            margin: 0 0 20px;
        }

        .hero-text h1 span {
            color: #a5b4fc;
        }

        .hero-text p {
            max-width: 430px;

            font-size: 15px;
            line-height: 1.8;

            color: rgba(255,255,255,0.78);
        }

        .left-features {
            display: flex;
            flex-direction: column;
            gap: 13px;

            margin-top: 30px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 12px;

            font-size: 13px;
            color: rgba(255,255,255,0.85);
        }

        .feature-icon {
            width: 31px;
            height: 31px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 9px;

            background: rgba(255,255,255,0.10);
        }

        .feature-icon svg {
            width: 16px;
            height: 16px;
        }

        .left-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;

            font-size: 11px;

            color: rgba(255,255,255,0.55);
        }

        /* =========================
           PARTIE DROITE
        ========================== */

        .login-right {
            width: 54%;
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 40px;
            background: #ffffff;
        }

        .login-container {
            width: 100%;
            max-width: 470px;

            animation: loginAppear 0.6s ease forwards;
        }

        @keyframes loginAppear {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mobile-logo {
            display: none;
        }

        .welcome {
            margin-bottom: 34px;
        }

        .welcome h2 {
            margin: 0;

            font-size: 30px;
            font-weight: 800;

            letter-spacing: -1px;

            color: #111827;
        }

        .welcome p {
            margin-top: 8px;

            color: #6b7280;

            font-size: 14px;
        }

        .session-status {
            margin-bottom: 18px;
        }

        /* FORM */

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;

            margin-bottom: 8px;

            font-size: 13px;
            font-weight: 600;

            color: #374151;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;

            left: 15px;
            top: 50%;

            transform: translateY(-50%);

            color: #9ca3af;

            pointer-events: none;
        }

        .input-icon svg {
            width: 18px;
            height: 18px;
        }

        .form-input {
            width: 100%;
            height: 51px;

            padding: 0 16px 0 46px;

            border: 1px solid #e5e7eb;

            border-radius: 12px;

            background: #ffffff;

            color: #111827;

            font-size: 14px;

            outline: none;

            transition: all 0.2s ease;

            box-sizing: border-box;
        }

        .form-input::placeholder {
            color: #b5bac4;
        }

        .form-input:focus {
            border-color: #4f46e5;

            box-shadow:
                0 0 0 4px rgba(79, 70, 229, 0.08);
        }

        .form-input:focus + .input-focus {
            color: #4f46e5;
        }

        .password-toggle {
            position: absolute;

            right: 15px;
            top: 50%;

            transform: translateY(-50%);

            border: 0;
            background: transparent;

            color: #9ca3af;

            cursor: pointer;
        }

        .password-toggle:hover {
            color: #4f46e5;
        }

        /* OPTIONS */

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin: 5px 0 24px;
        }

        .remember {
            display: flex;
            align-items: center;

            gap: 8px;

            cursor: pointer;

            font-size: 13px;
            color: #6b7280;
        }

        .remember input {
            width: 16px;
            height: 16px;

            accent-color: #4f46e5;

            cursor: pointer;
        }

        .forgot {
            color: #4f46e5;

            font-size: 13px;
            font-weight: 600;

            text-decoration: none;
        }

        .forgot:hover {
            color: #3730a3;
        }

        /* BUTTON */

        .login-button {
            width: 100%;
            height: 52px;

            display: flex;
            align-items: center;
            justify-content: center;

            gap: 10px;

            border: none;
            border-radius: 12px;

            background: linear-gradient(
                135deg,
                #4f46e5,
                #6366f1
            );

            color: white;

            font-size: 14px;
            font-weight: 700;

            cursor: pointer;

            box-shadow:
                0 8px 20px rgba(79, 70, 229, 0.22);

            transition: all 0.25s ease;
        }

        .login-button:hover {
            transform: translateY(-2px);

            box-shadow:
                0 12px 25px rgba(79, 70, 229, 0.30);
        }

        .login-button:active {
            transform: translateY(0);
        }

        /* ROLES */

        .roles-section {
            margin-top: 34px;
        }

        .roles-title {
            display: flex;
            align-items: center;
            gap: 12px;

            margin-bottom: 16px;

            color: #9ca3af;

            font-size: 11px;
            font-weight: 600;

            text-transform: uppercase;
            letter-spacing: 0.7px;
        }

        .roles-title::before,
        .roles-title::after {
            content: "";

            flex: 1;

            height: 1px;

            background: #e5e7eb;
        }

        .roles {
            display: grid;
            grid-template-columns: repeat(4, 1fr);

            gap: 9px;
        }

        .role {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            min-height: 75px;

            border: 1px solid #edf0f4;

            border-radius: 12px;

            background: #fafbfc;

            transition: all 0.2s ease;
        }

        .role:hover {
            transform: translateY(-2px);

            border-color: #c7d2fe;

            background: #f5f7ff;
        }

        .role-icon {
            width: 30px;
            height: 30px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 9px;

            margin-bottom: 7px;
        }

        .role-icon svg {
            width: 15px;
            height: 15px;
        }

        .role-name {
            font-size: 10px;
            font-weight: 600;
            color: #4b5563;
        }

        .admin {
            background: #eef2ff;
            color: #4f46e5;
        }

        .prof {
            background: #ecfdf5;
            color: #059669;
        }

        .stagiaire {
            background: #fff7ed;
            color: #ea580c;
        }

        .comptable {
            background: #f5f3ff;
            color: #7c3aed;
        }

        /* SECURITY */

        .security {
            display: flex;
            align-items: flex-start;

            gap: 10px;

            margin-top: 27px;
            padding: 13px;

            border-radius: 11px;

            background: #f8fafc;

            border: 1px solid #f1f5f9;
        }

        .security svg {
            width: 17px;
            height: 17px;

            color: #4f46e5;

            flex-shrink: 0;

            margin-top: 1px;
        }

        .security-text strong {
            display: block;

            margin-bottom: 3px;

            color: #374151;

            font-size: 11px;
        }

        .security-text span {
            color: #9ca3af;

            font-size: 10px;
            line-height: 1.5;
        }

        .copyright {
            margin-top: 25px;

            text-align: center;

            color: #9ca3af;

            font-size: 10px;
        }

        /* ERRORS */

        .error-message {
            margin-top: 6px;

            color: #dc2626;

            font-size: 12px;
        }

        /* =========================
           RESPONSIVE
        ========================== */

        @media (max-width: 900px) {

            .login-left {
                width: 40%;
            }

            .login-right {
                width: 60%;
                padding: 30px;
            }

            .left-content {
                padding: 40px 35px;
            }

            .hero-text h1 {
                font-size: 38px;
            }
        }

        @media (max-width: 700px) {

            .login-page {
                display: block;
            }

            .login-left {
                display: none;
            }

            .login-right {
                width: 100%;
                min-height: 100vh;

                padding: 30px 20px;
            }

            .login-container {
                max-width: 430px;
            }

            .mobile-logo {
                display: flex;

                align-items: center;
                justify-content: center;

                gap: 10px;

                margin-bottom: 35px;
            }

            .mobile-logo-icon {
                width: 43px;
                height: 43px;

                display: flex;
                align-items: center;
                justify-content: center;

                border-radius: 12px;

                color: white;

                background: linear-gradient(
                    135deg,
                    #4f46e5,
                    #7c3aed
                );
            }

            .mobile-logo-icon svg {
                width: 23px;
                height: 23px;
            }

            .mobile-logo span {
                font-size: 17px;
                font-weight: 800;
                color: #111827;
            }

            .welcome {
                text-align: center;
            }

            .welcome h2 {
                font-size: 27px;
            }

            .roles {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 380px) {

            .login-right {
                padding: 25px 16px;
            }

            .welcome h2 {
                font-size: 24px;
            }

            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>

<body>

<div class="login-page">

    <!-- =====================================================
         GAUCHE
    ====================================================== -->

    <section class="login-left">

        <div class="left-content">

            <!-- LOGO -->

            <div class="brand">

                <div class="brand-icon">

                    <svg
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.7"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                        />
                    </svg>

                </div>

                <div>
                    <div class="brand-title">
                        École Supérieure EMSI
                    </div>

                    <div class="brand-subtitle">
                        Excellence • Innovation • Réussite
                    </div>
                </div>

            </div>


            <!-- HERO -->

            <div class="hero-text">

                <div class="badge">
                    ✦ Plateforme académique
                </div>

                <h1>
                    Votre réussite,
                    <span>notre priorité.</span>
                </h1>

                <p>
                    Accédez à votre espace personnel et retrouvez
                    toutes vos informations académiques, pédagogiques
                    et administratives au même endroit.
                </p>


                <div class="left-features">

                    <div class="feature">

                        <div class="feature-icon">

                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                />
                            </svg>

                        </div>

                        <span>
                            Accès sécurisé à votre espace
                        </span>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">

                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"
                                />
                            </svg>

                        </div>

                        <span>
                            Une plateforme rapide et intuitive
                        </span>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">

                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622C17.176 19.29 21 14.591 21 9c0-.695-.059-1.376-.172-2.016z"
                                />
                            </svg>

                        </div>

                        <span>
                            Vos données protégées
                        </span>

                    </div>

                </div>

            </div>


            <!-- FOOTER -->

            <div class="left-footer">

                <span>
                    © {{ date('Y') }} EMSI
                </span>

                <span>
                    Savoir aujourd'hui, réussir demain.
                </span>

            </div>

        </div>

    </section>


    <!-- =====================================================
         DROITE
    ====================================================== -->

    <section class="login-right">

        <div class="login-container">


            <!-- MOBILE LOGO -->

            <div class="mobile-logo">

                <div class="mobile-logo-icon">

                    <svg
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.7"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                        />
                    </svg>

                </div>

                <span>
                    École Supérieure EMSI
                </span>

            </div>


            <!-- TITRE -->

            <div class="welcome">

                <h2>
                    Bienvenue 👋
                </h2>

                <p>
                    Connectez-vous pour accéder à votre espace
                </p>

            </div>


            <!-- SESSION -->

            <div class="session-status">
                <x-auth-session-status
                    :status="session('status')"
                />
            </div>


            <!-- FORMULAIRE -->

            <form
                method="POST"
                action="{{ route('login') }}"
            >

                @csrf


                <!-- EMAIL -->

                <div class="form-group">

                    <label
                        for="email"
                        class="form-label"
                    >
                        Adresse e-mail
                    </label>

                    <div class="input-wrapper">

                        <div class="input-icon">

                            <svg
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"
                                />
                            </svg>

                        </div>

                        <input
                            id="email"
                            class="form-input"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="exemple@emsi.ma"
                        >

                    </div>

                    @error('email')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <!-- MOT DE PASSE -->

                <div class="form-group">

                    <label
                        for="password"
                        class="form-label"
                    >
                        Mot de passe
                    </label>

                    <div class="input-wrapper">

                        <div class="input-icon">

                            <svg
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                />
                            </svg>

                        </div>


                        <input
                            id="password"
                            class="form-input"
                            style="padding-right: 50px;"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        >


                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword()"
                            aria-label="Afficher le mot de passe"
                        >

                            <svg
                                id="eyeIcon"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                />
                            </svg>

                        </button>

                    </div>

                    @error('password')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <!-- OPTIONS -->

                <div class="form-options">

                    <label
                        for="remember_me"
                        class="remember"
                    >

                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                        >

                        <span>
                            Se souvenir de moi
                        </span>

                    </label>


                    @if (Route::has('password.request'))

                        <a
                            href="{{ route('password.request') }}"
                            class="forgot"
                        >
                            Mot de passe oublié ?
                        </a>

                    @endif

                </div>


                <!-- BOUTON -->

                <button
                    type="submit"
                    class="login-button"
                >

                    <span>
                        Se connecter
                    </span>

                    <svg
                        width="17"
                        height="17"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5m5-5H6"
                        />
                    </svg>

                </button>

            </form>


            <!-- ROLES -->

            <div class="roles-section">

                <div class="roles-title">
                    Accès selon votre rôle
                </div>


                <div class="roles">

                    <!-- ADMIN -->

                    <div class="role">

                        <div class="role-icon admin">

                            <svg
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>

                        </div>

                        <span class="role-name">
                            Administrateur
                        </span>

                    </div>


                    <!-- PROFESSEUR -->

                    <div class="role">

                        <div class="role-icon prof">

                            <svg
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18 5.754 18 7.5 18s3.332.477 4.5 1.253"
                                />
                            </svg>

                        </div>

                        <span class="role-name">
                            Professeur
                        </span>

                    </div>


                    <!-- STAGIAIRE -->

                    <div class="role">

                        <div class="role-icon stagiaire">

                            <svg
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 14l9-5-9-5-9 5 9 5z"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 14l6.16-3.422A12.083 12.083 0 0118 20H6a12.083 12.083 0 01-.16-9.422L12 14z"
                                />

                            </svg>

                        </div>

                        <span class="role-name">
                            Stagiaire
                        </span>

                    </div>


                    <!-- COMPTABLE -->

                    <div class="role">

                        <div class="role-icon comptable">

                            <svg
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>

                        </div>

                        <span class="role-name">
                            Comptable
                        </span>

                    </div>

                </div>

            </div>


            <!-- SECURITE -->

            <div class="security">

                <svg
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                    />
                </svg>

                <div class="security-text">

                    <strong>
                        Connexion sécurisée
                    </strong>

                    <span>
                        Vos informations sont protégées.
                        Ne partagez jamais vos identifiants.
                    </span>

                </div>

            </div>


            <!-- COPYRIGHT -->

            <div class="copyright">

                © {{ date('Y') }} École Supérieure EMSI.
                Tous droits réservés.

            </div>

        </div>

    </section>

</div>


<script>

function togglePassword() {

    const password = document.getElementById('password');

    if (password.type === 'password') {

        password.type = 'text';

    } else {

        password.type = 'password';

    }

}

</script>

</body>
</html>