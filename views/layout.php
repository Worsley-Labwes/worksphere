<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WorkSphere</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            max-width: 420px;
            margin: 3rem auto;
            padding: 0 1rem;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        label {
            display: block;
            margin-top: 1rem;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: .5rem;
            margin-top: .25rem;
            box-sizing: border-box;
        }

        button {
            margin-top: 1.25rem;
            padding: .6rem 1rem;
            cursor: pointer;
        }

        .error {
            color: #b00020;
            font-size: .9rem;
            margin: .25rem 0 0;
        }
    </style>
</head>

<body>
    <nav>
        <strong>WorkSphere</strong>
        <?php if (auth_user()): ?>
            <form method="post" action="/logout" style="margin:0">
                <?= csrf_field() ?>
                <button type="submit" style="margin:0">Log out</button>
            </form>
        <?php endif; ?>
    </nav>
    <?= $content ?>
</body>

</html>