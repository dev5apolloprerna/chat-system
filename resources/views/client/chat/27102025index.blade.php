@extends('layouts.client')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
    .conversation-item.active {
        background-color: #d9edf7;
        font-weight: bold;
    }

    *::-webkit-scrollbar {
        width: 5px;
        /* width of scrollbar */
    }

    *::-webkit-scrollbar-track {
        background: #fff;
        /* track color */
        border-radius: 6px;
    }

    /* Handle (the draggable part) */
    *::-webkit-scrollbar-thumb {
        background-color: #fff;
        /* scrollbar color */
        border-radius: 6px;
        border: 3px solid #fff;
        /* optional padding effect */
    }

    /* Optional: Hover effect */
    *::-webkit-scrollbar-thumb:hover {
        background-color: #0056b3;
    }

    .chat-body {
        scrollbar-width: none;
        /* Firefox */
        -ms-overflow-style: none;
    }

    .chat-body::-webkit-scrollbar {
        display: none;
        /* Chrome, Safari, Edge */
    }

    .chat-left {
        scrollbar-width: 3px;
        /* Firefox */
    }

    .chat-left::-webkit-scrollbar {
        width: 12px;
        /* width of scrollbar */
    }

    .chat-left::-webkit-scrollbar-track {
        background: #f0f0f0;
        /* track color */
        border-radius: 6px;
    }

    /* Handle (the draggable part) */
    .chat-left::-webkit-scrollbar-thumb {
        background-color: rgb(61 120 227 / 75%);
        /* scrollbar color */
        border-radius: 6px;
        border: 3px solid #f0f0f0;
        /* optional padding effect */
    }

    /* Optional: Hover effect */
    .chat-left::-webkit-scrollbar-thumb:hover {
        background-color: #0056b3;
    }

    .btn-icon {
        width: 40px;
        height: 40px;
        padding: 0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar (Conversations) -->
                <div class="col-md-4 col-lg-3 border-end bg-light p-0">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0">Chats</h5>
                    </div>

                    <!-- Scrollable conversation list -->
                    <div style="height: calc(100vh - 300px); overflow-y: auto;" class=" chat-left">
                        <ul class="list-group list-group-flush chat-list">
                            <?php $i = 1; ?>
                            @foreach ($conversations as $c)
                            <li class="list-group-item conversation-item text-white" data-id="{{ $c->id }}"
                                data-name="{{ $c->sender_name ? $c->sender_name : $c->sender_number }}">
                                <a href="javascript:void(0)" class="d-flex align-items-center text-decoration-none">
                                    <div>
                                        <strong>{{ $c->sender_name ? $c->sender_name : $c->sender_number }}</strong><br>
                                    </div>
                                </a>
                            </li>
                            <?php $i++; ?>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <!-- Chat Window -->
                <div class="col-md-8 col-lg-9">
                    <div class="card shadow-sm ">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0 text-white" id="chatTitle">Select a conversation</h6>
                        </div>

                        <div class="card-body" id="messages"
                            style="height: calc(100vh - 370px); overflow-y: auto; background:#f9f9f9;">
                            <p class="text-muted text-center mt-5">No messages yet</p>
                        </div>


                    </div>
                    <div class="card-footer">
                        <form id="chatForm" class="d-flex">
                            @csrf
                            <input type="text" id="message" name="message" class="form-control me-2"
                                placeholder="Type a message..." disabled>
                            <button type="submit" class="btn btn-primary btn-icon" disabled>
                                <i class="bi bi-send"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        let currentConversationId = null;

        const fetchMessagesRoute = @json(route('client.chat.messages', ['conversation' => ':id']));
        const sendMessageRoute = @json(route('client.chat.send', ['conversation' => ':id']));

        let fetchMessagesUrl = "";
        let sendMessageUrl = "";

        function loadMessages() {
            if (!currentConversationId) return;

            fetch(fetchMessagesUrl)
                .then(res => res.json())
                .then(data => {
                    let html = '';
                    if (data.length === 0) {
                        html = '<p class="text-muted text-center mt-5">No messages yet</p>';
                    } else {
                        data.forEach(m => {
                            let alignClass = m.sender_type === 'self' ? 'text-end' : 'text-start';
                            let bubbleClass = m.sender_type === 'self' ?
                                'bg-primary text-white' :
                                'bg-light';

                            html += `
                                    <div class="mb-2 ${alignClass}">
                                        <span class="d-inline-block px-3 py-2 rounded ${bubbleClass}">
                                            ${m.body}
                                        </span>
                                    </div>`;
                        });

                        // Update last message preview in sidebar
                        let activeItem = document.querySelector(
                            `.conversation-item[data-id="${currentConversationId}"] .last-message-preview`
                        );
                        if (activeItem && data.length > 0) {
                            activeItem.textContent = data[data.length - 1].message;
                        }
                    }
                    document.getElementById('messages').innerHTML = html;
                    document.getElementById('messages').scrollTop = document.getElementById('messages')
                        .scrollHeight;
                })
                .catch(err => console.error(err));
        }

        function selectConversation(item) {
            document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            currentConversationId = item.getAttribute('data-id');
            let name = item.getAttribute('data-name');

            document.getElementById('chatTitle').innerText = name;

            document.getElementById('message').disabled = false;
            document.querySelector('#chatForm button').disabled = false;

            // Replace :id in routes
            fetchMessagesUrl = fetchMessagesRoute.replace(':id', currentConversationId);
            sendMessageUrl = sendMessageRoute.replace(':id', currentConversationId);

            loadMessages();
        }

        document.querySelector('.chat-list').addEventListener('click', function(e) {
            let item = e.target.closest('.conversation-item');
            if (item) selectConversation(item);
        });

        // Auto-select first conversation
        let firstConversation = document.querySelector('.conversation-item');
        if (firstConversation) selectConversation(firstConversation);

        setInterval(loadMessages, 3000);

        document.getElementById('chatForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if (!currentConversationId || !sendMessageUrl) return;

            let messageText = document.getElementById('message').value.trim();
            if (messageText === '') return;

            fetch(sendMessageUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: messageText
                    })
                })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('message').value = '';
                    loadMessages();
                })
                .catch(err => console.error(err));
        });

    });
</script>
@endsection