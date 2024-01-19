<?php

namespace App\Http\Controllers\Api\Eligible;

use App\Http\Controllers\Controller;
use App\Models\EligibleCertificates;
use App\Models\EligibleUserBook;
use App\Notifications\Eligible_Certificate;
use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TCPDF_FONTS;

class EligiblePDFController extends Controller
{

    public function generatePDF($user_book_id)
    {


        ######### START GET USER ACHEVMENTS #########
        $fullCertificate = EligibleUserBook::where('id', $user_book_id)->with('thesises', function ($query) {
            $query->where('status', '=', 'audited');
        })->with('generalInformation', function ($query) {
            $query->where('status', '=', 'audited');
        })->with('questions', function ($query) {
            $query->where('status', '=', 'audited');
        })->get();

        $all_avareges = EligibleUserBook::join('eligible_general_informations', 'eligible_user_books.id', '=', 'eligible_general_informations.eligible_user_books_id')
            ->join('eligible_questions', 'eligible_user_books.id', '=', 'eligible_questions.eligible_user_books_id')
            ->join('eligible_thesis', 'eligible_user_books.id', '=', 'eligible_thesis.eligible_user_books_id')
            ->select(DB::raw('avg(eligible_general_informations.degree) as general_informations_degree,avg(eligible_questions.degree) as questions_degree,avg(eligible_thesis.degree) as thesises_degree'))
            ->where('eligible_user_books.id', $user_book_id)
            ->get();
        $thesisDegree = $all_avareges[0]['thesises_degree'];
        $generalInformationsDegree = $all_avareges[0]['general_informations_degree'];
        $questionsDegree = $all_avareges[0]['questions_degree'];
        $finalDegree = ($questionsDegree + $generalInformationsDegree + $thesisDegree) / 3;
        $certificateDegrees = new EligibleCertificates();

        $certificateDegrees->thesis_grade = $thesisDegree;
        $certificateDegrees->questions_grade = $questionsDegree;
        $certificateDegrees->general_summary_grade = $generalInformationsDegree;
        $certificateDegrees->final_grade = $finalDegree;

        ######### END GET USER ACHEVMENTS #########

        ######### START GENERATING PDF #########

        // set document information
        PDF::SetAuthor('OSBOHA 180');
        $title = $fullCertificate[0]->user->name  . ' || ' . $fullCertificate[0]->book->name;
        PDF::SetTitle($title);
        PDF::SetSubject('توثيق انجاز كتاب');
        PDF::SetKeywords('Osboha, PDF, توثيق, كتاب, كتب, أصبوحة , اصبوحة, 180');

        $tagvs = array('p' => array(0 => array('h' => 0, 'n' => 0), 1 => array('h' => 0, 'n' => 0)));
        PDF::setHtmlVSpace($tagvs);

        $lg = array();
        $lg['a_meta_charset'] = 'UTF-8';
        $lg['a_meta_dir'] = 'rtl';
        $lg['a_meta_language'] = 'fa';
        $lg['w_page'] = 'page';

        // set some language-dependent strings (optional)
        PDF::setLanguageArray($lg);

        //After Write
        PDF::setRTL(true);


        // set margins
        PDF::SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        PDF::SetHeaderMargin(0);
        PDF::SetFooterMargin(0);

        // remove default footer
        PDF::setPrintFooter(false);

        // set auto page breaks
        PDF::SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        PDF::setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set font
        PDF::SetFont('Calibri', '', 48);

        // ###################### START PAGES ###################### //

        // ###################### PAGE 1 ###################### //

        // add a page
        PDF::AddPage();
        // get the current page break margin
        $bMargin = PDF::getBreakMargin();
        // get current auto-page-break mode
        $auto_page_break = PDF::getAutoPageBreak();
        // disable auto-page-break
        PDF::SetAutoPageBreak(false, 0);

        // set bacground image
        $img_file = "https://platform.osboha180.com/backend/public/asset/images/certTempWthiSign.jpg";
        // Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false)
        PDF::Image($img_file, 210, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);

        // restore auto-page-break status
        PDF::SetAutoPageBreak($auto_page_break, $bMargin);
        // set the starting point for the page content
        PDF::setPageMark();
        PDF::writeHTML(view('certificate.page1', ['name' => $fullCertificate[0]->user->name, 'book' => $fullCertificate[0]->book->name, 'level' => $fullCertificate[0]->book->level->name, 'date' => \Carbon\Carbon::parse($fullCertificate[0]->updated_at)->format('d/m/Y')])->render(), true, false, true, false, '');

        // ###################### END PAGE 1 ###################### //

        // ###################### START PAGE 2 ###################### //
        $this->addPage();
        PDF::writeHTML(view('certificate.page2', ['certificateDegrees' => $certificateDegrees])->render(), true, false, true, false, '');
        ###################### END PAGE 2 ######################

        ###################### START PAGE 3 ######################
        $this->addPage();
        PDF::writeHTML(view('certificate.page3')->render(), true, false, true, false, '');
        ###################### END PAGE 3 ###################### 


        ###################### START GRNRRAL INFORMATION ###################### 
        foreach ($fullCertificate as $part) {
            if (strlen($part['generalInformation']->summary) > 1700) {
                $summaryWords = explode(' ', $part['generalInformation']->summary);
                $pages = floor(count($summaryWords) / 300);
                $summary = implode(" ", array_slice($summaryWords, 0, 200));
                $this->addPage();
                PDF::writeHTML(view('certificate.generalInfo', ['summary' => $summary, 'certificate' => $part['generalInformation'], 'textDegree' => $this->textDegree($part['generalInformation']->degree)])->render(), true, false, true, false, '');

                $start = 200;
                $length = 350;

                for ($i = 2; $i <= $pages + 1; $i++) {
                    $summary = implode(" ", array_slice($summaryWords, $start, $length));

                    $this->addPage();
                    PDF::writeHTML(view('certificate.generalSummary', ['summary' => $summary])->render(), true, false, true, false, '');
                    $start = $start + 350;
                }
            }
        }
        ###################### END GRNRRAL INFORMATION ###################### 



        ###################### START THESIS ###################### 
        foreach ($fullCertificate as $key => $part) {
            foreach ($part['thesises'] as $key => $thesis) {


                if (strlen($thesis) > 1700) {
                    $thesisWords = explode(' ', $thesis->thesis_text);
                    $pages = floor(count($thesisWords) / 300);
                    $thesisText = implode(" ", array_slice($thesisWords, 0, 350));
                    $this->addPage();
                    PDF::writeHTML(view('certificate.achevment', ['mainTitle' => 'الأطروحات', 'subTitle' => 'أطروحة', 'index' => $key + 1, 'achevmentText' => $thesisText, 'textDegree' => $this->textDegree($thesis->degree)])->render(), true, false, true, false, '');

                    $start = 350;
                    $length = 350;

                    for ($i = 2; $i <= $pages + 1; $i++) {
                        $thesisText = implode(" ", array_slice($thesisWords, $start, $length));

                        $this->addPage();
                        PDF::writeHTML(view('certificate.theses', ['thesis' => $thesisText])->render(), true, false, true, false, '');
                        $start = $start + 400;
                    }
                    // $this->addPage();
                    // PDF::writeHTML(view('certificate.achevment', ['mainTitle' => 'الأطروحات', 'subTitle' => 'أطروحة', 'index' => $key + 1, 'achevmentText' => $thesis->thesis_text, 'textDegree' => $this->textDegree($thesis->degree)])->render(), true, false, true, false, '');
                }
            }
        }
        ###################### END THESIS ###################### 

        ###################### START THESIS ###################### 
        foreach ($fullCertificate as $key => $part) {
            foreach ($part['questions'] as $key => $question) {
                $this->addPage();
                PDF::writeHTML(view('certificate.achevment', ['mainTitle' => 'الأسئلة المعرفية', 'subTitle' => 'سؤال', 'index' => $key + 1, 'achevmentText' => $question->question, 'textDegree' => $this->textDegree($question->degree), 'quotes' => $question->quotation])->render(), true, false, true, false, '');
            }
        }
        ###################### END THESIS ###################### 


        //        $pdf->lastPage();

        //Close and output PDF document
        PDF::Output($title . '.pdf', 'I');


        ######### END GENERATING PDF #########

    }
    public function addPage()
    {
        PDF::AddPage();

        $bMargin = PDF::getBreakMargin();
        $auto_page_break = PDF::getAutoPageBreak();
        PDF::SetAutoPageBreak(false, 0);

        $img_file = "https://platform.osboha180.com/backend/public/asset/images/certTemp.jpg";

        PDF::Image($img_file, 210, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);

        //        PDF::SetAutoPageBreak($auto_page_break, $bMargin);
        //      PDF::setPageMark();
    }

    public function textDegree($degree)
    {
        $textDegree = "";

        if ($degree <= 100 && $degree > 94) $textDegree = "امتياز";
        else if ($degree < 95 && $degree > 89.9) $textDegree = "ممتاز";
        else if ($degree < 90 && $degree > 84.9) $textDegree = "جيد جدا";
        else if ($degree < 85 && $degree > 79.9) $textDegree = "جيد";
        else if ($degree > 69.9 && $degree < 80) $textDegree = "مقبول";

        return $textDegree;
    }
}
