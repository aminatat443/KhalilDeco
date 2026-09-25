@extends('layouts.admin-modal')

@php
    $modalBack = route('admin.campaigns.index');
    $initialTab = in_array(request('type'), ['new_arrivals', 'active_promotions', 'newsletter'], true)
        ? request('type')
        : (request()->filled('segment') ? 'newsletter' : 'new_arrivals');
@endphp

@section('title', 'Nouvelle campagne')

@section('modal-width', 'max-w-4xl')

@section('modal')

<div x-data="{ tab: '{{ $initialTab }}' }">

    <h1 class="pr-10 text-2xl font-semibold text-secondary-shade dark:text-white">Nouvelle campagne</h1>
    <p class="mt-2 text-sm text-grey dark:text-white/40">Choisissez le type de campagne. Les produits des campagnes Nouveautés et Promotions sont détectés automatiquement depuis votre catalogue.</p>

    <p class="mt-4 text-xs font-semibold uppercase tracking-[0.12em] text-secondary-shade dark:text-white/70">
        <i class="fa-solid fa-users mr-1.5 text-primary"></i>Destinataires : {{ $subscribersCount }} abonné{{ $subscribersCount === 1 ? '' : 's' }} actif{{ $subscribersCount === 1 ? '' : 's' }}
    </p>

    <div class="mt-4 inline-flex flex-wrap bg-grey-tint p-1 text-xs font-semibold dark:bg-white/5">
        <button type="button" @click="tab = 'new_arrivals'" :class="tab === 'new_arrivals' ? 'bg-secondary-shade text-white' : 'text-grey hover:text-secondary-shade dark:text-white/50 dark:hover:text-white'" class="px-4 py-2.5 uppercase tracking-[0.08em] transition">Nouveautés</button>
        <button type="button" @click="tab = 'active_promotions'" :class="tab === 'active_promotions' ? 'bg-secondary-shade text-white' : 'text-grey hover:text-secondary-shade dark:text-white/50 dark:hover:text-white'" class="px-4 py-2.5 uppercase tracking-[0.08em] transition">Promotions</button>
        <button type="button" @click="tab = 'newsletter'" :class="tab === 'newsletter' ? 'bg-secondary-shade text-white' : 'text-grey hover:text-secondary-shade dark:text-white/50 dark:hover:text-white'" class="px-4 py-2.5 uppercase tracking-[0.08em] transition">Personnalisée</button>
    </div>

    {{-- NOUVEAUTÉS --}}
    <div x-show="tab === 'new_arrivals'" class="mt-6" x-data="campaignForm('new_arrivals')">
        @include('admin.campaigns.partials.auto-tab', [
            'products' => $newArrivalsProducts,
            'countLabel' => 'produit'.($newArrivalsProducts->count() === 1 ? '' : 's').' détecté'.($newArrivalsProducts->count() === 1 ? '' : 's'),
            'emptyMessage' => 'Aucun produit marqué comme nouveauté dans le catalogue.',
            'template' => $newArrivalsTemplate,
            'sendRoute' => route('admin.campaigns.send-new-arrivals'),
            'kind' => 'new_arrivals',
        ])
    </div>

    {{-- PROMOTIONS --}}
    <div x-show="tab === 'active_promotions'" x-cloak class="mt-6" x-data="campaignForm('active_promotions')">
        @include('admin.campaigns.partials.auto-tab', [
            'products' => $activePromotionsProducts,
            'countLabel' => 'produit'.($activePromotionsProducts->count() === 1 ? '' : 's').' en promotion',
            'emptyMessage' => 'Aucun produit actuellement en promotion dans le catalogue.',
            'template' => $activePromotionsTemplate,
            'sendRoute' => route('admin.campaigns.send-promotions'),
            'kind' => 'active_promotions',
        ])
    </div>

    {{-- PERSONNALISÉE --}}
    <div x-show="tab === 'newsletter'" x-cloak class="mt-6" x-data="campaignForm('newsletter')">
        @include('admin.campaigns.partials.custom-tab')
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('campaignForm', (type) => ({
        type,
        sendMode: 'now',
        scheduledDate: '',
        scheduledTime: '',
        today: new Date().toISOString().slice(0, 10),
        testEmail: '',
        testStatus: null,
        testMessage: '',
        productSearch: '',
        productResults: [],
        selectedProducts: [],
        categoryTree: @json($categoryTree ?? []),
        selectedCategory: '',
        selectedSubcategory: '',
        segment: new URLSearchParams(window.location.search).get('segment') || 'all',
        segmentCategoryId: new URLSearchParams(window.location.search).get('segment_category_id') || '',
        segmentCount: null,

        init() {
            if (this.type === 'newsletter') {
                this.searchProducts();
                this.updateSegmentCount();
            }
        },

        updateSegmentCount() {
            const params = new URLSearchParams({ segment: this.segment });
            if (this.segment === 'category_buyers' && this.segmentCategoryId) {
                params.set('category_id', this.segmentCategoryId);
            }
            if (this.segment === 'category_buyers' && ! this.segmentCategoryId) {
                this.segmentCount = null;
                return;
            }
            fetch('{{ route('admin.segments.count') }}?' + params.toString())
                .then((r) => r.json())
                .then((data) => { this.segmentCount = data.count; })
                .catch(() => { this.segmentCount = null; });
        },

        get subcategoryOptions() {
            const category = this.categoryTree.find((c) => String(c.id) === String(this.selectedCategory));
            return category ? category.children : [];
        },

        onCategoryChange() {
            this.selectedSubcategory = '';
            this.searchProducts();
        },

        fieldValue(ref) {
            return this.$refs[ref] ? this.$refs[ref].value : undefined;
        },

        draftPayload() {
            const payload = {
                campaign_type: this.type,
                subject: this.fieldValue('subject'),
                title: this.fieldValue('title'),
                message: this.fieldValue('message'),
                image_url: this.fieldValue('image_url'),
                button_text: this.fieldValue('button_text'),
                button_url: this.fieldValue('button_url'),
            };

            if (this.type === 'newsletter') {
                payload.product_ids = this.selectedProducts.map((p) => p.id);
            }

            return payload;
        },

        preview() {
            const payload = this.draftPayload();
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('admin.campaigns.preview-draft') }}';
            form.target = '_blank';
            form.style.display = 'none';

            const appendInput = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value ?? '';
                form.appendChild(input);
            };

            appendInput('_token', '{{ csrf_token() }}');
            Object.entries(payload).forEach(([key, value]) => {
                if (key === 'product_ids') {
                    (value || []).forEach((id) => appendInput('product_ids[]', id));
                } else {
                    appendInput(key, value);
                }
            });

            document.body.appendChild(form);
            form.submit();
            form.remove();
        },

        async sendTest() {
            if (! this.testEmail) return;
            this.testStatus = 'sending';

            try {
                const response = await fetch('{{ route('admin.campaigns.send-test') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': window.csrfToken(),
                    },
                    body: JSON.stringify({ ...this.draftPayload(), test_email: this.testEmail }),
                });
                const data = await response.json();
                this.testStatus = response.ok ? 'success' : 'error';
                this.testMessage = data.message || (response.ok ? 'Email test envoyé.' : "Échec de l'envoi test.");
            } catch (e) {
                this.testStatus = 'error';
                this.testMessage = 'Erreur réseau — réessayez.';
            }
        },

        async searchProducts() {
            const params = new URLSearchParams();
            const q = this.productSearch.trim();
            if (q !== '') params.append('q', q);
            if (this.selectedSubcategory) {
                params.append('subcategory_id', this.selectedSubcategory);
            } else if (this.selectedCategory) {
                params.append('category_id', this.selectedCategory);
            }

            try {
                const response = await fetch('{{ route('admin.campaigns.search-products') }}?' + params.toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await response.json();
                const selectedIds = this.selectedProducts.map((p) => p.id);
                this.productResults = (data.products || []).filter((p) => ! selectedIds.includes(p.id));
            } catch (e) {
                this.productResults = [];
            }
        },

        addProduct(product) {
            if (! this.selectedProducts.some((p) => p.id === product.id)) {
                this.selectedProducts.push(product);
            }
            this.productResults = this.productResults.filter((p) => p.id !== product.id);
        },

        removeProduct(id) {
            this.selectedProducts = this.selectedProducts.filter((p) => p.id !== id);
        },
    }));
});
</script>

@endsection
