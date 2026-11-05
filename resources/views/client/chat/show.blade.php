@extends('layouts.client')

@section('content')
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <!-- Chat Window -->
                    <div class="col-12 col-md-10 mx-auto">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Conversation #{{ $conversation->id }}</h6>
                            </div>

                            <div class="card-body chat-body" id="messages"
                                style="height:400px; overflow-y:auto; background:#f9f9f9;">
                                <!-- Messages will load here -->
                            </div>

                            <div class="card-footer">
                                <form id="chatForm" class="d-flex">
                                    @csrf
                                    <input type="text" id="message" name="message" class="form-control me-2"
                                        placeholder="Type a message...">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send"></i> Send
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let conversationId = "{{ $conversation->id }}";
        let fetchMessagesUrl = "{{ route('client.chat.messages', $conversation->id) }}";
        let sendMessageUrl = "{{ route('client.chat.send', $conversation->id) }}";

        // Load messages
        function loadMessages() {
            fetch(fetchMessagesUrl)
                .then(res => res.json())
                .then(data => {
                    let html = '';
                    data.forEach(m => {
                        let alignClass = m.sender_type === 'client' ? 'text-end' : 'text-start';
                        let bubbleClass = m.sender_type === 'client' ? 'bg-primary text-white' : 'bg-light';
                        html += `
                        <div class="mb-2 ${alignClass}">
                            <span class="d-inline-block px-3 py-2 rounded ${bubbleClass}">
                                ${m.message}
                            </span>
                        </div>`;
                    });
                    document.getElementById('messages').innerHTML = html;
                    document.getElementById('messages').scrollTop = document.getElementById('messages').scrollHeight;
                });
        }
        loadMessages();
        setInterval(loadMessages, 3000);

        // Send message
        document.getElementById('chatForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch(sendMessageUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: document.getElementById('message').value
                })
            }).then(() => {
                document.getElementById('message').value = '';
                loadMessages();
            });
        });
    </script>
@endpush
