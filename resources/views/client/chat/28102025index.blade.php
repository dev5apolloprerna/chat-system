@extends('layouts.client')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        .bg-primary {
            background-color: #d9fdd3 !important;
            color: black !important;
        }

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

        .badge {
            font-size: 0.8rem;
            min-width: 25px;
            text-align: center;
        }

        .bi {
            font-size: 13px;
            vertical-align: middle;
            margin-left: 3px;
        }

        .message-bubble {
            position: relative;
            padding-right: 20px;
            /* Space for the tick */
            padding-bottom: 20px;
            /* Space for the time and tick */
        }

        .message-body {
            font-size: 14px;
            margin-bottom: 5px;
            /* Space between message and time */
        }

        .message-time {
            font-size: 11px;
            color: black;
            margin-bottom: 5px;
            text-align: left;
            /* Space between time and tick */
        }

        .message-status {
            position: absolute;
            right: 5px;
            bottom: 5px;
            /* Adjust the bottom margin for the tick icon */
            font-size: 12px;
            color: black;
        }

        /* Optional: Adjust the tick icon color for different statuses */
        .message-status .bi {
            color: black;
            /* Default white for sent messages */
        }

        .message-status .bi-check {
            color: black;
            /* Sent tick */
        }

        .message-status .bi-check-all {
            color: #0dcaf0;
            /* Read tick (blue) */
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
                                        <a href="javascript:void(0)"
                                            class="d-flex align-items-center justify-content-between text-decoration-none">
                                            <div>
                                                <strong>{{ $c->sender_name ? $c->sender_name : $c->sender_number }}</strong><br>
                                            </div>
                                            @if ($c->unread_count > 0)
                                                <span class="badge bg-danger rounded-pill">{{ $c->unread_count }}</span>
                                            @endif

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
                                <h6 class="mb-0" id="chatTitle">Select a conversation</h6>
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
        const markReadRoute = "{{ route('client.chat.markAsRead', ':id') }}";

        function getTickIcon(status, isSelf = false) {
            switch (status) {
                case 0:
                    return `<i class="bi bi-clock"></i>`; // Pending ⏳
                case 1:
                    return `<i class="bi bi-check"></i>`; // Sent ✓
                case 2:
                    return `<i class="bi bi-check-all"></i>`; // Delivered ✓✓
                case 3:
                    return `<i class="bi bi-check-all text-info"></i>`; // Read ✓✓ (blue)
                default:
                    return '';
            }
        }



        document.addEventListener('DOMContentLoaded', function() {

            let currentConversationId = null;

            const fetchMessagesRoute = @json(route('client.chat.messages', ['conversation' => ':id']));
            const sendMessageRoute = @json(route('client.chat.send', ['conversation' => ':id']));

            let fetchMessagesUrl = "";
            let sendMessageUrl = "";

            let isAtBottom = true; // Keep track if the user is at the bottom of the chat

            // Check if the user is at the bottom of the chat
            function checkIfAtBottom() {
                const messagesDiv = document.getElementById('messages');
                return messagesDiv.scrollHeight - messagesDiv.scrollTop === messagesDiv.clientHeight;
            }

            function loadMessages() {
                const messagesDiv = document.getElementById('messages');
                const wasAtBottom = checkIfAtBottom(); // Save the current scroll position


                if (!currentConversationId) return;

                fetch(fetchMessagesUrl)
                    .then(res => res.json())
                    .then(data => {
                        let html = '';
                        if (data.length === 0) {
                            html = '<p class="text-muted text-center mt-5">No messages yet</p>';
                        } else {
                            let lastDateLabel = '';

                            data.forEach(m => {
                                // 🕒 Check if new date divider is needed
                                if (m.date_label !== lastDateLabel) {
                                    html += `
                                    <div class="text-center text-muted small my-3">
                                        <span class="px-3 py-1 rounded-pill bg-light border">${m.date_label}</span>
                                    </div>`;
                                    lastDateLabel = m.date_label;
                                }

                                let alignClass = m.sender_type === 'self' ? 'text-end' : 'text-start';
                                let bubbleClass = m.sender_type === 'self' ? 'bg-primary text-white' :
                                    'bg-light';

                                html += `
                                    <div class="mb-2 ${alignClass}">
                                        <div class="d-inline-block px-3 py-2 rounded ${bubbleClass} message-bubble">
                                            <div>
                                                <div class="message-body">${m.body}</div> <!-- Message -->
                                            </div>
                                        <div>
                                            <div class="message-time">${m.time ?? ''}</div> <!-- Time -->
                                            <div class="message-status">${m.sender_type === 'self' ? getTickIcon(m.isMessageStatus, true) : ''}</div> <!-- Tick icon -->
                                        </div>

                                        </div>
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

                        // Update chat messages
                        document.getElementById('messages').innerHTML = html;

                        // If the user was at the bottom, scroll to the bottom again after messages are loaded
                        if (wasAtBottom) {
                            messagesDiv.scrollTop = messagesDiv.scrollHeight;
                        }

                        {{--  document.getElementById('messages').innerHTML = html;
                        document.getElementById('messages').scrollTop = document.getElementById('messages')
                            .scrollHeight;  --}}
                    })
                    .catch(err => console.error(err));
            }

            // Detect if the user scrolls manually, and stop auto-scrolling if so
            document.getElementById('messages').addEventListener('scroll', function() {
                isAtBottom = checkIfAtBottom();
            });

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

                const markReadUrl = markReadRoute.replace(':id', currentConversationId);
                // Mark messages as read (AJAX request)
                fetch(markReadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log(`Marked ${data.updated} messages as read.`);

                        // ✅ Remove unread badge from sidebar immediately
                        const activeItem = document.querySelector(
                            `.conversation-item[data-id="${currentConversationId}"] .badge`);
                        if (activeItem) {
                            activeItem.remove();
                        }

                        // Optionally reload messages
                        loadMessages();
                    })
                    .catch(err => console.error(err));
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
