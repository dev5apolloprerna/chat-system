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
                                    <li class="list-group-item conversation-item text-white" data-id="{{ $c->id }}" data-conversation-id="{{ $c->conversation_id }}" data-peer="{{ $c->sender_number }}"
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
document.addEventListener('DOMContentLoaded', () => {
    const routes = {
        list: @json(route('client.chat.index')),
        messages: @json(route('client.chat.messages', ['conversation' => ':id'])),
        send: @json(route('client.chat.send', ['conversation' => ':id']))
    };
    const list = document.querySelector('.chat-list');
    const panel = document.getElementById('messages');
    const input = document.getElementById('message');
    const button = document.querySelector('#chatForm button');
    const csrf = document.querySelector('input[name=_token]').value;
    const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    let selected = null, channel = null, sending = false, loading = false;
    const key = @json(config('broadcasting.connections.pusher.key'));
    const pusher = key && window.Pusher ? new Pusher(key, {
        cluster: @json(config('broadcasting.connections.pusher.options.cluster')), forceTLS: true
    }) : null;
    async function request(url, options = {}) {
        const response = await fetch(url, {...options, headers: {
            'Accept': 'application/json', 'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf, ...(options.headers || {})
        }});
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || data.message || 'Request failed');
        return data;
    }
    async function loadMessages() {
        if (!selected || loading) return;
        loading = true;
        const id = selected.id;
        try {
            const messages = await request(routes.messages.replace(':id', id));
            if (!selected || selected.id !== id) return;
            const atBottom = panel.scrollHeight - panel.scrollTop - panel.clientHeight < 40;
            panel.innerHTML = messages.map(m => `<div class="mb-2 ${m.sender_type === 'self' ? 'text-end' : 'text-start'}" id="message-${escape(m.id)}">
                <div class="message-bubble ${m.sender_type === 'self' ? 'sender' : 'receiver'}">
                    <div class="message-body">${escape(m.body)}</div>
                    <div class="message-footer"><span>${escape(m.time)}</span>${m.sender_type === 'self' ?
                        `<i class="bi ${Number(m.isMessageStatus) >= 2 ? 'bi-check-all' : 'bi-check'} ${Number(m.isMessageStatus) === 3 ? 'text-info' : ''}"></i>` : ''}</div>
                </div></div>`).join('') || '<p class="text-center text-muted">No messages yet</p>';
            if (atBottom) panel.scrollTop = panel.scrollHeight;
        } catch (error) { console.error(error); }
        finally { loading = false; }
    }
    function select(item) {
        const conversationId = item.dataset.conversationId;
        if (pusher && channel) pusher.unsubscribe(channel);
        selected = {id: item.dataset.id, peer: item.dataset.peer};
        list.querySelectorAll('.conversation-item').forEach(el => el.classList.toggle('active', el === item));
        document.getElementById('chatTitle').textContent = item.dataset.name;
        input.disabled = false;
        button.disabled = sending;
        if (pusher) {
            channel = `chat.${conversationId}`;
            const subscription = pusher.subscribe(channel);
            subscription.bind('message.sent', loadMessages);
            subscription.bind('App\\Events\\MessageRead', loadMessages);
        }
        loadMessages();
    }
    async function refreshChats() {
        try {
            const chats = await request(routes.list);
            for (const chat of chats) {
                let item = Array.from(list.children).find(el => el.dataset.peer === chat.sender_number);
                if (!item) {
                    item = document.createElement('li');
                    item.className = 'list-group-item conversation-item';
                    list.appendChild(item);
                }
                Object.assign(item.dataset, {id: String(chat.id), conversationId: String(chat.conversation_id),
                    peer: chat.sender_number, name: chat.sender_name || chat.sender_number});
                item.innerHTML = `<a href="#" class="d-flex justify-content-between"><strong>${escape(item.dataset.name)}</strong>
                    ${chat.unread_count ? `<span class="badge bg-danger rounded-pill">${Number(chat.unread_count)}</span>` : ''}</a>`;
            }
        } catch (error) { console.error(error); }
    }
    list.addEventListener('click', e => {
        const item = e.target.closest('.conversation-item');
        if (item) { e.preventDefault(); select(item); }
    });
    const first = list.querySelector('.conversation-item');
    if (first) select(first);
    setInterval(() => { loadMessages(); refreshChats(); }, 3000);
    document.getElementById('chatForm').addEventListener('submit', async e => {
        e.preventDefault();
        if (!selected || sending || !input.value.trim()) return;
        const id = selected.id, text = input.value.trim();
        sending = true; button.disabled = true;
        try {
            await request(routes.send.replace(':id', id), {method: 'POST', body: JSON.stringify({message: text})});
            if (selected.id === id && input.value.trim() === text) input.value = '';
            await loadMessages();
            panel.scrollTop = panel.scrollHeight;
            await refreshChats();
        } catch (error) { window.alert(error.message); }
        finally { sending = false; button.disabled = false; }
    });
});
</script>
@endsection
