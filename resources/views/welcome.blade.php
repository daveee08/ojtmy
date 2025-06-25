<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CK AI Tools</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        :root {
            --pink: #e91e63;
            --white: #ffffff;
            --dark: #333333;
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
            background-color: var(--white);
            border-right: 1px solid #e5e5e5;
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
            color: var(--dark);
            text-decoration: none;
            margin: 14px 0;
            font-size: 1rem;
            padding: 10px 16px;
            border-radius: 6px;
            transition: background 0.2s, color 0.2s;
        }

        .sidebar a:hover {
            background-color: var(--light-grey);
            color: var(--pink);
        }

        .content {
            margin-left: 240px;
            padding: 40px;
        }

        .hero {
            background-color: #fef5f8;
            border: 1px solid #f2f2f2;
            padding: 50px;
            border-radius: 12px;
            margin-bottom: 40px;
            text-align: center;
        }

        .hero h1 {
            font-size: 2rem;
            color: var(--pink);
            font-weight: 700;
        }

        .hero p {
            font-size: 1rem;
            color: #555;
            max-width: 600px;
            margin: 15px auto 0;
        }

        .tool-card {
            background-color: var(--white);
            border: 1px solid #eee;
            border-left: 5px solid var(--pink);
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 20px;
            transition: box-shadow 0.3s ease;
        }

        .tool-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        }

        .tool-card h5 {
            font-size: 1.15rem;
            color: var(--pink);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .tool-card p {
            font-size: 0.95rem;
            color: #555;
            margin: 0;
        }

        .tool-card a {
            text-decoration: none;
            color: var(--pink);
        }

        .tool-card a:hover {
            text-decoration: underline;
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
        <a href="#tool-summary">Text Summarizer</a>
        <a href="#tool-understanding">Conceptual Understanding</a>
        <a href="#tool-rewriter">Text Rewriter</a>
        <a href="#tool-proofreader">Proofreader</a>
    </div>

    <div class="content">
        <div class="hero">
            <h1>Welcome to CK AI Tools</h1>
            <p>A supportive suite of tools to help young learners grow their reading and writing skills with clarity and
                confidence.</p>
        </div>

        <div class="tool-card" id="tool-leveler">
            <h5><a href="http://192.168.50.144:8000/leveler" target="_blank">Text Leveler</a></h5>
            <p>Adjust text difficulty to match your reading level and comprehension needs.</p>
        </div>

        <div class="tool-card" id="tool-summary">
            <h5><a href="http://192.168.50.238:8000/" target="_blank">Text Summarizer</a></h5>
            <p>Simplify long text into concise, easy-to-understand summaries.</p>
        </div>

        <div class="tool-card" id="tool-understanding">
            <h5><a href="http://192.168.50.127:8000/tutor" target="_blank">Understanding Tutor</a></h5>
            <p>Get writing feedback on grammar, structure, and clarity to build stronger writing skills.</p>
        </div>

        <div class="tool-card" id="tool-rewriter">
            <h5><a href="http://192.168.50.123:8000/rewriter" target="_blank">Rewriter</a></h5>
            <p>Rephrase sentences to enhance expression and explore new ways of writing.</p>
        </div>

        <div class="tool-card" id="tool-proofreader">
            <h5><a href="http://192.168.50.18:5001/proofreader" target="_blank">Proofreader</a></h5>
            <p>Automatically catch and correct grammar, spelling, and punctuation errors.</p>
        </div>

        <div class="footer">
            &copy; <span id="year"></span> CK Children’s Publishing. All rights reserved.
        </div>
    </div>

    <script>
        document.getElementById("year").textContent = new Date().getFullYear();
    </script>

</body>

</html>
