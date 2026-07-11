<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\CompanyService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CompanyContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_switch_to_their_own_company(): void
    {
        $company = Company::create(['name' => 'Own Co', 'slug' => 'own-co', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        Auth::login($user);

        app(CompanyService::class)->switchTo($company->id);

        $this->assertSame($company->id, session('current_company_id'));
        $this->assertSame($company->id, app('current_company_id'));
    }

    public function test_user_cannot_switch_to_a_company_they_do_not_belong_to(): void
    {
        $ownCompany = Company::create(['name' => 'Own Co', 'slug' => 'own-co', 'is_active' => true]);
        $otherCompany = Company::create(['name' => 'Other Co', 'slug' => 'other-co', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $ownCompany->id]);
        Auth::login($user);

        $this->expectException(AuthorizationException::class);

        app(CompanyService::class)->switchTo($otherCompany->id);
    }
}
