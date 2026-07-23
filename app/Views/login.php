<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardian Control - Login</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <style>
        body {
            margin: 0;
            min-height: 100vh;

            background-color: #050b16;
            background-image: url('<?= base_url('Assets/images/GC.jpeg') ?>');
            background-position: center center;
            background-repeat: no-repeat;
            background-size: contain;

        }

        .login-card {
            background: transparent;
            backdrop-filter: none;
            box-shadow: none;
        }

        .login-title {
            font-weight: 700;
            color: #0d1b2a;
        }

        .btn-login {
            background: #0d6efd;
            border: none;
            font-weight: 600;
        }

        .btn-login:hover {
            background: #0b5ed7;
        }

        @media (max-width: 768px) {
            body {
                background-size: 90%;
                background-position: center top;
            }

            .login-card {
                margin-top: 220px;
            }
        }
    </style>
</head>

<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-11 col-sm-8 col-md-5 col-lg-4">

            <div class="card login-card">
                <div class="card-body p-4">

                    <h3 class="card-title mb-4 text-center login-title">
                        Login
                    </h3>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= base_url('login') ?>">

                        <div class="mb-3">
                            <label for="email" class="form-label text-white">Email</label>

                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                required
                                value="<?= isset($email) ? esc($email) : '' ?>">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label text-white">Password</label>

                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                required>
                        </div>

                        <button type="submit" class="btn btn-login text-white w-100">
                            Login
                        </button>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>