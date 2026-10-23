let assistantReplyAudio = null;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('supplifyAssistant', (config = {}) => ({
        open: false,
        soundEnabled: true,
        sending: false,
        loading: false,
        error: null,
        messages: [],
        lastPayload: null,
        restoring: true,
        draft: '',
        liveMessage: '',
        name: config.name || 'Supplify Assistant',
        tagline: config.tagline || '',
        messageUrl: config.messageUrl || '/assistant/message',
        resetUrl: config.resetUrl || '/assistant/reset',
        historyUrl: config.historyUrl || '/assistant/history',
        contactUrl: config.contactUrl || '/pages/contact',
        maxLength: config.maxLength || 500,
        welcomeChoices: config.welcomeChoices || [],

        init() {
            try {
                this.soundEnabled = window.localStorage.getItem('supplify.assistant.sound') !== 'off';
            } catch { /* Storage may be unavailable in private browsing. */ }
            this.restoreHistory();
            this.$watch('draft', () => this.$nextTick(() => this.resizeComposer()));
            this.$watch('open', (value) => {
                if (value) {
                    this.$nextTick(() => this.focusPanel());
                } else {
                    this.$nextTick(() => this.focusLauncher());
                }
            });
        },

        toggleSound() {
            this.soundEnabled = !this.soundEnabled;
            try {
                window.localStorage.setItem('supplify.assistant.sound', this.soundEnabled ? 'on' : 'off');
            } catch { /* Keep the preference for this page even without storage. */ }
            if (this.soundEnabled) this.prepareReplySound();
            this.liveMessage = this.soundEnabled ? 'Reply sound on.' : 'Reply sound muted.';
        },

        prepareReplySound() {
            if (!this.soundEnabled) return;
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                assistantReplyAudio ??= new AudioContext();
                // Unlock only from a user-initiated send/retry/toggle, never on page load.
                if (assistantReplyAudio.state === 'suspended') {
                    assistantReplyAudio.resume().catch(() => {});
                }
            } catch { /* Audio is optional and must never interrupt the conversation. */ }
        },

        playReplySound() {
            if (!this.soundEnabled || assistantReplyAudio?.state !== 'running') return;
            try {
                const context = assistantReplyAudio;
                const start = context.currentTime;
                // A quiet, short two-note chime with soft attack/release; no audio download.
                [660, 880].forEach((frequency, index) => {
                    const oscillator = context.createOscillator();
                    const gain = context.createGain();
                    const at = start + index * 0.11;
                    oscillator.type = 'sine';
                    oscillator.frequency.setValueAtTime(frequency, at);
                    gain.gain.setValueAtTime(0, at);
                    gain.gain.linearRampToValueAtTime(0.035, at + 0.018);
                    gain.gain.exponentialRampToValueAtTime(0.0001, at + 0.22);
                    oscillator.connect(gain);
                    gain.connect(context.destination);
                    oscillator.onended = () => { oscillator.disconnect(); gain.disconnect(); };
                    oscillator.start(at);
                    oscillator.stop(at + 0.24);
                });
            } catch { /* Blocked or unavailable audio remains silent. */ }
        },

        toggle() {
            this.open = !this.open;
            if (this.open && this.messages.length === 0) {
                this.liveMessage = `${this.name} opened.`;
            }
        },

        close() {
            this.open = false;
            this.liveMessage = `${this.name} closed.`;
        },

        focusLauncher() {
            this.$refs.launcher?.focus({ preventScroll: true });
        },

        focusPanel() {
            this.$refs.closeBtn?.focus({ preventScroll: true });
        },

        focusComposer() {
            this.$refs.composer?.focus({ preventScroll: true });
        },

        onKeydown(event) {
            if (event.key === 'Escape' && this.open) {
                event.preventDefault();
                this.close();
            }
        },

        async sendMessage(text = null) {
            if (this.sending || this.restoring) {
                return;
            }

            const message = (text ?? this.draft).trim();
            if (message.length < 2) {
                this.error = 'Enter at least two characters.';
                return;
            }

            this.error = null;
            this.draft = '';
            this.pushLocal('user', message);
            await this.request({ message });
        },

        async sendChoice(choice) {
            if (this.sending || !choice) {
                return;
            }

            this.error = null;
            this.pushLocal('user', choice.label || 'Selected option');
            await this.request({
                choice_id: choice.id || null,
                choice_payload: choice.payload || null,
                message: choice.label || '',
            });
        },

        async welcome(choice) {
            if (choice?.href) {
                window.location.href = choice.href;
                return;
            }
            await this.sendMessage(choice?.message || choice?.label);
        },

        async resetChat() {
            if (this.sending || this.restoring) {
                return;
            }

            this.sending = true;
            this.error = null;
            try {
                await window.axios.post(this.resetUrl);
                this.messages = [];
                this.lastPayload = null;
                this.liveMessage = 'Chat reset.';
                this.$nextTick(() => this.focusComposer());
            } catch (err) {
                this.error = this.readError(err) || 'Could not reset the chat.';
                this.liveMessage = 'Reset failed.';
            } finally {
                this.sending = false;
            }
        },

        async retryLast() {
            if (this.sending || !this.lastPayload) {
                return;
            }
            // Remove trailing assistant error bubble if present.
            if (this.messages.at(-1)?.role === 'assistant' && this.messages.at(-1)?.isError) {
                this.messages.pop();
            }
            await this.request(this.lastPayload);
        },

        async request(payload) {
            this.prepareReplySound();
            this.lastPayload = { ...payload };
            this.sending = true;
            this.loading = true;
            this.error = null;
            this.liveMessage = 'Assistant is responding.';

            try {
                const { data } = await window.axios.post(this.messageUrl, payload);
                if (!data?.ok || !data?.response) {
                    throw new Error(data?.error || 'Unexpected assistant response.');
                }
                this.pushAssistant(data.response);
                this.playReplySound();
                this.liveMessage = 'New assistant reply.';
                this.$nextTick(() => {
                    this.scrollToEnd();
                    this.focusComposer();
                });
            } catch (err) {
                const message = this.readError(err) || 'Something went wrong. You can retry.';
                this.error = message;
                this.messages.push({
                    id: `err-${Date.now()}`,
                    role: 'assistant',
                    text: message,
                    isError: true,
                    response: null,
                });
                this.liveMessage = 'Assistant request failed.';
            } finally {
                this.loading = false;
                this.sending = false;
            }
        },

        pushLocal(role, text) {
            this.messages.push({
                id: `${role}-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
                role,
                text,
                isError: false,
                response: null,
            });
            this.$nextTick(() => this.scrollToEnd());
        },

        pushAssistant(response) {
            this.messages.push({
                id: `assistant-${Date.now()}`,
                role: 'assistant',
                text: response.answer || '',
                isError: false,
                response,
            });
        },

        async restoreHistory() {
            try {
                const { data } = await window.axios.get(this.historyUrl);
                if (data?.ok && Array.isArray(data.messages)) {
                    this.messages = data.messages.slice(-40);
                }
            } catch {
                this.error = 'Could not restore this conversation. Please refresh or reset the chat.';
            } finally {
                this.restoring = false;
                this.$nextTick(() => this.scrollToEnd());
            }
        },

        resizeComposer() {
            const input = this.$refs.composer;
            if (!input) return;
            input.style.height = 'auto';
            input.style.height = `${Math.min(120, Math.max(40, input.scrollHeight))}px`;
            input.style.overflowY = input.scrollHeight > 120 ? 'auto' : 'hidden';
        },

        scrollToEnd() {
            const el = this.$refs.transcript;
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        },

        readError(err) {
            return err?.response?.data?.error
                || err?.response?.data?.message
                || err?.response?.data?.errors?.message?.[0]
                || err?.message
                || null;
        },
    }));
});
