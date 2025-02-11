<?php

namespace Tests\Feature\Roles;

use App\Enums\SystemRole;
use App\Models\User;
use App\Models\UserParent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssignRoleTest extends TestCase
{
    protected $user;
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('email', 'platform.admin@osboha.com')->first();

        if (!$this->user) {
            $this->fail('Admin user not found');
        }
    }

    public function test_fails_if_user_already_has_role()
    {

        $this->actingAs($this->user, 'web');

        $user = User::factory()->create();
        $user->assignRole(SystemRole::AMBASSADOR->value, SystemRole::LEADER->value);

        $headUser = User::factory()->create();

        $response = $this->postJson(route('roles.assign'), [
            'user' => $user->email,
            'head_user' => $headUser->email,
            'role_id' => Role::where('name', SystemRole::LEADER->value)->first()->id
        ]);

        $response->assertJsonFragment([
            'data' => "المستخدم موجود مسبقاً كقائد"
        ]);
    }

    public function test_fails_if_acting_user_has_less_ranked_role_than_user()
    {
        $actingUser = User::where('email', 'platform.leader@osboha.com')->first();
        $this->actingAs($actingUser, 'web');

        $user = User::factory()->create();
        $user->assignRole(SystemRole::AMBASSADOR->value, SystemRole::SUPERVISOR->value);
        $headUser = User::factory()->create();


        $response = $this->postJson(route('roles.assign'), [
            'user' => $user->email,
            'head_user' => $headUser->email,
            'role_id' => Role::where('name', SystemRole::ADVISOR->value)->first()->id
        ]);

        $response->assertJsonFragment([
            'data' => "ليس لديك صلاحية لترقية العضو ل " . SystemRole::translate(SystemRole::ADVISOR->value)
        ]);
    }

    public function test_fails_if_head_user_has_less_ranked_role_than_user()
    {
        $this->actingAs($this->user, 'web');

        $user = User::factory()->create();
        $user->assignRole(SystemRole::AMBASSADOR->value, SystemRole::SUPERVISOR->value);
        $headUser = User::factory()->create();
        $headUser->assignRole(SystemRole::AMBASSADOR->value, SystemRole::LEADER->value);


        $response = $this->postJson(route('roles.assign'), [
            'user' => $user->email,
            'head_user' => $headUser->email,
            'role_id' => Role::where('name', SystemRole::ADVISOR->value)->first()->id
        ]);

        $response->assertJsonFragment([
            'data' => "يجب أن تكون رتبة المسؤول اعلى من رتبة المستخدم"
        ]);
    }

    public function test_fails_if_head_user_has_less_ranked_role_than_role()
    {

        $this->actingAs($this->user, 'web');

        $user = User::factory()->create();
        $user->assignRole(SystemRole::AMBASSADOR->value, SystemRole::LEADER->value);
        $headUser = User::factory()->create();
        $headUser->assignRole(SystemRole::AMBASSADOR->value, SystemRole::SUPERVISOR->value);

        $response = $this->postJson(route('roles.assign'), [
            'user' => $user->email,
            'head_user' => $headUser->email,
            'role_id' => Role::where('name', SystemRole::ADVISOR->value)->first()->id
        ]);

        $response->assertJsonFragment(['data' => "يجب أن تكون رتبة المسؤول أعلى من الرتبة المراد الترقية لها"]);
    }

    function test_fails_if_supervisor_is_not_a_leader()
    {
        $this->actingAs($this->user, 'web');

        $user = User::factory()->create();
        $user->assignRole(SystemRole::AMBASSADOR->value);
        $headUser = User::factory()->create();
        $headUser->assignRole(SystemRole::AMBASSADOR->value, SystemRole::ADVISOR->value);

        $response = $this->postJson(route('roles.assign'), [
            'user' => $user->email,
            'head_user' => $headUser->email,
            'role_id' => Role::where('name', SystemRole::SUPERVISOR->value)->first()->id
        ]);

        $response->assertJsonFragment(['data' => "لا يمكنك ترقية العضو لمراقب مباشرة, يجب أن يكون قائد أولاً"]);
    }

    //success cases
    function test_success_add_role()
    {
        $this->actingAs($this->user, 'web');
        $user = User::factory()->create();
        $user->assignRole(SystemRole::AMBASSADOR->value);
        $headUser = User::factory()->create();
        $headUser->assignRole(SystemRole::AMBASSADOR->value, SystemRole::SUPERVISOR->value);

        $response = $this->postJson(route('roles.assign'), [
            'user' => $user->email,
            'head_user' => $headUser->email,
            'role_id' => Role::where('name', SystemRole::LEADER->value)->first()->id
        ]);

        $response->assertJsonFragment(['data' => "تمت ترقية العضو ل " . SystemRole::translate(SystemRole::LEADER->value) . " - المسؤول عنه:  " . $headUser->fullName]);

        //get active user parent
        $user->refresh();
        $parent = $user->parent;
        $countActiveParents = $user->parents()->where('is_active', 1)->count();
        $userParent = UserParent::where('user_id', $user->id)->where('is_active', 1)->first();
        $this->assertEquals($parent->id, $headUser->id);
        $this->assertEquals($parent->id, $userParent->parent_id);
        $this->assertEquals($userParent->parent_id, $headUser->id);
        $this->assertEquals(1, $countActiveParents);
    }

    public function test_remove_all_roles_except_leader_for_supervisor()
    {
        $this->actingAs($this->user, 'web');
        $user = User::factory()->create();
        $user->assignRole(SystemRole::AMBASSADOR->value, SystemRole::LEADER->value, SystemRole::ADVISOR->value);
        $headUser = User::factory()->create();
        $headUser->assignRole(SystemRole::AMBASSADOR->value, SystemRole::CONSULTANT->value);

        $response = $this->postJson(route('roles.assign'), [
            'user' => $user->email,
            'head_user' => $headUser->email,
            'role_id' => Role::where('name', SystemRole::SUPERVISOR->value)->first()->id
        ]);

        $user->refresh();
        $userRoles = $user->roles()->pluck('name')->toArray();
        //check if user has ambassador, leader, and supervisor roles
        $this->assertContains(SystemRole::AMBASSADOR->value, $userRoles);
        $this->assertContains(SystemRole::LEADER->value, $userRoles);
        $this->assertContains(SystemRole::SUPERVISOR->value, $userRoles);
        $this->assertNotContains(SystemRole::ADVISOR->value, $userRoles);
        $this->assertEquals(3, count($userRoles));

        $successMessage = "تم سحب دور ال" . SystemRole::translate(SystemRole::ADVISOR) . " من العضو, إنه الآن " . SystemRole::translate(SystemRole::SUPERVISOR);

        $response->assertJsonFragment(['data' => $successMessage]);
    }
}
