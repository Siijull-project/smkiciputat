<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\GalleryPhoto;
use App\Models\NewsArticle;
use App\Models\PrincipalWelcome;
use App\Models\Program;
use App\Models\Teacher;
use App\Support\SiteConfiguration;
use App\Support\TeacherPhotoFocus;
use App\Support\VideoSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(TeacherPhotoFocus $photoFocus, SiteConfiguration $configuration): View
    {
        $programs = Program::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $teachers = Teacher::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $photoFocus->decorate($teachers);
        $principalWelcomes = PrincipalWelcome::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $positionedNews = NewsArticle::query()
            ->published()
            ->whereIn('metadata->homepage_group', ['featured', 'carousel', 'below_carousel', 'sidebar'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();
        $recentNews = NewsArticle::query()
            ->published()
            ->where(function (Builder $query): void {
                $query->whereNull('metadata->homepage_group')
                    ->orWhereNotIn('metadata->homepage_group', [
                        'featured',
                        'carousel',
                        'below_carousel',
                        'sidebar',
                        'news_only',
                    ]);
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(max(0, 14 - $positionedNews->count()))
            ->get();
        $news = $positionedNews->concat($recentNews)->unique('id')->values();
        $mainArticle = $news->first(fn (NewsArticle $article): bool => data_get($article->metadata, 'homepage_group') === 'featured');
        $newsWithoutMain = $news
            ->reject(fn (NewsArticle $article): bool => $mainArticle !== null && $article->is($mainArticle))
            ->values();
        $carouselNews = $newsWithoutMain
            ->filter(fn (NewsArticle $article): bool => data_get($article->metadata, 'homepage_group') === 'carousel')
            ->values();
        $belowCarouselNews = $newsWithoutMain
            ->filter(fn (NewsArticle $article): bool => data_get($article->metadata, 'homepage_group') === 'below_carousel')
            ->take(6)
            ->values();
        $automaticNews = $newsWithoutMain
            ->reject(fn (NewsArticle $article): bool => in_array(
                data_get($article->metadata, 'homepage_group'),
                ['carousel', 'below_carousel', 'sidebar', 'news_only'],
                true,
            ))
            ->sortByDesc(fn (NewsArticle $article): int => $article->published_at?->getTimestamp() ?? 0)
            ->values();

        $featuredNews = $mainArticle === null ? collect() : collect([$mainArticle]);
        $carouselNews = $this->fillNewsSection(
            $carouselNews,
            $automaticNews,
            max(0, 3 - $featuredNews->count()),
        );
        $belowCarouselNews = $this->fillNewsSection($belowCarouselNews, $automaticNews, 6);
        $sidebarNews = $newsWithoutMain
            ->filter(fn (NewsArticle $article): bool => data_get($article->metadata, 'homepage_group') === 'sidebar')
            ->take(3)
            ->concat(NewsArticle::query()
                ->published()
                ->where(function (Builder $query): void {
                    $query->whereNull('metadata->homepage_group')
                        ->orWhere('metadata->homepage_group', '<>', 'news_only');
                })
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(3)
                ->get(['id', 'title', 'slug', 'image_path', 'published_at', 'metadata']))
            ->unique('id')
            ->take(3)
            ->values();
        $carouselNews = $featuredNews
            ->concat($carouselNews)
            ->concat($automaticNews)
            ->values();
        $newsVideos = $this->newsVideos($configuration);
        $latestActivity = GalleryPhoto::query()
            ->published()
            ->whereNotNull('image_path')
            ->where('image_path', '<>', '')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first(['id', 'title', 'summary', 'image_path', 'published_at'])
            ?? $newsWithoutMain->first(fn (NewsArticle $article): bool => filled($article->image_path))
            ?? $mainArticle;
        $latestActivityUrl = match (true) {
            $latestActivity instanceof NewsArticle => route('news.show', $latestActivity->slug),
            $latestActivity instanceof GalleryPhoto => route('gallery'),
            default => null,
        };

        return view('pages.home', [
            'programs' => $programs,
            'teachers' => $teachers,
            'principalWelcomes' => $principalWelcomes,
            'featuredNews' => $featuredNews,
            'carouselNews' => $carouselNews,
            'belowCarouselNews' => $belowCarouselNews,
            'sidebarNews' => $sidebarNews,
            'newsVideos' => $newsVideos,
            'latestActivity' => $latestActivity,
            'latestActivityUrl' => $latestActivityUrl,
            'showAllNewsLink' => NewsArticle::query()->published()->count() > 6,
        ]);
    }

    /**
     * @return array<int, array{href: string}>
     */
    private function newsVideos(SiteConfiguration $configuration): array
    {
        $settings = $configuration->all();

        return collect([
            $settings['home.news.video_1'] ?? null,
            $settings['home.news.video_2'] ?? null,
        ])
            ->filter(fn (mixed $value): bool => is_string($value) && filled($value))
            ->map(function (string $value) use ($configuration): array {
                $youtubeId = VideoSource::youtubeId($value);

                if ($youtubeId !== null) {
                    return [
                        'href' => 'https://www.youtube.com/watch?v='.$youtubeId,
                    ];
                }

                return [
                    'href' => $configuration->imageUrl($value),
                ];
            })
            ->filter(fn (array $video): bool => filled($video['href']))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, NewsArticle>  $section
     * @param  Collection<int, NewsArticle>  $automaticNews
     * @return Collection<int, NewsArticle>
     */
    private function fillNewsSection(Collection $section, Collection &$automaticNews, int $minimumCount): Collection
    {
        $additionalCount = max(0, $minimumCount - $section->count());
        $section = $section->concat($automaticNews->take($additionalCount))->values();
        $automaticNews = $automaticNews->slice($additionalCount)->values();

        return $section;
    }
}
