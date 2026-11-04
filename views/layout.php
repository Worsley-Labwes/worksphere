<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WorkSphere</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 1rem;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        nav form,
        .inline {
            max-width: none;
            display: inline;
            margin: 0;
        }

        form {
            max-width: 420px;
        }

        label {
            display: block;
            margin-top: 1rem;
            font-weight: 600;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: .5rem;
            margin-top: .25rem;
            box-sizing: border-box;
            font: inherit;
        }

        button {
            margin-top: 1.25rem;
            padding: .6rem 1rem;
            cursor: pointer;
        }

        button.small {
            margin: .25rem .25rem 0 0;
            padding: .2rem .5rem;
            font-size: .8rem;
        }

        .error {
            color: #b00020;
            font-size: .9rem;
            margin: .25rem 0 0;
        }

        .badge {
            font-size: .75rem;
            background: #eee;
            padding: .15rem .5rem;
            border-radius: 999px;
            font-weight: 600;
            vertical-align: middle;
        }

        .badge.high {
            background: #fde0e0;
        }

        .badge.medium {
            background: #fff1cc;
        }

        .badge.low {
            background: #e0f0e0;
        }

        .overdue {
            color: #b00020;
            font-weight: 600;
        }

        .muted {
            color: #666;
            font-size: .9rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: .5rem;
            border-bottom: 1px solid #ddd;
        }

        progress {
            width: 100%;
            height: .8rem;
        }

        .board {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            align-items: start;
            margin-top: 1rem;
        }

        .column {
            background: #f6f6f6;
            padding: .75rem;
            border-radius: 8px;
            min-height: 6rem;
        }

        .column h3 {
            margin: 0 0 .5rem;
        }

        .card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: .6rem;
            margin-bottom: .6rem;
        }

        .card strong {
            display: block;
            margin-bottom: .25rem;
        }

        @media (max-width: 700px) {
            .board {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <nav>
        <strong><a href="/workspaces" style="text-decoration:none;color:inherit">WorkSphere</a></strong>
        <?php if (auth_user()): ?>
            <form method="post" action="/logout">
                <?= csrf_field() ?>
                <button type="submit" style="margin:0">Log out</button>
            </form>
        <?php endif; ?>
    </nav>
    <?= $content ?>
</body>

</html>