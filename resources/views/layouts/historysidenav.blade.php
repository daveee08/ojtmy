<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CK AI Tools</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --pink: #e91e63;
            --white: #ffffff;
            --dark: #191919;
            --light-grey: #f8f9fa;
            --hover-grey: #f1f3f5;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-grey);
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
            padding: 100px 10px 30px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.06);
            z-index: 1000;
            transition: width 0.3s ease, padding 0.3s ease;
        }

        .sidebar.collapsed {
            width: 70px;
            padding: 120px 10px 40px;
        }

        .sidebar h2 {
            font-size: 1.2rem;
            font-weight: 700;
            color: black;
            margin-bottom: 20px;
            text-align: center;
            transition: opacity 0.2s ease;
        }

        .sidebar.collapsed h2 {
            opacity: 0;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--dark);
            text-decoration: none;
            margin: 6px 0;
            font-size: 1rem;
            padding: 10px 12px;
            border-radius: 8px;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .sidebar a:hover {
            background-color: var(--hover-grey);
            color: var(--pink);
        }

        .sidebar a.active {
            background-color: #F5F5F5;
        }

        .sidebar.collapsed a {
            justify-content: center;
            font-size: 0.9rem;
            padding: 10px 8px;
        }

        .sidebar a i {
            font-size: 1.2rem;
        }

        .link-text {
            white-space: nowrap;
            overflow: hidden;
            opacity: 1;
            transition: opacity 0.3s ease, width 0.3s ease;
        }

        .sidebar.collapsed .link-text {
            opacity: 0;
            width: 0;
        }

        .content {
            margin-left: 240px;
            padding: 30px;
            transition: margin-left 0.3s ease;
        }

        .content.expanded {
            margin-left: 70px;
        }

        #toggleSidebar {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background: var(--white);
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 1.1rem;
            color: var(--dark);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        #toggleSidebar:hover {
            background-color: var(--hover-grey);
        }

        #sessionList p {
            font-size: 0.95rem;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <button id="toggleSidebar"><i class="bi bi-list"></i></button>

    <div class="sidebar" id="sidebar">
        <h2>History</h2>
        <div id="sessionList">
            <p>Loading sessions...</p>
        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById("toggleSidebar");
        const sidebar = document.getElementById("sidebar");
        const content = document.querySelector(".content");
        const sessionList = document.getElementById("sessionList");

        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
            if (content) content.classList.toggle("expanded");
            document.body.classList.toggle("sidebar-collapsed");
        });

        const userId = {{ Auth::id() ?? 1 }};
        const currentPath = window.location.pathname;

        fetch(`http://localhost:5001/sessions/${userId}`)
            .then(response => response.json())
            .then(data => {
                sessionList.innerHTML = '';
                if (!data.length) {
                    sessionList.innerHTML = '<p>No sessions yet.</p>';
                } else {
                    data.forEach(sessionId => {
                        const link = document.createElement('a');
                        link.href = `/chat/history/${sessionId}`;
                        link.innerHTML =
                            `<i class="bi bi-chat-dots"></i> <span class="link-text">Session ${sessionId}</span>`;

                        // Add active class if this is the current session
                        if (currentPath.includes(`/chat/history/${sessionId}`)) {
                            link.classList.add('active');
                        }

                        sessionList.appendChild(link);
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching sessions:', error);
                sessionList.innerHTML = '<p>Error loading sessions.</p>';
            });
    </script>
</body>

</html>
