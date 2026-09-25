<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    public const ABANDONED_CART = 'abandoned_cart';

    public const LOW_STOCK_FAVORITE = 'low_stock_favorite';

    public const PROMOTION = 'promotion';

    public const ACTIVE_PROMOTIONS = 'active_promotions';

    public const NEW_ARRIVALS = 'new_arrivals';

    public const KEYS = [
        self::ABANDONED_CART => 'Panier abandonné',
        self::LOW_STOCK_FAVORITE => 'Favoris / stock faible',
        self::PROMOTION => 'Promotion (produit favori)',
        self::ACTIVE_PROMOTIONS => 'Promotions (catalogue)',
        self::NEW_ARRIVALS => 'Nouveautés',
    ];

    protected $fillable = [
        'key',
        'subject',
        'title',
        'content',
        'button_text',
        'image_url',
    ];

    public static function findByKey(string $key): self
    {
        return static::where('key', $key)->firstOrFail();
    }

    /**
     * Remplace les variables ([Prénom], [Produit]…) par leur valeur réelle dans le sujet,
     * le titre et le contenu — seul point du système qui connaît la syntaxe `[Variable]`
     * (section 7 du cahier des charges).
     *
     * @param  array<string, string>  $variables  Clé sans crochets (ex. "Prénom") => valeur.
     * @return array{subject: string, title: ?string, content: string}
     */
    public function render(array $variables): array
    {
        return [
            'subject' => self::substitute($this->subject, $variables),
            'title' => self::substitute($this->title, $variables),
            'content' => self::substitute($this->content, $variables),
        ];
    }

    /**
     * Même substitution `[Variable]`, mais applicable à un texte libre qui ne vient pas d'un
     * modèle enregistré — utilisé par la campagne "Personnalisée", dont le contenu est saisi
     * directement par l'administrateur (pas de ligne `email_templates` associée).
     *
     * @param  array<string, string>  $variables
     */
    public static function substitute(?string $text, array $variables): ?string
    {
        if ($text === null) {
            return null;
        }

        $search = array_map(fn ($key) => '['.$key.']', array_keys($variables));
        $replace = array_values($variables);

        return str_replace($search, $replace, $text);
    }
}
