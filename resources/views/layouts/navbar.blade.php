<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'CK AI Tools')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        :root {
            --pink: #e91e63;
            --white: #ffffff;
            --dark: #191919;
            --light-grey: #f5f5f5;
        }

        body {
            font-family: system-ui, sans-serif;
            background-color: var(--white);
            color: var(--dark);
            margin: 0;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 240px;
            background-color: #191919;
            border-right: 1px solid #2a2a2a;
            padding: 40px 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.03);
        }

        .sidebar h2 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--pink);
            margin-bottom: 40px;
            text-align: center;
        }

        .sidebar a {
            display: block;
            color: #ffffff;
            text-decoration: none;
            margin: 14px 0;
            font-size: 1rem;
            padding: 10px 16px;
            border-radius: 6px;
            transition: background 0.2s, color 0.2s;
        }

        .sidebar a:hover {
            background-color: #333333;
            color: var(--pink);
        }

        .content {
            margin-left: 240px;
            padding: 40px;
        }

        .footer {
            text-align: center;
            margin-top: 60px;
            padding-top: 30px;
            font-size: 0.9rem;
            color: #888;
            border-top: 1px solid #eee;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                border-right: none;
                border-bottom: 1px solid #e5e5e5;
                text-align: center;
            }

            .content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>CK AI Tools</h2>
        <a href="#tool-leveler">Text Leveler</a>
        <a href="#tool-summary">Summarizer</a>
        <a href="#tool-understanding">Understanding</a>
        <a href="#tool-rewriter">Rewriter</a>
        <a href="#tool-proofreader">Proofreader</a>
    </div>

    <div class="content">
        @yield('content')
        <div class="footer">
            &copy; <span id="year"></span> CK Children’s Publishing. All rights reserved.
        </div>
    </div>

    <script>
        document.getElementById("year").textContent = new Date().getFullYear();
    </script>

</body>

</html>
