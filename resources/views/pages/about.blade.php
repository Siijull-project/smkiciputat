@extends('layouts.app')

@section('title', $siteSettings['about.title'])
@section('meta_description', $siteSettings['about.description'])

@section('content')
    @php
        $profileParagraphs = collect([
            $siteSettings['home.about.paragraph_1'],
            $siteSettings['home.about.paragraph_2'],
        ])->filter(fn (string $paragraph): bool => filled($paragraph))->values();
        $heroDescription = filled($siteSettings['about.description'])
            ? $siteSettings['about.description']
            : $profileParagraphs->first();
        $schoolImage = $siteConfiguration->imageUrl($siteSettings['about.image']);
        $schoolStats = collect([
            [
                'icon' => 'users',
                'label' => 'Total Siswa',
                'value' => $siteSettings['home.stats.student_count'],
            ],
            [
                'icon' => 'graduation-cap',
                'label' => 'Program Keahlian',
                'value' => $navigationPrograms->count(),
            ],
            [
                'icon' => 'users-round',
                'label' => 'Tenaga Pendidik',
                'value' => $siteSettings['home.stats.teacher_count'],
            ],
        ])->filter(fn (array $stat): bool => filled($stat['value']))->values();
        $missions = collect(preg_split('/\r\n|\r|\n/', $siteSettings['about.mission']))
            ->filter(fn (string $mission): bool => trim($mission) !== '')
            ->values();
    @endphp

    <section class="section public-page about-page">
        <div class="container public-page__container about-page__container">
            <header class="about-hero{{ $schoolImage ? '' : ' about-hero--without-image' }}">
                <div class="about-hero__copy">
                    <span class="about-eyebrow">TENTANG KAMI</span>
                    <h1>{{ $siteSettings['about.title'] }}</h1>
                    @if (filled($heroDescription))
                        <p>{{ $heroDescription }}</p>
                    @endif
                </div>
                @if ($schoolImage)
                    <figure class="about-hero__image">
                        <img
                            src="{{ $schoolImage }}"
                            alt="{{ $siteSettings['home.about.image_alt'] }}"
                            fetchpriority="high"
                        >
                    </figure>
                    <svg class="about-hero__wave" viewBox="0 0 640 96" preserveAspectRatio="none" aria-hidden="true">
                        <path d="M0 96C126 65 245 81 362 52 475 24 548 27 640 0v96H0Z" fill="#E9B526"></path>
                        <path d="M0 96C126 76 245 90 362 63 475 37 548 40 640 16v80H0Z" fill="#0B5539"></path>
                    </svg>
                @endif
            </header>

            @if ($schoolImage || $profileParagraphs->isNotEmpty() || filled($siteSettings['identity.location']))
                <section class="about-profile{{ $schoolImage ? '' : ' about-profile--without-image' }}" id="about-profile" aria-labelledby="about-profile-title">
                    @if ($schoolImage)
                        <figure class="about-profile__image">
                            <img
                                src="{{ $schoolImage }}"
                                alt="{{ $siteSettings['home.about.image_alt'] }}"
                                loading="lazy"
                            >
                        </figure>
                    @endif
                    <div class="about-profile__copy">
                        <span class="about-section-label">PROFIL SEKOLAH</span>
                        <h2 id="about-profile-title">Mengenal SMK Islamiyah Ciputat</h2>
                        @foreach ($profileParagraphs as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                    <aside class="about-facts" aria-labelledby="about-facts-title">
                        <h3 id="about-facts-title">Informasi Sekolah</h3>
                        <div class="about-facts__items">
                            @if (filled($siteSettings['identity.location']))
                                <div class="about-facts__item">
                                    <span class="about-facts__icon" aria-hidden="true">
                                        <i data-lucide="map-pin"></i>
                                    </span>
                                    <span>
                                        <strong>Lokasi</strong>
                                        <span>{{ $siteSettings['identity.location'] }}</span>
                                    </span>
                                </div>
                            @endif
                            <div class="about-facts__item">
                                <span class="about-facts__icon" aria-hidden="true">
                                    <i data-lucide="school"></i>
                                </span>
                                <span>
                                    <strong>Jenis Sekolah</strong>
                                    <span>Sekolah Menengah Kejuruan (SMK)</span>
                                </span>
                            </div>
                        </div>
                    </aside>
                </section>
            @endif

            <section class="about-principles" aria-label="Visi dan misi sekolah">
                <article class="about-principle about-principle--vision">
                    <div class="about-principle__heading">
                        <i data-lucide="target" aria-hidden="true"></i>
                        <h2>Visi Sekolah</h2>
                    </div>
                    @if (filled($siteSettings['about.vision']))
                        <div class="about-principle__body">
                            <span class="about-principle__quote" aria-hidden="true">“</span>
                            <p>{{ $siteSettings['about.vision'] }}</p>
                        </div>
                    @else
                        <p class="about-principle__empty">Informasi visi sekolah belum tersedia.</p>
                    @endif
                </article>
                <article class="about-principle about-principle--mission">
                    <i class="about-principle__motif" data-lucide="book-open" aria-hidden="true"></i>
                    <div class="about-principle__heading">
                        <i data-lucide="book-open" aria-hidden="true"></i>
                        <h2>Misi Sekolah</h2>
                    </div>
                    @if ($missions->isNotEmpty())
                        <ul class="about-mission-list">
                            @foreach ($missions as $mission)
                                <li>
                                    <span class="about-mission-list__icon" aria-hidden="true">
                                        <i data-lucide="check-circle-2"></i>
                                    </span>
                                    <span>{{ trim($mission) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="about-principle__empty">Informasi misi sekolah belum tersedia.</p>
                    @endif
                </article>
            </section>

            @if ($schoolStats->isNotEmpty())
                <section class="about-statistics" aria-labelledby="about-statistics-title">
                    <h2 class="sr-only" id="about-statistics-title">Sekolah dalam Angka</h2>
                    <div class="about-stats">
                        @foreach ($schoolStats as $stat)
                            <div class="about-stat">
                                <i data-lucide="{{ $stat['icon'] }}" aria-hidden="true"></i>
                                <span>
                                    <strong>{{ $stat['value'] }}</strong>
                                    <span>{{ $stat['label'] }}</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="about-gallery" aria-labelledby="about-gallery-title">
                <header class="about-section-heading">
                    <h2 id="about-gallery-title">Galeri Sekolah</h2>
                    <a href="{{ route('gallery') }}">Lihat Semua Galeri <span aria-hidden="true">→</span></a>
                </header>
                @if ($galleryPhotos->isNotEmpty())
                    <div class="about-gallery__grid">
                        @foreach ($galleryPhotos as $photo)
                            @php($photoUrl = $siteConfiguration->imageUrl($photo->image_path))
                            @if ($photoUrl)
                                <figure class="about-gallery__item">
                                    <img src="{{ $photoUrl }}" alt="{{ $photo->title }}" loading="lazy">
                                    @if (filled($photo->title))
                                        <figcaption>{{ $photo->title }}</figcaption>
                                    @endif
                                </figure>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="about-gallery__empty" role="status">
                        <p>Belum ada foto galeri sekolah yang dipublikasikan.</p>
                        <span>Foto kegiatan sekolah akan tampil di sini setelah tersedia.</span>
                    </div>
                @endif
            </section>

            @if (filled($siteSettings['about.history']))
                <section class="about-history" aria-labelledby="about-history-title">
                    <span class="about-section-label">SEJARAH SEKOLAH</span>
                    <h2 id="about-history-title">Perjalanan Sekolah</h2>
                    <p>{{ $siteSettings['about.history'] }}</p>
                </section>
            @endif
        </div>
    </section>
@endsection
