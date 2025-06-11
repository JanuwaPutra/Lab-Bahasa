<x-app-layout>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">{{ __('My Profile') }}</h4>
                </div>
                
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar-placeholder mb-3">
                            <span class="display-4">{{ substr(Auth::user()->name, 0, 1) }}</span>
                        </div>
                        <h3>{{ Auth::user()->name }}</h3>
                        <p class="text-muted">{{ Auth::user()->email }}</p>
                        
                        @if(Auth::user()->role === 'admin')
                        <span class="badge bg-danger">Administrator</span>
                        @elseif(Auth::user()->role === 'teacher')
                        <span class="badge bg-success">Teacher</span>
                        @else
                        <span class="badge bg-info">Student</span>
                        @endif
                    </div>
                    
                    @if(Auth::user()->role !== 'admin' && Auth::user()->role !== 'teacher')
                    <h5>{{ __('Language Learning Progress') }}</h5>
                    @php
                      $levelNames = [
                        1 => 'Beginner',
                        2 => 'Intermediate',
                        3 => 'Advanced'
                      ];
                    @endphp
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span>English</span>
                            @php
                              $enLevel = Auth::user()->getCurrentLevel('en');
                              $enLevelName = $levelNames[$enLevel] ?? '';
                            @endphp
                            <span>Level {{ $enLevel }} ({{ $enLevelName }})</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $enLevel * 33.33 }}%" aria-valuenow="{{ $enLevel * 33.33 }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Bahasa Indonesia</span>
                            @php
                              $idLevel = Auth::user()->getCurrentLevel('id');
                              $idLevelName = $levelNames[$idLevel] ?? '';
                            @endphp
                            <span>Level {{ $idLevel }} ({{ $idLevelName }})</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $idLevel * 33.33 }}%" aria-valuenow="{{ $idLevel * 33.33 }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Русский (Russian)</span>
                            @php
                              $ruLevel = Auth::user()->getCurrentLevel('ru');
                              $ruLevelName = $levelNames[$ruLevel] ?? '';
                            @endphp
                            <span>Level {{ $ruLevel }} ({{ $ruLevelName }})</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $ruLevel * 33.33 }}%" aria-valuenow="{{ $ruLevel * 33.33 }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    
                    <h5>{{ __('Recent Activity') }}</h5>
                    <ul class="list-group mb-4">
                        @forelse (Auth::user()->assessments()->latest()->take(5)->get() as $assessment)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ ucfirst(str_replace('_', ' ', $assessment->type)) }} - {{ ucfirst($assessment->language) }}</span>
                                <span class="badge bg-primary rounded-pill">{{ $assessment->score }}/100</span>
                            </li>
                        @empty
                            <li class="list-group-item text-center">{{ __('No recent assessments') }}</li>
                        @endforelse
                    </ul>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="{{ route('progress-report') }}" class="btn btn-outline-primary">{{ __('Full Progress Report') }}</a>
                    </div>
                    @else
                    <div class="mb-4">
                        <h5>{{ __('Account Information') }}</h5>
                        <div class="mb-3">
                            <small class="text-muted">Role</small>
                            <p>{{ ucfirst(Auth::user()->role) }}</p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Joined On</small>
                            <p>{{ Auth::user()->created_at->format('d M Y') }}</p>
                        </div>
                    </div>
                    @endif
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>{{ __('Logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-placeholder {
        width: 100px;
        height: 100px;
        background-color: #e9ecef;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }
</style>
</x-app-layout> 