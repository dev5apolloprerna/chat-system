@extends('layouts.client')
@section('title', 'Chat')
@section('content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
    /* ======== GENERAL COLORS ======== */
    :root {
        --chat-primary: #0370c9ff;         /* Dark green header */
        --chat-secondary: #0370c9ff;       /* Lighter accent */
        --chat-bg: #fff;              /* WhatsApp background */
        --chat-bubble-sender: #c7d7f1;   /* WhatsApp green bubble */
        --chat-bubble-receiver: #dbe8fdff; /* Receiver bubble */
        --chat-sidebar-bg: #dbe8fdff;
        --chat-active-bg: #c7d7f1;       /* Active conversation */
        --chat-scroll-thumb: #dbe8fdff;
    }

    .bg-primary {
        background-color: var(--chat-primary) !important;
        color: white !important;
    }
    .page-content {
        background: #dfdfdf;
        height: calc(100vh - 95px);
        margin-top:90px!important;
    }
    /* Sidebar */
    .conversation-item.active {
        background-color: var(--chat-active-bg) !important;
        font-weight: bold;
        color: #075E54 !important;
    }

    .list-group-item {
        background: var(--chat-sidebar-bg);
        border: none;
        border-bottom: 1px solid #f2f2f2;
        color: #333;
    }

    /* Scrollbars */
    *::-webkit-scrollbar {
        width: 6px;
    }

    *::-webkit-scrollbar-thumb {
        background-color: var(--chat-secondary);
        border-radius: 6px;
    }

    *::-webkit-scrollbar-track {
        background: #c7d7f1;
    }

    /* Hide inner chat scrollbars */
    .chat-body::-webkit-scrollbar { display: none; }

    .chat-left{height: calc(100vh - 235px); 
        overflow-y: auto;
        border:1px solid #a4c8e8
    }
    /* Beautiful scroll for sidebar */
    .chat-left::-webkit-scrollbar-thumb {
        background-color: var(--chat-secondary);
        border-radius: 10px;
    }
    .chat-list>li.active a{
        background-color: var(--chat-active-bg);
        color: #075E54;
        font-weight: bold;
    }
    /* Buttons */
    .btn-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--chat-secondary);
        border: none;
        color: white;
    }

    .btn-icon:hover {
        background: var(--chat-primary);
    }

    /* Conversation preview text */
    .badge {
        font-size: 0.75rem;
    }

    .bi {
        font-size: 14px;
    }

    /* Chat background */
    #messages {
        background: var(--chat-bg);
    }

   .message-bubble {
    position: relative;
    max-width: 70%;
    padding: 8px 55px 25px 10px; /* add right padding for time */
    border-radius: 10px;
    background: #dcf8c6; /* example for sent message */
    display: inline-block;
    word-wrap: break-word;
}

.message-footer {
    position: absolute;
    bottom: 4px;
    right: 0px;
    font-size: 11px;
    color: #777;
    display: flex;
    align-items: center;
    gap: 3px;
    width:70px;
    justify-content: flex-start;
}

.message-footer img,
.message-footer svg {
    height: 14px; /* tick icons */
}
/* Container alignment */
.message-row {
    display: flex;
    margin: 4px 0;
}

.message-row.self {
    justify-content: flex-end;
}

.message-row.other {
    justify-content: flex-start;
}

    /* RECEIVER message (left side) */
.message-bubble.receiver {
    background-color:var(--chat-bubble-receiver);
    color: #000;
    border-radius: 20px 20px 20px 0px; /* Tail on LEFT */
    display: inline-block;
    max-width: 75%;
    align-self: flex-start;
}

/* SENDER message (right side) */
.message-bubble.sender {
    background-color:var(--chat-bubble-sender);
    color: #000;
    border-radius: 20px 20px 0px 20px; /* Tail on RIGHT */
    display: inline-block;
    max-width: 75%;
    align-self: flex-end;
}

    .message-body {
        font-size: 14px;
        margin-bottom: 5px;
        white-space: pre-wrap;
    }

   

    /* Message ticks */
    .message-status .bi-check {
        color: #6c757d;
        width: 14px;
    }

    .bi-check-all {
        color: #0069a7ff !important; /* Blue-green read tick */
    }
  
    .chat-list>li.active a{
        color: #000!important;
    }
    .chat-list>li a{
        color: #000!important;
       
    }
    .chat-list>li{
       
        border-bottom:1px dashed #0069a7;
    }
    /* Bubble Tail */
.message-row.self .message-bubble::after,
.message-row.other .message-bubble::after {
    content: "";
    position: absolute;
    bottom: 0;
    width: 0;
    height: 0;
}

.message-row.self .message-bubble::after {
    right: -8px;
    border-left: 8px solid #dcf8c6;
    border-top: 8px solid transparent;
}

.message-row.other .message-bubble::after {
    left: -8px;
    border-right: 8px solid #ffffff;
    border-top: 8px solid transparent;
}

/* Bubble Appearance */
.message-bubble {
    position: relative;
    max-width: 75%;
    padding: 8px 70px 0px 12px;
    border-radius: 8px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.15);
    font-size: 15px;
    line-height: 1.35;
}
#chatForm input[type="text"] {
    border-radius: 20px;
    border: 1px solid #0069a7;
    padding: 10px 15px;
    font-size: 14px;

}
.sidebar-chat {
    background: #0370c9 !important;
    border: 1px solid #c7d7f1;
    border-radius: 5px 5px 0 0;
    color: #fff!important;
}
.sidebar-chat h5{
    color:#fff!important;}
.card-footer{border-top:none}
    </style>


    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <!-- Sidebar (Conversations) -->
                    <div class="col-md-4 col-lg-3 bg-light p-0 sidebar-chat">
                        <div class="p-3 border-bottom">
                            <h5 class="mb-0">Chats</h5>
                        </div>

                        <!-- Scrollable conversation list -->
                        <div  class="chat-left">
                            <ul class="list-group list-group-flush chat-list">
                                <?php $i = 1; ?>
                                @foreach ($conversations as $c)
                                    <li class="list-group-item conversation-item text-white" data-id="{{ $c->id }}"
                                        data-name="{{ $c->sender_name ? $c->sender_name : $c->sender_number }}">
                                        <a href="javascript:void(0)"
                                            class="d-flex align-items-center justify-content-between text-decoration-none">
                                            <div>
                                                <strong>{{ $c->sender_name ? $c->sender_name : $c->sender_number }}</strong><br>
                                                <div class="last-message-preview text-muted small"></div>
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

                    <!-- Chat Window  --> 
                    <div class="col-md-8 col-lg-9">
                        <div class="card shadow-sm ">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0 text-white" id="chatTitle">Select a conversation</h6>
                            </div>

                            <div class="card-body" id="messages"
                                style="height: calc(100vh - 295px); overflow-y: auto; background:#f9f9f9;">
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
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

   <script>
    const markReadRoute = "{{ route('client.chat.markAsRead', ':id') }}";

    function getTickIcon(status, isSelf = false) {
        switch (status) {
            case 0:                                                                                                                                                                                                                                                                                                                                                                                                                                                       
                return `<i class="bi bi-clock"></i>`;
            case 1:
                return `<i class="bi bi-check"></i>`;
            case 2:
                return `<i class="bi bi-check-all"></i>`;
            case 3:
                return `<i class="bi bi-check-all text-info"></i>`;
            default:
                return '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {

        let currentConversationId = null;

        Pusher.logToConsole = true;

        const pusher = new Pusher("{{ env('PUSHER_APP_KEY') }}", {
            cluster: "{{ env('PUSHER_APP_CLUSTER') }}",
            forceTLS: true
        });

        let pusherChannel = null;

        const fetchMessagesRoute = @json(route('client.chat.messages', ['conversation' => ':id']));
        const sendMessageRoute = @json(route('client.chat.send', ['conversation' => ':id']));

        let fetchMessagesUrl = "";
        let sendMessageUrl = "";

        let isAtBottom = true;

        function checkIfAtBottom() {
            const messagesDiv = document.getElementById('messages');
            return messagesDiv.scrollHeight - messagesDiv.scrollTop === messagesDiv.clientHeight;
        }

        function loadMessages() {
            const messagesDiv = document.getElementById('messages');
            const wasAtBottom = checkIfAtBottom();

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
                            if (m.date_label !== lastDateLabel) {
                                html += `
                                <div class="text-center text-muted small my-3">
                                    <span class="px-3 py-1 rounded-pill bg-light border">${m.date_label}</span>
                                </div>`;
                                lastDateLabel = m.date_label;
                            }

                            let alignClass = m.sender_type === 'self' ? 'text-end' : 'text-start';
                            let bubbleClass = m.sender_type === 'self' ? 'sender' : 'receiver';

                            html += `
                                <div class="mb-2 ${alignClass}" id="message-${m.id}">
                                    <div class="message-bubble ${bubbleClass}">
                                        <div class="message-body">${m.body}</div>
                                        <div class="message-footer">
                                            <span>${m.time ?? ''}</span>
                                            ${m.sender_type === 'self' ? getTickIcon(m.isMessageStatus, true) : ''}
                                        </div>
                                    </div>
                                </div>`;
                        });

                        let activeItem = document.querySelector(
                            `.conversation-item[data-id="${currentConversationId}"] .last-message-preview`
                        );
                        if (activeItem && data.length > 0) {
                            activeItem.textContent = data[data.length - 1].message;
                        }
                    }

                    document.getElementById('messages').innerHTML = html;

                    if (wasAtBottom) {
                        messagesDiv.scrollTop = messagesDiv.scrollHeight;
                    }
                })
                .catch(err => console.error(err));
        }

        document.getElementById('messages').addEventListener('scroll', function() {
            isAtBottom = checkIfAtBottom();
        });

        function selectConversation(item) {
            document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            currentConversationId = item.getAttribute('data-id');

            if (pusherChannel) {
                pusherChannel.unbind_all();
                pusherChannel.unsubscribe();
            }

            pusherChannel = pusher.subscribe(`chat.${currentConversationId}`);

            pusherChannel.bind('message.sent', function(data) {
                console.log('📩 New message received:', data);
                loadMessages();
            });

            let name = item.getAttribute('data-name');

            document.getElementById('chatTitle').innerText = name;

            document.getElementById('message').disabled = false;
            document.querySelector('#chatForm button').disabled = false;

            fetchMessagesUrl = fetchMessagesRoute.replace(':id', currentConversationId);
            sendMessageUrl = sendMessageRoute.replace(':id', currentConversationId);

            loadMessages();

            const markReadUrl = markReadRoute.replace(':id', currentConversationId);

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

                    const activeItem = document.querySelector(
                        `.conversation-item[data-id="${currentConversationId}"] .badge`);
                    if (activeItem) {
                        activeItem.remove();
                    }

                    loadMessages();
                })
                .catch(err => console.error(err));
        }

        document.querySelector('.chat-list').addEventListener('click', function(e) {
            let item = e.target.closest('.conversation-item');
            if (item) selectConversation(item);
        });

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

                    const messageInput = document.getElementById('message');
                    messageInput.value = '';
                    messageInput.focus();

                    // ✅ 24-Hour format for temporary appended message
                    const tempTime = new Date().toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    });

                    const messagesDiv = document.getElementById('messages');
                    const tempMessageHtml = `
                        <div class="mb-2 text-end">
                            <div class="message-bubble sender">
                                <div class="message-body">${data.body}</div>
                                <div class="message-footer">
                                    <span>${data.time ?? tempTime}</span>
                                    <i class="bi bi-clock"></i>
                                </div>
                            </div>
                        </div>
                    `;
                    messagesDiv.insertAdjacentHTML('beforeend', tempMessageHtml);
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;

                    setTimeout(loadMessages, 800);

                })
                .catch(err => console.error(err));
        });

    });

    const pusher = new Pusher("{{ config('broadcasting.connections.pusher.key') }}", {
        cluster: "{{ config('broadcasting.connections.pusher.options.cluster') }}",
        encrypted: true,
    });

    const userConversationIds = @json($conversations->pluck('id'));

    userConversationIds.forEach((id) => {
        const channel = pusher.subscribe(`chat.${id}`);

        channel.bind('App\\Events\\MessageSent', function(data) {
            console.log('📩 New message received:', data);

            if (parseInt(data.conversation_id) === parseInt(currentConversationId)) {
                loadMessages();
            } else {
                const chatItem = document.querySelector(
                    `.conversation-item[data-id="${data.conversation_id}"]`);

                if (chatItem) {
                    let badge = chatItem.querySelector('.badge');
                    if (badge) {
                        badge.textContent = parseInt(badge.textContent) + 1;
                    } else {
                        const span = document.createElement('span');
                        span.classList.add('badge', 'bg-danger', 'rounded-pill');
                        span.textContent = '1';
                        chatItem.querySelector('a').appendChild(span);
                    }
                }
            }

            const chatItem = document.querySelector(
                `.conversation-item[data-id="${data.conversation_id}"]`);
            if (chatItem) {
                let preview = chatItem.querySelector('.last-message-preview');
                if (!preview) {
                    preview = document.createElement('div');
                    preview.classList.add('last-message-preview', 'text-muted', 'small');
                    chatItem.querySelector('a div').appendChild(preview);
                }
                preview.textContent = data.body;
            }
        });

        channel.bind('App\\Events\\MessageRead', function(data) {
            console.log('✅ Messages marked as read:', data);
            data.message_ids.forEach(id => {
                const tick = document.querySelector(`#message-${id} .bi`);
                if (tick) {
                    tick.classList.remove('bi-check', 'bi-check-all', 'text-info');
                    tick.classList.add('bi-check-all', 'text-info');
                }
            });
        });

    });
</script>

@endsection
