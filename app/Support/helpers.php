<?php

if (! function_exists('img_url')) {
    /**
     * Redimensionne/recadre une image à la volée pour son contexte d'affichage (miniature liste,
     * carte produit, image principale, slider hero...) via les transformations Cloudinary — sans
     * effet sur une image encore stockée localement (pas de service de transformation disponible
     * dans ce cas, l'URL est renvoyée telle quelle).
     */
    function img_url(?string $url, int $width, ?int $height = null, string $crop = 'fill', ?string $background = null): ?string
    {
        if (! $url || ! str_contains($url, '/image/upload/')) {
            return $url;
        }

        $height ??= $width;

        // g_auto : recadrage centré sur la zone la plus intéressante de l'image plutôt qu'un
        // simple centre géométrique — valide uniquement pour les modes qui recadrent réellement
        // (fill, crop, thumb...) ; Cloudinary renvoie une erreur 400 si on l'associe à "pad" ou
        // "fit", qui eux conservent l'image entière.
        // q_auto/f_auto : compression et format (WebP/AVIF) au mieux selon le navigateur.
        // dpr_auto : netteté sur les écrans haute densité (Retina).
        $gravity = in_array($crop, ['pad', 'fit', 'limit', 'scale'], true) ? '' : ',g_auto';
        $transform = "w_{$width},h_{$height},c_{$crop}{$gravity},q_auto,f_auto,dpr_auto";

        if ($background === 'auto') {
            // Couleur dominante de l'image plutôt qu'un fond blanc imposé — chaque photo garde
            // un fond cohérent avec elle-même au lieu d'un bandeau blanc systématique.
            $transform .= ',b_auto:predominant';
        } elseif ($background) {
            $transform .= ",b_rgb:{$background}";
        }

        return str_replace('/image/upload/', "/image/upload/{$transform}/", $url);
    }
}

if (! function_exists('email_track_pixel')) {
    /**
     * URL du pixel de suivi d'ouverture à intégrer en fin de template email — `null` si l'email
     * n'est pas rattaché à un CampaignSend (emails transactionnels, hors suivi).
     */
    function email_track_pixel(?int $campaignSendId): ?string
    {
        if (! $campaignSendId) {
            return null;
        }

        return \Illuminate\Support\Facades\URL::signedRoute('email.track.open', ['send' => $campaignSendId]);
    }
}

if (! function_exists('email_track_click')) {
    /**
     * Fait passer un lien d'email de campagne par le suivi de clic avant sa destination réelle —
     * renvoie l'URL d'origine telle quelle si l'email n'est pas rattaché à un CampaignSend.
     */
    function email_track_click(?int $campaignSendId, string $url): string
    {
        if (! $campaignSendId) {
            return $url;
        }

        return \Illuminate\Support\Facades\URL::signedRoute('email.track.click', ['send' => $campaignSendId, 'url' => $url]);
    }
}
