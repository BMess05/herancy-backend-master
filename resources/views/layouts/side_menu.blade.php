 <ul class="navbar-nav">
    <li class="nav-item">
        <a class="nav-link" href="{{ route('home') }}">
            <i class="ni ni-tv-2 text-primary"></i> {{ __('Dashboard') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="#navbar-examples" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="navbar-examples">
            <i class="fab fa-laravel" style="color: #f4645f;"></i>
            <span class="nav-link-text" style="color: #f4645f;">{{ __('User') }}</span>
        </a>

        <div class="collapse show" id="navbar-examples">
            <ul class="nav nav-sm flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('profile.edit') }}">
                            <i class="ni ni-circle-08 text-pink"></i> {{ __('User profile') }}
                        </a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="{{ route('user.index') }}">
                            <i class="ni ni-bullet-list-67 text-default"></i>{{ __('User Management') }}
                        </a>
                    </li> 
            </ul>
        </div>
    </li>
</ul>