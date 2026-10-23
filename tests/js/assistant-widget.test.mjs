import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import { readFileSync } from 'node:fs';

function widget(axios, browser = {}) {
    let factory;
    vm.runInNewContext(readFileSync('resources/js/assistant-widget.js', 'utf8'), {
        document: { addEventListener: (_, cb) => cb() },
        window: { ...browser, axios, Alpine: { data: (_, fn) => { factory = fn; } } },
    });
    const chat = factory({ historyUrl: '/custom/history' });
    chat.$nextTick = (fn) => fn();
    chat.$refs = {};
    chat.restoring = false;
    return chat;
}

test('retry preserves the product action and payload', async () => {
    const calls = [];
    const chat = widget({ post: async (url, payload) => {
        calls.push(JSON.parse(JSON.stringify(payload)));
        if (calls.length === 1) throw new Error('Connection interrupted');
        return { data: { ok: true, response: { answer: 'PKR 2,800.00' } } };
    } });
    await chat.sendChoice({ id: 'price', payload: '20', label: 'What is its price?' });
    assert.equal(chat.error, 'Connection interrupted');
    await chat.retryLast();
    assert.deepEqual(calls[1], calls[0]);
    assert.equal(chat.messages.filter((m) => m.role === 'user').length, 1);
    assert.equal(chat.messages.at(-1).text, 'PKR 2,800.00');
    assert.equal(chat.sending, false);
});

test('history restores from configured route and reset clears it', async () => {
    const chat = widget({
        get: async (url) => { assert.equal(url, '/custom/history'); return { data: { ok: true, messages: [{ role: 'user', text: 'CoQ10' }] } }; },
        post: async () => ({ data: { ok: true } }),
    });
    await chat.restoreHistory();
    assert.equal(chat.messages[0].text, 'CoQ10');
    assert.equal(chat.restoring, false);
    await chat.resetChat();
    assert.equal(chat.messages.length, 0);
});

test('double submission sends one request while the first is pending', async () => {
    let finish;
    let calls = 0;
    const chat = widget({ post: () => { calls++; return new Promise((resolve) => { finish = resolve; }); } });
    const first = chat.sendMessage('CoQ10');
    await chat.sendMessage('CoQ10');
    assert.equal(calls, 1);
    finish({ data: { ok: true, response: { answer: 'Found CoQ10' } } });
    await first;
    assert.equal(chat.sending, false);
});


test('reply chime sounds once per successful answer, never for history, errors or muted replies', async () => {
    let notes = 0;
    let contexts = 0;
    let fail = false;
    const saved = new Map();
    class AudioContext {
        constructor() { contexts++; this.state = 'running'; this.currentTime = 0; this.destination = {}; }
        createOscillator() { return { frequency: { setValueAtTime() {} }, connect() {}, disconnect() {}, start() { notes++; }, stop() {} }; }
        createGain() { return { gain: { setValueAtTime() {}, linearRampToValueAtTime() {}, exponentialRampToValueAtTime() {} }, connect() {}, disconnect() {} }; }
    }
    const chat = widget({
        get: async () => ({ data: { ok: true, messages: [{ role: 'assistant', text: 'Old reply' }] } }),
        post: async () => { if (fail) throw new Error('Offline'); return { data: { ok: true, response: { answer: 'New reply' } } }; },
    }, { AudioContext, localStorage: { getItem: (key) => saved.get(key), setItem: (key, value) => saved.set(key, value) } });
    await chat.restoreHistory();
    assert.equal(contexts, 0);
    await chat.sendMessage('hello');
    assert.equal(notes, 2, 'one two-note chime per answer');
    fail = true;
    await chat.sendMessage('another question');
    assert.equal(notes, 2);
    chat.toggleSound();
    assert.equal(saved.get('supplify.assistant.sound'), 'off');
    fail = false;
    await chat.sendMessage('muted reply');
    assert.equal(notes, 2);
    chat.toggleSound();
    await chat.sendMessage('sound enabled');
    assert.equal(notes, 4);
    assert.equal(contexts, 1);
});

test('blocked audio never prevents a successful chat reply', async () => {
    const chat = widget({ post: async () => ({ data: { ok: true, response: { answer: 'Still works' } } }) }, {
        AudioContext: class { constructor() { throw new Error('Audio blocked'); } },
    });
    await chat.sendMessage('hello');
    assert.equal(chat.messages.at(-1).text, 'Still works');
    assert.equal(chat.error, null);
});
