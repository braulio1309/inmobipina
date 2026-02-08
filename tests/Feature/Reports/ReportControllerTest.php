<?php

namespace Tests\Feature\Reports;

use App\Models\Activity;
use App\Models\Core\Auth\User;
use App\Models\Operation;
use App\Models\Property;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    // Note: Tests use generic User models. In production, users with specific roles
    // (advisors, admins) would be distinguished by their assigned roles/permissions.

    /** @test */
    public function advisor_can_view_own_metrics()
    {
        $user = factory(User::class)->create();
        
        // Create some test data
        factory(Sale::class, 3)->create(['seller_id' => $user->id]);
        factory(Property::class, 2)->create(['created_by' => $user->id]);
        factory(Activity::class, 5)->create(['user_id' => $user->id, 'type' => 'demostración']);

        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/reports/advisor-metrics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'advisor' => ['id', 'name'],
                'metrics' => [
                    'sales_count',
                    'reservations_count',
                    'properties_count',
                    'activities_by_type'
                ]
            ])
            ->assertJson([
                'advisor' => ['id' => $user->id],
                'metrics' => [
                    'sales_count' => 3,
                    'properties_count' => 2
                ]
            ]);
    }

    /** @test */
    public function advisor_cannot_view_other_advisor_metrics()
    {
        $user1 = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $this->actingAs($user1, 'api');

        $response = $this->getJson('/api/reports/advisor-metrics?user_id=' . $user2->id);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized to view this advisor\'s metrics']);
    }

    /** @test */
    public function admin_can_view_any_advisor_metrics()
    {
        $admin = $this->loginAsAdmin();
        $user = factory(User::class)->create();
        
        factory(Sale::class, 2)->create(['seller_id' => $user->id]);

        $response = $this->getJson('/api/reports/advisor-metrics?user_id=' . $user->id);

        $response->assertStatus(200)
            ->assertJson([
                'advisor' => ['id' => $user->id],
                'metrics' => ['sales_count' => 2]
            ]);
    }

    /** @test */
    public function admin_can_get_list_of_advisors()
    {
        $admin = $this->loginAsAdmin();
        
        $users = factory(User::class, 3)->create();

        $response = $this->getJson('/api/reports/advisors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'advisors' => [
                    '*' => ['id', 'name', 'email']
                ]
            ]);
    }

    /** @test */
    public function regular_user_cannot_get_list_of_advisors()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/reports/advisors');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized. Admin access required.']);
    }

    /** @test */
    public function metrics_return_zero_for_activities_not_performed()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/reports/advisor-metrics');

        $response->assertStatus(200)
            ->assertJson([
                'metrics' => [
                    'activities_by_type' => [
                        'demostración' => 0,
                        'captación' => 0,
                        'venta' => 0,
                        'alquiler' => 0,
                        'reserva' => 0
                    ]
                ]
            ]);
    }

    /** @test */
    public function reservation_count_includes_operations_with_type_reserva()
    {
        $admin = $this->loginAsAdmin();
        $user = factory(User::class)->create();
        
        // Create operations with type reserva
        $property = factory(Property::class)->create();
        $operation = factory(Operation::class)->create([
            'type' => 'reserva',
            'property_id' => $property->id
        ]);
        
        // Attach advisor to operation
        $operation->sellers()->attach($user->id);

        $response = $this->getJson('/api/reports/advisor-metrics?user_id=' . $user->id);

        $response->assertStatus(200)
            ->assertJson([
                'metrics' => ['reservations_count' => 1]
            ]);
    }
}
