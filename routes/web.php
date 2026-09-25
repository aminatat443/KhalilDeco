<?php

use App\Http\Controllers\Admin\AttributeController as AdminAttributeController;
use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DeliveryController as AdminDeliveryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FinanceController as AdminFinanceController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductImageController as AdminProductImageController;
use App\Http\Controllers\Admin\ColorController as AdminColorController;
use App\Http\Controllers\Admin\ProductVariantController as AdminProductVariantController;
use App\Http\Controllers\Admin\ProformaInvoiceController as AdminProformaInvoiceController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\ReturnController as AdminReturnController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\CampaignAutomationController as AdminCampaignAutomationController;
use App\Http\Controllers\Admin\CampaignController as AdminCampaignController;
use App\Http\Controllers\Admin\CampaignSendController as AdminCampaignSendController;
use App\Http\Controllers\Admin\EmailTemplateController as AdminEmailTemplateController;
use App\Http\Controllers\Admin\SecurityController as AdminSecurityController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\CashSessionController as AdminCashSessionController;
use App\Http\Controllers\Admin\CustomerSegmentController as AdminCustomerSegmentController;
use App\Http\Controllers\Admin\ExpenseController as AdminExpenseController;
use App\Http\Controllers\Admin\FinanceReportController as AdminFinanceReportController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PaymentReconciliationController as AdminPaymentReconciliationController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SearchController as AdminSearchController;
use App\Http\Controllers\Admin\StockMovementController as AdminStockMovementController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailTrackingController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OrangeMoneyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayDunyaController;
use App\Http\Controllers\PayTechController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\WaveController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::post('/connexion', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login');
Route::post('/inscription', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register');
Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout');

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware('signed')->name('verification.verify');
Route::post('/email/renvoyer', [AuthController::class, 'resendVerification'])->middleware('throttle:3,1')->name('verification.resend');

Route::middleware('auth')->prefix('mon-compte')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::post('/adresse', [AccountController::class, 'updateAddress'])->name('address.update');
    Route::delete('/', [AccountController::class, 'destroy'])->name('destroy');
    Route::get('/commandes', [AccountController::class, 'orders'])->name('orders');
    Route::get('/commandes/{order}', [AccountController::class, 'orderShow'])->name('orders.show');
    Route::get('/retours', [ReturnController::class, 'index'])->name('returns');
    Route::post('/retours', [ReturnController::class, 'store'])->name('returns.store');
    Route::post('/commandes/{order}/retour', [ReturnController::class, 'storeForOrder'])->name('orders.return');
});

Route::get('/recherche', [SearchController::class, 'index'])->name('search');
Route::get('/recherche/suggestions', [SearchController::class, 'suggest'])->name('search.suggestions');

Route::post('/assistant/message', [AssistantController::class, 'message'])->middleware('throttle:15,1')->name('assistant.message');

Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/{id}/lu', [NotificationController::class, 'markRead'])->name('read');
    Route::post('/tout-lire', [NotificationController::class, 'markAllRead'])->name('read-all');
});

Route::get('/panier', [CartController::class, 'index'])->name('cart.index');
Route::post('/panier/ajouter', [CartController::class, 'store'])->name('cart.add');
Route::post('/panier/variante', [CartController::class, 'assignVariant'])->name('cart.assignVariant');
Route::post('/panier/retirer', [CartController::class, 'destroy'])->name('cart.remove');

// Favoris — invités en localStorage uniquement ; synchronisation serveur réservée aux clients
// connectés (nécessaire pour les alertes stock faible/promotion par email).
Route::middleware('auth')->prefix('favoris')->name('favorites.')->group(function () {
    Route::post('/', [FavoriteController::class, 'store'])->name('store');
    Route::post('/synchroniser', [FavoriteController::class, 'sync'])->name('sync');
});

Route::get('/commande', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/commande', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/commande/{order}/confirmation', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

Route::get('/payment/retour/{order?}', [PayTechController::class, 'success'])->name('paytech.success');
Route::get('/payment/annulation/{order?}', [PayTechController::class, 'cancel'])->name('paytech.cancel');
Route::post('/payment/ipn', [PayTechController::class, 'ipn'])->name('paytech.ipn');

Route::get('/payment/wave/retour/{order?}', [WaveController::class, 'success'])->name('wave.success');
Route::get('/payment/wave/annulation/{order?}', [WaveController::class, 'cancel'])->name('wave.cancel');
Route::post('/payment/wave/webhook', [WaveController::class, 'webhook'])->name('wave.webhook');

Route::get('/payment/orange-money/retour/{order?}', [OrangeMoneyController::class, 'success'])->name('orange-money.success');
Route::get('/payment/orange-money/annulation/{order?}', [OrangeMoneyController::class, 'cancel'])->name('orange-money.cancel');
Route::post('/payment/orange-money/notif', [OrangeMoneyController::class, 'notif'])->name('orange-money.notif');

Route::get('/payment/paydunya/retour/{order?}', [PayDunyaController::class, 'success'])->name('paydunya.success');
Route::get('/payment/paydunya/annulation/{order?}', [PayDunyaController::class, 'cancel'])->name('paydunya.cancel');
Route::post('/payment/paydunya/callback', [PayDunyaController::class, 'callback'])->name('paydunya.callback');

Route::match(['get', 'post'], '/payment/{order}/reessayer', [PaymentController::class, 'retry'])->name('payment.retry');

Route::get('/commandes/{order}/facture', [InvoiceController::class, 'show'])->name('orders.invoice');

// Suivi des emails de campagne (ouvertures/clics) — URL signées, publiques par nature (le
// destinataire n'est pas connecté en consultant son email).
Route::get('/email/pixel/{send}.gif', [EmailTrackingController::class, 'pixel'])->name('email.track.open');
Route::get('/email/clic/{send}', [EmailTrackingController::class, 'click'])->name('email.track.click');
Route::get('/factures/proforma/{proforma}', [AdminProformaInvoiceController::class, 'show'])->name('proforma.show');

Route::post('/newsletter/inscription', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/desinscription/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

Route::get('/produit/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/produit/{product}/avis', [ReviewController::class, 'store'])->middleware('auth')->name('products.reviews.store');
Route::delete('/produit/{product}/avis', [ReviewController::class, 'destroy'])->middleware('auth')->name('products.reviews.destroy');

// Back-office (sections 40 à 49 du cahier des charges) — réservé Gestionnaire/Admin/Super Admin
Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('recherche', [AdminSearchController::class, 'index'])->name('search');

    Route::resource('products', AdminProductController::class)->except(['show', 'create']);
    // "Créer" démarre immédiatement un brouillon en base (voir ProductController::create) —
    // une action d'écriture n'a pas sa place derrière une requête GET.
    Route::post('products/create', [AdminProductController::class, 'create'])->name('products.create');
    Route::post('products/{product}/toggle/{flag}', [AdminProductController::class, 'toggleFlag'])->name('products.toggle');
    Route::post('products/{product}/promo', [AdminProductController::class, 'setPromo'])->name('products.promo');
    Route::post('products/{product}/images', [AdminProductImageController::class, 'store'])->name('products.images.store');
    Route::post('products/{product}/images/{image}/primary', [AdminProductImageController::class, 'primary'])->name('products.images.primary');
    Route::post('products/{product}/images/reorder', [AdminProductImageController::class, 'reorder'])->name('products.images.reorder');
    Route::delete('products/{product}/images/{image}', [AdminProductImageController::class, 'destroy'])->name('products.images.destroy');
    Route::post('colors', [AdminColorController::class, 'store'])->name('colors.store');
    Route::post('products/{product}/variants', [AdminProductVariantController::class, 'store'])->name('products.variants.store');
    Route::put('products/{product}/variants/{variant}', [AdminProductVariantController::class, 'update'])->name('products.variants.update');
    Route::delete('products/{product}/variants/{variant}', [AdminProductVariantController::class, 'destroy'])->name('products.variants.destroy');

    Route::resource('categories', AdminCategoryController::class)->except('show');

    Route::get('attributs', [AdminAttributeController::class, 'index'])->name('attributes.index');
    Route::post('attributs', [AdminAttributeController::class, 'store'])->name('attributes.store');
    Route::delete('attributs/{attribute}', [AdminAttributeController::class, 'destroy'])->name('attributes.destroy');
    Route::post('attributs/{attribute}/valeurs', [AdminAttributeController::class, 'storeValue'])->name('attributes.values.store');
    Route::delete('attributs/{attribute}/valeurs/{value}', [AdminAttributeController::class, 'destroyValue'])->name('attributes.values.destroy');

    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/nouvelle', [AdminOrderController::class, 'create'])->name('orders.create');
    Route::get('orders/clients', [AdminOrderController::class, 'searchClients'])->name('orders.clients.search');
    Route::post('orders', [AdminOrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/confirmer', [AdminOrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('orders/{order}/annuler', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/statut', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('orders/{order}/paiements', [AdminPaymentController::class, 'store'])->name('payments.store');

    Route::get('paiements', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::post('paiements/{payment}/verifier', [AdminPaymentController::class, 'verify'])->name('payments.verify');
    Route::post('paiements/{payment}/rembourser', [AdminPaymentController::class, 'refund'])->name('payments.refund');

    Route::resource('promotions', AdminPromotionController::class)->except('show');
    Route::post('promotions/{promotion}/envoyer', [AdminPromotionController::class, 'sendEmail'])->name('promotions.send-email');
    Route::resource('coupons', AdminCouponController::class)->except('show');
    Route::resource('livraison', AdminDeliveryController::class)->except('show')->parameters(['livraison' => 'delivery'])->names('deliveries');
    Route::resource('banners', AdminBannerController::class)->except('show');

    Route::get('reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('reviews/{review}/publier', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('reviews/{review}/repondre', [AdminReviewController::class, 'reply'])->name('reviews.reply');
    Route::delete('reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('retours', [AdminReturnController::class, 'index'])->name('returns.index');
    Route::put('retours/{return}', [AdminReturnController::class, 'update'])->name('returns.update');

    Route::get('utilisateurs', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('utilisateurs/creer', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('utilisateurs', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('utilisateurs/{user}/modifier', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('utilisateurs/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('utilisateurs/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // Administration : rôles et permissions (RBAC)
    Route::get('roles', [AdminRoleController::class, 'index'])->name('roles.index');
    Route::get('roles/creer', [AdminRoleController::class, 'create'])->name('roles.create');
    Route::post('roles', [AdminRoleController::class, 'store'])->name('roles.store');
    Route::get('roles/{role}/modifier', [AdminRoleController::class, 'edit'])->name('roles.edit');
    Route::put('roles/{role}', [AdminRoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [AdminRoleController::class, 'destroy'])->name('roles.destroy');

    // Administration : journal d'activité
    Route::get('journal-activite', [AdminActivityLogController::class, 'index'])->name('activity-logs.index');

    // Stock : entrées / sorties / mouvements
    Route::get('stock', [AdminStockMovementController::class, 'index'])->name('stock.index');
    Route::post('stock', [AdminStockMovementController::class, 'store'])->name('stock.store');

    // Caisse : ouverture / clôture
    Route::get('caisse', [AdminCashSessionController::class, 'index'])->name('cash.index');
    Route::post('caisse/ouvrir', [AdminCashSessionController::class, 'open'])->name('cash.open');
    Route::post('caisse/{session}/cloturer', [AdminCashSessionController::class, 'close'])->name('cash.close');

    Route::get('finances', [AdminFinanceController::class, 'index'])->name('finances.index')->middleware('permission:finances.view');
    Route::get('finances/rapprochement', [AdminPaymentReconciliationController::class, 'index'])->name('finances.reconciliation')->middleware('permission:payments.view_transactions');
    Route::get('finances/rapports', [AdminFinanceReportController::class, 'index'])->name('finances.reports')->middleware('permission:reports.view');
    Route::get('depenses', [AdminExpenseController::class, 'index'])->name('expenses.index');
    Route::post('depenses', [AdminExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('depenses/{expense}', [AdminExpenseController::class, 'destroy'])->name('expenses.destroy');

    Route::get('factures', [AdminInvoiceController::class, 'index'])->name('invoices.index')->middleware('permission:invoices.view');
    Route::get('factures/creer', [AdminInvoiceController::class, 'create'])->name('invoices.create')->middleware('permission:invoices.create');
    Route::get('factures/rechercher-commandes', [AdminInvoiceController::class, 'searchOrders'])->name('invoices.search-orders')->middleware('permission:invoices.view');
    Route::post('factures/proforma', [AdminProformaInvoiceController::class, 'store'])->name('invoices.proforma.store')->middleware('permission:invoices.create');

    Route::get('configuration', [AdminSettingController::class, 'edit'])->name('settings.edit');
    Route::post('configuration', [AdminSettingController::class, 'update'])->name('settings.update');

    Route::get('campagnes', [AdminCampaignController::class, 'index'])->name('campaigns.index');
    Route::get('campagnes/nouvelle', [AdminCampaignController::class, 'create'])->name('campaigns.create');
    Route::post('campagnes', [AdminCampaignController::class, 'store'])->name('campaigns.store');
    Route::post('campagnes/nouveautes', [AdminCampaignController::class, 'sendNewArrivals'])->name('campaigns.send-new-arrivals');
    Route::post('campagnes/promotions', [AdminCampaignController::class, 'sendActivePromotions'])->name('campaigns.send-promotions');
    Route::post('campagnes/apercu', [AdminCampaignController::class, 'previewDraft'])->name('campaigns.preview-draft');
    Route::post('campagnes/test', [AdminCampaignController::class, 'sendTest'])->name('campaigns.send-test');
    Route::get('campagnes/produits/recherche', [AdminCampaignController::class, 'searchProducts'])->name('campaigns.search-products');
    Route::get('campagnes/automatisations', [AdminCampaignAutomationController::class, 'edit'])->name('campaigns.automations.edit');
    Route::put('campagnes/automatisations', [AdminCampaignAutomationController::class, 'update'])->name('campaigns.automations.update');
    Route::get('campagnes/modeles', [AdminEmailTemplateController::class, 'index'])->name('email-templates.index');
    Route::get('campagnes/modeles/{emailTemplate}', [AdminEmailTemplateController::class, 'edit'])->name('email-templates.edit');
    Route::put('campagnes/modeles/{emailTemplate}', [AdminEmailTemplateController::class, 'update'])->name('email-templates.update');
    Route::get('campagnes/modeles/{emailTemplate}/apercu', [AdminEmailTemplateController::class, 'preview'])->name('email-templates.preview');
    Route::post('campagnes/modeles/{emailTemplate}/test', [AdminEmailTemplateController::class, 'sendTest'])->name('email-templates.send-test');
    Route::get('campagnes/historique', [AdminCampaignSendController::class, 'index'])->name('campaign-sends.index');
    Route::get('campagnes/abonnes', [AdminCampaignController::class, 'subscribers'])->name('campaigns.subscribers');
    Route::get('campagnes/segments', [AdminCustomerSegmentController::class, 'index'])->name('segments.index');
    Route::get('campagnes/segments/compte', [AdminCustomerSegmentController::class, 'count'])->name('segments.count');
    Route::get('campagnes/segments/{segment}', [AdminCustomerSegmentController::class, 'show'])->name('segments.show');
    // Route "show" en dernier : {campaign} matcherait sinon les segments littéraux ci-dessus (apercu, test, produits...).
    Route::get('campagnes/{campaign}', [AdminCampaignController::class, 'show'])->name('campaigns.show');

    Route::get('securite', [AdminSecurityController::class, 'index'])->name('security.index');
    Route::post('securite/bloquer', [AdminSecurityController::class, 'block'])->name('security.block');
    Route::delete('securite/{blockedIp}', [AdminSecurityController::class, 'unblock'])->name('security.unblock');
});

// Route catalogue en dernier : {category:slug} matcherait sinon les segments ci-dessus
Route::get('/{category:slug}', [CatalogController::class, 'show'])->name('catalog.show');
