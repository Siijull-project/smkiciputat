@extends('layouts.app')

@section('title', $article->meta_title ?: $article->title.' | '.$siteSettings['identity.school_name'])
@section('meta_description', $article->meta_description ?: $article->summary)
@section('meta_image', $article->image_path ? $siteConfiguration->imageUrl($article->image_path) : '')
@section('meta_type', 'article')

@php
    $articleImage = $article->image_path ? $siteConfiguration->imageUrl($article->image_path) : null;
    $articleAuthor = data_get($article->metadata, 'author');
    $storedImages = data_get($article->metadata, 'images', []);
    $articleImages = is_array($storedImages)
        ? collect($storedImages)
            ->filter(fn (mixed $image): bool => is_string($image) && trim($image) !== '')
            ->values()
        : collect();
    $articleSchema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => $article->title,
        'description' => $article->meta_description ?: $article->summary,
        'mainEntityOfPage' => route('news.show', $article->slug),
        'datePublished' => $article->published_at?->toAtomString(),
        'dateModified' => $article->updated_at?->toAtomString(),
        'image' => $articleImage ? url($articleImage) : null,
        'publisher' => [
            '@type' => 'Organization',
            'name' => $siteSettings['identity.school_name'],
        ],
    ], fn (mixed $value): bool => $value !== null && $value !== '');
@endphp

@push('head')
    <script type="application/ld+json">@json($articleSchema)</script>
@endpush

@section('content')
    @php
        $newsBannerImage = $siteConfiguration->imageUrl($siteSettings['about.image']);
        $newsBannerAlt = $siteSettings['home.about.image_alt'];
    @endphp

    <section class="news-detail-page">
        <div class="container news-detail__container">
            <header class="news-portal-banner">
                <div class="news-portal-banner__copy">
                    <span class="news-portal-banner__label">Berita Sekolah</span>
                    <h2>Portal Berita<br>SMK Islamiyah Ciputat</h2>
                    <p>Informasi terkini seputar kegiatan, prestasi, dan perkembangan SMK Islamiyah Ciputat.</p>
                </div>
                @if ($newsBannerImage)
                    <figure class="news-portal-banner__image">
                        <img src="{{ $newsBannerImage }}" alt="{{ $newsBannerAlt }}" fetchpriority="high">
                    </figure>
                @endif
            </header>

            <nav class="news-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Beranda</a>
                <span aria-hidden="true">›</span>
                <a href="{{ route('news.index') }}">Berita</a>
                <span aria-hidden="true">›</span>
                <span aria-current="page">{{ $article->title }}</span>
            </nav>

            <div class="news-detail-layout">
                <article class="news-detail-main">
                    <header class="news-article-header">
                        @if ($article->category)
                            <span class="news-card-category">{{ $article->category }}</span>
                        @endif
                        <h1>{{ $article->title }}</h1>
                        <div class="news-article-header__bottom">
                            <div class="news-article-byline">
                                @if ($article->published_at)
                                    <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('d/m/Y') }}</time>
                                @endif
                                @if (filled($articleAuthor))
                                    <span>{{ $articleAuthor }}</span>
                                @endif
                            </div>
                            <div class="news-share" aria-label="Bagikan artikel">
                                <span class="news-share__label">Bagikan</span>
                                <a
                                    class="news-share__button news-share__button--whatsapp"
                                    href="https://wa.me/?text={{ urlencode($article->title.' '.route('news.show', $article->slug)) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="Bagikan lewat WhatsApp"
                                >WA</a>
                                <a
                                    class="news-share__button news-share__button--facebook"
                                    href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('news.show', $article->slug)) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="Bagikan lewat Facebook"
                                >f</a>
                                <button
                                    class="news-share__button news-share__button--copy"
                                    type="button"
                                    data-copy-article-link="{{ route('news.show', $article->slug) }}"
                                    aria-label="Salin tautan artikel"
                                >⧉</button>
                                <span class="news-share__status" data-copy-article-status role="status" aria-live="polite"></span>
                            </div>
                        </div>
                    </header>

                    @if ($articleImage)
                        <figure class="news-article-cover">
                            <img src="{{ $articleImage }}" alt="Foto {{ $article->title }}" fetchpriority="high">
                        </figure>
                    @endif

                    <div class="news-article-body">
                        @if ($article->summary)
                            <p class="news-article-lead">{{ $article->summary }}</p>
                        @endif
                        @if ($article->body)
                            <div class="news-article-content">{{ $article->body }}</div>
                        @endif
                    </div>

                    @if ($articleImages->isNotEmpty())
                        <section class="news-article-gallery" aria-label="Foto tambahan berita">
                            @foreach ($articleImages as $image)
                                <figure>
                                    <img src="{{ $siteConfiguration->imageUrl($image) }}" alt="Foto {{ $article->title }} {{ $loop->iteration }}" loading="lazy">
                                </figure>
                            @endforeach
                        </section>
                    @endif

                    @if ($relatedArticles->isNotEmpty())
                        <section class="news-related-articles" aria-labelledby="news-related-heading">
                            <h2 class="news-related-articles__heading" id="news-related-heading">Berita Lainnya</h2>
                            <div class="news-related-articles__grid">
                                @foreach ($relatedArticles as $relatedArticle)
                                    <article class="news-related-card">
                                        <a class="news-related-card__link" href="{{ route('news.show', $relatedArticle->slug) }}">
                                            @if ($relatedArticle->image_path)
                                                <img
                                                    class="news-related-card__image"
                                                    src="{{ $siteConfiguration->imageUrl($relatedArticle->image_path) }}"
                                                    alt="Foto {{ $relatedArticle->title }}"
                                                    loading="lazy"
                                                >
                                            @else
                                                <span class="news-related-card__placeholder" aria-hidden="true"></span>
                                            @endif
                                            @if ($relatedArticle->category)
                                                <span class="news-related-card__category">{{ $relatedArticle->category }}</span>
                                            @endif
                                            <h3 class="news-related-card__title">{{ $relatedArticle->title }}</h3>
                                        </a>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </article>

                <aside class="news-detail-sidebar" aria-label="Berita terkait dan kategori">
                    @if ($relatedArticles->isNotEmpty())
                        <section class="news-sidebar-card" aria-labelledby="news-detail-related-heading">
                            <h2 class="news-sidebar-card__title" id="news-detail-related-heading">Berita Terkait</h2>
                            <ol class="news-detail-related-list">
                                @foreach ($relatedArticles->take(3) as $relatedArticle)
                                    <li class="news-detail-related-item">
                                        <a class="news-detail-related-item__link" href="{{ route('news.show', $relatedArticle->slug) }}">
                                            @if ($relatedArticle->image_path)
                                                <img
                                                    class="news-detail-related-item__image"
                                                    src="{{ $siteConfiguration->imageUrl($relatedArticle->image_path) }}"
                                                    alt=""
                                                    loading="lazy"
                                                >
                                            @else
                                                <span class="news-detail-related-item__placeholder" aria-hidden="true"></span>
                                            @endif
                                            <span class="news-detail-related-item__copy">
                                                <strong>{{ $relatedArticle->title }}</strong>
                                                @if ($relatedArticle->published_at)
                                                    <time datetime="{{ $relatedArticle->published_at->toDateString() }}">{{ $relatedArticle->published_at->format('d F Y') }}</time>
                                                @endif
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
                        </section>
                    @endif

                    <section class="news-sidebar-card" aria-labelledby="news-detail-categories-heading">
                        <h2 class="news-sidebar-card__title" id="news-detail-categories-heading">Kategori Berita</h2>
                        <ul class="news-sidebar-categories news-detail-category-list">
                            <li>
                                <a href="{{ route('news.index') }}">
                                    <span>Semua Berita</span>
                                    <span class="news-sidebar-categories__count" aria-label="{{ $publishedArticleCount }} berita">{{ $publishedArticleCount }}</span>
                                </a>
                            </li>
                            @foreach ($categoryCounts as $category => $count)
                                <li>
                                    <a href="{{ route('news.index', ['category' => $category]) }}">
                                        <span>{{ $category }}</span>
                                        <span class="news-sidebar-categories__count" aria-label="{{ $count }} berita">{{ $count }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                </aside>
            </div>
        </div>
    </section>
@endsection
