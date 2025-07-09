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
            --pink: #EC298B;
            --white: #ffffff;
            --dark: #191919;
            --light-grey: #f5f5f5;
        }

        body {
            font-family: 'Poppins', system-ui, sans-serif;
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
            padding: 40px 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            transition: width 0.3s ease;
            padding-top: 120px;
        }

        .sidebar.collapsed {
            width: 70px;
            padding: 40px 10px;
        }

        .sidebar h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--pink);
            margin-bottom: 30px;
            text-align: center;
            transition: opacity 0.2s ease;
        }

        .sidebar.collapsed h2 {
            opacity: 0;
        }

        .sidebar a {
            display: block;
            color: var(--dark);
            text-decoration: none;
            margin: 5px 0;
            font-size: 1rem;
            padding: 10px 10px;
            border-radius: 10px;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .sidebar a:hover {
            background-color: var(--light-grey);
            color: var(--pink);
        }

        .sidebar.collapsed a {
            text-align: center;
            padding: 10px 5px;
            font-size: 0.85rem;
            overflow: hidden;
        }

        .sidebar a i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .sidebar.collapsed a i {
            margin-right: 0;
            font-size: 1rem;
        }

        .link-text {
            display: inline;
            transition: opacity 0.3s ease, width 0.3s ease;
            opacity: 1;
            width: auto;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar.collapsed .link-text {
            opacity: 0;
            width: 0;
        }

        .content {
            margin-left: 240px;
            padding: 50px 30px;
            background-color: var(--light-grey);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .content.expanded {
            margin-left: 70px;
        }

        #toggleSidebar {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            border: none;
            background: var(--white);
            color: rgb(90, 89, 89);
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 1rem;
        }
    </style>
</head>

<body>

    <button id="toggleSidebar">☰</button>

    <div class="sidebar" id="sidebar">
        <h2>History</h2>
        <div id="sessionList">
            <!-- Session links will be injected here -->
        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById("toggleSidebar");
        const sidebar = document.getElementById("sidebar");
        const content = document.querySelector(".content");
        const sessionList = document.getElementById("sessionList");

        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
            if (content) {
                content.classList.toggle("expanded");
            }

            // Add this line to affect chat layout globally
            document.body.classList.toggle("sidebar-collapsed");
        });

        // Replace with actual logged-in user ID from Laravel
        const userId = {{ Auth::id() ?? 1 }};

        // Fetch session data from FastAPI
        fetch(`http://localhost:5001/sessions/${userId}`)
            .then(response => response.json())
            .then(data => {
                sessionList.innerHTML = '';
                if (data.length === 0) {
                    sessionList.innerHTML = '<p>No sessions yet.</p>';
                } else {
                    data.forEach(sessionId => {
                        const link = document.createElement('a');
                        link.href = `/chat/history/${sessionId}`; // Or your desired URL pattern
                        link.innerHTML =
                            `<i class="bi bi-chat-dots"></i> <span class="link-text">Session ${sessionId}</span>`;
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
