<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * Modération des avis clients (section 39 du cahier des charges).
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Review::class);

        $search = function ($query) use ($request) {
            $term = '%'.$request->input('q').'%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('user', fn ($q) => $q->where('name', 'ilike', $term))
                    ->orWhereHas('product', fn ($q) => $q->where('name', 'ilike', $term));
            });
        };

        $data = [
            'pending' => Review::with(['user', 'product'])
                ->where('is_approved', false)
                ->when($request->filled('q'), $search)
                ->latest()
                ->paginate(15, ['*'], 'pending_page')
                ->withQueryString(),
            'approved' => Review::with(['user', 'product'])
                ->where('is_approved', true)
                ->when($request->filled('q'), $search)
                ->latest()
                ->paginate(15, ['*'], 'approved_page')
                ->withQueryString(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.reviews.partials.lists', $data)->render(),
            ]);
        }

        $ratingCounts = Review::where('is_approved', true)
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        return view('admin.reviews.index', $data + ['ratingCounts' => $ratingCounts]);
    }

    public function approve(Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $review->update(['is_approved' => true]);

        return back()->with('status', 'Avis publié.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        return back()->with('status', 'Avis supprimé.');
    }

    public function reply(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('reply', $review);

        $data = $request->validate([
            'admin_reply' => ['nullable', 'string', 'max:2000'],
        ]);

        $reply = trim($data['admin_reply'] ?? '');

        $review->update([
            'admin_reply' => $reply !== '' ? $reply : null,
            'admin_replied_at' => $reply !== '' ? now() : null,
        ]);

        return back()->with('status', $reply !== '' ? 'Réponse publiée.' : 'Réponse supprimée.');
    }
}
