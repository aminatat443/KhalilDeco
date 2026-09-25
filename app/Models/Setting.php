<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'shop_name',
        'shop_address',
        'shop_phone',
        'shop_email',
        'ninea',
        'rccm',
        'invoice_logo',
        'invoice_signature',
        'abandoned_cart_enabled',
        'abandoned_cart_delay_days',
        'low_stock_favorite_enabled',
        'low_stock_threshold',
        'promotion_favorite_enabled',
        'newsletter_enabled',
    ];

    protected function casts(): array
    {
        return [
            'abandoned_cart_enabled' => 'boolean',
            'low_stock_favorite_enabled' => 'boolean',
            'promotion_favorite_enabled' => 'boolean',
            'newsletter_enabled' => 'boolean',
        ];
    }

    /**
     * Configuration en ligne unique (logo/signature de facture, coordonnées de la boutique) —
     * un seul enregistrement pour tout le site plutôt qu'un système de clé/valeur générique,
     * puisque ces quelques champs ne varient pas dans le temps.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], ['shop_name' => 'Khalil Déco']);
    }

    /**
     * Disque de stockage des images produit/facture : Cloudinary dès que ses identifiants sont
     * renseignés dans .env (CLOUDINARY_URL), stockage local sur le disque `public` en attendant
     * (section 49 du cahier des charges).
     */
    public static function mediaDisk(): string
    {
        $url = config('cloudinary.cloud_url');

        return $url && $url !== 'cloudinary://' ? 'cloudinary' : 'public';
    }

    /**
     * URL affichable dans un navigateur (aperçu admin) pour l'un des champs image.
     */
    public function mediaUrl(string $field): ?string
    {
        if (! $this->$field) {
            return null;
        }

        // Si le disque a changé (local → Cloudinary) depuis cet upload, le chemin enregistré
        // n'existe plus dessus : on masque l'aperçu plutôt que de faire planter la page.
        try {
            return \Illuminate\Support\Facades\Storage::disk(self::mediaDisk())->url($this->$field);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Image encodée en base64 pour la facture PDF : DomPDF n'a pas besoin d'aller chercher le
     * fichier à distance (Cloudinary) ni de connaître un chemin disque local, ce qui fonctionne
     * quel que soit le disque de stockage actif.
     */
    public function mediaDataUri(string $field): ?string
    {
        if (! $this->$field) {
            return null;
        }

        try {
            $disk = \Illuminate\Support\Facades\Storage::disk(self::mediaDisk());
            $contents = $disk->get($this->$field);
            $mime = $disk->mimeType($this->$field) ?: 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode($contents);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Logo de facture affiché à défaut d'un logo personnalisé uploadé (ci-dessus) : le logo
     * officiel Khalil Déco embarqué dans l'app, encodé en base64 pour DomPDF.
     */
    public function invoiceLogoDataUri(): ?string
    {
        return $this->mediaDataUri('invoice_logo') ?? self::defaultLogoDataUri();
    }

    public static function defaultLogoDataUri(): ?string
    {
        $path = public_path('images/logo_khalil_deco_full.png');

        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($path));
    }
}
