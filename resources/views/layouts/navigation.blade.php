{{-- resources/views/layouts/navigation.blade.php --}}
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="{{ url('/') }}">
      {{ config('app.name', 'Laravel') }}
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
      aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        @php use Illuminate\Support\Facades\Route; @endphp

        @if(Route::has('dashboard'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('dashboard') }}">Home</a>
          </li>
        @endif

        @if(Route::has('dev.dashboard'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('dev.dashboard') }}">Dev Dashboard</a>
          </li>
        @endif

        @if(Route::has('dev.clients.index'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('dev.clients.index') }}">Clients</a>
          </li>
        @endif

        @if(Route::has('dev.projects.index'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('dev.projects.index') }}">Projects</a>
          </li>
        @endif

        @if(Route::has('dev.data.index'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('dev.data.index') }}">Master Data</a>
          </li>
        @endif

        @if(Route::has('dev.data.create'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('dev.data.create') }}">Tambah Master</a>
          </li>
        @endif

        {{-- Only show RAB/RAPP links if routes exist (they're currently disabled) --}}
        @if(Route::has('dev.rab.index'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('dev.rab.index') }}">RAB</a>
          </li>
        @endif

        @if(Route::has('dev.rapps.index'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('dev.rapps.index') }}">RAPP</a>
          </li>
        @endif
      </ul>

      <ul class="navbar-nav ms-auto">
        @auth
          <li class="nav-item dropdown">
            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
              aria-haspopup="true" aria-expanded="false" v-pre>
              {{ Auth::user()->name }}
            </a>

            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
              @if(Route::has('profile.edit'))
                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                  Profile
                </a>
              @endif

              <a class="dropdown-item" href="#"
                 onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                Logout
              </a>

              <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
              </form>
            </div>
          </li>
        @else
          @if(Route::has('login'))
            <li class="nav-item">
              <a class="nav-link" href="{{ route('login') }}">Login</a>
            </li>
          @endif
          @if(Route::has('register'))
            <li class="nav-item">
              <a class="nav-link" href="{{ route('register') }}">Register</a>
            </li>
          @endif
        @endauth
      </ul>
    </div>
  </div>
</nav>


