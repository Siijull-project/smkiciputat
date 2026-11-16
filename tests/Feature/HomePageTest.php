<?php

namespace Tests\Feature;

use App\Models\GalleryPhoto;
use App\Models\NewsArticle;
use App\Models\PrincipalWelcome;
use App\Models\SiteSetting;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_published_principal_welcomes_and_hides_drafts(): void
    {
        PrincipalWelcome::query()->create([
            'title' => 'Sambutan Terbit',
            'slug' => 'sambutan-terbit',
            'category' => 'Kepala Sekolah',
            'summary' => 'Salam yang tampil.',
            'body' => 'Isi sambutan yang tampil.',
            'status' => 'published',
            'sort_order' => 1,
            'metadata' => [
                'name' => 'Kepala Sekolah Terbit',
                'description' => 'Deskripsi yang tampil.',
            ],
        ]);
        PrincipalWelcome::query()->create([
            'title' => 'Sambutan Draft',
            'slug' => 'sambutan-draft',
            'body' => 'Isi draft yang tidak boleh tampil.',
            'status' => 'draft',
            'sort_order' => 2,
            'metadata' => ['name' => 'Kepala Sekolah Draft'],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Kepala Sekolah Terbit')
            ->assertSee('Isi sambutan yang tampil.')
            ->assertDontSee('Kepala Sekolah Draft')
            ->assertDontSee('Isi draft yang tidak boleh tampil.');
    }

    public function test_homepage_renders_all_published_teachers_beyond_six_records(): void
    {
        $teacherNames = [];

        for ($index = 1; $index <= 10; $index++) {
            $teacherName = "Tenaga Pendidik {$index}";
            $teacherNames[] = $teacherName;

            Teacher::query()->create([
                'name' => $teacherName,
                'slug' => "tenaga-pendidik-{$index}",
                'position' => "Jabatan {$index}",
                'status' => 'published',
                'sort_order' => $index,
            ]);
        }

        Teacher::query()->create([
            'name' => 'Tenaga Nonaktif',
            'slug' => 'tenaga-nonaktif',
            'position' => 'Jabatan nonaktif',
            'status' => 'draft',
            'sort_order' => 11,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();

        foreach ($teacherNames as $teacherName) {
            $response->assertSee($teacherName);
        }

        $response->assertDontSee('Tenaga Nonaktif');
    }

    public function test_homepage_hides_placeholder_profile_values_from_teacher_modal(): void
    {
        $placeholderTeacher = Teacher::query()->create([
            'name' => 'DATA CONTOH 03',
            'slug' => 'data-contoh-03',
            'position' => 'Jabatan Contoh 03',
            'category' => 'DATA CONTOH kategori',
            'summary' => 'DATA CONTOH - Ganti dengan deskripsi profil yang sebenarnya.',
            'education' => 'Jabatan Contoh pendidikan',
            'status' => 'published',
            'sort_order' => 1,
        ]);
        $actualTeacher = Teacher::query()->create([
            'name' => 'Guru Nyata',
            'slug' => 'guru-nyata',
            'position' => 'Guru Matematika',
            'category' => 'Guru',
            'summary' => 'Mengajar sejak 2015.',
            'education' => 'S1 Pendidikan Matematika',
            'status' => 'published',
            'sort_order' => 2,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $content = $response->getContent() ?? '';
        $profileMarkupFor = static function (string $content, Teacher $teacher): string {
            $start = strpos($content, 'id="teacher-profile-'.$teacher->getKey().'"');

            if ($start === false) {
                return '';
            }

            $end = strpos($content, '</article>', $start);

            if ($end === false) {
                return '';
            }

            return substr($content, $start, $end - $start);
        };
        $placeholderModal = $profileMarkupFor($content, $placeholderTeacher);
        $actualModal = $profileMarkupFor($content, $actualTeacher);

        $this->assertSame(true, str_contains(
            $placeholderModal,
            '<h2 id="teacher-profile-'.$placeholderTeacher->getKey().'-name">Profil Pendidik</h2>',
        ));
        $this->assertSame(false, str_contains($placeholderModal, 'DATA CONTOH'));
        $this->assertSame(false, str_contains($placeholderModal, 'Jabatan Contoh'));
        $this->assertSame(true, str_contains($actualModal, 'Guru Nyata'));
        $this->assertSame(true, str_contains($actualModal, 'Guru Matematika'));
        $this->assertSame(true, str_contains($actualModal, 'Guru'));
        $this->assertSame(true, str_contains($actualModal, 'Mengajar sejak 2015.'));
        $this->assertSame(true, str_contains($actualModal, 'S1 Pendidikan Matematika'));
    }

    public function test_homepage_renders_published_news_without_a_homepage_group(): void
    {
        $this->freezeTime();

        NewsArticle::query()->create([
            'title' => 'Berita Unggulan',
            'slug' => 'berita-unggulan',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'metadata' => ['homepage_group' => 'featured'],
        ]);
        NewsArticle::query()->create([
            'title' => 'Berita Terbaru Tanpa Grup',
            'slug' => 'berita-terbaru-tanpa-grup',
            'status' => 'published',
            'published_at' => now(),
            'metadata' => ['homepage_group' => 'null'],
        ]);
        NewsArticle::query()->create([
            'title' => 'Berita Lama Tanpa Metadata',
            'slug' => 'berita-lama-tanpa-metadata',
            'status' => 'published',
            'published_at' => now()->subHours(2),
        ]);
        NewsArticle::query()->create([
            'title' => 'Berita Arsip Lama',
            'slug' => 'berita-arsip-lama',
            'status' => 'published',
            'published_at' => now()->subDays(2),
            'metadata' => ['homepage_group' => 'archive'],
        ]);
        NewsArticle::query()->create([
            'title' => 'Berita Draft',
            'slug' => 'berita-draft',
            'status' => 'draft',
            'published_at' => now(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Berita Unggulan')
            ->assertSee('Berita Terbaru Tanpa Grup')
            ->assertSee('Berita Lama Tanpa Metadata')
            ->assertSee('Berita Arsip Lama')
            ->assertDontSee('Berita Draft');
    }

    public function test_homepage_shows_one_selected_main_news_and_distributes_fourteen_articles(): void
    {
        $this->freezeTime();

        for ($index = 1; $index <= 16; $index++) {
            $placement = match ($index) {
                1 => 'featured',
                2 => 'carousel',
                3 => 'below_carousel',
                4 => 'sidebar',
                default => 'auto',
            };

            NewsArticle::query()->create([
                'title' => "Berita Beranda {$index}",
                'slug' => "berita-beranda-{$index}",
                'status' => 'published',
                'published_at' => now()->subMinutes($index),
                'image_path' => "/images/berita-{$index}.jpg",
                'metadata' => ['homepage_group' => $placement],
            ]);
        }
        NewsArticle::query()->create([
            'title' => 'Berita Hanya di Halaman Berita',
            'slug' => 'berita-hanya-di-halaman-berita',
            'status' => 'published',
            'published_at' => now(),
            'metadata' => ['homepage_group' => 'news_only'],
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('data-news-track', false)
            ->assertSee('Berita Sekolah Lainnya')
            ->assertSee('Berita Populer')
            ->assertSee('Kegiatan Terbaru')
            ->assertDontSee('Berita Hanya di Halaman Berita');

        for ($index = 1; $index <= 14; $index++) {
            $response->assertSee("Berita Beranda {$index}");
        }

        $response->assertDontSee('Berita Beranda 15')
            ->assertDontSee('Berita Beranda 16');

        $content = $response->getContent() ?? '';

        $this->assertSame(7, substr_count($content, 'data-news-carousel-item'));
        $this->assertSame(6, substr_count($content, 'data-news-below-carousel-item'));
        $this->assertSame(3, substr_count($content, 'data-news-sidebar-item'));
        $this->assertSame(6, substr_count($content, 'class="news-below-carousel-card__image"'));
        $this->assertSame(3, substr_count($content, 'class="news-popular-item__image"'));

        $this->get(route('news.index'))
            ->assertOk()
            ->assertSee('Berita Hanya di Halaman Berita');
    }

    public function test_homepage_keeps_an_explicitly_selected_older_main_article_visible(): void
    {
        $this->freezeTime();

        NewsArticle::query()->create([
            'title' => 'Berita Utama Pilihan Lama',
            'slug' => 'berita-utama-pilihan-lama',
            'status' => 'published',
            'published_at' => now()->subDays(30),
            'metadata' => ['homepage_group' => 'featured'],
        ]);

        for ($index = 1; $index <= 14; $index++) {
            NewsArticle::query()->create([
                'title' => "Berita Otomatis {$index}",
                'slug' => "berita-otomatis-{$index}",
                'status' => 'published',
                'published_at' => now()->subMinutes($index),
                'metadata' => ['homepage_group' => 'auto'],
            ]);
        }

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Berita Utama Pilihan Lama');
        for ($index = 1; $index <= 13; $index++) {
            $response->assertSee("Berita Otomatis {$index}");
        }
        $response->assertDontSee('Berita Otomatis 14');

        $content = $response->getContent() ?? '';
        $this->assertSame(8, substr_count($content, 'data-news-carousel-item'));
        $this->assertSame(6, substr_count($content, 'data-news-below-carousel-item'));
        $this->assertSame(3, substr_count($content, 'data-news-sidebar-item'));
    }

    public function test_homepage_limits_the_below_carousel_news_cards_to_six(): void
    {
        for ($index = 1; $index <= 6; $index++) {
            NewsArticle::query()->create([
                'title' => "Berita Tambahan {$index}",
                'slug' => "berita-tambahan-{$index}",
                'status' => 'published',
                'published_at' => now()->subMinutes($index),
                'image_path' => "/images/berita-{$index}.jpg",
                'metadata' => ['homepage_group' => 'below_carousel'],
            ]);
        }

        $response = $this->get(route('home'));

        $response->assertOk();
        $content = $response->getContent() ?? '';
        $this->assertSame(6, substr_count($content, 'data-news-below-carousel-item'));
        $response->assertSee('Berita Tambahan 1')
            ->assertSee('Berita Tambahan 6');
    }

    public function test_homepage_uses_the_three_latest_published_articles_for_popular_news(): void
    {
        for ($index = 1; $index <= 4; $index++) {
            NewsArticle::query()->create([
                'title' => "Populer Terbaru {$index}",
                'slug' => "populer-terbaru-{$index}",
                'status' => 'published',
                'published_at' => now()->subMinutes($index),
                'metadata' => ['homepage_group' => 'auto'],
            ]);
        }

        $response = $this->get(route('home'));

        $response->assertOk();
        $popularSection = substr(
            $response->getContent() ?? '',
            strpos($response->getContent() ?? '', '<section class="news-popular-panel"'),
        );
        $popularSection = substr($popularSection, 0, strpos($popularSection, '</section>') + strlen('</section>'));

        $this->assertSame(3, substr_count($popularSection, 'data-news-sidebar-item'));
        $this->assertStringContainsString('Populer Terbaru 1', $popularSection);
        $this->assertStringContainsString('Populer Terbaru 2', $popularSection);
        $this->assertStringContainsString('Populer Terbaru 3', $popularSection);
        $this->assertStringNotContainsString('Populer Terbaru 4', $popularSection);
    }

    public function test_homepage_links_to_configured_videos_from_the_latest_activity(): void
    {
        GalleryPhoto::query()->create([
            'title' => 'Kegiatan Robotika',
            'slug' => 'kegiatan-robotika',
            'summary' => 'Kegiatan pembelajaran siswa.',
            'image_path' => '/images/5.jpg',
            'status' => 'published',
            'published_at' => now(),
        ]);

        foreach ([
            'home.news.video_1' => 'https://www.youtube.com/watch?v=AbCdeFGhi12',
            'home.news.video_2' => 'https://cdn.example.test/videos/kegiatan-sekolah.mp4',
        ] as $key => $value) {
            SiteSetting::query()->create([
                'key' => $key,
                'group' => 'home',
                'value' => json_encode($value, JSON_THROW_ON_ERROR),
            ]);
        }

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('class="news-activity-play"', false)
            ->assertSee('href="https://www.youtube.com/watch?v=AbCdeFGhi12"', false)
            ->assertSee('href="https://cdn.example.test/videos/kegiatan-sekolah.mp4"', false)
            ->assertSee('Kegiatan Robotika');
    }
}
