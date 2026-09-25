{{-- Aide flottante (WhatsApp + Assistant IA) — disponible sur tout le site --}}
@php
    $assistantWhatsappDigits = preg_replace('/\D/', '', \App\Models\Setting::current()->shop_phone ?? '');
    if (strlen($assistantWhatsappDigits) === 9) {
        $assistantWhatsappDigits = '221'.$assistantWhatsappDigits;
    }
@endphp
<div
    x-data="{
        menuOpen: false,
        chatOpen: false,
        input: '',
        sending: false,
        messages: [
            { role: 'assistant', content: 'Bonjour ! Je suis l\'assistant Khalil Déco. Posez-moi une question sur la livraison, les retours, le paiement ou votre compte.' },
        ],
        toggleMain() {
            if (this.chatOpen) { this.chatOpen = false; return; }
            this.menuOpen = ! this.menuOpen;
        },
        openChat() {
            this.chatOpen = true;
            this.menuOpen = false;
            this.$nextTick(() => this.scrollToBottom());
        },
        async send() {
            const text = this.input.trim();
            if (! text || this.sending) return;

            this.messages.push({ role: 'user', content: text });
            this.input = '';
            this.sending = true;
            this.$nextTick(() => this.scrollToBottom());

            try {
                const response = await fetch('{{ route('assistant.message') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-XSRF-TOKEN': window.csrfToken(),
                    },
                    body: JSON.stringify({ messages: this.messages }),
                });
                const data = await response.json();
                this.messages.push({ role: 'assistant', content: data.reply });
            } catch (e) {
                this.messages.push({ role: 'assistant', content: 'Une erreur est survenue. Réessayez.' });
            } finally {
                this.sending = false;
                this.$nextTick(() => this.scrollToBottom());
            }
        },
        scrollToBottom() {
            if (this.$refs.scrollArea) this.$refs.scrollArea.scrollTop = this.$refs.scrollArea.scrollHeight;
        },
    }"
    @click.outside="menuOpen = false"
    class="fixed bottom-6 right-6 z-[80]"
>
    {{-- Options (WhatsApp + Assistant) --}}
    <div
        x-show="menuOpen"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="absolute bottom-[4.5rem] right-0 flex flex-col items-end gap-3"
    >
        <a
            href="https://wa.me/{{ $assistantWhatsappDigits }}"
            target="_blank"
            rel="noopener"
            @click="menuOpen = false"
            class="group flex items-center gap-3"
        >
            <span class="bg-white px-3 py-1.5 text-xs font-medium text-secondary-shade shadow-md">WhatsApp</span>
            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-[#25D366] text-lg text-white shadow-lg transition group-hover:scale-105">
                <i class="fa-brands fa-whatsapp"></i>
            </span>
        </a>

        <button type="button" @click="openChat()" class="group flex items-center gap-3">
            <span class="bg-white px-3 py-1.5 text-xs font-medium text-secondary-shade shadow-md">Assistant</span>
            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-secondary-shade text-lg text-white shadow-lg transition group-hover:scale-105">
                <i class="fa-solid fa-comment-dots"></i>
            </span>
        </button>
    </div>

    {{-- Bouton principal --}}
    <button
        type="button"
        @click="toggleMain()"
        aria-label="Aide"
        class="flex h-14 w-14 items-center justify-center rounded-full bg-primary text-xl text-white shadow-lg transition hover:bg-primary-shade"
    >
        <i class="fa-solid" :class="(menuOpen || chatOpen) ? 'fa-xmark' : 'fa-circle-question'"></i>
    </button>

    {{-- Fenêtre de chat --}}
    <div
        x-show="chatOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-x-4 bottom-24 top-36 z-[80] flex min-h-0 flex-col border border-secondary-shade/10 bg-white shadow-2xl sm:absolute sm:inset-x-auto sm:inset-y-auto sm:bottom-[4.5rem] sm:right-0 sm:top-auto sm:z-auto sm:h-[28rem] sm:w-[22rem] sm:max-w-[calc(100vw-3rem)]"
    >
        <div class="flex shrink-0 items-center justify-between border-b border-secondary-shade/10 bg-secondary-shade px-5 py-4">
            <p class="text-sm font-medium text-white">Assistant Khalil Déco</p>
            <button type="button" @click="chatOpen = false" class="text-white/70 transition hover:text-white" aria-label="Fermer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div x-ref="scrollArea" class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-4">
            <template x-for="(message, index) in messages" :key="index">
                <div :class="message.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <p
                        :class="message.role === 'user' ? 'bg-secondary-shade text-white' : 'bg-grey-tint text-secondary-shade'"
                        class="max-w-[85%] whitespace-pre-line px-4 py-2.5 text-sm"
                        x-text="message.content"
                    ></p>
                </div>
            </template>

            <template x-if="sending">
                <div class="flex justify-start">
                    <p class="bg-grey-tint px-4 py-2.5 text-sm text-grey">
                        <i class="fa-solid fa-ellipsis fa-fade"></i>
                    </p>
                </div>
            </template>
        </div>

        <form @submit.prevent="send()" class="flex shrink-0 items-center gap-2 border-t border-secondary-shade/10 p-3">
            <input
                type="text"
                x-model="input"
                placeholder="Posez votre question…"
                :disabled="sending"
                class="w-full border-b border-secondary-shade/20 bg-transparent px-1 py-2 text-sm text-secondary-shade outline-none placeholder:text-grey/50 focus:border-primary disabled:opacity-50"
            >
            <button
                type="submit"
                :disabled="sending || ! input.trim()"
                class="flex h-9 w-9 shrink-0 items-center justify-center bg-secondary-shade text-white transition hover:bg-primary disabled:opacity-40"
                aria-label="Envoyer"
            >
                <i class="fa-solid fa-arrow-up text-xs"></i>
            </button>
        </form>
    </div>
</div>
