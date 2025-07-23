<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CK AI Tools - History</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous" />
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

        [data-bs-theme="dark"] {
            --pink: #d61f5c;
            --white: #1e1e1e;
            --dark: #e0e0e0;
            --light-grey: #2c2c2c;
            --hover-grey: #3a3a3a;
        }

        body {
            font-family: 'Poppins', system-ui, sans-serif;
            background-color: var(--light-grey);
            color: var(--dark);
            margin: 0;
            overflow-x: hidden;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 240px;
            background-color: var(--white);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.06);
            z-index: 1000;
            transition: width 0.3s ease, padding 0.3s ease;
            display: flex;
            flex-direction: column;
            padding: 110px 10px 50px;
        }

        .sidebar.collapsed {
            width: 70px;
            padding: 120px 10px 40px;
        }

        .sidebar.collapsed .delete-btn {
            display: none !important;
        }

        .sidebar h2 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 20px;
            text-align: center;
            transition: opacity 0.2s ease;
            flex-shrink: 0;
        }

        .sidebar.collapsed h2 {
            opacity: 0;
        }

        #sessionList {
            overflow-y: auto;
            flex-grow: 1;
            padding-right: 1px;
            scrollbar-width: thin;
            scrollbar-color: var(--light-grey) transparent;
        }

        .session-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: var(--dark);
            text-decoration: none;
            margin: 6px 0;
            font-size: 1rem;
            padding: 10px 12px;
            border-radius: 8px;
            transition: background 0.3s ease, color 0.3s ease;
            position: relative;
        }

        .session-link i {
            margin-right: 8px;
        }

        .session-link:hover {
            background-color: var(--hover-grey);
            color: var(--pink);
        }

        .session-link.active {
            background-color: var(--light-grey);
        }

        .session-link .delete-btn {
            display: none;
            background: none;
            border: none;
            color: #dc3545;
            font-size: 1rem;
            cursor: pointer;
        }

        .session-link:hover .delete-btn {
            display: inline;
        }

        .sidebar.collapsed .session-link {
            justify-content: center;
            padding: 10px 8px;
        }

        .sidebar.collapsed .session-link .link-text {
            display: none;
        }

        #toggleSidebar {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background: var(--white);
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 1.1rem;
            color: #5a5959;
            /* cursor: pointer; */
            /* transition: all 0.2s ease; */
        }
        
        #toggleSidebar:hover {
            /* background-color: var(--hover-grey); */
            color: var(--pink);
        }

        #sessionList p {
            font-size: 0.95rem;
            color: #6c757d;
        }

        [data-bs-theme="dark"] #sessionList p {
            color: #a0a0a0;
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>

    <script>
        const toggleBtn = document.getElementById("toggleSidebar");
        const sidebar = document.getElementById("sidebar");
        const sessionList = document.getElementById("sessionList");

        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
            document.body.classList.toggle("sidebar-collapsed");
        });

        const userId = {{ Auth::id() ?? 'null' }};
        const currentPath = window.location.pathname;

        fetch(`{{ route('api.user_sessions') }}`)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw err;
                    });
                }
                return response.json();
            })
            .then(data => {
                sessionList.innerHTML = '';
                if (!data.length) {
                    sessionList.innerHTML = '<p>No sessions yet.</p>';
                } else {
                    data.reverse().forEach(session => {
                        const sessionId = session.message_id;
                        const sessionTitle = session.title || `Session ${sessionId}`;

                        const link = document.createElement('a');
                        link.href = `/chat/history/${sessionId}`;
                        link.className = 'session-link';

                        link.innerHTML = `
                            <div style="display: flex; align-items: center;">
                                <i class="bi bi-chat-dots"></i>
                                <span class="link-text">${sessionTitle}</span>
                            </div>
                            <button class="delete-btn" title="Delete session"><i class="bi bi-trash"></i></button>
                        `;

                        if (currentPath.includes(`/chat/history/${sessionId}`)) {
                            link.classList.add('active');
                            setTimeout(() => {
                                link.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }, 0);
                        }

                        const deleteBtn = link.querySelector('.delete-btn');
                        deleteBtn.addEventListener('click', (e) => {
                            e.preventDefault();
                            fetch(`/api/sessions/${sessionId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json'
                                }
                            })
                            .then(res => {
                                if (res.ok) {
                                    link.remove();
                                    if (currentPath.includes(`/chat/history/${sessionId}`)) {
                                        fetch(`{{ route('api.user_sessions') }}`)
                                            .then(res => res.json())
                                            .then(newSessions => {
                                                if (newSessions.length) {
                                                    const latestId = newSessions[newSessions.length - 1].message_id;
                                                    window.location.href = `/chat/history/${latestId}`;
                                                } else {
                                                    window.location.href = `/tools`;
                                                }
                                            });
                                    }
                                } else {
                                    console.error("Failed to delete session", res.statusText);
                                }
                            })
                            .catch(err => {
                                console.error("Delete error:", err);
                            });
                        });

                        sessionList.appendChild(link);
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching sessions:', error);
                let errorMessage = 'Error loading sessions.';
                if (error.message) {
                    errorMessage += ` Details: ${error.message}`;
                } else if (error.error) {
                    errorMessage += ` Details: ${error.error}`;
                }
                sessionList.innerHTML = `<p>${errorMessage}</p><p>Auth ID is ${userId}</p>`;
            });
    </script>
</body>
</html>