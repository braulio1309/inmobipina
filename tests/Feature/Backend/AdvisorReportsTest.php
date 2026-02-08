<?php

namespace Tests\Feature\Backend;

use App\Models\Activity;
use App\Models\Core\Auth\Role;
use App\Models\Core\Auth\User;
use App\Models\Operation;
use App\Models\Property;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class AdvisorReportsTest.
 */
class AdvisorReportsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function advisor_can_access_their_own_reports()
    {
        $advisor = factory(User::class)->create();
        
        $this->actingAs($advisor);

        $response = $this->get('/app/reports/advisor');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'advisor' => ['id', 'name'],
                'metrics' => [
                    'sales_count',
                    'reservations_count',
                    'properties_count',
                    'activities_by_type'
                ]
            ]);
    }

    /** @test */
    public function advisor_cannot_access_other_advisor_reports()
    {
        $advisor1 = factory(User::class)->create();
        $advisor2 = factory(User::class)->create();
        
        $this->actingAs($advisor1);

        $response = $this->get('/app/reports/advisor?user_id=' . $advisor2->id);

        // Should return advisor1's reports, not advisor2's
        $response->assertStatus(200)
            ->assertJson([
                'advisor' => ['id' => $advisor1->id]
            ]);
    }

    /** @test */
    public function admin_can_access_any_advisor_reports()
    {
        $this->loginAsAdmin();
        
        $advisor = factory(User::class)->create();

        $response = $this->get('/app/reports/advisor?user_id=' . $advisor->id);

        $response->assertStatus(200)
            ->assertJson([
                'advisor' => ['id' => $advisor->id]
            ]);
    }

    /** @test */
    public function advisor_cannot_list_all_advisors()
    {
        $advisor = factory(User::class)->create();
        
        $this->actingAs($advisor);

        $response = $this->get('/app/reports/advisors');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_list_all_advisors()
    {
        $this->loginAsAdmin();

        $response = $this->get('/app/reports/advisors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name']
            ]);
    }

    /** @test */
    public function reports_include_correct_sales_count()
    {
        $advisor = factory(User::class)->create();
        
        // Create sales for advisor
        factory(Sale::class, 3)->create(['seller_id' => $advisor->id]);
        
        $this->actingAs($advisor);

        $response = $this->get('/app/reports/advisor');

        $response->assertStatus(200)
            ->assertJson([
                'metrics' => ['sales_count' => 3]
            ]);
    }

    /** @test */
    public function reports_include_correct_properties_count()
    {
        $advisor = factory(User::class)->create();
        
        // Create properties for advisor
        factory(Property::class, 5)->create(['created_by' => $advisor->id]);
        
        $this->actingAs($advisor);

        $response = $this->get('/app/reports/advisor');

        $response->assertStatus(200)
            ->assertJson([
                'metrics' => ['properties_count' => 5]
            ]);
    }

    /** @test */
    public function reports_group_activities_by_type()
    {
        $advisor = factory(User::class)->create();
        
        // Create activities of different types
        factory(Activity::class, 2)->create(['user_id' => $advisor->id, 'type' => 'call']);
        factory(Activity::class, 3)->create(['user_id' => $advisor->id, 'type' => 'visit']);
        
        $this->actingAs($advisor);

        $response = $this->get('/app/reports/advisor');

        $response->assertStatus(200)
            ->assertJson([
                'metrics' => [
                    'activities_by_type' => [
                        'call' => 2,
                        'visit' => 3
                    ]
                ]
            ]);
    }
}
