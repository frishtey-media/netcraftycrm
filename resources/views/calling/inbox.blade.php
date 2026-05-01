@extends('layouts.calling')

@section('content')
    <div class="container-fluid">

        <h5 class="mb-3">📥 Inbox</h5>

        <div class="row">

            {{-- LEFT SIDEBAR (CONVERSATIONS) --}}
            <div class="col-md-4 border-end" style="height:80vh; overflow:auto;">

                @forelse($conversations as $c)
                    <a href="{{ route('calling.chat', $c->id) }}" class="d-block p-3 border-bottom text-decoration-none">

                        <strong>{{ $c->customer_phone }}</strong><br>

                        <small class="text-muted">
                            {{ \Illuminate\Support\Str::limit($c->last_message, 30) }}
                        </small>

                    </a>

                @empty

                    <div class="text-center p-4 text-muted">
                        No Conversations
                    </div>
                @endforelse

            </div>

            {{-- RIGHT SIDE --}}
            <div class="col-md-8 d-flex align-items-center justify-content-center">

                <div class="text-muted">
                    Select a chat to start conversation
                </div>

            </div>

        </div>

    </div>
@endsection
