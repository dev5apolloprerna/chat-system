<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu"></div>
            <ul class="navbar-nav" id="navbar-nav">

                @auth

                    <li class="menu-title"><span data-key="t-menu"></span></li>

                    <li class="nav-item">
                        <a class="nav-link menu-link @if (request()->routeIs('client.dashboard')) {{ 'active' }} @endif"
                            href="{{ route('client.dashboard') }}">
                            <i class="mdi mdi-speedometer"></i>
                            <span data-key="t-dashboards">Dashboards</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link menu-link @if (request()->routeIs('client.chat.index')) {{ 'active' }} @endif"
                            href="{{ route('client.chat.index') }}">
                            <i class="mdi mdi-chat"></i>
                            <span data-key="t-dashboards">Chat</span>
                        </a>
                    </li>

                @endauth
            </ul>
        </div>
        <!-- Sidebar -->
    </div>

    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>
