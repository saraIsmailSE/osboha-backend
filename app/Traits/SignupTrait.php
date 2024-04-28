<?php

namespace App\Traits;

use App\Models\User;
use App\Models\UserGroup;

trait SignupTrait
{

    //check if new user is already an ambassador
    public function checkAmbassador($email)
    {
        $user_group = null;
        $user = User::where('email', $email)->first();
        if ($user) {
            //return last user group result as ambassador
            $user_group = UserGroup::where('user_id', $user->id)->where('user_type', 'ambassador')->latest()->first();
        }
        return $user_group;
    }

    // select team
    public function selectTeam($ambassador, $leaderGender)
    {
        //NEED $ambassador, $leaderGender
        //ambassador is a User instance

        $ambassadorGender = $ambassador->gender;

        if ($ambassadorGender == 'any') {
            $ambassador_condition = "leader_request.gender = '" . $ambassadorGender . "'";
        } else {
            $ambassador_condition = "(leader_request.gender = '" . $ambassadorGender . "' OR leader_request.gender = 'any')";
        }

        if ($leaderGender == "any") {
            $leader_condition = " (leader_info.leader_gender = 'female' OR leader_info.leader_gender = 'male')";
        } else {
            $leader_condition = "leader_info.leader_gender = '" . $leaderGender . "'";
        }


        // Check for SpecialCare
        // Check for High Priority Requests
        //Check New Teams
        //Check Teams With Less Than 12 Members
        //Check Teams With More Than 12 Members

    }
    public function informLeader()
    {

        $firstMsg = "السلام عليكم ورحمة الله وبركاته " . '\n' . " أرجو أن تكون بخير 🌸 " . '\n' . " . " . '\n' . "  لقد قام موقع الإرشاد الإلكتروني بتوزيع بعض المشتركين الجدد لفريقك حسب طلبك." . '\n' . " . " . '\n' . " . " . '\n' . " ⚠️ تذكر، بعض المشتركين الجدد قد يغير رأيه و يمتنع عن الانضمام لفريق المتابعة أو لمشروعنا لأسباب شخصية مختلفة.  لا تقلق أبدًا لأن هدفنا هو الاستمرار بالمحاولة وتغيير نظرة المجتمع والتزامه اتجاه التعلم بالقراءة المنهجية، في حال لم يقم المشترك الجديد بالانضمام لمجموعة المتابعة الخاص بك، فإن بإمكانك طلب عدد جديد وسوف نقوم بتوفيره لك سريعًا ♥️." . '\n' . " . " . '\n' . " " . '\n' . " ✅ حفظًا على جهودكم وجهود فريقكم، في حال ⛔ لم يظهر المشترك الجديد أي ردة فعل أو رغبة في القراءة بإمكانك ضغط على زر (انسحاب⛔) في موقع العلامات بعد نهاية الأسبوع الأول له." . '\n' . " " . '\n' . " قواكم الله وبارك همتكم قائدنا.";
    } //informLeader

}
