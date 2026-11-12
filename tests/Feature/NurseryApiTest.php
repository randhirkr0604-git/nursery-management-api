<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Room;
use App\Models\Child;
use App\Models\CheckIn;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NurseryApiTest extends TestCase
{
    use DatabaseTransactions;

    private function createNursery(): array
    {
        $tenant = Tenant::create([
            'name' => 'Sunflower Nursery',
        ]);

        $room = $tenant->rooms()->create([
            'name' => 'Sunflowers',
        ]);

        $child = $tenant->children()->create([
            'name' => 'Rahul',
        ]);

        return [$tenant, $room, $child];
    }

    private function tenantHeaders(Tenant $tenant): array
    {
        return [
            'X-Tenant-ID' => (string) $tenant->id,
        ];
    }

    public function test_child_can_check_in_successfully(): void
    {
        [$tenant, $room, $child] = $this->createNursery();

        $response = $this->withHeaders($this->tenantHeaders($tenant))
            ->postJson("/api/rooms/{$room->id}/check-ins", [
                'child_id' => $child->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.child_id', $child->id)
            ->assertJsonPath('data.room_id', $room->id);

        $this->assertDatabaseHas('check_ins', [
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'child_id' => $child->id,
            'checked_out_at' => null,
        ]);
    }

    public function test_child_cannot_check_in_twice(): void
    {
        [$tenant, $room, $child] = $this->createNursery();

        $this->withHeaders($this->tenantHeaders($tenant))
            ->postJson("/api/rooms/{$room->id}/check-ins", [
                'child_id' => $child->id,
            ])
            ->assertCreated();

        $this->withHeaders($this->tenantHeaders($tenant))
            ->postJson("/api/rooms/{$room->id}/check-ins", [
                'child_id' => $child->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('child_id');
            
    }

    public function test_child_from_another_tenant_cannot_check_in(): void
    {
        [$tenant1, $room1] = $this->createNursery();
        [$tenant2, , $child2] = $this->createNursery();

        $this->withHeaders($this->tenantHeaders($tenant1))
            ->postJson("/api/rooms/{$room1->id}/check-ins", [
                'child_id' => $child2->id,
            ])
            ->assertNotFound();
    }

    public function test_room_from_another_tenant_cannot_be_used(): void
    {
        [$tenant1] = $this->createNursery();
        [, $room2] = $this->createNursery();

        $child1 = $tenant1->children()->first();

        $this->withHeaders($this->tenantHeaders($tenant1))
            ->postJson("/api/rooms/{$room2->id}/check-ins", [
                'child_id' => $child1->id,
            ])
            ->assertNotFound();
    }

    public function test_child_can_check_out_successfully(): void
    {
        [$tenant, $room, $child] = $this->createNursery();

        $checkIn = CheckIn::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'child_id' => $child->id,
            'checked_in_at' => now(),
        ]);

        $this->withHeaders($this->tenantHeaders($tenant))
            ->postJson("/api/check-ins/{$checkIn->id}/check-out")
            ->assertOk();

        $this->assertDatabaseMissing('check_ins', [
            'id' => $checkIn->id,
            'checked_out_at' => null,
        ]);
    }

    public function test_child_cannot_check_out_another_tenants_check_in(): void
    {
        [$tenant1] = $this->createNursery();
        [$tenant2, $room2, $child2] = $this->createNursery();

        $checkIn = CheckIn::create([
            'tenant_id' => $tenant2->id,
            'room_id' => $room2->id,
            'child_id' => $child2->id,
            'checked_in_at' => now(),
        ]);

        $this->withHeaders($this->tenantHeaders($tenant1))
            ->postJson("/api/check-ins/{$checkIn->id}/check-out")
            ->assertNotFound();
    }

    public function test_occupants_api_returns_only_active_children(): void
    {
        [$tenant, $room, $child] = $this->createNursery();

        $activeCheckIn = CheckIn::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'child_id' => $child->id,
            'checked_in_at' => now(),
        ]);

        $this->withHeaders($this->tenantHeaders($tenant))
            ->getJson("/api/rooms/{$room->id}/occupants")
            ->assertOk()
            ->assertJsonPath('total_occupants', 1)
            ->assertJsonPath('data.0.child_id', $child->id);

        $activeCheckIn->update([
            'checked_out_at' => now(),
        ]);

        $this->withHeaders($this->tenantHeaders($tenant))
            ->getJson("/api/rooms/{$room->id}/occupants")
            ->assertOk()
            ->assertJsonPath('total_occupants', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_missing_tenant_header_is_rejected(): void
    {
        $this->getJson('/api/rooms/1/occupants')
            ->assertStatus(400);
    }
}
