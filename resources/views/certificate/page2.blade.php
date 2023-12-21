@extends('certificate.layout')
<style>  .highlight {
    background-color: yellow;
    color: black;
  }
</style>

<div class="page">
    <div class="page-content">
        <br />

        <p class="sub-header">التقييم الفردي لحامل هذه الشهادة</p>
        <br />
        <p>
            يهدف مشروع أصبوحة 180 إلى رفع كفاءة التعلم من خلال القراءة المنهجية لدى القارئ وذلك من خلال تعميق أهمية الكتابة التوثيقية المصاحبة للقراءة و المتمثلة في تنظيم وحفظ أوعية المعرفة، وتيسير سبل الإفادة من محتوياتها؛ لأغراض المناقشة والمقارنة، بهدف تحقيق أقصى درجات الإفادة منها، واستثمار أكبر قدر ممكن من النتاج الفكري لتتحول القراءة عند القارئ من مجرد اكتساب إلى عملية تبادلية فيها الاكتساب والنتاج المتولد عنها، إيمانًا منا أن إعمال الذهن فيما تتعلم،

            وتوثيقه كتابيًا يعد وسيلة أساسية للابتكار والتطور وتحقيق النهضة.

            لقد قام فريق مختص بمتابعة القارئ و قام فريق مدرب على تقييم إنجازه ضمن خطة منهجية ومعايير محددة مسبقًا، اعتمدت على قراءة الأفكار الأساسية والفرعية الموجودة في الكتاب، وتقديم تغذية راجعة للأطروحات المقدّمة للمساهمة في رفع استفادة الفرد من حصيلة ما قد قرأ وتعزيز مهارات القراءة الفعّالة من خلال مناقشات وحوارات، وتمكينه من ترسيخ أفكار المادة المقروءة وتحويلها إلى أسئلة معرفية، وكلمات دلالية، وملخصات مرجعية، وأطروحات ناتجة عن حصاد ذلك كله. </p>
        <p>
            <strong><span><br />
                    <u>التقديرات التي حصل عليها القارئ كما يلي: </u></span></strong>
        </p>
        <p>
            - السؤال العام المقدم من القارئ تقديره (

                <span class="{{ ($certificateDegrees->general_summary_grade <= 100 && $certificateDegrees->general_summary_grade > 94) ? 'highlight' : ''  }}"> امتياز </span>
            <strong style="color: #00b050">||</strong>
            <span class="{{ ($certificateDegrees->general_summary_grade <= 94 && $certificateDegrees->general_summary_grade > 89) ? 'highlight' : ''  }}"> ممتاز </span>
            <strong style="color: #00b050">||</strong>
            <span class="{{ ($certificateDegrees->general_summary_grade <= 89 && $certificateDegrees->general_summary_grade > 84) ? 'highlight' : ''  }}"> جيد جدا </span>
            <strong style="color: #00b050">||</strong>
            <span class="{{ ($certificateDegrees->general_summary_grade <= 84 && $certificateDegrees->general_summary_grade > 79) ? 'highlight' : ''  }}"> جيد </span>
            <strong style="color: #00b050">||</strong>
            <span class="{{ ($certificateDegrees->general_summary_grade <= 79 && $certificateDegrees->general_summary_grade > 69) ? 'highlight' : ''  }}"> مقبول </span>

            (
        </p>
        <p>

            - الأسئلة المعرفية المقدمة من القارئ تقديرها
            (

            <span class="{{ ($certificateDegrees->questions_grade <= 100 && $certificateDegrees->questions_grade > 94) ? 'highlight' : ''  }}"> امتياز </span>
            <strong style="color: #00b050">||</strong>
            <span class="{{ ($certificateDegrees->questions_grade <= 94 && $certificateDegrees->questions_grade > 89) ? 'highlight' : ''  }}"> ممتاز </span>
            <strong style="color: #00b050">||</strong>
            <span class="{{ ($certificateDegrees->questions_grade <= 89 && $certificateDegrees->questions_grade > 84) ? 'highlight' : ''  }}"> جيد جدا </span>
            <strong style="color: #00b050">||</strong>
            <span class="{{ ($certificateDegrees->questions_grade <= 84 && $certificateDegrees->questions_grade > 79) ? 'highlight' : ''  }}"> جيد </span>
            <strong style="color: #00b050">||</strong>
            <span class="{{ ($certificateDegrees->questions_grade <= 79 && $certificateDegrees->questions_grade > 69) ? 'highlight' : ''  }}"> مقبول </span>

            (
        </p>
        <p>
            - الأطروحات المقدمة من القارئ تقديرها (
                <span class="{{ ($certificateDegrees->thesis_grade <= 100 && $certificateDegrees->thesis_grade > 94) ? 'highlight' : ''  }}"> امتياز </span>
            <strong style="color: #00b050">||</strong>
            <span class="{{ ($certificateDegrees->thesis_grade <= 94 && $certificateDegrees->thesis_grade > 89) ? 'highlight' : ''  }}"> ممتاز </span>
            <strong style="color: #00b050">||</strong>
            <span class="{{ ($certificateDegrees->thesis_grade <= 89 && $certificateDegrees->thesis_grade > 84) ? 'highlight' : ''  }}"> جيد جدا </span>
            <strong style="color: #00b050">||</strong>
            <span class="{{ ($certificateDegrees->thesis_grade <= 84 && $certificateDegrees->thesis_grade > 79) ? 'highlight' : ''  }}"> جيد </span>
            <strong style="color: #00b050">||</strong>
            <span class="{{ ($certificateDegrees->thesis_grade <= 79 && $certificateDegrees->thesis_grade > 69) ? 'highlight' : ''  }}"> مقبول </span>
            (
        </p>
    </div>
</div>
