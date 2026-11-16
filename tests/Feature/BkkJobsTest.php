<?php

namespace Tests\Feature;

use App\Models\JobVacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BkkJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_shows_only_active_published_vacancies_and_grounded_statistics(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 15)->startOfDay());

        $this->createVacancy('Finance Analyst', 'Mitra Satu', 'Akuntansi', [
            'closing_date' => '2026-01-20',
        ]);
        $this->createVacancy('Graphic Designer', 'Mitra Dua', 'DKV');
        $this->createVacancy('Expired Vacancy', 'Mitra Kedaluwarsa', 'Akuntansi', [
            'closing_date' => '2026-01-14',
        ]);
        $this->createVacancy('Draft Vacancy', 'Mitra Draft', 'Akuntansi', [], 'draft');

        $response = $this->get(route('bkk.jobs'));

        $response->assertOk()
            ->assertSee('Finance Analyst')
            ->assertSee('Graphic Designer')
            ->assertDontSee('Expired Vacancy')
            ->assertDontSee('Draft Vacancy')
            ->assertSee('Lowongan Aktif')
            ->assertSee('Perusahaan Mitra')
            ->assertSee('2')
            ->assertDontSee('Alumni Terhubung')
            ->assertDontSee('Terverifikasi');
    }

    public function test_search_matches_position_and_company(): void
    {
        $this->createVacancy('Finance Analyst', 'Mitra Satu', 'Akuntansi');
        $this->createVacancy('Office Administrator', 'Finance Partners', 'Administrasi');

        $positionResponse = $this->get(route('bkk.jobs', ['search' => 'Finance Analyst']));
        $companyResponse = $this->get(route('bkk.jobs', ['search' => 'Finance Partners']));

        $positionResponse->assertOk()
            ->assertSee('Finance Analyst')
            ->assertDontSee('Office Administrator');
        $companyResponse->assertOk()
            ->assertSee('Office Administrator')
            ->assertDontSee('Finance Analyst');
    }

    public function test_category_and_company_filters_limit_the_listing(): void
    {
        $this->createVacancy('Finance Analyst', 'Mitra Satu', 'Akuntansi');
        $this->createVacancy('Graphic Designer', 'Mitra Dua', 'DKV');

        $categoryResponse = $this->get(route('bkk.jobs', ['category' => 'Akuntansi']));
        $companyResponse = $this->get(route('bkk.jobs', ['company' => 'Mitra Dua']));

        $categoryResponse->assertOk()
            ->assertSee('Finance Analyst')
            ->assertDontSee('Graphic Designer');
        $companyResponse->assertOk()
            ->assertSee('Graphic Designer')
            ->assertDontSee('Finance Analyst');
    }

    private function createVacancy(
        string $position,
        string $company,
        ?string $category,
        array $metadata = [],
        string $status = 'published',
    ): JobVacancy {
        return JobVacancy::query()->create([
            'title' => $position,
            'slug' => Str::slug($position),
            'summary' => 'Ringkasan '.$position,
            'category' => $category,
            'status' => $status,
            'published_at' => now()->subMinute(),
            'metadata' => array_replace([
                'company' => $company,
                'position' => $position,
            ], $metadata),
        ]);
    }
}
