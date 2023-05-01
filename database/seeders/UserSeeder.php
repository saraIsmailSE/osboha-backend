<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\ProfileSetting;
use App\Models\Timeline;
use App\Models\Group;
use App\Models\TimelineType;
use App\Models\UserGroup;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Illuminate\Database\Eloquent\Builder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ####### roles in the system #######
        // $role1 admin
        // $role2 advisor
        // $role3 supervisor
        // $role4 leader
        // $role5 ambassador
        // reading groups
        $groups = Group::where('type_id', 1)->get();
        $timeline_type_id = TimelineType::where('type', 'profile')->first()->id;

        ####### Seed Admin #######
        $user = User::factory()->create();

        $user->assignRole('admin');
        foreach ($groups as $group) {
            $user->groups()->attach($group->id, ['user_type' => 'admin']);

            if ($group->id == 1) {
                $user->groups()->attach($group->id, ['user_type' => 'ambassador']);
            }
        }

        $timeline = Timeline::create(['type_id' => $timeline_type_id]);
        UserProfile::factory(1)->create([
            'user_id' => $user->id,
            'timeline_id' => $timeline->id
        ]);
        ProfileSetting::factory(1)->create([
            'user_id' => $user->id,
        ]);
        ####### End Seed Admin #######


        ####### Seed Advisors #######
        $advisor = 0;
        while ($advisor <= 1) {
            $groups  = Group::where('type_id', 1)->whereDoesntHave('users', function ($query) {
                return $query->where('user_type', 'advisor');
            })->limit(5)->get();

            $user = User::factory()->create();
            $user->assignRole('advisor');
            foreach ($groups as $group) {
                $user->groups()->attach($group->id, ['user_type' => 'advisor']);
            }
            $timeline = Timeline::create(['type_id' => $timeline_type_id]);
            UserProfile::factory(1)->create([
                'user_id' => $user->id,
                'timeline_id' => $timeline->id
            ]);
            ProfileSetting::factory(1)->create([
                'user_id' => $user->id,
            ]);
            $advisor++;
        }
        ####### End Seed Advisors #######

        ####### Seed Supervisors ########
        $supervisor = 0;
        while ($supervisor <= 4) {
            $groups  = Group::where('type_id', 1)->whereDoesntHave('users', function ($query) {
                return $query->where('user_type', 'supervisor');
            })->limit(3)->get();
            $user = User::factory()->create();
            $user->assignRole('supervisor');
            foreach ($groups as $group) {
                $user->groups()->attach($group->id, ['user_type' => 'supervisor']);
            }
            $timeline = Timeline::create(['type_id' => $timeline_type_id]);
            UserProfile::factory(1)->create([
                'user_id' => $user->id,
                'timeline_id' => $timeline->id
            ]);
            ProfileSetting::factory(1)->create([
                'user_id' => $user->id,
            ]);
            $supervisor++;
        }
        ####### End Seed Supervisors ########


        ######## Seed Leaders #######
        $leader = 0;
        while ($leader <= 9) {
            $groups  = Group::where('type_id', 1)->whereDoesntHave('users', function ($query) {
                return $query->where('user_type', 'leader');
            })->limit(1)->get();

            $user = User::factory()->create();
            $user->assignRole('leader');
            foreach ($groups as $group) {
                $user->groups()->attach($group->id, ['user_type' => 'leader']);

                if ($group->id == 1) {
                    //get admin user
                    $admin = $group->admin()->first();
                    $admin->parent_id = $user->id;
                    $admin->save();
                }
            }
            $timeline = Timeline::create(['type_id' => $timeline_type_id]);
            UserProfile::factory(1)->create([
                'user_id' => $user->id,
                'timeline_id' => $timeline->id
            ]);
            ProfileSetting::factory(1)->create([
                'user_id' => $user->id,
            ]);
            $leader++;
        }
        ######## End Seed Leaders #######

        ####### Seed Ambassadors #######
        $ambassadors = 0;
        while ($ambassadors <= 150) {
            $groups  = Group::where('type_id', 1)->withCount(['users' => function (Builder $query) {
                $query->where('user_type', 'ambassador');
            }])->get();
            $groupsForAmbassador  = $groups->where('users_count', '<', 20)->first();
            $user = User::factory()->create();
            $user->assignRole('ambassador');

            $user->groups()->attach($groupsForAmbassador->id, ['user_type' => 'ambassador']);

            $timeline = Timeline::create(['type_id' => $timeline_type_id]);
            UserProfile::factory(1)->create([
                'user_id' => $user->id,
                'timeline_id' => $timeline->id
            ]);
            ProfileSetting::factory(1)->create([
                'user_id' => $user->id,
            ]);
            $ambassadors++;
        }
        ####### End Seed Ambassadors #######

    }
}