<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\TimelineType;
use App\Models\ProfileSetting;
use App\Models\Timeline;
use App\Models\Book;
use App\Models\BookLevel;
use App\Models\Language;
use App\Models\BookType;
use App\Models\Section;

use App\Notifications\MoveToPlatform;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;


class EligibleMoveDBController extends Controller
{
    public function moveEligibleDB()
    {
        DB::beginTransaction();
        try {

            $users_certificate = DB::connection('mysql_second')->table("users")->get();
            foreach ($users_certificate as $key => $user_certificate) {
                if (!User::where('email', $user_certificate->email)->exists()) {
                    $user = new User();
                    $user->name = $user_certificate->name;
                    $user->password = $user_certificate->password;
                    $user->email = $user_certificate->email;
                    $user->gender = 'undefined';
                    $user->parent_id = null;
                    $user->assignRole('ambassador');
                    $user->save();

                    //create new timeline - type = profile
                    $profileTimeline = TimelineType::where('type', 'profile')->first();
                    $timeline = new Timeline();
                    $timeline->type_id = $profileTimeline->id;
                    $timeline->save();

                    //create user profile, with profile settings
                    UserProfile::create([
                        'user_id' => $user->id,
                        'first_name_ar' => $user->name,
                        'timeline_id' => $timeline->id
                    ]);
                    ProfileSetting::create([
                        'user_id' => $user->id,
                    ]);

                    //notify user with email
                    $user->notify((new MoveToPlatform()));
                }

                //user platform account
                $user_platdorm = User::where('email', $user_certificate->email)->get()->first();

                // Set allowed_to_eligible
                $user_platdorm->allowed_to_eligible = $user_certificate->is_active;
                $user_platdorm->save();

                //Update profile name
                $userProfile = UserProfile::where('user_id', $user_platdorm->id)->first();
                if (is_null($userProfile->first_name_ar)) {
                    $userProfile->first_name_ar = $user_certificate->name;
                    $userProfile->save();
                }

                //assignRoles to the ueser 
                $user_roles_id = DB::connection('mysql_second')->table("model_has_roles")->where('model_id', $user_certificate->id)->pluck('role_id')->toArray();
                $user_roles_name = DB::connection('mysql_second')->table("roles")->whereIn('id', $user_roles_id)->pluck('name')->toArray();
                $user_platdorm->assignRole($user_roles_name);


                // move user books
                $user_books = DB::connection('mysql_second')->table("user_book")->where('user_id', $user_certificate->id)->get();

                if ($user_books->isNotEmpty()) {
                    foreach ($user_books as $key => $user_book) {
                        $user_book_eligible = DB::connection('mysql_second')->table("books")->where('id', $user_book->book_id)->first();

                        $book_id_platform = Book::where('name', $user_book_eligible->name)->orWhere('link', $user_book_eligible->link)->pluck('id')->first();
                        // إذا الكتاب غير موجود بالمنصة 
                        if (!$book_id_platform) {
                            // إضافة الكتاب
                            $book = new Book();
                            $book->name = $user_book_eligible->name;

                            $book->start_page = $user_book_eligible->start_page;
                            $book->end_page = $user_book_eligible->end_page;

                            //writer
                            if ($user_book_eligible->writer) {
                                $book->writer = $user_book_eligible->writer;
                            } else {
                                $book->writer = 'غير محدد';
                            }

                            //publisher
                            if ($user_book_eligible->publisher) {
                                $book->publisher = $user_book_eligible->publisher;
                            } else {
                                $book->publisher = 'غير محدد';
                            }

                            //link
                            if ($user_book_eligible->link) {
                                $book->link = $user_book_eligible->link;
                            } else {
                                $book->link = 'https://www.platform.osboha180.com/';
                            }

                            //brief
                            if ($user_book_eligible->brief) {
                                $book->brief = $user_book_eligible->brief;
                            } else {
                                $book->brief = 'لا يوجد وصف';
                            }

                            //section_id
                            if ($user_book_eligible->section_id) {
                                $book->section_id = $user_book_eligible->section_id;
                            } else {
                                $section = Section::where('section', 'غير محدد')->first();
                                $book->section_id = $section->id;
                            }

                            //level_id 
                            if ($user_book_eligible->level_id) {
                                $book->level_id = $user_book_eligible->level_id;
                            } else {
                                $level = BookLevel::where('arabic_level', 'غير محدد')->first();
                                $book->level_id = $level->id;
                            }

                            //type_id 
                            if ($user_book_eligible->type_id) {
                                $book->type_id = $user_book_eligible->type_id;
                            } else {
                                $type = BookType::where('type', 'free')->first();
                                $book->type_id = $type->id;
                            }

                            //language_id
                            if ($user_book_eligible->language_id) {
                                $book->language_id = $user_book_eligible->language_id;
                            } else {
                                $language = Language::where('language', 'arabic')->first();

                                $book->language_id = $language->id;
                            }
                            $book->save();

                            $book_id_platform = $book->id;
                        }



                        $eligible_user_book = DB::table('eligible_user_books')->updateOrInsert(
                            [
                                'user_id' => $user_platdorm->id,
                                'book_id' => $book_id_platform,
                                'status' => $user_book->status
                            ],
                            [
                                'reviews' => $user_book->reviews,
                                'created_at' => $user_book->created_at,
                                'updated_at' => $user_book->updated_at,
                            ]

                        );

                        $eligible_user_book_id = DB::table('eligible_user_books')->where('user_id', $user_platdorm->id)->where('book_id', $book_id_platform)->pluck('id')->first();

                        // move general_informations for this user book
                        $general_information = DB::connection('mysql_second')->table("general_informations")->where('user_book_id', $user_book->id)->first();
                        if ($general_information) {
                            DB::table('eligible_general_informations')->updateOrInsert(
                                ['eligible_user_books_id' => $eligible_user_book_id],
                                [
                                    'general_question' => $general_information->general_question,
                                    'summary' => $general_information->summary,
                                    'reviews' => $general_information->reviews,
                                    'degree' => $general_information->degree,
                                    'status' => $general_information->status,
                                    'reviewer_id' => 1,
                                    'auditor_id' => 1,
                                    'created_at' => $general_information->created_at,
                                    'updated_at' => $general_information->updated_at,

                                ]
                            );
                        }

                        // move general_theses for this user book
                        $general_theses = DB::connection('mysql_second')->table("thesis")->where('user_book_id', $user_book->id)->get();

                        if ($general_theses->isNotEmpty()) {
                            foreach ($general_theses as $general_thesis) {
                                DB::table('eligible_general_thesis')->updateOrInsert(
                                    [
                                        'eligible_user_books_id' => $eligible_user_book_id,
                                        'thesis_text' => $general_thesis->thesis_text
                                    ],
                                    [
                                        'starting_page' => $general_thesis->starting_page,
                                        'ending_page' => $general_thesis->ending_page,
                                        'reviews' => $general_thesis->reviews,
                                        'degree' => $general_thesis->degree,
                                        'status' => $general_thesis->status,
                                        'reviewer_id' => 1,
                                        'auditor_id' => 1,
                                        'created_at' => $general_thesis->created_at,
                                        'updated_at' => $general_thesis->updated_at,

                                    ]
                                );
                            }
                        }

                        // move questions and quotations for this user book 
                        $questions = DB::connection('mysql_second')->table("questions")->where('user_book_id', $user_book->id)->get();

                        if ($questions->isNotEmpty()) {
                            foreach ($questions as $question) {
                                DB::table('eligible_questions')->updateOrInsert(
                                    [
                                        'eligible_user_book_id' => $eligible_user_book_id,
                                        'question' => $question->question,
                                        'starting_page' => $question->starting_page,
                                        'ending_page' => $question->ending_page
                                    ],
                                    [
                                        'reviews' => $question->reviews,
                                        'degree' => $question->degree,
                                        'status' => $question->status,
                                        'reviewer_id' => 1,
                                        'auditor_id' => 1,
                                        'created_at' => $question->created_at,
                                        'updated_at' => $question->updated_at,

                                    ]
                                );

                                $eligible_question_id = DB::table('eligible_questions')->where('eligible_user_book_id', $eligible_user_book_id)->where('question', $question->question)->pluck('id')->first();

                                // move quotations 
                                $quotations = DB::connection('mysql_second')->table("quotations")->where('question_id', $question->id)->get();
                                if ($questions->isNotEmpty()) {
                                    foreach ($quotations as $quotation) {
                                        DB::table('eligible_quotations')->updateOrInsert(
                                            [
                                                'question_id' => $eligible_question_id,
                                                'text' => $quotation->text
                                            ],
                                            [
                                                'created_at' => $quotation->created_at,
                                                'updated_at' => $quotation->updated_at,
                                            ]
                                        );
                                    }
                                }
                            }
                        }

                        // move certificates for this user book
                        $certificate = DB::connection('mysql_second')->table("certificates")->where('user_book_id', $user_book->id)->first();

                        if ($certificate) {
                            DB::table('eligible_certificates')->updateOrInsert(
                                ['eligible_user_books_id' => $eligible_user_book_id],
                                [
                                    'final_grade' => $certificate->final_grade,
                                    'general_summary_grade' => $certificate->general_summary_grade,
                                    'thesis_grade' => $certificate->thesis_grade,
                                    'check_reading_grade' => $certificate->check_reading_grade,
                                    'created_at' => $certificate->created_at,
                                    'updated_at' => $certificate->updated_at,

                                ]
                            );
                        }
                    }
                }
            }
            DB::commit();
            return $this->jsonResponseWithoutMessage('تم النقل بنجاح', 'data', 200);
        } catch (\Exception $e) {

            DB::rollBack();
            return $this->jsonResponseWithoutMessage($e->getMessage() . ' at line ' . $e->getLine(), 'data', 500);
        }
    }
}
