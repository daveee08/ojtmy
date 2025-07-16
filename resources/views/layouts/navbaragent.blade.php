<!DOCTYPE html>
<html lang="en">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

<nav class="sidebar" id="sidebar">
    <h2></h2>

    <a href="{{ url('/') }}" class="{{ request()->is('/') ? 'active-link' : '' }}" data-bs-toggle="tooltip" title="Home">
        <i class="bi bi-house-door"></i>
        <span class="link-text">Home</span>
    </a>

    <a href="{{ url('/tools') }}" class="{{ request()->is('tools*') ? 'active-link' : '' }}" data-bs-toggle="tooltip" title="Tools">
        <i class="bi bi-tools"></i>
        <span class="link-text">Tools</span>
    </a>

    <a href="{{ url('/virtual_tutor') }}" class="{{ request()->is('virtual_tutor*') ? 'active-link' : '' }}" data-bs-toggle="tooltip" title="Virtual Tutor">
        <i class="bi bi-robot"></i>
        <span class="link-text">Virtual Tutor</span>
    </a>
</nav>

<!-- Toggle Sidebar Button -->
<button id="toggleSidebar">☰</button>

<style>
    :root {
        --pink: #e91e63;
        --white: #ffffff;
        --dark: #191919;
        --light-grey: #f5f5f5;
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 250px;
        background-color: var(--white);
        padding: 40px 20px;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.08);
        z-index: 1000;
        transition: width 0.3s ease;
    }

    .sidebar.collapsed {
        width: 70px;
        padding: 40px 10px;
    }

    .sidebar h2 {
        font-family: 'Poppins', sans-serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--pink);
        margin-bottom: 50px;
        text-align: center;
        transition: opacity 0.2s ease;
    }

    .sidebar.collapsed h2 {
        opacity: 0;
    }

    .sidebar a {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        color: var(--dark);
        text-decoration: none;
        margin: 12px 0;
        font-family: 'Poppins', sans-serif;
        font-size: 1rem;
        padding: 12px 18px;
        border-radius: 10px;
        transition: background 0.3s ease, color 0.3s ease;
        white-space: nowrap;
        overflow: hidden;
    }

    .sidebar.collapsed a {
        justify-content: center;
        padding-left: 12px;
        padding-right: 12px;
    }

    .sidebar a i {
        font-family: 'Bootstrap Icons', sans-serif;
        margin-right: 12px;
        font-size: 1.2rem;
        min-width: 24px;
        width: 24px;
        text-align: center;
        transition: margin 0.3s ease, font-size 0.3s ease;
    }

    .sidebar.collapsed a i {
        margin-right: 0;
    }

    .link-text {
        font-family: 'Poppins', sans-serif;
        display: inline-block;
        opacity: 1;
        width: auto;
        max-width: 200px;
        transition: opacity 0.3s ease, max-width 0.3s ease;
        overflow: hidden;
    }

    .sidebar.collapsed .link-text {
        opacity: 0;
        max-width: 0;
    }

    .sidebar a.active-link {
        background-color: rgba(221, 175, 198, 0.15);
    }

    .sidebar a.active-link i {
        color: inherit !important;
    }

    .sidebar a:hover {
        background-color: rgba(221, 175, 198, 0.15);
        color: var(--dark); /* Explicitly set to default black on hover */
    }

    #toggleSidebar {
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1100;
        border: none;
        background: var(--white);
        color: #5a5959;
        padding: 8px 12px;
        border-radius: 5px;
        font-size: 1rem;
    }

    #toggleSidebar:hover {
        color: var(--pink);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById("toggleSidebar");
        const sidebar = document.getElementById("sidebar");

        // Load sidebar state from localStorage
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        } else {
            sidebar.classList.remove('collapsed');
        }

        // Toggle sidebar and save state
        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
            const isCollapsedNow = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsedNow);
        });

        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(el => {
            new bootstrap.Tooltip(el);
        });
    });
</script>