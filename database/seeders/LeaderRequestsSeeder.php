<?php
namespace Database\Seeders;

use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;
class LeaderRequestsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $gender= ['male', 'female'];
       $i=0;
       $leaders = User::role('leader')->get();    
        while ($i<=20){
            DB::table('leader_requests')->insert([
                'members_num' => rand(1,10),
                'gender' => $gender[rand(0,1)],
                'leader_id' => $leaders[$i]->id,
                'current_team_count' => rand(0,10),
            ]);
            $i++;    
        }
    }
} 





