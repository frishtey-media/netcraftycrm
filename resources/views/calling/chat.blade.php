@extends('layouts.calling')

@section('content')
    <div class="container-fluid">

        <div class="row">

            {{-- LEFT SIDEBAR --}}
            <div class="col-md-4 border-end" style="height:80vh; overflow:auto;">

                @foreach ($conversation->staff->conversations ?? [] as $c)
                    <a href="{{ route('calling.chat', $c->id) }}" class="d-block p-3 border-bottom text-decoration-none">

                        <strong>{{ $c->customer_phone }}</strong><br>

                        <small>{{ \Illuminate\Support\Str::limit($c->last_message, 25) }}</small>

                    </a>
                @endforeach

            </div>

            {{-- CHAT AREA --}}
            <div class="col-md-8 d-flex flex-column" style="height:80vh;">

                {{-- HEADER --}}
                <div class="border-bottom p-3 d-flex justify-content-between">
                    <strong>{{ $conversation->customer_phone }}</strong>

                    <a href="tel:{{ $conversation->customer_phone }}" class="btn btn-sm btn-primary">
                        📞 Call
                    </a>
                </div>

                {{-- MESSAGES --}}
                <div class="flex-grow-1 p-3" style="overflow:auto; background:#f7f7f7;">

                    @foreach ($messages as $m)
                        <div class="mb-2 text-{{ $m->sender == 'staff' ? 'end' : 'start' }}">

                            <span
                                class="px-3 py-2 rounded
                            {{ $m->sender == 'staff' ? 'bg-success text-white' : 'bg-white' }}"
                                style="display:inline-block; max-width:70%;">

                                {{ $m->message }}

                            </span>

                        </div>
                    @endforeach

                </div>

                {{-- INPUT --}}
                <form method="POST" action="{{ route('calling.send') }}" class="p-3 border-top">
                    @csrf

                    <input type="hidden" name="conversation_id" value="{{ $conversation->id }}">

                    <div class="d-flex gap-2">

                        <input type="text" name="message" class="form-control" placeholder="Type message..." required>

                        <button class="btn btn-success">
                            Send
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>
    <script>
        window.onload = function() {
            let chatBox = document.querySelector('.flex-grow-1');
            if (chatBox) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        };
    </script>
@endsection
