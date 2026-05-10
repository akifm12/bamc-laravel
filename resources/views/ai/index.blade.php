@extends('layouts.app')

@section('title', 'AI Financial Analyst')

@section('content')

<div class="max-w-3xl">

    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
        <p class="text-sm text-gray-500">Ask any question about your financial data and get instant AI-powered insights.</p>
    </div>

    <!-- Chat history -->
    <div id="chat-history" class="space-y-4 mb-4"></div>

    <!-- Example questions -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-4">
        <p class="text-xs text-gray-400 mb-2 font-semibold uppercase">Example Questions</p>
        <div class="flex flex-wrap gap-2">
            @foreach([
                'What is my net profit this year?',
                'How much do customers owe me?',
                'What are my biggest expenses?',
                'Is my balance sheet balanced?',
                'How much do I owe vendors?',
                'Give me a financial health summary',
                'What is my current ratio?',
                'Am I profitable this year?',
            ] as $example)
            <button onclick="askQuestion('{{ $example }}')"
                class="text-xs bg-gray-100 text-gray-600 px-3 py-1.5 rounded-full hover:bg-green-50 hover:text-green-700 transition">
                {{ $example }}
            </button>
            @endforeach
        </div>
    </div>

    <!-- Input -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex gap-3">
            <input type="text" id="question-input" placeholder="Ask about your finances..."
                class="flex-1 border border-gray-200 rounded px-3 py-2 text-sm focus:outline-none focus:border-green-400"
                onkeydown="if(event.key==='Enter') sendQuestion()">
            <button onclick="sendQuestion()" id="send-btn"
                class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
                Ask →
            </button>
        </div>
    </div>

</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
    || '{{ csrf_token() }}';

function askQuestion(text) {
    document.getElementById('question-input').value = text;
    sendQuestion();
}

async function sendQuestion() {
    const input   = document.getElementById('question-input');
    const btn     = document.getElementById('send-btn');
    const history = document.getElementById('chat-history');
    const question = input.value.trim();
    if (!question) return;

    // Show user message
    history.innerHTML += `
        <div class="flex justify-end">
            <div class="bg-green-700 text-white text-sm px-4 py-2 rounded-lg max-w-lg">
                ${escapeHtml(question)}
            </div>
        </div>`;

    // Show loading
    const loadingId = 'loading-' + Date.now();
    history.innerHTML += `
        <div id="${loadingId}" class="flex justify-start">
            <div class="bg-gray-100 text-gray-500 text-sm px-4 py-2 rounded-lg">
                🤖 Thinking...
            </div>
        </div>`;

    history.scrollTop = history.scrollHeight;
    input.value = '';
    btn.disabled = true;
    btn.textContent = '...';

    try {
        const res = await fetch('/ai/query', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ question }),
        });

        const data = await res.json();
        document.getElementById(loadingId).remove();

        history.innerHTML += `
            <div class="flex justify-start">
                <div class="bg-white border border-gray-200 text-gray-800 text-sm px-4 py-3 rounded-lg max-w-2xl whitespace-pre-wrap">
                    🤖 ${escapeHtml(data.answer)}
                </div>
            </div>`;

    } catch (e) {
        document.getElementById(loadingId).remove();
        history.innerHTML += `
            <div class="flex justify-start">
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-2 rounded-lg">
                    Error: Could not get a response.
                </div>
            </div>`;
    }

    btn.disabled = false;
    btn.textContent = 'Ask →';
    history.scrollTop = history.scrollHeight;
}

function escapeHtml(text) {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>

@endsection