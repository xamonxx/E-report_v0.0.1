<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Consultation;
use App\Models\NeedsCategory;
use App\Models\StatusCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_open_consultation_from_other_account(): void
    {
        $adminAccount = Account::create(['name' => 'Account One']);
        $otherAccount = Account::create(['name' => 'Account Two']);
        $category = NeedsCategory::create(['name' => 'Kitchenset']);
        $status = StatusCategory::create(['name' => 'Request Survey', 'color' => '#123456', 'sort_order' => 1]);

        $admin = User::create([
            'name' => 'Admin One',
            'email' => 'admin-one@example.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Admin,
            'account_id' => $adminAccount->id,
        ]);

        $consultation = Consultation::create([
            'consultation_id' => '02.2604.001',
            'client_name' => 'Client Two',
            'phone' => '081234567890',
            'account_id' => $otherAccount->id,
            'needs_category_id' => $category->id,
            'status_category_id' => $status->id,
            'created_by' => $admin->id,
            'consultation_date' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->get(route('consultations.show', $consultation))
            ->assertForbidden();
    }

    public function test_admin_preview_id_endpoint_ignores_other_account_id(): void
    {
        $adminAccount = Account::create(['name' => 'Account Three']);
        $otherAccount = Account::create(['name' => 'Account Four']);

        $admin = User::create([
            'name' => 'Admin Two',
            'email' => 'admin-two@example.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Admin,
            'account_id' => $adminAccount->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('api.consultation-id-preview', ['account_id' => $otherAccount->id]));

        $response->assertOk();
        $this->assertStringStartsWith(
            str_pad((string) $adminAccount->id, 2, '0', STR_PAD_LEFT) . '.',
            $response->json('id')
        );
    }
}
